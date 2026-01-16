<?php
$page = 'grammar';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 900px; margin: 0 auto;">
    <section class="card">
        <h1>AI Grammar Assessment</h1>
        <p>Test your English grammar with 10 AI-generated questions adapted to your level.</p>

<?php if (!$state): ?>
    <section class="card" style="margin-top:16px;">
        <h2>Ready?</h2>
        <p>Current CEFR: <strong><?php echo htmlspecialchars($cefr); ?></strong></p>
        <form method="post">
            <input type="hidden" name="action" value="start">
            <button class="btn-primary" type="submit">Start</button>
        </form>
        <p style="opacity:.8; margin-top:10px;">Start dediğinde sorular CEFR seviyene göre üretilir.</p>
    </section>

<?php else: ?>

    <?php
    $questions = $state["questions"];
    $total = count($questions);
    $idx = $state["idx"];
    ?>

    <?php
    $startedAt = $state["started_at"] ?? time();
    $duration = 30 * 60; // 30 minutes
    $elapsed = time() - $startedAt;
    $remaining = max(0, $duration - $elapsed);
    ?>
    
    <section class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap;">
            <div>
                <div>Progress: <strong><?php echo min($idx + 1, $total); ?>/<?php echo $total; ?></strong></div>
                <div style="margin-top:4px;">CEFR: <strong><?php echo htmlspecialchars($state["cefr"]); ?></strong></div>
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
        <div style="margin-top:8px; opacity:.75;">Source: <?php echo htmlspecialchars($state["source"]); ?></div>
    </section>

    <?php if ($total === 0): ?>
        <section class="card" style="margin-top:16px;">
            <h2>No questions generated</h2>
            <a class="btn" href="grammar.php?reset=1">Try Again</a>
        </section>

    <?php elseif ($idx >= $total): ?>
        <?php
        $correct = 0;
        foreach ($questions as $i => $q) {
            $u = $state["answers"][$i] ?? null;
            if ($u !== null && intval($u) === intval($q["answer_index"]))
                $correct++;
        }
        $pct = $total > 0 ? round(($correct / $total) * 100) : 0;

        // --- SAVE RESULT LOGIC ---
        if (!isset($_SESSION[$sessionKey]["saved"])) {
            $_SESSION[$sessionKey]["saved"] = true;

            // 1. Determine new level based on Mixed-Difficulty Score
            // 0-20% -> A1, 21-40% -> A2, 41-60% -> B1, 61-80% -> B2, 81-95% -> C1, >95% -> C2
            if ($pct <= 20)
                $newLevel = "A1";
            elseif ($pct <= 40)
                $newLevel = "A2";
            elseif ($pct <= 60)
                $newLevel = "B1";
            elseif ($pct <= 80)
                $newLevel = "B2";
            elseif ($pct <= 95)
                $newLevel = "C1";
            else
                $newLevel = "C2";

            $change = 1; // Always animate progress for new placement

            // 2. Update Profile
            $mapIelts = ["A1" => 3.0, "A2" => 4.0, "B1" => 5.0, "B2" => 6.0, "C1" => 7.0, "C2" => 8.0];
            $mapToefl = ["A1" => 20, "A2" => 35, "B1" => 55, "B2" => 75, "C1" => 95, "C2" => 110];

            updateUserProfile([
                "current_level" => $newLevel,
                "ielts_estimate" => $mapIelts[$newLevel],
                "toefl_estimate" => $mapToefl[$newLevel],
                "progress_percent" => ($change > 0) ? 20 : 50
            ]);

            // 3. Add History (save to DB)
            addTestResult([
                "id" => time(),
                "date" => date("Y-m-d"),
                "test" => "Grammar Assessment",
                "type" => "grammar",
                "score" => $correct,
                "max_score" => $total,
                "level" => $newLevel,
                "status" => "Completed",
                "details" => [
                    "correct" => $correct,
                    "total" => $total,
                    "percent" => $pct
                ]
            ]);
        }
        // --- END SAVE LOGIC ---
        ?>
        <section class="card" style="margin-top:16px;">
            <h2>Completed</h2>
            <p>Score: <strong><?php echo $correct; ?>/<?php echo $total; ?></strong> (<?php echo $pct; ?>%)</p>

            <div style="display:flex; gap:10px; margin-top:12px;">
                <a class="btn" href="grammar.php?reset=1">Restart</a>
                <a class="btn" href="reports.php">Go to Reports</a>
            </div>
        </section>

        <section class="card" style="margin-top:16px;">
            <h2>Review</h2>
            <?php foreach ($questions as $i => $q): ?>
                <?php
                $u = $state["answers"][$i] ?? null;
                $isCorrect = ($u !== null && intval($u) === intval($q["answer_index"]));
                ?>
                <div style="padding:12px; border:1px solid var(--border-color); border-radius:10px; margin:10px 0;">
                    <div style="font-weight:600;">Q<?php echo $i + 1; ?>: <?php echo htmlspecialchars($q["stem"]); ?></div>
                    <div style="margin-top:6px; opacity:.85;">
                        Your answer: <strong><?php echo $u !== null ? htmlspecialchars($q["choices"][$u]) : "—"; ?></strong>
                        <span style="margin-left:10px;">(<?php echo $isCorrect ? "Correct" : "Wrong"; ?>)</span>
                    </div>
                    <div style="margin-top:4px; opacity:.85;">
                        Correct: <strong><?php echo htmlspecialchars($q["choices"][intval($q["answer_index"])]); ?></strong>
                    </div>
                </div>
                <div class="stat-card">
                    <h3>Target</h3>
                    <div class="value">10 Questions</div>
                </div>
            </div>

    <?php else: ?>
        <?php 
        // Auto-submit if time expired
        if ($remaining <= 0 && $idx < $total) {
            // Mark all unanswered questions as -1 (not answered)
            for ($i = $idx; $i < $total; $i++) {
                if (!isset($state["answers"][$i])) {
                    $_SESSION[$sessionKey]["answers"][$i] = -1;
                }
            }
            $_SESSION[$sessionKey]["idx"] = $total;
            header("Location: grammar.php");
            exit;
        }
        
        $q = $questions[$idx]; 
        ?>
        <section class="card" style="margin-top:16px;">
            <h2>Question <?php echo $idx + 1; ?></h2>
            <p style="margin-top:10px;"><?php echo htmlspecialchars($q["stem"]); ?></p>

            <form id="grammarForm" method="post" style="margin-top:12px;">
                <input type="hidden" name="action" value="answer">
                <input type="hidden" name="idx" value="<?php echo $idx; ?>">

                <?php foreach ($q["choices"] as $i => $c): ?>
                    <label style="display:block; margin:10px 0;">
                        <input type="radio" name="choice" value="<?php echo $i; ?>" required>
                        <?php echo htmlspecialchars($c); ?>
                    </label>
                <?php endforeach; ?>

                <div style="display:flex; gap:10px; margin-top:12px;">
                    <button class="btn-primary" type="submit">Next</button>
                    <?php if ($idx > 0): ?>
                        <a class="btn" href="grammar.php?back=1">Previous</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
        
        <script>
        (function() {
            let remaining = <?php echo intval($remaining); ?>;
            const timerEl = document.getElementById("timer");
            const form = document.getElementById("grammarForm");
            
            function fmt(s) {
                const mm = String(Math.floor(s / 60)).padStart(2, "0");
                const ss = String(s % 60).padStart(2, "0");
                return mm + ":" + ss;
            }
            
            timerEl.textContent = fmt(remaining);
            
            const tick = setInterval(function() {
                remaining--;
                if (remaining < 0) remaining = 0;
                timerEl.textContent = fmt(remaining);
                
                if (remaining === 0) {
                    clearInterval(tick);
                    if (form) {
                        // Auto-submit by redirecting to completion
                        window.location.href = "grammar.php";
                    }
                }
            }, 1000);
        })();
        </script>
    <?php endif; ?>

    <!-- Step 3: Results -->
    <section id="resultArea" class="card" style="display:none; margin-top: 20px;">
        <h2>Test Results</h2>
        <div class="dashboard-grid">
            <div style="text-align: center; padding: 20px; background: #f0fdf4; border-radius: 10px;">
                <h3>Your Score</h3>
                <div id="resScore" style="font-size: 3rem; font-weight: 800; color: #166534;">0/10</div>
            </div>
            <div style="text-align: center; padding: 20px; background: #fffbeb; border-radius: 10px;">
                <h3>Estimated Level</h3>
                <div id="resLevel" style="font-size: 3rem; font-weight: 800; color: #92400e;">-</div>
            </div>
        </div>

        <div id="feedbackContainer" style="margin-top:25px;"></div>

        <div style="margin-top: 25px; text-align: center;">
            <button onclick="location.reload()" class="btn">Take Another Test</button>
        </div>
    </section>
</div>

<script>
    let currentQuestions = [];
    const skill = "grammar";
    const currentLevel = "<?= htmlspecialchars($user['current_level'] ?? 'B1') ?>";

    const btnStart = document.getElementById('btnStart');
    const loading = document.getElementById('loading');
    const startArea = document.getElementById('startArea');
    const quizArea = document.getElementById('quizArea');
    const questionsContainer = document.getElementById('questionsContainer');
    const resultArea = document.getElementById('resultArea');

    btnStart.addEventListener('click', async () => {
        btnStart.style.display = 'none';
        loading.style.display = 'block';

        try {
            // Fetch Questions API
            const fd = new FormData();
            fd.append('skill', skill);
            fd.append('level', currentLevel);
            fd.append('count', 10);

            const res = await fetch('api/get_test_questions.php', { method: 'POST', body: fd });
            const data = await res.json();

            if (data.error) throw new Error(data.error);
            if (!data.questions || data.questions.length === 0) throw new Error("No questions generated.");

            currentQuestions = data.questions;
            renderQuiz();

            loading.style.display = 'none';
            // startArea is hidden by parent logic if we want, or just hide it manually
            startArea.style.display = 'none';
            quizArea.style.display = 'block';

        } catch (e) {
            alert("Error: " + e.message);
            loading.style.display = 'none';
            btnStart.style.display = 'inline-block';
        }
    });

    function renderQuiz() {
        questionsContainer.innerHTML = currentQuestions.map((q, i) => `
        <div class="question-block" style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #374151;">
            <p style="font-weight: 600; margin-bottom: 15px; color: #ffffff; font-size: 1.1rem;">Q${i + 1}: ${q.stem}</p>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                ${q.choices.map((choice, cIdx) => `
                    <label style="padding: 10px; border: 1px solid #4b5563; border-radius: 6px; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: background 0.2s; color: #e5e7eb;">
                        <input type="radio" name="q_${i}" value="${cIdx}" required>
                        <span>${choice}</span>
                    </label>
                `).join('')}
            </div>
        </div>
    `).join('');
    }

    async function handleSubmit(e) {
        e.preventDefault();
        document.getElementById('btnSubmit').disabled = true;
        document.getElementById('btnSubmit').textContent = "Evaluating...";

        const formData = new FormData(document.getElementById('quizForm'));
        let correctCount = 0;

        // Evaluate Local (Standardized Logic)
        const detailedResults = currentQuestions.map((q, i) => {
            const userChoice = parseInt(formData.get(`q_${i}`));
            const isCorrect = (userChoice === q.answer_index);
            if (isCorrect) correctCount++;
            return {
                question: q.stem,
                userVal: q.choices[userChoice],
                correctVal: q.choices[q.answer_index],
                isCorrect: isCorrect
            };
        });

        const scorePct = Math.round((correctCount / currentQuestions.length) * 100);

        // Determine Level
        let newLevel = "A1";
        if (scorePct > 20) newLevel = "A2";
        if (scorePct > 40) newLevel = "B1";
        if (scorePct > 60) newLevel = "B2";
        if (scorePct > 80) newLevel = "C1";
        if (scorePct > 95) newLevel = "C2";

        // Save Results
        try {
            const fd = new FormData();
            fd.append('test_type', 'grammar');
            fd.append('score', scorePct);
            fd.append('level', newLevel);
            fd.append('details', JSON.stringify(detailedResults));

            await fetch('api/save_test_result.php', { method: 'POST', body: fd });

        } catch (e) {
            console.error("Failed to save result", e);
        }

        // Render Results
        document.getElementById('resScore').textContent = `${correctCount}/${currentQuestions.length}`;
        document.getElementById('resLevel').textContent = newLevel;

        // Detailed Breakdown
        const fbHtml = detailedResults.map((r, i) => `
        <div style="margin-bottom: 10px; padding: 10px; border-radius: 6px; background: ${r.isCorrect ? '#f0fdf4' : '#fef2f2'}; border: 1px solid ${r.isCorrect ? '#bbf7d0' : '#fecaca'};">
            <strong>Q${i + 1}:</strong> ${r.question}<br>
            <span style="color: ${r.isCorrect ? 'green' : 'red'};">You: ${r.userVal}</span> 
            ${!r.isCorrect ? `<span style="color: green; margin-left:10px;">Correct: ${r.correctVal}</span>` : ''}
        </div>
    `).join('');

        document.getElementById('feedbackContainer').innerHTML = "<h4>Review</h4>" + fbHtml;

        quizArea.style.display = 'none';
        resultArea.style.display = 'block';
        resultArea.scrollIntoView({ behavior: 'smooth' });
    }
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>