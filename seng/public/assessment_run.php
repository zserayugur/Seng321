<?php
$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) { echo "attempt_id missing"; exit; }
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <title>Assessment Run</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="container">
  <div class="topbar">
    <div>
      <h2 id="title">Assessment</h2>
      <div id="meta" class="muted"></div>
    </div>
    <div class="timer">
      <div class="muted">Remaining</div>
      <div id="timerValue" class="timerValue">--:--</div>
    </div>
  </div>

  <div id="passageBox" class="card" style="display:none;"></div>
  <div id="questions"></div>

  <div class="actions">
    <button id="pauseBtn" onclick="pauseAttempt()">Pause</button>
    <button id="resumeBtn" onclick="resumeAttempt()" style="display:none;">Resume</button>
    <button class="primary" onclick="submitAttempt('MANUAL')">Submit</button>
    <button onclick="window.location.href='index.php'">Back</button>
  </div>

  <div id="afterSubmit" class="card" style="display:none;"></div>

<script>
const attemptId = <?= (int)$attemptId ?>;

let attempt = null;
let remaining = 0;
let tick = null;

function fmt(sec){
  const m = Math.floor(sec/60);
  const s = sec%60;
  return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
}

function syncButtons(){
  const pauseBtn = document.getElementById('pauseBtn');
  const resumeBtn = document.getElementById('resumeBtn');
  if (!attempt) return;

  if (attempt.status === 'PAUSED'){
    pauseBtn.style.display = 'none';
    resumeBtn.style.display = 'inline-block';
  } else {
    pauseBtn.style.display = 'inline-block';
    resumeBtn.style.display = 'none';
  }
}

function renderChoice(qid, key, text, selected){
  const checked = (selected === key) ? 'checked' : '';
  return `
    <label class="choice">
      <input type="radio" name="q_${qid}" value="${key}" ${checked}
             onchange="saveAnswer(${qid}, '${key}')">
      <span>${key}) ${text}</span>
    </label>
  `;
}

async function loadState(){
  const res = await fetch('../api/state.php?attempt_id=' + attemptId);
  const data = await res.json();
  if (!data.ok){ alert(data.error); return; }

  attempt = data.attempt;
  remaining = attempt.remainingSec;

  document.getElementById('title').textContent =
    attempt.testType + (attempt.readingStage ? (' (Stage ' + attempt.readingStage + ')') : '');

  document.getElementById('meta').textContent =
    'Status: ' + attempt.status + (attempt.expiresAt ? (' | ExpiresAt: ' + attempt.expiresAt) : '');

  // passage
  const pb = document.getElementById('passageBox');
  if (data.passage){
    pb.style.display = 'block';
    pb.innerHTML = `<h3>${data.passage.title}</h3><p>${data.passage.body}</p>`;
  } else {
    pb.style.display = 'none';
  }

  // questions
  const qwrap = document.getElementById('questions');
  qwrap.innerHTML = '';
  for (const q of data.questions){
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML = `
      <div class="qtitle">${q.ord}) ${q.prompt}</div>
      ${renderChoice(q.id,'A',q.choices.A,q.selected)}
      ${renderChoice(q.id,'B',q.choices.B,q.selected)}
      ${renderChoice(q.id,'C',q.choices.C,q.selected)}
      ${renderChoice(q.id,'D',q.choices.D,q.selected)}
      <div class="muted small" id="saved_${q.id}"></div>
    `;
    qwrap.appendChild(card);
  }

  syncButtons();

  // timer
  if (tick) clearInterval(tick);
  document.getElementById('timerValue').textContent = fmt(remaining);

  tick = setInterval(async () => {
    if (!attempt) return;

    if (attempt.status === 'IN_PROGRESS') {
      remaining = Math.max(0, remaining - 1);
      document.getElementById('timerValue').textContent = fmt(remaining);

      if (remaining === 0) {
        // client-side auto-submit (server da garanti ediyor)
        await submitAttempt('TIME_EXPIRED');
      }
    }
  }, 1000);
}

async function saveAnswer(questionId, choice){
  const res = await fetch('../api/save_answer.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({attemptId, questionId, choice})
  });
  const data = await res.json();
  const el = document.getElementById('saved_' + questionId);
  if (data.ok) el.textContent = 'Saved.';
  else el.textContent = 'Save failed: ' + data.error;
}

async function pauseAttempt(){
  const res = await fetch('../api/pause.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({attemptId})
  });
  const data = await res.json();
  if (!data.ok){ alert(data.error); return; }
  await loadState();
}

async function resumeAttempt(){
  const res = await fetch('../api/resume.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({attemptId})
  });
  const data = await res.json();
  if (!data.ok){ alert(data.error); return; }
  await loadState();
}

async function submitAttempt(reason){
  const res = await fetch('../api/submit.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify({attemptId, reason})
  });
  const data = await res.json();
  if (!data.ok){ alert(data.error); return; }

  const box = document.getElementById('afterSubmit');
  box.style.display = 'block';
  box.innerHTML = `
    <h3>Submitted</h3>
    <p>Score: ${data.result.attempt.correct}/${data.result.attempt.total} (${data.result.attempt.scorePct}%)</p>
    <button onclick="window.location.href='review.php?attempt_id=${attemptId}'">Go to Review</button>
  `;

  // Reading stage1 bitince Start Next Test
  if (data.canStartNext && data.readingGroup){
    const btn = document.createElement('button');
    btn.textContent = 'Start Next Test';
    btn.onclick = async () => {
      const r = await fetch('../api/reading_next.php', {
        method:'POST',
        headers:{'Content-Type':'application/json'},
        body: JSON.stringify({readingGroup: data.readingGroup})
      });
      const d = await r.json();
      if (!d.ok){ alert(d.error); return; }
      window.location.href = 'assessment_run.php?attempt_id=' + d.attempt.id;
    };
    box.appendChild(btn);
  }

  await loadState();
}

loadState();
</script>
</body>
</html>
