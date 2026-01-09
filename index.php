<?php
$page = 'dashboard';
require_once 'includes/header.php';

require_once 'includes/mock_data.php';

// Mock Data for Dashboard
$profile = getUserProfile();
$current_level = $profile['current_level'];
$progress_percent = $profile['progress_percent'];
$next_milestone = $profile['target_level'];
$streak_days = $profile['streak_days'];
?>

<div class="dashboard-grid">
    <!-- Welcome Section -->
    <section class="card grid-col-2"
        style="background: linear-gradient(135deg, rgba(99, 102, 241, 0.2), rgba(168, 85, 247, 0.2));">
        <h2>Welcome back, Student!</h2>
        <p>You are on a <strong>
                <?php echo $streak_days; ?> day streak!
            </strong> Keep up the momentum to reach
            <?php echo $next_milestone; ?>.
        </p>
        <div class="cefr-meter" style="height: 15px; background: rgba(0,0,0,0.3); margin-top: 15px;">
            <div class="cefr-fill" style="width: <?php echo $progress_percent; ?>%;"></div>
        </div>
        <div
            style="display: flex; justify-content: space-between; font-size: 0.8rem; margin-top: 5px; color: var(--text-muted);">
            <span>Current:
                <?php echo $current_level; ?>
            </span>
            <span>Target:
                <?php echo $next_milestone; ?>
            </span>
        </div>
    </section>

    <!-- Quick Actions -->
    <section class="card">
        <h2>Quick Actions</h2>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <a href="pages/todo.php" class="btn btn-primary" style="text-align: center;">View To-Do List</a>
            <a href="pages/cefr.php" class="btn" style="background: rgba(255,255,255,0.1); text-align: center;">Check
                Level</a>
        </div>
    </section>

    <!-- Performance Snapshot (Mock Chart) -->
    <section class="card grid-col-2">
        <h2>Performance Snapshot</h2>
        <canvas id="miniChart" height="100"></canvas>
    </section>

    <script>
        const ctx = document.getElementById('miniChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
                datasets: [{
                    label: 'Learning Hours',
                    data: [1, 2.5, 1.5, 3, 2, 4, 1.5],
                    borderColor: '#6366f1',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { display: false } },
                    y: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255,255,255,0.05)' } }
                }
            }
        });
    </script>

    <!-- Recent Task -->
    <section class="card">
        <h2>Latest Task</h2>
        <div class="todo-item">
            <span>Vocabulary Quiz: Business Terms</span>
            <span class="badge badge-b2">High Priority</span>
        </div>
        <div style="margin-top: 15px; text-align: center;">
            <a href="pages/todo.php" style="color: var(--accent-color); font-size: 0.9rem;">View All Tasks &rarr;</a>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>