<?php
$page = 'writing';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Writing Essay (250–450 words, 50 min)</h2>

<button id="btnStart">Start Writing</button>

<div id="panel" style="display:none;">
  <p><b>Time left:</b> <span id="timeLeft">50:00</span> | <b>Words:</b> <span id="wordCount">0</span></p>
  <textarea id="essay" rows="14" style="width:100%;"></textarea>

  <button id="btnSubmit">Submit</button>
  <p id="status"></p>

  <h3 style="margin-top:16px;">Evaluation (Saved to DB)</h3>
  <pre id="mockBox" style="white-space:pre-wrap;"></pre>
</div>

<script>
let attemptId = null, secondsLeft = 50*60, timer = null;

function formatTime(s){
  const m = Math.floor(s/60), r = s % 60;
  return String(m).padStart(2,'0') + ":" + String(r).padStart(2,'0');
}
function countWords(t){
  return (t.trim().match(/\S+/g) || []).length;
}

async function startAttempt(){
  const fd = new FormData();
  fd.append('type','writing');

  const res = await fetch('api/start_attempt.php', { method:'POST', body:fd });
  const data = await res.json();

  attemptId = data.attempt_id ?? null;
  if (!attemptId) throw new Error("attempt_id missing");
}

function startTimer(){
  secondsLeft = 50*60;
  document.getElementById('timeLeft').textContent = formatTime(secondsLeft);

  timer = setInterval(async () => {
    secondsLeft--;
    document.getElementById('timeLeft').textContent = formatTime(secondsLeft);

    if (secondsLeft <= 0){
      clearInterval(timer);
      await submitEssay(true);
    }
  }, 1000);
}

async function saveEssay(){
  const fd = new FormData();
  fd.append('attempt_id', attemptId);
  fd.append('question_index', 1);
  fd.append('question_text', 'WRITING_ESSAY');
  fd.append('answer_text', document.getElementById('essay').value);
  await fetch('api/save_progress.php', { method:'POST', body:fd });
}

async function submitEssay(isAuto=false){
  const btn = document.getElementById('btnSubmit');
  btn.disabled = true;

  document.getElementById('status').textContent = isAuto ? "Auto-submitting..." : "Submitting...";
  if (timer) clearInterval(timer);

  try {
    await saveEssay();

    const fd2 = new FormData();
    fd2.append('attempt_id', attemptId);
    fd2.append('assignment_id', "<?= (int)($_GET['assignment_id'] ?? 0) ?>");
    await fetch('api/submit_attempt.php', { method:'POST', body:fd2 });

    // ✅ Evaluate + send essay text
    const fd3 = new FormData();
    fd3.append('attempt_id', attemptId);
    fd3.append('skill', 'writing');
    fd3.append('text', document.getElementById('essay').value);

    const ev = await fetch('api/evaluate_attempt.php', { method:'POST', body:fd3 });
    const evData = await ev.json();

    document.getElementById('mockBox').textContent = JSON.stringify(evData, null, 2);
    document.getElementById('status').textContent = "Done.";
  } catch (e) {
    document.getElementById('status').textContent = "Error: " + (e?.message || e);
    btn.disabled = false;
  }
}

document.getElementById('btnStart').addEventListener('click', async () => {
  try {
    await startAttempt();
    document.getElementById('panel').style.display = 'block';
    startTimer();
  } catch (e) {
    alert("Start failed: " + (e?.message || e));
  }
});

document.getElementById('essay').addEventListener('input', () => {
  document.getElementById('wordCount').textContent = countWords(document.getElementById('essay').value);
});

document.getElementById('btnSubmit').addEventListener('click', () => submitEssay(false));
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>