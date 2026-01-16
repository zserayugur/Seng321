<?php
$page = 'vocabulary';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 900px; margin: 0 auto;">
    <section class="card">
        <h1>AI Vocabulary Assessment</h1>
        <p>Test your English vocabulary with 20 AI-generated questions adapted to your level.</p>

        <!-- Step 1: Start -->
        <div id="startArea" style="margin-top: 30px; text-align: center;">
            <div class="stat-grid" style="grid-template-columns: repeat(2, 1fr); gap: 15px; margin-bottom: 25px;">
                <div class="stat-card">
                    <h3>Current Level</h3>
                    <div class="value"><?= htmlspecialchars($user['current_level'] ?? 'B1') ?></div>
                </div>
                <div class="stat-card">
                    <h3>Target</h3>
                    <div class="value">10 Questions</div>
                </div>
            </div>

            <button id="btnStart" class="btn-primary" style="padding: 12px 30px;">Start Vocabulary Test</button>
            <p id="loading" style="display:none; color: #666; margin-top: 15px;">Generating your test... Please wait...
            </p>
        </div>

        <!-- Step 2: Quiz -->
        <div id="quizArea" style="display:none; margin-top: 30px;">
            <form id="quizForm" onsubmit="handleSubmit(event)">
                <div id="questionsContainer"></div>

                <div style="text-align: right; margin-top: 25px;">
                    <button type="submit" id="btnSubmit" class="btn-primary">Submit Answers</button>
                </div>
            </form>
        </div>
    </section>

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
    const skill = "vocabulary";
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

        // Local Evaluation (since we know the answers from the API usually, or we can send back to server)
        // Actually, for security, the server should evaluate. 
        // But our get_test_questions.php returns the answer_index usually. 
        // Let's assume we can evaluate locally for immediate feedback display, 
        // AND send to server for recording progress.

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
            fd.append('test_type', 'vocabulary');
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