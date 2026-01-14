<?php
$page = 'ai';
$path_prefix = '../';
require_once '../includes/header.php';
require_once '../includes/ai_service.php'; // AI Service Dahil Edildi

// AI Verilerini Çek (API Key varsa ChatGPT'den, yoksa Mock'tan)
$aiData = fetchAIRecommendationsFromChatGPT();
?>

<div style="text-align: center; margin-bottom: 40px;">
    <h1
        style="background: linear-gradient(to right, #38bdf8, #818cf8); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-size: 2.5rem;">
        AI Smart Coach</h1>
    <p style="color: var(--text-muted);">Analyzing your recent performance to build your personalized path.</p>
</div>

<div class="dashboard-grid">
    <!-- AI Insight -->
    <section class="card grid-col-2" style="position: relative; overflow: hidden;">
        <div style="position: absolute; top: -10px; right: -10px; opacity: 0.1;">
            <svg width="200" height="200" viewBox="0 0 24 24" fill="white">
                <path
                    d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 15h-2v-6h2v6zm0-8h-2V7h2v2z" />
            </svg>
        </div>
        <h2>Diagnostic Insight</h2>
        <p style="font-size: 1.1rem; line-height: 1.8;">
            <?php echo $aiData['insight_text']; ?>
        </p>
        <div
            style="margin-top: 20px; padding: 15px; background: rgba(56, 189, 248, 0.1); border-left: 4px solid var(--accent-color); border-radius: 4px;">
            <strong>Focus Area:</strong> <?php echo $aiData['focus_area']; ?>
        </div>
    </section>

    <!-- Daily Plan -->
    <section class="card">
        <h2>Today's AI Plan (Personalized)</h2>
        <ul style="margin-top: 10px;">
            <?php
            foreach ($aiData['daily_plan'] as $rec):
                $color = 'var(--text-muted)';
                if ($rec['priority'] == 'High')
                    $color = 'var(--danger)';
                if ($rec['priority'] == 'Medium')
                    $color = 'var(--secondary-color)';
                if ($rec['priority'] == 'Low')
                    $color = 'var(--success)';
                ?>
                <li class="todo-item"
                    style="border: none; padding: 15px 0; display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <span style="display: flex; align-items: center; gap: 10px;">
                            <span
                                style="width: 10px; height: 10px; background: <?php echo $color; ?>; border-radius: 50%;"></span>
                            <strong style="font-size: 1rem;"><?php echo $rec['title']; ?></strong>
                        </span>
                        <span style="font-size: 0.85rem; color: var(--text-muted); margin-left: 20px;">
                            Duration: <?php echo $rec['duration']; ?> • Focus: <?php echo ucfirst($rec['type']); ?>
                        </span>
                    </div>

                    <!-- integration with To-Do Module -->
                    <form action="todo.php" method="POST" style="margin: 0;">
                        <input type="hidden" name="task_text" value="<?php echo htmlspecialchars($rec['title']); ?>">
                        <input type="hidden" name="priority" value="<?php echo $rec['priority']; ?>">
                        <button type="submit" name="add" class="btn btn-sm"
                            style="border: 1px solid var(--border-color); background: rgba(255,255,255,0.05);">
                            + Add to List
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
        <div style="text-align: center; margin-top: 15px; font-size: 0.9rem; color: var(--text-muted);">
            Accepting a task adds it directly to your To-Do List module.
        </div>
    </section>

    <!-- Content Recommendations -->
    <section class="card grid-col-2">
        <h2>Recommended Resources</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <?php foreach ($aiData['resources'] as $res): ?>
                <?php
                // Badge color logic
                $badgeClass = 'badge-b1'; // default
                if (stripos($res['type'], 'Quiz') !== false)
                    $badgeClass = 'badge-a1';
                if (stripos($res['type'], 'Video') !== false)
                    $badgeClass = 'badge-b2';
                ?>
                <div
                    style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                    <div
                        style="height: 100px; background: #334155; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                        Thumbnail
                    </div>
                    <h3 style="font-size: 1rem; margin-bottom: 5px;"><?php echo $res['title']; ?></h3>
                    <p style="font-size: 0.8rem; color: var(--text-muted); margin-bottom: 10px; line-height: 1.4;">
                        <?php echo isset($res['description']) ? $res['description'] : ''; ?>
                    </p>
                    <span class="badge <?php echo $badgeClass; ?>"><?php echo $res['type']; ?></span>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<?php require_once '../includes/footer.php'; ?>