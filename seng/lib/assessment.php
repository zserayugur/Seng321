<?php
// lib/assessment.php
require_once __DIR__ . '/database.php';
require_once __DIR__ . '/timer.php';

function tests_cfg(): array {
  return (require __DIR__ . '/../config/config.php')['tests'];
}

function uuid36(): string {
  $hex = bin2hex(random_bytes(16));
  return substr($hex,0,8).'-'.substr($hex,8,4).'-'.substr($hex,12,4).'-'.substr($hex,16,4).'-'.substr($hex,20,12);
}

function start_attempt(int $userId, string $type, ?int $readingStage = null, ?string $readingGroup = null): int {
  $pdo = pdo();
  $cfg = tests_cfg();

  $type = strtoupper($type);

  if ($type === 'READING') {
    if (!$readingStage || !in_array($readingStage, [1,2], true)) {
      throw new RuntimeException("READING requires stage 1 or 2");
    }
    $duration = (int)$cfg['READING'][$readingStage]['duration_sec'];
    $count    = (int)$cfg['READING'][$readingStage]['count'];
    if (!$readingGroup) $readingGroup = uuid36();
  } elseif (in_array($type, ['GRAMMAR','VOCAB'], true)) {
    $duration = (int)$cfg[$type]['duration_sec'];
    $count    = (int)$cfg[$type]['count'];
    $readingStage = null;
    $readingGroup = null;
  } else {
    throw new RuntimeException("Invalid test type");
  }

  $started = now_dt();
  $expires = (new DateTimeImmutable('now'))->modify("+{$duration} seconds")->format('Y-m-d H:i:s');

  $pdo->beginTransaction();

  $ins = $pdo->prepare("INSERT INTO attempts
    (user_id, test_type, reading_stage, reading_group, status, duration_sec, started_at, expires_at)
    VALUES (?,?,?,?, 'IN_PROGRESS', ?, ?, ?)");
  $ins->execute([$userId, $type, $readingStage, $readingGroup, $duration, $started, $expires]);
  $attemptId = (int)$pdo->lastInsertId();

  // Soru setini sabitle: attempt_questions
  if ($type === 'READING') {
    $q = $pdo->prepare("SELECT id FROM questions WHERE type='READING' AND reading_stage=? ORDER BY RAND() LIMIT {$count}");
    $q->execute([$readingStage]);
  } else {
    $q = $pdo->prepare("SELECT id FROM questions WHERE type=? ORDER BY RAND() LIMIT {$count}");
    $q->execute([$type]);
  }

  $rows = $q->fetchAll();
  if (count($rows) < $count) {
    $pdo->rollBack();
    throw new RuntimeException("Not enough questions in DB for {$type}");
  }

  $ord = 1;
  $map = $pdo->prepare("INSERT INTO attempt_questions(attempt_id, question_id, ord) VALUES (?,?,?)");
  foreach ($rows as $r) {
    $map->execute([$attemptId, (int)$r['id'], $ord++]);
  }

  $pdo->commit();
  return $attemptId;
}

function get_attempt(int $userId, int $attemptId): array {
  $pdo = pdo();
  $st = $pdo->prepare("SELECT * FROM attempts WHERE id=? AND user_id=?");
  $st->execute([$attemptId, $userId]);
  $a = $st->fetch();
  if (!$a) throw new RuntimeException("Attempt not found");

  $a['remainingSec'] = remaining_sec($a);
  return $a;
}

function ensure_auto_submit_if_needed(int $userId, int $attemptId): void {
  $pdo = pdo();
  $a = get_attempt($userId, $attemptId);
  if ($a['status'] === 'SUBMITTED') return;

  // 1) Normal expiry
  if (is_expired($a)) {
    submit_attempt($userId, $attemptId, 'TIME_EXPIRED');
    return;
  }

  // 2) Pause policy (FR24 extension)
  if (pause_policy_triggered($a)) {
    submit_attempt($userId, $attemptId, 'PAUSE_POLICY');
    return;
  }
}

function fetch_questions_for_attempt(int $attemptId): array {
  $pdo = pdo();

  $sql = "SELECT aq.ord, q.*
          FROM attempt_questions aq
          JOIN questions q ON q.id=aq.question_id
          WHERE aq.attempt_id=?
          ORDER BY aq.ord";
  $st = $pdo->prepare($sql);
  $st->execute([$attemptId]);
  $qs = $st->fetchAll();
  if (!$qs) throw new RuntimeException("No questions mapped to attempt");

  $ansSt = $pdo->prepare("SELECT question_id, selected_choice FROM answers WHERE attempt_id=?");
  $ansSt->execute([$attemptId]);
  $answers = [];
  foreach ($ansSt->fetchAll() as $r) {
    $answers[(int)$r['question_id']] = $r['selected_choice'];
  }

  $passage = null;
  if ($qs[0]['type'] === 'READING' && !empty($qs[0]['passage_id'])) {
    $p = $pdo->prepare("SELECT id, title, body FROM passages WHERE id=?");
    $p->execute([(int)$qs[0]['passage_id']]);
    $passage = $p->fetch();
  }

  $clientQs = [];
  foreach ($qs as $q) {
    $qid = (int)$q['id'];
    $clientQs[] = [
      'id' => $qid,
      'ord' => (int)$q['ord'],
      'prompt' => $q['prompt'],
      'choices' => [
        'A' => $q['choice_a'],
        'B' => $q['choice_b'],
        'C' => $q['choice_c'],
        'D' => $q['choice_d'],
      ],
      'selected' => $answers[$qid] ?? null,
    ];
  }

  return ['passage' => $passage, 'questions' => $clientQs];
}

function save_answer(int $userId, int $attemptId, int $questionId, string $choice): void {
  $pdo = pdo();
  $choice = strtoupper(trim($choice));

  $a = get_attempt($userId, $attemptId);
  if ($a['status'] === 'SUBMITTED') throw new RuntimeException("Already submitted");
  if ($a['status'] === 'PAUSED') throw new RuntimeException("Attempt paused");
  if (is_expired($a)) {
    submit_attempt($userId, $attemptId, 'TIME_EXPIRED');
    throw new RuntimeException("Time expired; auto-submitted");
  }

  if (!in_array($choice, ['A','B','C','D'], true)) throw new RuntimeException("Invalid choice");

  $chk = $pdo->prepare("SELECT 1 FROM attempt_questions WHERE attempt_id=? AND question_id=?");
  $chk->execute([$attemptId, $questionId]);
  if (!$chk->fetch()) throw new RuntimeException("Question not in attempt");

  $up = $pdo->prepare("INSERT INTO answers(attempt_id, question_id, selected_choice, saved_at)
    VALUES (?,?,?,?)
    ON DUPLICATE KEY UPDATE selected_choice=VALUES(selected_choice), saved_at=VALUES(saved_at)");
  $up->execute([$attemptId, $questionId, $choice, now_dt()]);
}

function submit_attempt(int $userId, int $attemptId, string $reason): array {
  $pdo = pdo();

  $pdo->beginTransaction();

  $st = $pdo->prepare("SELECT * FROM attempts WHERE id=? AND user_id=? FOR UPDATE");
  $st->execute([$attemptId, $userId]);
  $a = $st->fetch();
  if (!$a) {
    $pdo->rollBack();
    throw new RuntimeException("Attempt not found");
  }

  if ($a['status'] === 'SUBMITTED') {
    $pdo->commit();
    return get_result($userId, $attemptId);
  }

  $q = $pdo->prepare("
    SELECT aq.ord, q.id AS qid, q.correct_choice, a.selected_choice
    FROM attempt_questions aq
    JOIN questions q ON q.id=aq.question_id
    LEFT JOIN answers a ON a.attempt_id=aq.attempt_id AND a.question_id=aq.question_id
    WHERE aq.attempt_id=?
    ORDER BY aq.ord
  ");
  $q->execute([$attemptId]);
  $rows = $q->fetchAll();

  $total = count($rows);
  $correct = 0;

  foreach ($rows as $r) {
    if (!empty($r['selected_choice']) && $r['selected_choice'] === $r['correct_choice']) {
      $correct++;
    }
  }

  $upd = $pdo->prepare("UPDATE attempts
    SET status='SUBMITTED', submit_reason=?, submitted_at=?, total_count=?, correct_count=?
    WHERE id=? AND user_id=?");
  $upd->execute([$reason, now_dt(), $total, $correct, $attemptId, $userId]);

  $pdo->commit();
  return get_result($userId, $attemptId);
}

function get_result(int $userId, int $attemptId): array {
  $pdo = pdo();

  $aSt = $pdo->prepare("SELECT * FROM attempts WHERE id=? AND user_id=?");
  $aSt->execute([$attemptId, $userId]);
  $attempt = $aSt->fetch();
  if (!$attempt) throw new RuntimeException("Attempt not found");
  if ($attempt['status'] !== 'SUBMITTED') throw new RuntimeException("Attempt not submitted yet");

  $q = $pdo->prepare("
    SELECT aq.ord, q.id AS qid, q.prompt, q.choice_a, q.choice_b, q.choice_c, q.choice_d,
           q.correct_choice, q.explanation,
           a.selected_choice
    FROM attempt_questions aq
    JOIN questions q ON q.id=aq.question_id
    LEFT JOIN answers a ON a.attempt_id=aq.attempt_id AND a.question_id=aq.question_id
    WHERE aq.attempt_id=?
    ORDER BY aq.ord
  ");
  $q->execute([$attemptId]);
  $rows = $q->fetchAll();

  $items = [];
  foreach ($rows as $r) {
    $isCorrect = (!empty($r['selected_choice']) && $r['selected_choice'] === $r['correct_choice']);
    $items[] = [
      'ord' => (int)$r['ord'],
      'questionId' => (int)$r['qid'],
      'prompt' => $r['prompt'],
      'choices' => [
        'A' => $r['choice_a'],
        'B' => $r['choice_b'],
        'C' => $r['choice_c'],
        'D' => $r['choice_d'],
      ],
      'userAnswer' => $r['selected_choice'] ?? null,
      'correctAnswer' => $r['correct_choice'],
      'isCorrect' => $isCorrect,
      'explanation' => $r['explanation'] ?? null,
    ];
  }

  $total = (int)($attempt['total_count'] ?? count($items));
  $correct = (int)($attempt['correct_count'] ?? 0);
  $scorePct = $total > 0 ? round(($correct / $total) * 100, 1) : 0.0;

  return [
    'attempt' => [
      'id' => (int)$attempt['id'],
      'testType' => $attempt['test_type'],
      'readingStage' => $attempt['reading_stage'],
      'readingGroup' => $attempt['reading_group'],
      'status' => $attempt['status'],
      'submitReason' => $attempt['submit_reason'],
      'submittedAt' => $attempt['submitted_at'],
      'total' => $total,
      'correct' => $correct,
      'scorePct' => $scorePct,
    ],
    'items' => $items,
  ];
}

function start_reading_next(int $userId, string $readingGroup): int {
  $pdo = pdo();

  // Stage1 submitted olmalı
  $st1 = $pdo->prepare("SELECT id FROM attempts
    WHERE user_id=? AND test_type='READING' AND reading_group=? AND reading_stage=1 AND status='SUBMITTED'
    ORDER BY id DESC LIMIT 1");
  $st1->execute([$userId, $readingGroup]);
  if (!$st1->fetch()) throw new RuntimeException("Reading stage1 not submitted");

  // Stage2 zaten varsa tekrar oluşturma
  $st2 = $pdo->prepare("SELECT id FROM attempts
    WHERE user_id=? AND test_type='READING' AND reading_group=? AND reading_stage=2
    LIMIT 1");
  $st2->execute([$userId, $readingGroup]);
  if ($st2->fetch()) throw new RuntimeException("Reading stage2 already started");

  return start_attempt($userId, 'READING', 2, $readingGroup);
}
