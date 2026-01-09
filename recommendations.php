<?php
$page = 'ai';
require_once 'includes/header.php';
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
            Based on your last <strong>3 tests</strong>, our AI has detected a pattern. You are excelling in <strong
                style="color: var(--success);">Reading Comprehension</strong> but struggling slightly with <strong
                style="color: var(--warning);">Past Perfect Continuous</strong> tense in Speaking.
        </p>
        <div
            style="margin-top: 20px; padding: 15px; background: rgba(56, 189, 248, 0.1); border-left: 4px solid var(--accent-color); border-radius: 4px;">
            <strong>Focus Area:</strong> Grammar & Fluency
        </div>
    </section>

    <!-- Daily Plan -->
    <section class="card">
        <h2>Today's AI Plan</h2>
        <ul style="margin-top: 10px;">
            <li class="todo-item" style="border: none; padding: 8px 0;">
                <span style="display: flex; align-items: center; gap: 10px;">
                    <span
                        style="width: 8px; height: 8px; background: var(--secondary-color); border-radius: 50%;"></span>
                    Review Temporal Clauses
                </span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">15 min</span>
            </li>
            <li class="todo-item" style="border: none; padding: 8px 0;">
                <span style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 8px; height: 8px; background: var(--primary-color); border-radius: 50%;"></span>
                    Listen to "Tech News Podcast"
                </span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">20 min</span>
            </li>
            <li class="todo-item" style="border: none; padding: 8px 0;">
                <span style="display: flex; align-items: center; gap: 10px;">
                    <span style="width: 8px; height: 8px; background: var(--success); border-radius: 50%;"></span>
                    Speak: Describe your workspace
                </span>
                <span style="font-size: 0.8rem; color: var(--text-muted);">5 min</span>
            </li>
        </ul>
        <button class="btn btn-primary" style="width: 100%; margin-top: 15px;">Start Session</button>
    </section>

    <!-- Content Recommendations -->
    <section class="card grid-col-2">
        <h2>Recommended Resources</h2>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
            <div
                style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                <div
                    style="height: 100px; background: #334155; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                    Thumbnail</div>
                <h3 style="font-size: 1rem; margin-bottom: 5px;">Advanced Grammar Guide</h3>
                <span class="badge badge-b2">Article</span>
            </div>
            <div
                style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                <div
                    style="height: 100px; background: #334155; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                    Thumbnail</div>
                <h3 style="font-size: 1rem; margin-bottom: 5px;">BBC Learning English</h3>
                <span class="badge badge-b2">Video</span>
            </div>
            <div
                style="background: rgba(0,0,0,0.2); padding: 15px; border-radius: 8px; border: 1px solid var(--border-color);">
                <div
                    style="height: 100px; background: #334155; border-radius: 4px; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; color: #64748b;">
                    Thumbnail</div>
                <h3 style="font-size: 1rem; margin-bottom: 5px;">IELTS Mock Test 4</h3>
                <span class="badge badge-a1">Quiz</span>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>