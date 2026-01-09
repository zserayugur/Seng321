<?php
$page = 'review';
$path_prefix = '../';
require_once '../includes/header.php';

$test_name = isset($_GET['test']) ? htmlspecialchars($_GET['test']) : "General Knowledge Quiz";
?>

<div style="margin-bottom: 20px;">
    <a href="reports.php" style="color: var(--text-muted); font-size: 0.9rem;">&larr; Back to Reports</a>
    <h1 style="margin-top: 10px;">Review:
        <?php echo $test_name; ?>
    </h1>
</div>

<div class="dashboard-grid">
    <section class="card">
        <h2>Score Summary</h2>
        <div style="font-size: 3rem; font-weight: 800; color: var(--success); text-align: center;">85%</div>
        <p style="text-align: center; color: var(--text-muted);">Great job! You passed looking strong.</p>
    </section>

    <section class="card">
        <h2>Details</h2>
        <div class="stat-row">
            <span>Total Questions:</span>
            <strong>20</strong>
        </div>
        <div class="stat-row">
            <span>Correct:</span>
            <strong style="color: var(--success);">17</strong>
        </div>
        <div class="stat-row">
            <span>Incorrect:</span>
            <strong style="color: var(--danger);">3</strong>
        </div>
        <div class="stat-row">
            <span>Time Taken:</span>
            <strong>14m 32s</strong>
        </div>
    </section>
</div>

<section class="card" style="margin-top: 20px;">
    <h2>Question Breakdown</h2>

    <!-- Question 1 (Correct) -->
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

    <!-- Question 2 (Incorrect) -->
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
        <p style="font-size: 0.9rem; color: var(--text-muted); margin-top: 10px;">
            <strong>Explanation:</strong> Ephemeral means lasting for a very short time. Eternal means lasting forever.
        </p>
    </div>

    <!-- Question 3 (Correct) -->
    <div class="todo-item" style="display: block;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
            <strong style="color: var(--text-color);">3. Fill in the blank: "I look forward _____ from you."</strong>
            <span class="badge badge-c1">Correct</span>
        </div>
        <div
            style="background: rgba(34, 197, 94, 0.1); border: 1px solid var(--success); padding: 10px; border-radius: 8px;">
            Your Answer: <em>to hearing</em>
        </div>
    </div>
</section>

<?php require_once '../includes/footer.php'; ?>