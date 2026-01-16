<?php
$page = 'writing';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/mock_data.php';
require_once __DIR__ . '/../includes/ai_service.php';
require_once __DIR__ . '/../includes/header.php';

$sessionKey = 'writing_prompt';
$prompt = $_SESSION[$sessionKey] ?? null;

// Generate prompt if not exists
if (!$prompt && isset($_GET['start'])) {
    $profile = getUserProfile();
    $cefr = $profile['current_level'] ?? 'B1';
    
    // Generate writing prompt using AI
    if (AI_MODE === 'live' && !empty(GEMINI_API_KEY)) {
        $promptText = geminiCallJson("
You are an expert English teacher. Create a writing prompt for a CEFR {$cefr} level student.

REQUIREMENTS:
- The prompt should require an essay of 250-450 words.
- It should be appropriate for {$cefr} level.
- Return ONLY the prompt text, no JSON, no markdown, just the prompt question/topic.
- Make it engaging and relevant.

Example format:
'Write an essay discussing the advantages and disadvantages of remote work. Include your personal opinion and support your arguments with examples.'

Now create a similar prompt for {$cefr} level:
");
        
        if (!empty($promptText)) {
            $prompt = trim(cleanJson($promptText));
        }
    }
    
    // Fallback prompt
    if (empty($prompt)) {
        $fallbackPrompts = [
            "Write an essay discussing the advantages and disadvantages of social media. Include your personal opinion and support your arguments with examples. (250-450 words)",
            "Describe a place you would like to visit in the future. Explain why you want to go there and what you would do. (250-450 words)",
            "Some people believe that technology makes our lives easier, while others think it creates more problems. Discuss both views and give your own opinion. (250-450 words)"
        ];
        $prompt = $fallbackPrompts[array_rand($fallbackPrompts)];
    }
    
    $_SESSION[$sessionKey] = $prompt;
    header("Location: writing.php");
    exit;
}
?>

<?php if (!$prompt): ?>
    <section class="card" style="margin-top:16px;">
        <h2>Ready to start?</h2>
        <p>You will have 50 minutes to write an essay of 250-450 words based on an AI-generated prompt.</p>
        <a class="btn-primary" href="writing.php?start=1">Start Writing</a>
    </section>
<?php else: ?>
    <section class="card" style="margin-top:16px;">
        <h2>Writing Prompt</h2>
        <p style="white-space:pre-wrap; line-height:1.6; padding:12px; background:#f5f5f5; border-radius:8px; color:#000;">
            <?php echo htmlspecialchars($prompt); ?>
        </p>
    </section>

    <button id="btnStart" class="btn-primary" style="margin-top:16px;">Begin Writing</button>

    <div id="panel" style="display:none; margin-top:16px;">
        <section class="card">
            <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; margin-bottom:12px;">
                <div><b>Time left:</b> <span id="timeLeft">50:00</span></div>
                <div><b>Words:</b> <span id="wordCount">0</span> / 250-450</div>
            </div>
            <textarea id="essay" rows="14" style="width:100%; padding:12px; font-family:inherit; font-size:14px;" placeholder="Start writing your essay here..."></textarea>
            <div style="margin-top:12px;">
                <button id="btnSubmit" class="btn-primary">Submit Essay</button>
                <p id="status" style="margin-top:8px;"></p>
            </div>
        </section>

        <section class="card" style="margin-top:16px; display:none;" id="resultSection">
            <h3>Evaluation Results</h3>
            <div id="evaluationDisplay"></div>
        </section>
    </div>
<?php endif; ?>

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

    if (evData.ok && evData.evaluation) {
      const eval = evData.evaluation;
      const display = document.getElementById('evaluationDisplay');
      display.innerHTML = `
        <div style="padding:12px; background:#f0f8ff; border-radius:8px; margin-bottom:12px; color:#000;">
          <h4 style="color:#000;">CEFR Level: <strong>${eval.cefr || 'N/A'}</strong></h4>
          <p style="color:#000;"><strong>IELTS Estimate:</strong> ${eval.ielts_estimate || 'N/A'}</p>
          <p style="color:#000;"><strong>TOEFL Estimate:</strong> ${eval.toefl_estimate || 'N/A'}</p>
          <p style="color:#000;"><strong>Word Count:</strong> ${eval.word_count || 0}</p>
        </div>
        <div style="margin-top:12px; color:#000;">
          <h4 style="color:#000;">Diagnostic:</h4>
          <p style="color:#000;">${eval.diagnostic || 'Evaluation completed.'}</p>
        </div>
        ${eval.strengths && eval.strengths.length > 0 ? `
        <div style="margin-top:12px; color:#000;">
          <h4 style="color:#000;">Strengths:</h4>
          <ul style="color:#000;">${eval.strengths.map(s => `<li style="color:#000;">${s}</li>`).join('')}</ul>
        </div>
        ` : ''}
        ${eval.improvements && eval.improvements.length > 0 ? `
        <div style="margin-top:12px; color:#000;">
          <h4 style="color:#000;">Areas for Improvement:</h4>
          <ul style="color:#000;">${eval.improvements.map(i => `<li style="color:#000;">${i}</li>`).join('')}</ul>
        </div>
        ` : ''}
        ${eval.next_steps && eval.next_steps.length > 0 ? `
        <div style="margin-top:12px; color:#000;">
          <h4 style="color:#000;">Next Steps:</h4>
          <ul style="color:#000;">${eval.next_steps.map(n => `<li style="color:#000;">${n}</li>`).join('')}</ul>
        </div>
        ` : ''}
      `;
      document.getElementById('resultSection').style.display = 'block';
      document.getElementById('status').textContent = "Evaluation completed and saved.";
    } else {
      document.getElementById('status').textContent = "Error: " + (evData.error || "Evaluation failed");
    }
  } catch (e) {
    document.getElementById('status').textContent = "Error: " + (e?.message || e);
    btn.disabled = false;
  }
}

document.getElementById('btnStart')?.addEventListener('click', async () => {
  try {
    await startAttempt();
    document.getElementById('panel').style.display = 'block';
    document.getElementById('btnStart').style.display = 'none';
    startTimer();
  } catch (e) {
    alert("Start failed: " + (e?.message || e));
  }
});

const essayEl = document.getElementById('essay');
if (essayEl) {
  essayEl.addEventListener('input', () => {
    const wc = countWords(essayEl.value);
    document.getElementById('wordCount').textContent = wc;
    const wcEl = document.getElementById('wordCount');
    if (wc < 250) {
      wcEl.style.color = '#d32f2f';
    } else if (wc > 450) {
      wcEl.style.color = '#f57c00';
    } else {
      wcEl.style.color = '#388e3c';
    }
  });
}

document.getElementById('btnSubmit')?.addEventListener('click', () => submitEssay(false));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>