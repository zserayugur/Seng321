<?php
// lib/timer.php
require_once __DIR__ . '/database.php';

function cfg(): array {
  return require __DIR__ . '/../config/config.php';
}

function now_dt(): string {
  return (new DateTimeImmutable('now'))->format('Y-m-d H:i:s');
}

function remaining_sec(array $attempt): int {
  if ($attempt['status'] === 'PAUSED') {
    return (int)($attempt['remaining_sec'] ?? 0);
  }
  if (empty($attempt['expires_at'])) return 0;

  $now = new DateTimeImmutable('now');
  $exp = new DateTimeImmutable($attempt['expires_at']);
  return max(0, $exp->getTimestamp() - $now->getTimestamp());
}

function is_expired(array $attempt): bool {
  return ($attempt['status'] === 'IN_PROGRESS') && (remaining_sec($attempt) <= 0);
}

function pause_policy_triggered(array $attempt): bool {
  $limit = (int)(cfg()['pause_policy_limit_sec'] ?? 0);
  if ($limit <= 0) return false;
  if ($attempt['status'] !== 'PAUSED') return false;
  if (empty($attempt['paused_at'])) return false;

  $pausedAt = new DateTimeImmutable($attempt['paused_at']);
  $now = new DateTimeImmutable('now');
  return (($now->getTimestamp() - $pausedAt->getTimestamp()) >= $limit);
}

function pause_attempt(int $attemptId, int $userId): array {
  $pdo = pdo();

  $st = $pdo->prepare("SELECT * FROM attempts WHERE id=? AND user_id=?");
  $st->execute([$attemptId, $userId]);
  $a = $st->fetch();
  if (!$a) throw new RuntimeException("Attempt not found");
  if ($a['status'] !== 'IN_PROGRESS') return $a;

  $rem = remaining_sec($a);

  $upd = $pdo->prepare("UPDATE attempts
    SET status='PAUSED', remaining_sec=?, paused_at=?, expires_at=NULL
    WHERE id=? AND user_id=?");
  $upd->execute([$rem, now_dt(), $attemptId, $userId]);

  $st->execute([$attemptId, $userId]);
  return $st->fetch();
}

function resume_attempt(int $attemptId, int $userId): array {
  $pdo = pdo();

  $st = $pdo->prepare("SELECT * FROM attempts WHERE id=? AND user_id=?");
  $st->execute([$attemptId, $userId]);
  $a = $st->fetch();
  if (!$a) throw new RuntimeException("Attempt not found");
  if ($a['status'] !== 'PAUSED') return $a;

  $rem = (int)($a['remaining_sec'] ?? 0);
  $expires = (new DateTimeImmutable('now'))->modify("+{$rem} seconds")->format('Y-m-d H:i:s');

  $upd = $pdo->prepare("UPDATE attempts
    SET status='IN_PROGRESS', expires_at=?, paused_at=NULL
    WHERE id=? AND user_id=?");
  $upd->execute([$expires, $attemptId, $userId]);

  $st->execute([$attemptId, $userId]);
  return $st->fetch();
}
