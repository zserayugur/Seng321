<?php
$path_prefix = "../";
$page = "vocabulary";

require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../includes/mock_data.php";
require_once __DIR__ . "/../includes/ai_service.php";

include __DIR__ . "/../includes/header.php";

$skill = "vocabulary";
$sessionKey = "test_" . $skill;

$profile = getUserProfile();
$cefr = $profile["current_level"] ?? "B1";
$count = 20;

// Reset
if (isset($_GET["reset"])) {
    unset($_SESSION[$sessionKey]);
    header("Location: vocabulary.php");
    exit;
}

// Start
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "start") {
    $payload = fetchAITestQuestions($skill, $cefr, $count);

    $_SESSION[$sessionKey] = [
        "skill" => $skill,
        "cefr" => $payload["cefr"] ?? $cefr,
        "source" => $payload["source"] ?? "unknown",
        "questions" => $payload["questions"] ?? [],
        "answers" => [],
        "idx" => 0,
        "started_at" => time(),
    ];

    header("Location: vocabulary.php");
    exit;
}

// Answer
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "answer") {
    if (isset($_SESSION[$sessionKey])) {
        $idx = intval($_POST["idx"] ?? -1);
        $choice = intval($_POST["choice"] ?? -1);

        if ($idx === $_SESSION[$sessionKey]["idx"]) {
            $_SESSION[$sessionKey]["answers"][$idx] = $choice;
            $_SESSION[$sessionKey]["idx"]++;
        }
    }
    header("Location: vocabulary.php");
    exit;
}

$state = $_SESSION[$sessionKey] ?? null;
?>

<h1>Vocabulary Test</h1>

<?php if (!$state): ?>
    <section class="card" style="margin-top:16px;">
        <h2>Ready?</h2>
        <p>Current CEFR: <strong><?php echo htmlspecialchars($cefr); ?></strong></p>
        <form method="post">
            <input type="hidden" name="action" value="start">
            <button class="btn-primary" type="submit">Start</button>
        </form>
        <p style="opacity:.8; margin-top:10px;">Start dediğinde sorular CEFR seviyene göre üretilir.</p>
    </section>

<?php else: ?>

    <?php
        $questions = $state["questions"];
        $total = count($questions);
        $idx = $state["idx"];
    ?>

    <section class="card" style="margin-top:16px;">
        <div style="display:flex; justify-content:space-between; align-items:center;">
            <div>Progress: <strong><?php echo min($idx + 1, $total); ?>/<?php echo $total; ?></strong></div>
            <div>CEFR: <strong><?php echo htmlspecialchars($state["cefr"]); ?></strong></div>
        </div>
        <div style="margin-top:8px; opacity:.75;">Source: <?php echo htmlspecialchars($state["source"]); ?></div>
    </section>

    <?php if ($total === 0): ?>
        <section class="card" style="margin-top:16px;">
            <h2>No questions generated</h2>
            <a class="btn" href="vocabulary.php?reset=1">Try Again</a>
        </section>

    <?php elseif ($idx >= $total): ?>
        <?php
            $correct = 0;
            foreach ($questions as $i => $q) {
                $u = $state["answers"][$i] ?? null;
                if ($u !== null && intval($u) === intval($q["answer_index"])) $correct++;
            }
            $pct = round(($correct / $total) * 100);
        ?>
        <section class="card" style="margin-top:16px;">
            <h2>Completed</h2>
            <p>Score: <strong><?php echo $correct; ?>/<?php echo $total; ?></strong> (<?php echo $pct; ?>%)</p>

            <div style="display:flex; gap:10px; margin-top:12px;">
                <a class="btn" href="vocabulary.php?reset=1">Restart</a>
                <a class="btn" href="reports.php">Go to Reports</a>
            </div>
        </section>

        <section class="card" style="margin-top:16px;">
            <h2>Review</h2>
            <?php foreach ($questions as $i => $q): ?>
                <?php
                    $u = $state["answers"][$i] ?? null;
                    $isCorrect = ($u !== null && intval($u) === intval($q["answer_index"]));
                ?>
                <div style="padding:12px; border:1px solid var(--border-color); border-radius:10px; margin:10px 0;">
                    <div style="font-weight:600;">Q<?php echo $i+1; ?>: <?php echo htmlspecialchars($q["stem"]); ?></div>
                    <div style="margin-top:6px; opacity:.85;">
                        Your answer: <strong><?php echo $u !== null ? htmlspecialchars($q["choices"][$u]) : "—"; ?></strong>
                        <span style="margin-left:10px;">(<?php echo $isCorrect ? "Correct" : "Wrong"; ?>)</span>
                    </div>
                    <div style="margin-top:4px; opacity:.85;">
                        Correct: <strong><?php echo htmlspecialchars($q["choices"][intval($q["answer_index"])]); ?></strong>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

    <?php else: ?>
        <?php $q = $questions[$idx]; ?>
        <section class="card" style="margin-top:16px;">
            <h2>Question <?php echo $idx + 1; ?></h2>
            <p style="margin-top:10px;"><?php echo htmlspecialchars($q["stem"]); ?></p>

            <form method="post" style="margin-top:12px;">
                <input type="hidden" name="action" value="answer">
                <input type="hidden" name="idx" value="<?php echo $idx; ?>">

                <?php foreach ($q["choices"] as $i => $c): ?>
                    <label style="display:block; margin:10px 0;">
                        <input type="radio" name="choice" value="<?php echo $i; ?>" required>
                        <?php echo htmlspecialchars($c); ?>
                    </label>
                <?php endforeach; ?>

                <button class="btn-primary" type="submit" style="margin-top:10px;">Next</button>
            </form>
        </section>
    <?php endif; ?>

<?php endif; ?>

<?php include __DIR__ . "/../includes/footer.php"; ?>