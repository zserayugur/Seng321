<?php
$page = 'writing';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 900px; margin: 0 auto;">
  <section class="card">
    <h1>AI Writing Assessment</h1>
    <p>Get an essay topic, write your response (approx. 250 words), and receive instant AI feedback on grammar,
      vocabulary, and coherence.</p>

    <!-- Step 1: Topic -->
    <div id="topicArea" style="margin-top: 30px; text-align: center;">
      <button id="btnGetTopic" class="btn-primary" style="padding: 12px 24px;">Generate Essay Topic</button>
      <div id="topicBox"
        style="display:none; margin-top: 20px; padding: 25px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px;">
        <h3 style="color: #3730a3; margin-bottom: 5px;">Your Topic</h3>
        <p id="topicText" style="font-size: 1.2rem; font-weight: 500; color: #1e3a8a; line-height: 1.5;">...</p>
      </div>
    </div>

    <!-- Step 2: Writing Area -->
    <div id="writingArea" style="display:none; margin-top: 30px;">
      <div style="display:flex; justify-content:space-between; margin-bottom:10px; color:#666;">
        <span>Min: 150 words</span>
        <span>Word Count: <strong id="wordCount">0</strong></span>
      </div>

      <textarea id="essayInput" rows="15"
        style="width:100%; padding:15px; border-radius:8px; border:1px solid #ccc; font-family:sans-serif; line-height:1.6; font-size:1rem;"
        placeholder="Start writing your essay here..."></textarea>

      <div style="text-align: right; margin-top:15px;">
        <button id="btnSubmit" class="btn-primary">Submit for AI Evaluation</button>
      </div>
    </div>
  </section>

  <!-- Step 3: Result -->
  <section id="resultArea" class="card" style="display:none; margin-top: 20px;">
    <h2>AI Evaluation Result</h2>
    <div class="dashboard-grid">
      <div style="text-align: center; padding: 20px; background: #f0fdf4; border-radius: 10px;">
        <h3>CEFR Level</h3>
        <div id="resLevel" style="font-size: 3rem; font-weight: 800; color: #166534;">B2</div>
      </div>
      <div style="text-align: center; padding: 20px; background: #fffbeb; border-radius: 10px;">
        <h3>Score</h3>
        <div id="resScore" style="font-size: 3rem; font-weight: 800; color: #92400e;">78/100</div>
      </div>
    </div>

    <div style="margin-top: 20px; padding: 25px; background: #fafafa; border-radius: 8px; border: 1px solid #eee;">
      <h4 style="margin-bottom: 10px; color: #4b5563;">Detailed Feedback</h4>
      <div id="resFeedback" style="white-space: pre-wrap; margin-bottom: 25px; line-height: 1.6; color: #1f2937;"></div>

      <h4 style="color: #dc2626; margin-bottom: 10px;">Corrections & Suggestions</h4>
      <div id="resCorrections"
        style="white-space: pre-wrap; padding: 15px; background: #fee2e2; border-radius: 6px; color: #991b1b;"></div>
    </div>

    <div style="margin-top: 25px; text-align: center;">
      <button onclick="location.reload()" class="btn">Try Another Topic</button>
    </div>
  </section>
</div>

<script>
  // --- Variables ---
  let currentTopic = "";

  const btnGetTopic = document.getElementById('btnGetTopic');
  const topicBox = document.getElementById('topicBox');
  const topicText = document.getElementById('topicText');
  const writingArea = document.getElementById('writingArea');
  const essayInput = document.getElementById('essayInput');
  const wordCountSpan = document.getElementById('wordCount');
  const btnSubmit = document.getElementById('btnSubmit');
  const resultArea = document.getElementById('resultArea');

  // --- 1. Get Topic ---
  btnGetTopic.addEventListener('click', async () => {
    btnGetTopic.disabled = true;
    btnGetTopic.textContent = "Generating Topic...";

    try {
      const res = await fetch('api/get_writing_topic.php');
      const data = await res.json();

      if (data.error) throw new Error(data.error);

      currentTopic = data.topic;
      topicText.textContent = currentTopic;
      topicBox.style.display = 'block';
      btnGetTopic.style.display = 'none';
      writingArea.style.display = 'block';

    } catch (e) {
      alert("Error fetching topic: " + e.message);
      btnGetTopic.disabled = false;
      btnGetTopic.textContent = "Generate Essay Topic";
    }
  });

  // --- 2. Word Count ---
  essayInput.addEventListener('input', () => {
    const text = essayInput.value.trim();
    const count = text ? text.split(/\s+/).length : 0;
    wordCountSpan.textContent = count;
  });

  // --- 3. Submit & Evaluate ---
  btnSubmit.addEventListener('click', async () => {
    const text = essayInput.value.trim();
    if (text.length < 50) {
      alert("Your essay is too short. Please write at least a few sentences.");
      return;
    }

    btnSubmit.disabled = true;
    btnSubmit.textContent = "Analyzing Essay...";

    try {
      const res = await fetch('api/evaluate_writing.php', {
        method: 'POST',
        body: JSON.stringify({
          topic: currentTopic,
          text: text
        }),
        headers: { 'Content-Type': 'application/json' }
      });

      const data = await res.json();
      if (data.error) throw new Error(data.error);

      // Display Results
      document.getElementById('resLevel').textContent = data.cefr || 'N/A';
      document.getElementById('resScore').textContent = (data.score || 0) + '/100';
      document.getElementById('resFeedback').textContent = data.feedback || 'No specific feedback provided.';
      document.getElementById('resCorrections').textContent = data.correction_points || 'No specific corrections found.';

      resultArea.style.display = 'block';
      writingArea.style.display = 'none'; // Hide writing area to focus on result
      resultArea.scrollIntoView({ behavior: 'smooth' });

    } catch (e) {
      alert("Evaluation Failed: " + e.message);
      btnSubmit.disabled = false;
      btnSubmit.textContent = "Submit for AI Evaluation";
    }
  });
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>