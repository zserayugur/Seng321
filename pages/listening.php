<?php
$page = 'listening';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/csrf.php';   // ✅ EKLE
$assignment_id = (int)($_GET['assignment_id'] ?? 0); // ✅ EKLE
$csrf = csrf_token(); // ✅ EKLE
require_once __DIR__ . '/../includes/header.php';
?>

<h2>Listening Test (Part 1 & 2)</h2>

<button id="btnStart1">Start Listening Test 1</button>
<button id="btnStart2" disabled>Start Listening Test 2</button>

<div id="preview" style="display:none;">
  <h3>Preview: <span id="previewTimer">10</span>s</h3>
</div>

<div id="exam" style="display:none;">
  <h3>Time left: <span id="timeLeft">10:00</span></h3>
  <div id="q"></div>
  <button id="btnSubmit">Submit</button>
  <p id="status"></p>
  <pre id="mockBox"></pre>
</div>

<script>
let attemptId=null, part=1;
let previewLeft=10, secondsLeft=10*60;
let previewInterval=null, timerInterval=null;

const questions = (p)=>Array.from({length:10}).map((_,i)=>({idx:i+1,text:`(Part ${p}) Q${i+1}: Write your answer...`}));

function formatTime(s){
  const m=Math.floor(s/60), r=s%60;
  return String(m).padStart(2,'0')+":"+String(r).padStart(2,'0');
}

function renderQs(){
  const qs=questions(part);
  document.getElementById('q').innerHTML = qs.map(q=>`
    <div style="margin:10px 0; padding:10px; border:1px solid #ddd;">
      <p><b>${q.idx}.</b> ${q.text}</p>
      <input data-q="${q.idx}" style="width:100%;" placeholder="Answer..." />
    </div>
  `).join('');
}

async function startAttempt(p){
  part=p;
  const fd=new FormData();
  fd.append('type','listening');
  fd.append('part',String(part));
  const res=await fetch('api/start_attempt.php',{method:'POST',body:fd});
  const data=await res.json();
  attemptId=data.attempt_id;
}

function startPreview(){
  document.getElementById('preview').style.display='block';
  document.getElementById('exam').style.display='none';
  previewLeft=10;
  document.getElementById('previewTimer').textContent=previewLeft;

  previewInterval=setInterval(()=>{
    previewLeft--;
    document.getElementById('previewTimer').textContent=previewLeft;
    if(previewLeft<=0){
      clearInterval(previewInterval);
      beginExam();
    }
  },1000);
}

function beginExam(){
  document.getElementById('preview').style.display='none';
  document.getElementById('exam').style.display='block';
  renderQs();

  secondsLeft=10*60;
  document.getElementById('timeLeft').textContent=formatTime(secondsLeft);

  timerInterval=setInterval(()=>{
    secondsLeft--;
    document.getElementById('timeLeft').textContent=formatTime(secondsLeft);
    if(secondsLeft<=0){
      clearInterval(timerInterval);
      submitListening(true);
    }
  },1000);
}

async function saveAll(){
  const inputs=[...document.querySelectorAll('#q input')];
  for(const inp of inputs){
    const idx=parseInt(inp.getAttribute('data-q'),10);
    const fd=new FormData();
    fd.append('attempt_id',attemptId);
    fd.append('question_index',idx);
    fd.append('question_text',`LISTENING_PART_${part}_Q${idx}`);
    fd.append('answer_text',inp.value);
    await fetch('api/save_progress.php',{method:'POST',body:fd});
  }
}

async function submitListening(isAuto=false){
  document.getElementById('btnSubmit').disabled=true;
  document.getElementById('status').textContent=isAuto ? "Auto-submitting..." : "Submitting...";
  if(timerInterval) clearInterval(timerInterval);

  await saveAll();

  const fd2=new FormData();
  fd2.append('attempt_id',attemptId);
  await fetch('api/submit_attempt.php',{method:'POST',body:fd2});

  const fd3=new FormData();
  fd3.append('attempt_id',attemptId);
  const ev=await fetch('api/evaluate_attempt.php',{method:'POST',body:fd3});
  const evData=await ev.json();

  document.getElementById('mockBox').textContent=JSON.stringify(evData.evaluation,null,2);
  document.getElementById('status').textContent="Done.";

  if(part===1) document.getElementById('btnStart2').disabled=false;
}

document.getElementById('btnStart1').addEventListener('click', async ()=>{
  await startAttempt(1);
  document.getElementById('btnSubmit').disabled=false;
  startPreview();
});
document.getElementById('btnStart2').addEventListener('click', async ()=>{
  await startAttempt(2);
  document.getElementById('btnSubmit').disabled=false;
  document.getElementById('mockBox').textContent='';
  startPreview();
});
document.getElementById('btnSubmit').addEventListener('click', ()=>submitListening(false));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
