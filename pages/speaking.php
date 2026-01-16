<?php
$page = 'speaking';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-grid" style="grid-template-columns: 1fr; max-width: 800px; margin: 0 auto;">
  <section class="card">
    <h1>AI Speaking Practice</h1>
    <p>Get a topic, record your response, and receive instant AI feedback on your level, grammar, and pronunciation.</p>

    <!-- Step 1: Topic -->
    <div id="topicArea" style="margin-top: 30px; text-align: center;">
      <button id="btnGetTopic" class="btn-primary" style="padding: 12px 24px;">Generate Speaking Topic</button>
      <div id="topicBox"
        style="display:none; margin-top: 20px; padding: 20px; background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px;">
        <h3 style="color: #3730a3; margin-bottom: 5px;">Your Topic</h3>
        <p id="topicText" style="font-size: 1.2rem; font-weight: 500; color: #1e3a8a; line-height: 1.5;">...</p>
      </div>
    </div>

    <!-- Step 2: Recorder -->
    <div id="recorderArea" style="display:none; margin-top: 30px; text-align: center;">
      <div style="margin-bottom: 20px;">
        <span id="timerBadge" class="badge badge-b2" style="font-size: 1rem; padding: 8px 16px;">00:00</span>
      </div>

      <button id="btnRecord" class="btn-primary"
        style="background: #ef4444; border-color: #ef4444; width: 80px; height: 80px; border-radius: 50%; font-size: 2rem;">🎙️</button>
      <p id="recStatus" style="margin-top: 10px; color: #666;">Click mic to start recording</p>

      <div id="transcriptPreview"
        style="margin-top: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; text-align: left; max-height: 150px; overflow-y: auto; font-style: italic; color: #666;">
        (Live transcript will appear here...)
      </div>

      <audio id="audioPlayback" controls style="display:none; width: 100%; margin-top: 20px;"></audio>

      <button id="btnSubmit" class="btn-primary" style="margin-top: 20px; display: none;">Submit for AI
        Evaluation</button>
    </div>
  </section>

  <!-- Step 3: Result -->
  <section id="resultArea" class="card" style="display:none;">
    <h2>AI Evaluation Result</h2>
    <div class="dashboard-grid">
      <div style="text-align: center; padding: 20px; background: #f0fdf4; border-radius: 10px;">
        <h3>CEFR Level</h3>
        <div id="resLevel" style="font-size: 3rem; font-weight: 800; color: #166534;">B2</div>
      </div>
      <div style="text-align: center; padding: 20px; background: #fffbeb; border-radius: 10px;">
        <h3>Score</h3>
        <div id="resScore" style="font-size: 3rem; font-weight: 800; color: #92400e;">85/100</div>
      </div>
    </div>

    <div style="margin-top: 20px; padding: 20px; background: #fafafa; border-radius: 8px;">
      <h4>Analysis & Feedback</h4>
      <p id="resFeedback" style="white-space: pre-wrap; margin-bottom: 20px;"></p>

      <h4 style="color: #dc2626;">Corrections & Improvements</h4>
      <p id="resCorrections" style="white-space: pre-wrap;"></p>
    </div>

    <button onclick="location.reload()" class="btn" style="margin-top: 20px;">Try Another Topic</button>
  </section>
</div>

<script>
  // --- Variables ---
  let mediaRecorder;
  let audioChunks = [];
  let recognition;
  let isRecording = false;
  let timerInterval;
  let seconds = 0;
  let finalTranscript = "";
  let currentTopic = "";

  const btnGetTopic = document.getElementById('btnGetTopic');
  const topicArea = document.getElementById('topicArea');
  const topicBox = document.getElementById('topicBox');
  const topicText = document.getElementById('topicText');
  const recorderArea = document.getElementById('recorderArea');
  const btnRecord = document.getElementById('btnRecord');
  const recStatus = document.getElementById('recStatus');
  const timerBadge = document.getElementById('timerBadge');
  const transcriptPreview = document.getElementById('transcriptPreview');
  const audioPlayback = document.getElementById('audioPlayback');
  const btnSubmit = document.getElementById('btnSubmit');
  const resultArea = document.getElementById('resultArea');

  // --- 1. Get Topic ---
  btnGetTopic.addEventListener('click', async () => {
    btnGetTopic.disabled = true;
    btnGetTopic.textContent = "Loading Topic...";

    try {
      const res = await fetch('api/get_speaking_topic.php');
      const data = await res.json();

      if (data.error) throw new Error(data.error);

      currentTopic = data.topic;
      topicText.textContent = currentTopic;
      topicBox.style.display = 'block';
      btnGetTopic.style.display = 'none';
      recorderArea.style.display = 'block';

    } catch (e) {
      alert("Error fetching topic: " + e.message);
      btnGetTopic.disabled = false;
    }
  });

  // --- 2. Setup Recording & STT ---
  if ('webkitSpeechRecognition' in window) {
    recognition = new webkitSpeechRecognition();
    recognition.continuous = true;
    recognition.interimResults = true;
    recognition.lang = 'en-US';

    recognition.onresult = (event) => {
      let interimTranscript = '';
      for (let i = event.resultIndex; i < event.results.length; ++i) {
        if (event.results[i].isFinal) {
          finalTranscript += event.results[i][0].transcript + '. ';
        } else {
          interimTranscript += event.results[i][0].transcript;
        }
      }
      transcriptPreview.textContent = finalTranscript + interimTranscript;
    };

    recognition.onerror = (e) => {
      console.warn("Speech Recognition Error", e);
    };
  } else {
    alert("Warning: Your browser does not support Web Speech API. AI transcription might not work. Please use Chrome/Edge.");
  }

  // --- 3. Toggle Record ---
  btnRecord.addEventListener('click', async () => {
    if (!isRecording) {
      // Start
      try {
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        mediaRecorder = new MediaRecorder(stream);
        audioChunks = [];

        mediaRecorder.ondataavailable = event => {
          audioChunks.push(event.data);
        };

        mediaRecorder.onstop = () => {
          const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
          const audioUrl = URL.createObjectURL(audioBlob);
          audioPlayback.src = audioUrl;
          audioPlayback.style.display = 'block';
        };

        mediaRecorder.start();
        if (recognition) recognition.start();

        isRecording = true;
        btnRecord.innerHTML = "⏹"; // Stop icon
        recStatus.textContent = "Recording... Click to stop.";
        btnSubmit.style.display = 'none';

        // Timer
        seconds = 0;
        timerInterval = setInterval(() => {
          seconds++;
          const mins = Math.floor(seconds / 60).toString().padStart(2, '0');
          const secs = (seconds % 60).toString().padStart(2, '0');
          timerBadge.textContent = `${mins}:${secs}`;
        }, 1000);

      } catch (e) {
        alert("Microphone access denied or error: " + e.message);
      }
    } else {
      // Stop
      mediaRecorder.stop();
      if (recognition) recognition.stop();

      isRecording = false;
      clearInterval(timerInterval);
      btnRecord.innerHTML = "🎙️";
      recStatus.textContent = "Recording finished.";

      // Show submit
      setTimeout(() => {
        btnSubmit.style.display = 'inline-block';
      }, 500);
    }
  });

  // --- 4. Submit & Evaluate ---
  btnSubmit.addEventListener('click', async () => {
    if (!finalTranscript && seconds < 2) {
      alert("Please record something substantial first.");
      return;
    }

    // Fallback if STT failed but audio recorded (we can't eval easily but let's handle grace)
    if (!finalTranscript) finalTranscript = "(Audio recorded but speech-to-text unavailable. Evaluating based on mocked metadata or minimal context)";

    btnSubmit.disabled = true;
    btnSubmit.textContent = "Analyzing Speech...";

    try {
      const res = await fetch('api/evaluate_speaking.php', {
        method: 'POST',
        body: JSON.stringify({
          topic: currentTopic,
          transcript: finalTranscript
        }),
        headers: { 'Content-Type': 'application/json' }
      });

      const data = await res.json();
      if (data.error) throw new Error(data.error);

      // Display Results
      document.getElementById('resLevel').textContent = data.cefr || 'N/A';
      document.getElementById('resScore').textContent = (data.score || 0) + '/100';
      document.getElementById('resFeedback').textContent = data.feedback || 'No feedback.';
      document.getElementById('resCorrections').textContent = data.corrections || 'No specific corrections.';

      resultArea.style.display = 'block';
      resultArea.scrollIntoView({ behavior: 'smooth' });

    } catch (e) {
      alert("Evaluation Failed: " + e.message);
      btnSubmit.disabled = false;
      btnSubmit.textContent = "Submit for AI Evaluation";
    }
  });

</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>