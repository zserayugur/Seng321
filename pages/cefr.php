<?php
$page = 'cefr';
$path_prefix = '../';
require_once '../includes/header.php';

// Prediction Logic (Mock)
$predicted_ielts = "";
$predicted_toefl = "";
$user_input_score = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_input_score = intval($_POST['score']);
    // Simple mock logic: Score / 10 roughly
    $predicted_ielts = number_format(($user_input_score / 12), 1);
    if ($predicted_ielts > 9)
        $predicted_ielts = 9.0;

    $predicted_toefl = intval($user_input_score * 1.2);
    if ($predicted_toefl > 120)
        $predicted_toefl = 120;
}
?>

<h1>CEFR Level & Score Prediction</h1>

<div class="dashboard-grid">
    <!-- CEFR Mapping Visualization -->
    <section class="card grid-col-2">
        <h2>Your CEFR Journey</h2>
        <div
            style="display: flex; justify-content: space-between; align-items: flex-end; height: 150px; padding-top: 20px;">
            <div
                style="width: 15%; background: var(--border-color); height: 20%; border-radius: 8px 8px 0 0; text-align: center; padding-top: 5px;">
                A1</div>
            <div
                style="width: 15%; background: var(--border-color); height: 35%; border-radius: 8px 8px 0 0; text-align: center; padding-top: 5px;">
                A2</div>
            <div
                style="width: 15%; background: var(--primary-color); height: 50%; border-radius: 8px 8px 0 0; text-align: center; padding-top: 5px; box-shadow: 0 0 15px var(--primary-color);">
                B1</div>
            <div
                style="width: 15%; background: var(--border-color); height: 65%; border-radius: 8px 8px 0 0; text-align: center; padding-top: 5px;">
                B2</div>
            <div
                style="width: 15%; background: var(--border-color); height: 80%; border-radius: 8px 8px 0 0; text-align: center; padding-top: 5px;">
                C1</div>
            <div
                style="width: 15%; background: var(--border-color); height: 95%; border-radius: 8px 8px 0 0; text-align: center; padding-top: 5px;">
                C2</div>
        </div>
        <p style="margin-top: 20px; color: var(--text-muted);">You are currently at <strong>Level B1
                (Intermediate)</strong>. Your AI assessment suggests you are ready to tackle B2 material.</p>
    </section>

    <!-- Score Prediction Module -->
    <section class="card">
        <h2>IELTS / TOEFL Predictor</h2>
        <p style="font-size: 0.9rem; margin-bottom: 15px;">Enter your latest internal diagnostic score (0-100) to see
            your estimated international exam scores.</p>

        <form method="POST" action="">
            <input type="number" name="score" placeholder="Enter Diagnostic Score (0-100)" min="0" max="100" required
                value="<?php echo $user_input_score; ?>">
            <button type="submit" class="btn btn-primary" style="width: 100%;">Predict Scores</button>
        </form>

        <?php if ($predicted_ielts): ?>
            <div style="margin-top: 20px; padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px;">
                <h3 style="margin-bottom: 10px; font-size: 1rem;">Prediction Results:</h3>
                <div class="stat-row">
                    <span>IELTS Band:</span>
                    <span style="color: var(--accent-color); font-weight: bold;">
                        <?php echo $predicted_ielts; ?>
                    </span>
                </div>
                <div class="stat-row">
                    <span>TOEFL iBT:</span>
                    <span style="color: var(--secondary-color); font-weight: bold;">
                        <?php echo $predicted_toefl; ?>
                    </span>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>