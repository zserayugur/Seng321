<?php
$attemptId = (int)($_GET['attempt_id'] ?? 0);
if ($attemptId <= 0) { echo "attempt_id missing"; exit; }
?>
<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <title>Review</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="container">
  <h1>Review</h1>
  <div id="summary" class="card"></div>
  <div id="items"></div>

<script>
const attemptId = <?= (int)$attemptId ?>;

async function load(){
  const res = await fetch('../api/result.php?attempt_id=' + attemptId);
  const data = await res.json();
  if (!data.ok){ alert(data.error); return; }

  const a = data.result.attempt;
  document.getElementById('summary').innerHTML = `
    <h3>${a.testType}${a.readingStage ? (' (Stage '+a.readingStage+')') : ''}</h3>
    <p>Score: ${a.correct}/${a.total} (${a.scorePct}%)</p>
    <p class="muted">SubmittedAt: ${a.submittedAt} | Reason: ${a.submitReason}</p>
    <button onclick="window.location.href='index.php'">Back</button>
  `;

  const wrap = document.getElementById('items');
  wrap.innerHTML = '';
  for (const it of data.result.items){
    const card = document.createElement('div');
    card.className = 'card';
    card.innerHTML = `
      <div class="qtitle">${it.ord}) ${it.prompt}</div>
      <div class="${it.isCorrect ? 'good' : 'bad'}">
        Your answer: ${it.userAnswer ?? '(blank)'} | Correct: ${it.correctAnswer}
      </div>
      ${it.explanation ? `<div class="muted small">Explanation: ${it.explanation}</div>` : ''}
    `;
    wrap.appendChild(card);
  }
}
load();
</script>
</body>
</html>
