<?php
$page = 'speaking';
$path_prefix = '../';
require_once __DIR__ . '/../includes/auth_guard.php';
require_once __DIR__ . '/../includes/header.php';
?>
<h2>Speaking (10s prep + 150s recording)</h2>

<button id="btnStart">Start Speaking</button>

<div id="prep" style="display:none;">
  <h3>Preparation: <span id="prepTimer">10</span>s</h3>
</div>

<div id="record" style="display:none;">
  <h3>Recording: <span id="recTimer">150</span>s</h3>
  <button id="btnStop">Submit Now</button>
  <p id="status"></p>

  <h3>Playback</h3>
  <audio id="playback" controls></audio>
  <pre id="mockBox"></pre>
</div>

<script>
let attemptId=null, mediaRecorder=null, chunks=[];
let prepLeft=10, recLeft=150, prepInterval=null, recInterval=null;

async function startAttempt(){
  const fd=new FormData();
  fd.append('type','speaking');
  const res=await fetch('api/start_attempt.php',{method:'POST',body:fd});
  const data=await res.json();
  attemptId=data.attempt_id;
}

async function startMic(){
  const stream=await navigator.mediaDevices.getUserMedia({audio:true});
  mediaRecorder=new MediaRecorder(stream,{mimeType:'audio/webm'});
  chunks=[];
  mediaRecorder.ondataavailable=(e)=>{ if(e.data.size>0) chunks.push(e.data); };
  mediaRecorder.start();
}

function startPrep(){
  document.getElementById('btnStart').disabled=true;
  document.getElementById('prep').style.display='block';
  prepLeft=10;
  document.getElementById('prepTimer').textContent=prepLeft;

  prepInterval=setInterval(async ()=>{
    prepLeft--;
    document.getElementById('prepTimer').textContent=prepLeft;
    if(prepLeft<=0){
      clearInterval(prepInterval);
      document.getElementById('prep').style.display='none';
      await beginRecording();
    }
  },1000);
}

async function beginRecording(){
  document.getElementById('record').style.display='block';
  await startMic();
  recLeft=150;
  document.getElementById('recTimer').textContent=recLeft;

  recInterval=setInterval(()=>{
    recLeft--;
    document.getElementById('recTimer').textContent=recLeft;
    if(recLeft<=0){
      clearInterval(recInterval);
      submitSpeaking(true);
    }
  },1000);
}

async function submitSpeaking(isAuto=false){
  document.getElementById('btnStop').disabled=true;
  document.getElementById('status').textContent=isAuto ? "Auto-submitting..." : "Submitting...";
  if(recInterval) clearInterval(recInterval);

  mediaRecorder.onstop = async ()=>{
    const blob=new Blob(chunks,{type:'audio/webm'});

    // upload
    const fd=new FormData();
    fd.append('attempt_id',attemptId);
    fd.append('audio',blob,'speaking.webm');
    const up=await fetch('api/upload_speaking_audio.php',{method:'POST',body:fd});
    const upData=await up.json();
    document.getElementById('playback').src=upData.public_url;

    // submit
    const fd2=new FormData();
    fd2.append('attempt_id',attemptId);
    await fetch('api/submit_attempt.php',{method:'POST',body:fd2});

    // mock evaluate
    const fd3=new FormData();
    fd3.append('attempt_id',attemptId);
    const ev=await fetch('api/evaluate_attempt.php',{method:'POST',body:fd3});
    const evData=await ev.json();

    document.getElementById('mockBox').textContent = JSON.stringify(evData.evaluation, null, 2);
    document.getElementById('status').textContent="Done.";
  };

  mediaRecorder.stop();
}

document.getElementById('btnStart').addEventListener('click', async ()=>{
  await startAttempt();
  startPrep();
});
document.getElementById('btnStop').addEventListener('click', ()=>submitSpeaking(false));
</script>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
