<?php
$page = 'listening';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/csrf.php';
$assignment_id = (int) ($_GET['assignment_id'] ?? 0);
$csrf = csrf_token();
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 900px; margin: 0 auto;">
  <section class="card">
    <h1>AI Listening Test</h1>
    <p>Listen to a generated audio script (approx. 3-4 minutes) and answer open-ended comprehension questions. Receive
      instant AI grading and feedback.</p>

    <!-- Step 1: Generate -->
    <div id="controls" style="margin-top: 30px; text-align: center;">
      <button id="btnGenerate" class="btn-primary" style="padding: 12px 24px;">Generate Long Listening Test</button>
      <p id="loading" style="display:none; color: #666; margin-top: 15px;">Generating extended listening content (this
        may take 10-20 seconds)... Please wait...</p>
    </div>

    <!-- Step 2: Test Area -->
    <div id="testArea" style="display:none; margin-top: 30px;">

      <!-- Audio Player -->
      <div class="audio-player"
        style="background: #f3f4f6; padding: 20px; border-radius: 12px; margin-bottom: 25px; display: flex; flex-direction: column; gap: 15px; border: 1px solid #e5e7eb;">
        <div style="display: flex; align-items: center; gap: 15px;">
          <button id="btnPlay" class="btn-primary"
            style="background: #2563eb; width: 50px; height: 50px; border-radius: 50%; padding: 0; font-size: 1.2rem; display: flex; align-items: center; justify-content: center;">▶</button>
          <span id="audioStatus" style="font-weight: 500; color: #374151;">Click play to listen</span>
        </div>
        <!-- Progress -->
        <div style="width: 100%; height: 6px; background: #d1d5db; border-radius: 3px; overflow: hidden;">
          <div id="progressBar" style="width: 0%; height: 100%; background: #2563eb; transition: width 0.2s linear;">
          </div>
        </div>
      </div>

      <form id="quizForm" onsubmit="handleQuizSubmit(event)">
        <div id="questionsContainer"></div>

        <div style="text-align: right; margin-top: 25px;">
          <button type="submit" id="btnSubmit" class="btn-primary" style="padding: 12px 30px;">Submit Answers</button>
        </div>
      </form>
    </div>
  </section>

  <!-- Step 3: Result -->
  <section id="resultArea" class="card" style="display:none; margin-top: 20px;">
    <h2>AI Evaluation Result</h2>
    <!-- Check JS logic for how this is populated, it handles HTML injection cleanly -->
    <div id="feedbackText" style="line-height: 1.6;"></div>

    <div style="margin-top: 25px; text-align: center;">
      <button onclick="location.reload()" class="btn">Take Another Test</button>
    </div>
  </section>
</div>

<script>
  let currentScript = "";
  let currentQuestions = [];
  let speechUtterance = null;

  const btnGenerate = document.getElementById('btnGenerate');
  const loading = document.getElementById('loading');
  const controls = document.getElementById('controls');
  const testArea = document.getElementById('testArea');
  const questionsContainer = document.getElementById('questionsContainer');
  const btnPlay = document.getElementById('btnPlay');
  const audioStatus = document.getElementById('audioStatus');
  const progressBar = document.getElementById('progressBar');
  // Re-map resultArea to act as feedback container
  const resultArea = document.getElementById('resultArea');
  const feedbackText = document.getElementById('feedbackText');

  // Generate Test
  btnGenerate.addEventListener('click', async () => {
    loading.style.display = 'block';
    controls.querySelector('button').style.display = 'none'; // Hide button only
    testArea.style.display = 'none';
    resultArea.style.display = 'none';

    try {
      const res = await fetch(`api/get_listening.php`);
      const textResponse = await res.text();
      let data;
      try {
        data = JSON.parse(textResponse);
      } catch (err) {
        throw new Error("Server returned invalid JSON: " + textResponse.substring(0, 50));
      }

      if (data.error) {
        alert(data.error);
        controls.querySelector('button').style.display = 'inline-block';
        return;
      }

      currentScript = data.script;
      currentQuestions = data.questions;

      renderQuestions(currentQuestions);

      loading.style.display = 'none';
      controls.style.display = 'none'; // Hide entire controls area
      testArea.style.display = 'block';

    } catch (e) {
      console.error(e);
      alert("Failed to load test: " + e.message);
      loading.style.display = 'none';
      controls.querySelector('button').style.display = 'inline-block';
    }
  });

  // Play Audio (TTS)
  btnPlay.addEventListener('click', (e) => {
    e.preventDefault(); // prevent form submit if inside form (it is not, but good practice)
    if (!currentScript) return;

    if (window.speechSynthesis.speaking) {
      window.speechSynthesis.cancel();
      btnPlay.innerHTML = "▶";
      audioStatus.textContent = "Stopped.";
      progressBar.style.transition = 'none';
      progressBar.style.width = '0%';
      return;
    }

    const utterance = new SpeechSynthesisUtterance(currentScript);
    utterance.lang = 'en-US';
    utterance.rate = 0.85;

    utterance.onstart = () => {
      btnPlay.innerHTML = "⏹"; // Stop icon
      audioStatus.textContent = "Playing...";
      progressBar.style.width = '0%';
      // rough duration calc
      const estimatedDuration = (currentScript.length / 18);
      progressBar.style.transition = `width ${estimatedDuration}s linear`;
      setTimeout(() => progressBar.style.width = '100%', 100);
    };

    utterance.onend = () => {
      btnPlay.innerHTML = "▶";
      audioStatus.textContent = "Finished. You can replay if needed.";
      progressBar.style.transition = 'none';
      progressBar.style.width = '100%';
    };

    window.speechSynthesis.speak(utterance);
  });

  function renderQuestions(qs) {
    questionsContainer.innerHTML = qs.map((q, i) => `
        <div class="question-block" style="margin-bottom: 25px; padding-bottom: 20px; border-bottom: 1px solid #eee;">
            <p style="font-weight: 600; margin-bottom: 10px; color: #1f2937;">Q${i + 1}: ${q}</p>
            <textarea name="answer_${i}" rows="2" style="width: 100%; padding: 12px; border: 1px solid #d1d5db; border-radius: 6px; font-family: sans-serif;" placeholder="Type your answer here..." required></textarea>
        </div>
    `).join('');
  }

  // Submit Answers
  async function handleQuizSubmit(e) {
    e.preventDefault();

    const btn = document.getElementById('btnSubmit');
    const form = document.getElementById('quizForm');

    btn.disabled = true;
    btn.textContent = "Evaluating... (Please wait)";

    const formData = new FormData(form);
    const answers = [];
    currentQuestions.forEach((_, i) => {
      answers.push(formData.get(`answer_${i}`));
    });

    try {
      const res = await fetch('api/evaluate_listening.php', {
        method: 'POST',
        body: JSON.stringify({
          script: currentScript,
          questions: currentQuestions,
          answers: answers
        }),
        headers: { 'Content-Type': 'application/json' }
      });

      const textRes = await res.text();
      let data;
      try {
        data = JSON.parse(textRes);
      } catch (jsonErr) {
        console.error("Invalid JSON:", textRes);
        throw new Error("Server Error: " + textRes.substring(0, 100));
      }

      if (data.feedback) {
        let html = "";
        const fb = data.feedback;

        if (typeof fb === 'string') {
          html = `<p>${fb}</p>`;
        } else {
          // Check for structured report
          if (fb.report_title) html += `<h3 style="color:#111827; margin-bottom:15px;">${fb.report_title}</h3>`;

          if (fb.student_performance) {
            const p = fb.student_performance;
            html += `
            <div style="background:#f0fdf4; padding:20px; margin-bottom:20px; border-radius:8px; border:1px solid #bbf7d0;">
                <div style="font-size:2rem; font-weight:800; color:#166534; margin-bottom:5px;">${p.score}/${p.max_score}</div>
                <div style="color:#15803d; font-style:italic;">${p.overall_feedback}</div>
            </div>`;
          }

          if (fb.question_analysis && Array.isArray(fb.question_analysis)) {
            html += "<h4 style='margin-bottom:15px; border-bottom:2px solid #e5e7eb; padding-bottom:10px;'>Detailed Analysis</h4>";
            fb.question_analysis.forEach(q => {
              html += `
              <div style="margin-bottom:20px; padding:15px; background:#f9fafb; border-radius:8px;">
                  <strong style="display:block; margin-bottom:5px; color:#111827;">Q${q.question_number}: ${q.question}</strong>
                  <div style="margin-bottom:5px; color:#4b5563;">Your Answer: <span style="font-style:italic;">${q.student_answer}</span></div>
                  <div style="color:#059669; font-weight:500;">Feedback: ${q.feedback} <span style="font-size:0.9em; opacity:0.8;">(${q.score_awarded} pts)</span></div>
              </div>`;
            });
          }

          // Fallback
          if (html === "") html = "<pre>" + JSON.stringify(fb, null, 2) + "</pre>";
        }

        feedbackText.innerHTML = html;

        testArea.style.display = 'none';
        resultArea.style.display = 'block';
        window.speechSynthesis.cancel(); // Stop audio

        resultArea.scrollIntoView({ behavior: 'smooth' });

      } else if (data.error) {
        alert("Error: " + data.error);
      } else {
        alert("Unknown response format.");
      }

    } catch (e) {
      console.error(e);
      alert("Evaluation failed: " + e.message);
    } finally {
      btn.disabled = false;
      btn.textContent = "Submit Answers";
    }
  }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>