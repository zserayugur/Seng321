<?php
$page = 'review';
$path_prefix = '../';
require_once '../includes/header.php';
require_once '../includes/mock_data.php';

$testId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$allResults = getTestResults();
$currentTest = null;

foreach ($allResults as $res) {
    if ($res['id'] === $testId) {
        $currentTest = $res;
        break;
    }
}

// Fallback if not found
if (!$currentTest) {
    $currentTest = $allResults[0]; // Default to first
}
?>

<div style="margin-bottom: 20px;">
    <a href="reports.php" style="color: var(--text-muted); font-size: 0.9rem;">&larr; Back to Reports</a>
    <h1 style="margin-top: 10px;">Review: <?php echo htmlspecialchars($currentTest['test']); ?></h1>
    <span class="badge badge-b2"><?php echo ucfirst($currentTest['type'] ?? 'Standard'); ?> Assessment</span>
</div>

<div class="dashboard-grid">
    <section class="card">
        <h2>Score Summary</h2>
        <div style="font-size: 3rem; font-weight: 800; color: var(--success); text-align: center;">
            <?php echo $currentTest['score']; ?>%
        </div>
        <p style="text-align: center; color: var(--text-muted);">
            Level Achieved: <strong style="color: var(--accent-color);"><?php echo $currentTest['level']; ?></strong>
        </p>
    </section>

    <section class="card">
        <h2>Details</h2>
        <div class="stat-row">
            <span>Date Taken:</span>
            <strong><?php echo $currentTest['date']; ?></strong>
        </div>
        <div class="stat-row">
            <span>Status:</span>
            <strong><?php echo $currentTest['status']; ?></strong>
        </div>
        <div class="stat-row">
            <span>Max Score:</span>
            <strong><?php echo $currentTest['max_score']; ?></strong>
        </div>
    </section>
</div>

<section class="card" style="margin-top: 20px;">
    <h2>Assessment Feedback</h2>

    <!-- Dynamic Content Based on Type -->
    <?php if (isset($currentTest['type']) && $currentTest['type'] === 'speaking'): ?>

        <!-- FR15, FR16: Speaking Review (Audio & Transcript) -->
        <div style="background: rgba(0,0,0,0.2); padding: 20px; border-radius: 8px; margin-bottom: 20px;">
            <h3 style="margin-bottom: 15px;">Your Recording</h3>
            <!-- FR16: Replay Recordings -->
            <audio controls style="width: 100%; margin-bottom: 15px;">
                <source src="mock_audio.mp3" type="audio/mpeg">
                Your browser does not support the audio element.
            </audio>

            <!-- FR16: ASR Transcript -->
            <h4 style="color: var(--text-muted); margin-bottom: 10px;">AI Transcript (ASR):</h4>
            <div
                style="padding: 15px; background: rgba(56, 189, 248, 0.1); border-left: 4px solid var(--primary-color); font-style: italic;">
                "<?php echo $currentTest['details']['asr_transcript'] ?? 'Transcript not available.'; ?>"
            </div>

            <div style="margin-top: 20px;">
                <h4 style="color: var(--success); margin-bottom: 10px;">AI Feedback:</h4>
                <p><?php echo $currentTest['details']['feedback'] ?? 'Processing feedback...'; ?></p>
            </div>
        </div>

    <?php elseif (isset($currentTest['type']) && $currentTest['type'] === 'writing'): ?>

        <!-- FR18, FR12: Writing Review (Essay & Feedback) -->
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div>
                <h3 style="margin-bottom: 10px;">Your Essay</h3>
                <div
                    style="background: #1e293b; padding: 20px; border-radius: 8px; height: 300px; overflow-y: auto; font-family: courier, monospace; font-size: 0.9rem;">
                    <strong style="display:block; margin-bottom:10px; color:var(--accent-color);">Prompt:
                        <?php echo $currentTest['details']['prompt']; ?></strong>
                    <?php echo $currentTest['details']['essay'] ?? ''; ?>
                </div>
            </div>
            <div>
                <h3 style="margin-bottom: 10px;">AI Critique</h3>
                <div
                    style="background: rgba(168, 85, 247, 0.1); border: 1px solid var(--accent-color); padding: 20px; border-radius: 8px;">
                    <h4 style="color: var(--accent-color); margin-top: 0;">Coherence & Grammar</h4>
                    <p><?php echo $currentTest['details']['ai_corrections'] ?? ''; ?></p>

                    <h4 style="color: var(--accent-color); margin-top: 20px;">Vocabulary Range</h4>
                    <p>Good use of 'illustrates'. Try to use more varied transition words instead of 'and'.</p>

                    <div style="margin-top: 20px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                        <strong>Est. IELTS Score:</strong> <span class="badge badge-b2">6.5</span>
                    </div>
                </div>
            </div>
        </div>

    <?php elseif (isset($currentTest['type']) && $currentTest['type'] === 'listening'): ?>

        <div style="text-align: center; padding: 40px;">
            <h3>Listening Comprehension Analysis</h3>
            <p>You correctly answered Questions 1, 3, 5.</p>
            <p style="color: var(--text-muted);">Audio: Mock_Listening_Unit_4.mp3</p>
            <div
                style="margin-top: 20px; text-align: left; background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px;">
                <strong>Transcript Snippet:</strong>
                <p><em><?php echo $currentTest['details']['transcript']; ?></em></p>
            </div>
        </div>

    <?php else: ?>

        <!-- Standard Quiz Review (FallbackMock) -->
        <div class="todo-item" style="display: block;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <strong style="color: var(--text-color);">1. Which sentence is grammatically correct?</strong>
                <span class="badge badge-c1">Correct</span>
            </div>
            <div
                style="background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success); padding: 10px; border-radius: 8px;">
                Your Answer: <em>She has been working here for two years.</em>
            </div>
        </div>

        <div class="todo-item" style="display: block;">
            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                <strong style="color: var(--text-color);">2. Choose the synonym for 'Ephemeral'.</strong>
                <span class="badge badge-a1">Incorrect</span>
            </div>
            <div
                style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); padding: 10px; border-radius: 8px; margin-bottom: 10px;">
                Your Answer: <em>Eternal</em>
            </div>
            <div
                style="background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success); padding: 10px; border-radius: 8px;">
                Correct Answer: <em>Temporary</em>
            </div>
        </div>

    <?php endif; ?>
</section>

<?php require_once '../includes/footer.php'; ?>