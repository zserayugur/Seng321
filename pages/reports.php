<?php
$page = 'reports';
$path_prefix = '../';
require_once '../includes/header.php';

require_once '../includes/mock_data.php';

// Mock Data for Previous Results
$results = getTestResults();
?>

<div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
    <h1>Performance Analytics</h1>
    <button class="btn btn-primary" onclick="window.print()">Download Report PDF</button>
</div>

<div class="dashboard-grid">
    <!-- Main Progress Chart -->
    <section class="card grid-col-2">
        <h2>Skill Proficiency Growth</h2>
        <canvas id="mainChart" height="100"></canvas>
    </section>

    <!-- Skill Breakdown (Radar Chart) -->
    <section class="card">
        <h2>Skill Balance</h2>
        <canvas id="radarChart" height="200"></canvas>
    </section>
</div>

<!-- Previous Results Table -->
<section class="card" style="margin-top: 20px;">
    <h2>Previous Result Storage</h2>
    <div style="overflow-x: auto;">
        <table style="width: 100%; border-collapse: collapse; margin-top: 10px;">
            <thead>
                <tr style="text-align: left; border-bottom: 1px solid var(--text-muted); color: var(--text-muted);">
                    <th style="padding: 10px;">Date</th>
                    <th style="padding: 10px;">Test Name</th>
                    <th style="padding: 10px;">Score</th>
                    <th style="padding: 10px;">Level</th>
                    <th style="padding: 10px;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($results as $row): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 12px;">
                            <?php echo $row['date']; ?>
                        </td>
                        <td style="padding: 12px; font-weight: 500;">
                            <?php echo $row['test']; ?>
                        </td>
                        <td style="padding: 12px; color: var(--accent-color);">
                            <?php echo $row['score'] . '/' . $row['max_score']; ?>
                        </td>
                        <td style="padding: 12px;"><span class="badge badge-b1">
                                <?php echo $row['level']; ?>
                            </span></td>
                        <td style="padding: 12px;">
                            <a href="review.php?test=<?php echo urlencode($row['test']); ?>"
                                class="btn btn-sm btn-primary">Review Answers</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<script>
    // Line Chart
    const mainCtx = document.getElementById('mainChart').getContext('2d');
    new Chart(mainCtx, {
        type: 'line',
        data: {
            labels: ['Week 1', 'Week 2', 'Week 3', 'Week 4', 'Week 5', 'Week 6'],
            datasets: [{
                label: 'Overall Accuracy',
                data: [55, 60, 58, 65, 70, 72],
                borderColor: '#a855f7',
                backgroundColor: 'rgba(168, 85, 247, 0.2)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });

    // Radar Chart
    const radarCtx = document.getElementById('radarChart').getContext('2d');
    new Chart(radarCtx, {
        type: 'radar',
        data: {
            labels: ['Reading', 'Writing', 'Listening', 'Speaking', 'Grammar'],
            datasets: [{
                label: 'Current Skills',
                data: [80, 65, 75, 50, 85],
                fill: true,
                backgroundColor: 'rgba(56, 189, 248, 0.2)',
                borderColor: '#38bdf8',
                pointBackgroundColor: '#38bdf8'
            }]
        },
        options: {
            elements: { line: { borderWidth: 3 } },
            scales: {
                r: {
                    angleLines: { color: 'rgba(255,255,255,0.1)' },
                    grid: { color: 'rgba(255,255,255,0.1)' },
                    pointLabels: { color: '#f8fafc' },
                    ticks: { display: false }
                }
            }
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>