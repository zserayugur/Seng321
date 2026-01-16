<?php
$page = 'speaking';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/mock_data.php';
require_once __DIR__ . '/../includes/ai_service.php';
require_once __DIR__ . '/../includes/header.php';

$sessionKey = 'speaking_topic';
$topic = $_SESSION[$sessionKey] ?? null;
$profile = getUserProfile();
$cefr = $profile['current_level'] ?? 'B1';

// Generate topic if not exists
if (!$topic && isset($_GET['start'])) {
    $topic = fetchAISpeakingTopic($cefr);
    $_SESSION[$sessionKey] = $topic;
    header("Location: speaking.php");
    exit;
}
?>

<h2>Speaking Test (10s prep + 150s recording)</h2>

<?php if (!$topic): ?>
    <section class="card" style="margin-top:16px;">
        <h2>Ready to start?</h2>
        <p>You will have 10 seconds to prepare, then 150 seconds (2.5 minutes) to speak about an AI-generated topic.</p>
        <p>Current CEFR: <strong><?php echo htmlspecialchars($cefr); ?></strong></p>
        <a class="btn-primary" href="speaking.php?start=1">Start Speaking</a>
    </section>
<?php else: ?>
    <section class="card" style="margin-top:16px;">
        <h2>Speaking Topic</h2>
        <p style="white-space:pre-wrap; line-height:1.6; padding:12px; background:#f5f5f5; border-radius:8px; color:#000;">
            <?php echo htmlspecialchars($topic); ?>
        </p>
    </section>

    <button id="btnStart" class="btn-primary" style="margin-top:16px;">Begin Speaking</button>

    <div id="prep" style="display:none;" class="card" style="margin-top:16px;">
        <h3>Preparation: <span id="prepTimer">10</span>s</h3>
        <p>Think about what you want to say. Recording will start automatically.</p>
    </div>

    <div id="record" style="display:none;" class="card" style="margin-top:16px;">
        <h3>Recording: <span id="recTimer">150</span>s</h3>
        <div style="margin:12px 0;">
            <button id="btnStop" class="btn-primary">Submit Recording</button>
            <p id="status" style="margin-top:8px;"></p>
        </div>

        <div id="playbackSection" style="display:none; margin-top:16px;">
            <h3>Your Recording</h3>
            <audio id="playback" controls style="width:100%;"></audio>
        </div>

        <div id="resultSection" style="display:none; margin-top:16px;">
            <h3>Evaluation Results</h3>
            <div id="evaluationDisplay"></div>
        </div>
    </div>

    <script>
    let attemptId = null, mediaRecorder = null, chunks = [];
    let prepLeft = 10, recLeft = 150, prepInterval = null, recInterval = null;

    async function startAttempt() {
        const fd = new FormData();
        fd.append('type', 'speaking');
        const res = await fetch('api/start_attempt.php', { method: 'POST', body: fd });
        const data = await res.json();
        attemptId = data.attempt_id;
        if (!attemptId) throw new Error("attempt_id missing");
    }

    async function startMic() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
            mediaRecorder = new MediaRecorder(stream, { mimeType: 'audio/webm' });
            chunks = [];
            mediaRecorder.ondataavailable = (e) => { if (e.data.size > 0) chunks.push(e.data); };
            mediaRecorder.start();
        } catch (e) {
            alert("Microphone access denied. Please allow microphone access and try again.");
            throw e;
        }
    }

    function startPrep() {
        document.getElementById('btnStart').disabled = true;
        document.getElementById('prep').style.display = 'block';
        prepLeft = 10;
        document.getElementById('prepTimer').textContent = prepLeft;

        prepInterval = setInterval(async () => {
            prepLeft--;
            document.getElementById('prepTimer').textContent = prepLeft;
            if (prepLeft <= 0) {
                clearInterval(prepInterval);
                document.getElementById('prep').style.display = 'none';
                await beginRecording();
            }
        }, 1000);
    }

    async function beginRecording() {
        document.getElementById('record').style.display = 'block';
        await startMic();
        recLeft = 150;
        document.getElementById('recTimer').textContent = recLeft;

        recInterval = setInterval(() => {
            recLeft--;
            document.getElementById('recTimer').textContent = recLeft;
            if (recLeft <= 0) {
                clearInterval(recInterval);
                submitSpeaking(true);
            }
        }, 1000);
    }

    async function submitSpeaking(isAuto = false) {
        const btn = document.getElementById('btnStop');
        btn.disabled = true;
        document.getElementById('status').textContent = isAuto ? "Auto-submitting..." : "Submitting...";
        if (recInterval) clearInterval(recInterval);

        mediaRecorder.onstop = async () => {
            try {
                const blob = new Blob(chunks, { type: 'audio/webm' });

                // Upload audio
                const fd = new FormData();
                fd.append('attempt_id', attemptId);
                fd.append('audio', blob, 'speaking.webm');
                const up = await fetch('api/upload_speaking_audio.php', { method: 'POST', body: fd });
                const upData = await up.json();

                if (upData.ok && upData.public_url) {
                    document.getElementById('playback').src = upData.public_url;
                    document.getElementById('playbackSection').style.display = 'block';
                }

                // Submit attempt
                const fd2 = new FormData();
                fd2.append('attempt_id', attemptId);
                fd2.append('assignment_id', "<?= (int)($_GET['assignment_id'] ?? 0) ?>");
                await fetch('api/submit_attempt.php', { method: 'POST', body: fd2 });

                // Get transcript (mock for now - in production use speech recognition API)
                const transcript = await getTranscript(blob);

                // Evaluate
                const fd3 = new FormData();
                fd3.append('attempt_id', attemptId);
                fd3.append('skill', 'speaking');
                fd3.append('transcript', transcript);

                const ev = await fetch('api/evaluate_attempt.php', { method: 'POST', body: fd3 });
                const evData = await ev.json();

                if (evData.ok && evData.evaluation) {
                    const eval = evData.evaluation;
                    const display = document.getElementById('evaluationDisplay');
                    display.innerHTML = `
                        <div style="padding:12px; background:#f0f8ff; border-radius:8px; margin-bottom:12px; color:#000;">
                            <h4 style="color:#000;">CEFR Level: <strong>${eval.cefr || 'N/A'}</strong></h4>
                            <p style="color:#000;"><strong>IELTS Estimate:</strong> ${eval.ielts_estimate || 'N/A'}</p>
                            <p style="color:#000;"><strong>TOEFL Estimate:</strong> ${eval.toefl_estimate || 'N/A'}</p>
                            <p style="color:#000;"><strong>Overall Score:</strong> ${eval.overall_score || 'N/A'}/10</p>
                        </div>
                        <div style="margin-top:12px; color:#000;">
                            <h4 style="color:#000;">Score Breakdown:</h4>
                            <ul style="color:#000;">
                                <li style="color:#000;">Fluency: ${eval.fluency_score || 'N/A'}/10</li>
                                <li style="color:#000;">Pronunciation: ${eval.pronunciation_score || 'N/A'}/10</li>
                                <li style="color:#000;">Grammar: ${eval.grammar_score || 'N/A'}/10</li>
                                <li style="color:#000;">Vocabulary: ${eval.vocabulary_score || 'N/A'}/10</li>
                            </ul>
                        </div>
                        <div style="margin-top:12px; color:#000;">
                            <h4 style="color:#000;">Diagnostic:</h4>
                            <p style="color:#000;">${eval.diagnostic || 'Evaluation completed.'}</p>
                        </div>
                        ${eval.strengths && eval.strengths.length > 0 ? `
                        <div style="margin-top:12px; color:#000;">
                            <h4 style="color:#000;">Strengths:</h4>
                            <ul style="color:#000;">${eval.strengths.map(s => `<li style="color:#000;">${s}</li>`).join('')}</ul>
                        </div>
                        ` : ''}
                        ${eval.improvements && eval.improvements.length > 0 ? `
                        <div style="margin-top:12px; color:#000;">
                            <h4 style="color:#000;">Areas for Improvement:</h4>
                            <ul style="color:#000;">${eval.improvements.map(i => `<li style="color:#000;">${i}</li>`).join('')}</ul>
                        </div>
                        ` : ''}
                    `;
                    document.getElementById('resultSection').style.display = 'block';
                    document.getElementById('status').textContent = "Evaluation completed and saved.";
                } else {
                    document.getElementById('status').textContent = "Error: " + (evData.error || "Evaluation failed");
                }
            } catch (e) {
                document.getElementById('status').textContent = "Error: " + (e?.message || e);
            }
        };

        mediaRecorder.stop();
    }

    async function getTranscript(blob) {
        // Mock transcript - in production, use Web Speech API or send to speech recognition service
        // For now, return a placeholder that will be evaluated
        return "This is a mock transcript. In production, use speech recognition API to convert audio to text.";
    }

    document.getElementById('btnStart')?.addEventListener('click', async () => {
        try {
            await startAttempt();
            startPrep();
        } catch (e) {
            alert("Start failed: " + (e?.message || e));
        }
    });

    document.getElementById('btnStop')?.addEventListener('click', () => submitSpeaking(false));
    </script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
