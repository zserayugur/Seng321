<!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8" />
  <title>Assessments</title>
  <link rel="stylesheet" href="assets/style.css">
</head>
<body class="container">
  <h1>Assessment Engine</h1>

  <div class="card">
    <p class="muted">
      Grammar (20/30dk), Vocabulary (20/25dk), Reading (2 stage: 10/15dk + 10/15dk).
      Süre dolunca sistem auto-submit yapar.
    </p>
    <button onclick="startTest('GRAMMAR')">Start Grammar</button>
    <button onclick="startTest('VOCAB')">Start Vocabulary</button>
    <button onclick="startTest('READING')">Start Reading</button>
  </div>

  <script>
    async function startTest(type) {
      const res = await fetch('../api/start.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({type})
      });
      const data = await res.json();
      if (!data.ok) { alert(data.error); return; }
      window.location.href = 'assessment_run.php?attempt_id=' + data.attempt.id;
    }
  </script>
</body>
</html>
