<?php
$page = 'listening';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/mock_data.php';
require_once __DIR__ . '/../includes/ai_service.php';
require_once __DIR__ . '/../includes/csrf.php';
$assignment_id = (int)($_GET['assignment_id'] ?? 0);
$csrf = csrf_token();
require_once __DIR__ . '/../includes/header.php';

$sessionKey = 'listening_test';
$profile = getUserProfile();
$cefr = $profile['current_level'] ?? 'B1';

// Reset
if (isset($_GET['reset'])) {
    unset($_SESSION[$sessionKey]);
    header("Location: listening.php");
    exit;
}

// Start part
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'start_part') {
    $part = (int)($_POST['part'] ?? 1);
    if (!in_array($part, [1, 2], true)) $part = 1;
    
    // Generate listening test from AI
    $testData = fetchAIListeningTest($cefr, $part);
    
    $_SESSION[$sessionKey] = [
        'part' => $part,
        'cefr' => $cefr,
        'passage' => $testData['passage'],
        'questions' => $testData['questions'],
        'source' => $testData['source'] ?? 'unknown',
        'started_at' => time(),
        'answers' => [],
        'submitted' => false
    ];
    
    header("Location: listening.php");
    exit;
}

$state = $_SESSION[$sessionKey] ?? null;
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 900px; margin: 0 auto;">
  <section class="card">
    <h1>AI Listening Test</h1>
    <p>Listen to a generated audio script (approx. 3-4 minutes) and answer open-ended comprehension questions. Receive
      instant AI grading and feedback.</p>

<?php if (!$state): ?>
    <section class="card" style="margin-top:16px;">
        <h2>Ready to start?</h2>
        <p>You will listen to a 1-minute audio passage and answer 10 multiple choice questions.</p>
        <p>Current CEFR: <strong><?php echo htmlspecialchars($cefr); ?></strong></p>
        <form method="post">
            <input type="hidden" name="action" value="start_part">
            <input type="hidden" name="part" value="1">
            <button class="btn-primary" type="submit">Start Listening Test 1</button>
        </form>
    </section>
<?php else: ?>
    <?php
    $part = $state['part'] ?? 1;
    $passage = $state['passage'] ?? '';
    $questions = $state['questions'] ?? [];
    $submitted = $state['submitted'] ?? false;
    $answers = $state['answers'] ?? [];
    $startedAt = $state['started_at'] ?? time();
    $duration = 10 * 60; // 10 minutes
    $elapsed = time() - $startedAt;
    $remaining = max(0, $duration - $elapsed);
    ?>
    
    <section class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
            <div>
                <h3>Part <?php echo $part; ?> - Listening Test</h3>
                <div style="opacity:.75;">Source: <?php echo htmlspecialchars($state['source'] ?? 'unknown'); ?></div>
            </div>
            <div style="text-align:right;">
                <div style="opacity:.75;">Time remaining</div>
                <div id="timer" style="font-weight:800; font-size:1.2rem;">
                    <?php
                    $mm = floor($remaining / 60);
                    $ss = $remaining % 60;
                    echo sprintf("%02d:%02d", $mm, $ss);
                    ?>
                </div>
            </div>
        </div>
    </section>
    
    <?php if (!$submitted): ?>
        <!-- Preview Timer -->
        <div id="previewSection" style="display:none;" class="card" style="margin-top:16px;">
            <h3>Preview: <span id="previewTimer">10</span>s</h3>
            <p>Get ready to listen. The audio will start automatically.</p>
        </div>
        
        <!-- Audio Player and Questions -->
        <div id="examSection" style="display:none;">
            <section class="card" style="margin-top:16px;">
                <h3>Listen to the audio passage</h3>
                <div id="audioContainer">
                    <p style="padding:20px; background:#f0f0f0; border-radius:8px; color:#000; display:none;" id="audioTextContainer">
                        <strong>Audio Text:</strong><br>
                        <span id="audioText" style="white-space:pre-wrap; line-height:1.6;"><?php echo htmlspecialchars($passage); ?></span>
                    </p>
                    <button id="playAudio" class="btn-primary" style="margin-top:12px;">🔊 Play Audio (Text-to-Speech)</button>
                    <audio id="audioPlayer" style="display:none;" controls></audio>
                </div>
            </section>
            
            <section class="card" style="margin-top:16px;">
                <h3>Questions (10 questions)</h3>
                <form id="listeningForm" method="post">
                    <input type="hidden" name="action" value="submit">
                    <?php foreach ($questions as $i => $q): ?>
                        <div style="padding:12px; border:1px solid var(--border-color); border-radius:8px; margin:12px 0;">
                            <div style="font-weight:700; margin-bottom:8px;">
                                Q<?php echo $i + 1; ?>: <?php echo htmlspecialchars($q['stem'] ?? ''); ?>
                            </div>
                            <div style="margin-top:10px;">
                                <?php foreach ($q['choices'] as $ci => $c): ?>
                                    <label style="display:block; margin:8px 0; cursor:pointer;">
                                        <input type="radio" name="answers[<?php echo $i; ?>]" value="<?php echo intval($ci); ?>" 
                                               <?php echo isset($answers[$i]) && $answers[$i] == $ci ? 'checked' : ''; ?>>
                                        <?php echo htmlspecialchars($c); ?>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div style="margin-top:12px;">
                        <button class="btn-primary" type="submit">Submit Answers</button>
                    </div>
                </form>
            </section>
        </div>
        
        <script>
        (function() {
            let previewLeft = 10;
            let remaining = <?php echo intval($remaining); ?>;
            const timerEl = document.getElementById("timer");
            const previewSection = document.getElementById("previewSection");
            const examSection = document.getElementById("examSection");
            const previewTimerEl = document.getElementById("previewTimer");
            const form = document.getElementById("listeningForm");
            
            // Start preview
            previewSection.style.display = 'block';
            
            const previewInterval = setInterval(() => {
                previewLeft--;
                previewTimerEl.textContent = previewLeft;
                if (previewLeft <= 0) {
                    clearInterval(previewInterval);
                    previewSection.style.display = 'none';
                    examSection.style.display = 'block';
                }
            }, 1000);
            
            // Timer
            function fmt(s) {
                const mm = String(Math.floor(s / 60)).padStart(2, "0");
                const ss = String(s % 60).padStart(2, "0");
                return mm + ":" + ss;
            }
            
            timerEl.textContent = fmt(remaining);
            
            const tick = setInterval(() => {
                remaining--;
                if (remaining < 0) remaining = 0;
                timerEl.textContent = fmt(remaining);
                
                if (remaining === 0) {
                    clearInterval(tick);
                    if (form) form.submit();
                }
            }, 1000);
            
            // Text-to-Speech
            document.getElementById('playAudio')?.addEventListener('click', () => {
                const text = document.getElementById('audioText').textContent;
                if ('speechSynthesis' in window) {
                    const utterance = new SpeechSynthesisUtterance(text);
                    utterance.lang = 'en-US';
                    utterance.rate = 0.9;
                    speechSynthesis.speak(utterance);
                } else {
                    alert('Text-to-speech not supported in this browser. Please read the text above.');
                }
            });
        })();
        </script>
    <?php else: ?>
        <?php
        // Calculate score
        $correct = 0;
        foreach ($questions as $i => $q) {
            $userAnswer = $answers[$i] ?? null;
            if ($userAnswer !== null && intval($userAnswer) === intval($q['answer_index'])) {
                $correct++;
            }
        }
        $total = count($questions);
        $pct = $total > 0 ? round(($correct / $total) * 100) : 0;
        ?>
        
        <section class="card" style="margin-top:16px;">
            <h2>Test Completed</h2>
            <p>Score: <strong><?php echo $correct; ?>/<?php echo $total; ?></strong> (<?php echo $pct; ?>%)</p>
            
            <div style="margin-top:12px;">
                <?php if ($part === 1): ?>
                    <form method="post">
                        <input type="hidden" name="action" value="start_part">
                        <input type="hidden" name="part" value="2">
                        <button class="btn-primary" type="submit">Start Listening Test 2</button>
                    </form>
                <?php else: ?>
                    <a class="btn" href="reports.php">Go to Reports</a>
                    <a class="btn" href="listening.php?reset=1">Restart</a>
                <?php endif; ?>
            </div>
        </section>
        
        <section class="card" style="margin-top:16px;">
            <h2>Review</h2>
            <?php foreach ($questions as $i => $q): ?>
                <?php
                $userAnswer = $answers[$i] ?? null;
                $correctIdx = intval($q['answer_index']);
                $isCorrect = ($userAnswer !== null && intval($userAnswer) === $correctIdx);
                $choices = $q['choices'] ?? [];
                ?>
                <div style="padding:12px; border:1px solid var(--border-color); border-radius:10px; margin:10px 0;">
                    <div style="font-weight:700;">Q<?php echo $i + 1; ?>: <?php echo htmlspecialchars($q['stem'] ?? ''); ?></div>
                    <div style="margin-top:6px; opacity:.85;">
                        Your answer: <strong><?php echo ($userAnswer !== null && isset($choices[intval($userAnswer)])) ? htmlspecialchars($choices[intval($userAnswer)]) : "—"; ?></strong>
                        <span style="margin-left:10px;">(<?php echo $isCorrect ? "✓ Correct" : "✗ Wrong"; ?>)</span>
                    </div>
                    <div style="margin-top:4px; opacity:.85;">
                        Correct: <strong><?php echo isset($choices[$correctIdx]) ? htmlspecialchars($choices[$correctIdx]) : "—"; ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>

<?php
// Handle submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit') {
    if (isset($_SESSION[$sessionKey])) {
        $answersIn = $_POST['answers'] ?? [];
        $answers = [];
        foreach ($answersIn as $k => $v) {
            $i = intval($k);
            $choice = intval($v);
            if ($i >= 0 && $choice >= 0 && $choice <= 3) {
                $answers[$i] = $choice;
            }
        }
        $_SESSION[$sessionKey]['answers'] = $answers;
        $_SESSION[$sessionKey]['submitted'] = true;
        
        // Save to database via attempt
        try {
            require_once __DIR__ . '/../includes/attempt_repo.php';
            $userId = current_user_id();
            
            // Create attempt if not exists
            $attemptId = null;
            if (!isset($_SESSION[$sessionKey]['attempt_id'])) {
                $part = $_SESSION[$sessionKey]['part'] ?? 1;
                $duration = 10 * 60;
                $meta = ["preview_seconds" => 10, "questions_count" => 10, "level" => ($part === 1 ? "intermediate_easy" : "advanced")];
                $attemptId = create_attempt($userId, 'listening', $duration, $part, $meta);
                $_SESSION[$sessionKey]['attempt_id'] = $attemptId;
            } else {
                $attemptId = $_SESSION[$sessionKey]['attempt_id'];
            }
            
            // Save answers
            $questions = $_SESSION[$sessionKey]['questions'] ?? [];
            foreach ($answers as $i => $choice) {
                if (isset($questions[$i])) {
                    $q = $questions[$i];
                    save_answer($attemptId, $i + 1, $q['stem'] ?? "Q" . ($i + 1), $q['choices'][$choice] ?? '');
                }
            }
            
            // Submit attempt
            submit_attempt($attemptId, $userId);
        } catch (Exception $e) {
            error_log("Listening save error: " . $e->getMessage());
        }
        
        header("Location: listening.php");
        exit;
    }
}
?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
