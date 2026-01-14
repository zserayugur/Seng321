<?php
$page = 'cefr';
$path_prefix = '../';
$_SERVER['REQUEST_METHOD'] === 'POST' ? $user_input_score = intval($_POST['score']) : $user_input_score = "";
require_once '../includes/header.php';
require_once '../includes/mock_data.php';

$profile = getUserProfile();
$current_level = $profile['current_level'];

// Helper to check active level for chart
function isLevel($lvl, $current)
{
    return $lvl === $current;
}
?>

<h1>CEFR Level & Score Prediction</h1>

<div class="dashboard-grid">
    <!-- CEFR Mapping Visualization -->
    <section class="card grid-col-2">
        <h2>Your CEFR Journey</h2>
        <div
            style="display: flex; justify-content: space-between; align-items: flex-end; height: 150px; padding-top: 20px;">
            <?php
            $levels = ['A1', 'A2', 'B1', 'B2', 'C1', 'C2'];
            $heights = [20, 35, 50, 65, 80, 95];
            foreach ($levels as $index => $lvl):
                $isActive = isLevel($lvl, $current_level);
                $bg = $isActive ? 'var(--primary-color)' : 'var(--border-color)';
                $shadow = $isActive ? '0 0 15px var(--primary-color)' : 'none';
                $scale = $isActive ? 'transform: scale(1.1);' : '';
                ?>
                <div
                    style="width: 15%; background: <?php echo $bg; ?>; height: <?php echo $heights[$index]; ?>%; border-radius: 8px 8px 0 0; text-align: center; padding-top: 5px; box-shadow: <?php echo $shadow; ?>; <?php echo $scale; ?> transition: all 0.3s ease;">
                    <?php echo $lvl; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <p style="margin-top: 20px; color: var(--text-muted);">
            System Classification: You are currently at <strong
                style="color: var(--primary-color); font-size: 1.2rem;"><?php echo $current_level; ?>
                (Intermediate)</strong>.
            <br>
            <span style="font-size: 0.9rem;">(Based on your last 5 assessment results)</span>
        </p>
    </section>

    <!-- Score Prediction Module -->
    <section class="card">
        <h2>International Exam Equivalence</h2>
        <p style="font-size: 0.9rem; margin-bottom: 25px;">
            Based on your current CEFR level (<?php echo $current_level; ?>) and diagnostic performance, here are your
            estimated scores:
        </p>

        <div style="display: flex; flex-direction: column; gap: 15px;">
            <div
                style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px; border-left: 4px solid var(--accent-color);">
                <div class="stat-row">
                    <span>IELTS Band Prediction:</span>
                    <span style="color: var(--accent-color); font-weight: bold; font-size: 1.5rem;">
                        <?php echo $profile['ielts_estimate']; ?>
                    </span>
                </div>
                <div style="height: 6px; background: #334155; border-radius: 3px; margin-top: 10px;">
                    <div
                        style="width: <?php echo ($profile['ielts_estimate'] / 9) * 100; ?>%; height: 100%; background: var(--accent-color); border-radius: 3px;">
                    </div>
                </div>
            </div>

            <div
                style="padding: 15px; background: rgba(255,255,255,0.05); border-radius: 8px; border-left: 4px solid var(--secondary-color);">
                <div class="stat-row">
                    <span>TOEFL iBT Prediction:</span>
                    <span style="color: var(--secondary-color); font-weight: bold; font-size: 1.5rem;">
                        <?php echo $profile['toefl_estimate']; ?>
                    </span>
                </div>
                <div style="height: 6px; background: #334155; border-radius: 3px; margin-top: 10px;">
                    <div
                        style="width: <?php echo ($profile['toefl_estimate'] / 120) * 100; ?>%; height: 100%; background: var(--secondary-color); border-radius: 3px;">
                    </div>
                </div>
            </div>
        </div>

        <p style="margin-top: 20px; font-size: 0.8rem; color: var(--text-muted); text-align: center;">
            *Estimates are updated automatically after every major assessment.
        </p>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>