<?php
// pages/reading.php  (FR17 - Dual Reading Test)
// - 2 sequential reading tests: Intermediate–Easy then Advanced
// - Each test: 10 questions, 15-minute timer, manual submit or auto-submit at expiry
// - After test1 finishes => show "Start Next Test" button

$path_prefix = "../";
$page = "reading";

require_once __DIR__ . "/../includes/auth_guard.php";
require_once __DIR__ . "/../includes/mock_data.php";
require_once __DIR__ . "/../includes/ai_service.php";

include __DIR__ . "/../includes/header.php";

$sessionKey = "reading_dual_test";

/** CEFR helper */
function cefr_rank(string $lvl): int {
    $map = ["A1"=>1, "A2"=>2, "B1"=>3, "B2"=>4, "C1"=>5, "C2"=>6];
    $lvl = strtoupper(trim($lvl));
    return $map[$lvl] ?? 3; // default B1
}
function stage_cefr(string $current, int $stage): string {
    // Stage1: intermediate-ish (B1/B2), Stage2: advanced-ish (C1/C2)
    $current = strtoupper(trim($current));
    $r = cefr_rank($current);

    if ($stage === 1) {
        if ($r <= 2) return "B1";
        if ($r <= 4) return $current;     // B1/B2
        return "B2";                      // if C1/C2 => cap to B2
    } else {
        if ($r >= 6) return "C2";
        return "C1";
    }
}

function compute_score(array $questions, array $answers): array {
    $total = count($questions);
    $correct = 0;
    for ($i=0; $i<$total; $i++) {
        $a = $answers[$i] ?? null;
        $ansIdx = isset($questions[$i]["answer_index"]) ? intval($questions[$i]["answer_index"]) : -1;
        if ($a !== null && intval($a) === $ansIdx) $correct++;
    }
    $pct = $total > 0 ? round(($correct / $total) * 100) : 0;
    return ["correct"=>$correct, "total"=>$total, "pct"=>$pct];
}

// Constants per FR17
$COUNT = 10;
$DURATION_SEC = 15 * 60;

// Reset whole dual test
if (isset($_GET["reset"])) {
    unset($_SESSION[$sessionKey]);
    header("Location: reading.php");
    exit;
}

$profile = getUserProfile();
$currentCefr = $profile["current_level"] ?? "B1";

// Start Stage 1
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "start_stage1") {
    $cefr1 = stage_cefr($currentCefr, 1);
    $payload = fetchAITestQuestions("reading", $cefr1, $COUNT);

    $_SESSION[$sessionKey] = [
        "current_cefr" => $currentCefr,
        "active_stage" => 1,
        "stages" => [
            1 => [
                "label" => "Intermediate–Easy",
                "cefr" => $payload["cefr"] ?? $cefr1,
                "duration" => $DURATION_SEC,
                "started_at" => time(),
                "submitted_at" => null,
                "source" => $payload["source"] ?? "unknown",
                "passage" => $payload["passage"] ?? "",
                "questions" => $payload["questions"] ?? [],
                "answers" => [],
                "score" => null,
                "pct" => null,
            ],
            2 => [
                "label" => "Advanced",
                "cefr" => stage_cefr($currentCefr, 2),
                "duration" => $DURATION_SEC,
                "started_at" => null,
                "submitted_at" => null,
                "source" => null,
                "passage" => "",
                "questions" => [],
                "answers" => [],
                "score" => null,
                "pct" => null,
            ]
        ]
    ];

    header("Location: reading.php");
    exit;
}

// Start Stage 2 (only allowed after stage1 submitted)
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "start_stage2") {
    $state = $_SESSION[$sessionKey] ?? null;
    if (!$state || empty($state["stages"][1]["submitted_at"])) {
        header("Location: reading.php");
        exit;
    }

    $cefr2 = stage_cefr($currentCefr, 2);
    $payload = fetchAITestQuestions("reading", $cefr2, $COUNT);

    $_SESSION[$sessionKey]["active_stage"] = 2;
    $_SESSION[$sessionKey]["stages"][2] = [
        "label" => "Advanced",
        "cefr" => $payload["cefr"] ?? $cefr2,
        "duration" => $DURATION_SEC,
        "started_at" => time(),
        "submitted_at" => null,
        "source" => $payload["source"] ?? "unknown",
        "passage" => $payload["passage"] ?? "",
        "questions" => $payload["questions"] ?? [],
        "answers" => [],
        "score" => null,
        "pct" => null,
    ];

    header("Location: reading.php");
    exit;
}

// Submit stage (manual or auto via JS)
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "submit_stage") {
    $state = $_SESSION[$sessionKey] ?? null;
    $stage = intval($_POST["stage"] ?? 0);

    if ($state && ($stage === 1 || $stage === 2)) {
        $stageData = $state["stages"][$stage] ?? null;

        if ($stageData && empty($stageData["submitted_at"])) {
            $answersIn = $_POST["answers"] ?? [];
            $answers = [];

            if (is_array($answersIn)) {
                foreach ($answersIn as $k => $v) {
                    $i = intval($k);
                    $choice = intval($v);
                    if ($i >= 0 && $choice >= 0 && $choice <= 3) {
                        $answers[$i] = $choice;
                    }
                }
            }

            $_SESSION[$sessionKey]["stages"][$stage]["answers"] = $answers;

            $questions = $_SESSION[$sessionKey]["stages"][$stage]["questions"] ?? [];
            $sc = compute_score($questions, $answers);

            $_SESSION[$sessionKey]["stages"][$stage]["submitted_at"] = time();
            $_SESSION[$sessionKey]["stages"][$stage]["score"] = $sc["correct"];
            $_SESSION[$sessionKey]["stages"][$stage]["pct"] = $sc["pct"];
        }
    }

    header("Location: reading.php");
    exit;
}

$state = $_SESSION[$sessionKey] ?? null;
?>

<h1>Reading Test (Dual)</h1>

<?php if (!$state): ?>
    <section class="card" style="margin-top:16px;">
        <h2>Ready to start?</h2>
        <p>
            This module has <strong>2 sequential Reading tests</strong>:
            <strong>Intermediate–Easy</strong> then <strong>Advanced</strong>.
            Each test has <strong>10 questions</strong> and a <strong>15-minute timer</strong>.
        </p>
        <p>Detected CEFR (from profile): <strong><?php echo htmlspecialchars($currentCefr); ?></strong></p>

        <form method="post" style="margin-top:12px;">
            <input type="hidden" name="action" value="start_stage1">
            <button class="btn-primary" type="submit">Start Reading Test</button>
        </form>
    </section>

<?php else: ?>

    <?php
        $activeStage = intval($state["active_stage"] ?? 1);
        $stageData = $state["stages"][$activeStage] ?? null;

        $stage1Done = !empty($state["stages"][1]["submitted_at"]);
        $stage2Done = !empty($state["stages"][2]["submitted_at"]);
    ?>

    <?php if (!$stageData): ?>
        <section class="card" style="margin-top:16px;">
            <h2>State error</h2>
            <a class="btn" href="reading.php?reset=1">Reset</a>
        </section>

    <?php else: ?>

        <?php
            $label = $stageData["label"] ?? ("Stage " . $activeStage);
            $cefrUsed = $stageData["cefr"] ?? $currentCefr;
            $duration = intval($stageData["duration"] ?? $DURATION_SEC);
            $startedAt = intval($stageData["started_at"] ?? time());
            $submittedAt = $stageData["submitted_at"] ?? null;

            $remaining = $duration - (time() - $startedAt);
            if ($remaining < 0) $remaining = 0;

            $questions = $stageData["questions"] ?? [];
            $passage = $stageData["passage"] ?? "";
            $source = $stageData["source"] ?? "unknown";
        ?>

        <section class="card" style="margin-top:16px;">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:10px; flex-wrap:wrap;">
                <div>
                    <div style="font-weight:700; font-size:1.1rem;">
                        Stage <?php echo $activeStage; ?>: <?php echo htmlspecialchars($label); ?>
                    </div>
                    <div style="opacity:.8;">
                        CEFR target: <strong><?php echo htmlspecialchars($cefrUsed); ?></strong>
                        <span style="margin-left:10px;">Source: <?php echo htmlspecialchars($source); ?></span>
                    </div>
                </div>

                <div style="text-align:right;">
                    <div style="opacity:.75;">Time remaining</div>
                    <div id="timer" style="font-weight:800; font-size:1.2rem;">
                        <?php
                            $mm = floor($remaining / 60);
                            $ss = $remaining % 60;
                            echo sprintf("%02d:%02d", $mm, $ss);
                        ?>
                    </div>
                </div>
            </div>

            <div style="margin-top:12px; display:flex; gap:10px;">
                <a class="btn" href="reading.php?reset=1">Reset All</a>
            </div>
        </section>

        <?php if (!empty($submittedAt)): ?>
            <?php
                $score = intval($stageData["score"] ?? 0);
                $pct = intval($stageData["pct"] ?? 0);
                $total = count($questions);
            ?>
            <section class="card" style="margin-top:16px;">
                <h2>Stage <?php echo $activeStage; ?> Completed</h2>
                <p>Score: <strong><?php echo $score; ?>/<?php echo $total; ?></strong> (<?php echo $pct; ?>%)</p>

                <?php if ($activeStage === 1 && !$stage2Done): ?>
                    <form method="post" style="margin-top:12px;">
                        <input type="hidden" name="action" value="start_stage2">
                        <button class="btn-primary" type="submit">Start Next Test</button>
                    </form>
                <?php endif; ?>
            </section>

            <section class="card" style="margin-top:16px;">
                <h2>Review (Stage <?php echo $activeStage; ?>)</h2>
                <?php
                    $answers = $stageData["answers"] ?? [];
                    foreach ($questions as $i => $q):
                        $userA = $answers[$i] ?? null;
                        $correctIdx = intval($q["answer_index"] ?? -1);
                        $isCorrect = ($userA !== null && intval($userA) === $correctIdx);
                        $choices = $q["choices"] ?? [];
                ?>
                    <div style="padding:12px; border:1px solid var(--border-color); border-radius:10px; margin:10px 0;">
                        <div style="font-weight:700;">Q<?php echo $i+1; ?>: <?php echo htmlspecialchars($q["stem"] ?? ""); ?></div>
                        <div style="margin-top:8px; opacity:.85;">
                            Your answer:
                            <strong>
                                <?php
                                    echo ($userA !== null && isset($choices[intval($userA)]))
                                        ? htmlspecialchars($choices[intval($userA)])
                                        : "—";
                                ?>
                            </strong>
                            <span style="margin-left:10px;">(<?php echo $isCorrect ? "Correct" : "Wrong"; ?>)</span>
                        </div>
                        <div style="margin-top:4px; opacity:.85;">
                            Correct:
                            <strong>
                                <?php echo isset($choices[$correctIdx]) ? htmlspecialchars($choices[$correctIdx]) : "—"; ?>
                            </strong>
                        </div>
                    </div>
                <?php endforeach; ?>
            </section>

            <?php if ($stage1Done && $stage2Done): ?>
                <?php
                    $s1 = $state["stages"][1];
                    $s2 = $state["stages"][2];
                    $sumCorrect = intval($s1["score"] ?? 0) + intval($s2["score"] ?? 0);
                    $sumTotal = count($s1["questions"] ?? []) + count($s2["questions"] ?? []);
                    $sumPct = $sumTotal > 0 ? round(($sumCorrect / $sumTotal) * 100) : 0;
                ?>
                <section class="card" style="margin-top:16px;">
                    <h2>Overall Result</h2>
                    <p>
                        Stage 1: <strong><?php echo intval($s1["score"]); ?>/<?php echo count($s1["questions"]); ?></strong>
                        (<?php echo intval($s1["pct"]); ?>%)
                        <br>
                        Stage 2: <strong><?php echo intval($s2["score"]); ?>/<?php echo count($s2["questions"]); ?></strong>
                        (<?php echo intval($s2["pct"]); ?>%)
                    </p>
                    <p>
                        Total: <strong><?php echo $sumCorrect; ?>/<?php echo $sumTotal; ?></strong>
                        (<?php echo $sumPct; ?>%)
                    </p>

                    <div style="display:flex; gap:10px; margin-top:12px;">
                        <a class="btn" href="reports.php">Go to Reports</a>
                        <a class="btn" href="reading.php?reset=1">Restart All</a>
                    </div>
                </section>
            <?php endif; ?>

        <?php else: ?>
            <?php if (count($questions) === 0): ?>
                <section class="card" style="margin-top:16px;">
                    <h2>No questions generated</h2>
                    <p>AI did not return valid questions. Try reset and start again.</p>
                    <a class="btn" href="reading.php?reset=1">Reset</a>
                </section>
            <?php else: ?>

                <section class="card" style="margin-top:16px;">
                    <h2>Passage</h2>
                    <p style="white-space:pre-wrap; line-height:1.6;">
                        <?php echo htmlspecialchars($passage); ?>
                    </p>
                </section>

                <section class="card" style="margin-top:16px;">
                    <h2>Questions</h2>

                    <!-- Main submit form -->
                    <form id="readingForm" method="post" style="margin-top:12px;">
                        <input type="hidden" name="action" value="submit_stage">
                        <input type="hidden" name="stage" value="<?php echo $activeStage; ?>">

                        <?php foreach ($questions as $i => $q): ?>
                            <?php $choices = $q["choices"] ?? []; ?>
                            <div style="padding:12px; border:1px solid var(--border-color); border-radius:10px; margin:12px 0;">
                                <div style="font-weight:700;">Q<?php echo $i+1; ?>: <?php echo htmlspecialchars($q["stem"] ?? ""); ?></div>

                                <div style="margin-top:10px;">
                                    <?php foreach ($choices as $ci => $c): ?>
                                        <label style="display:block; margin:8px 0; cursor:pointer;">
                                            <input type="radio" name="answers[<?php echo $i; ?>]" value="<?php echo intval($ci); ?>">
                                            <?php echo htmlspecialchars($c); ?>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <div style="display:flex; gap:10px; margin-top:12px;">
                            <button class="btn-primary" type="submit">Submit</button>
                            <a class="btn" href="reading.php?reset=1">Cancel & Reset</a>
                        </div>
                        <p style="margin-top:10px; opacity:.8;">
                            Time expires -> system auto-submits the current selections.
                        </p>
                    </form>
                </section>

                <script>
                    (function () {
                        // Server-calculated remaining seconds
                        let remaining = <?php echo intval($remaining); ?>;
                        const timerEl = document.getElementById("timer");
                        const form = document.getElementById("readingForm");

                        function fmt(s) {
                            const mm = String(Math.floor(s / 60)).padStart(2, "0");
                            const ss = String(s % 60).padStart(2, "0");
                            return mm + ":" + ss;
                        }

                        // Update immediately
                        timerEl.textContent = fmt(remaining);

                        const tick = setInterval(function () {
                            remaining--;
                            if (remaining < 0) remaining = 0;
                            timerEl.textContent = fmt(remaining);

                            if (remaining === 0) {
                                clearInterval(tick);

                                // Auto-submit
                                if (form) {
                                    form.submit();
                                }
                            }
                        }, 1000);
                    })();
                </script>

            <?php endif; ?>
        <?php endif; ?>

    <?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . "/../includes/footer.php"; ?>