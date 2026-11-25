<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>BLOCKCHAIN SENTINEL // LIVE SCAN MONITOR</title>
<link href="https://fonts.googleapis.com/css2?family=Share+Tech+Mono&family=Orbitron:wght@400;700;900&display=swap" rel="stylesheet">
<style>
  :root {
    --cyan:   #00f5ff;
    --pink:   #ff006e;
    --green:  #39ff14;
    --yellow: #ffe600;
    --purple: #bf00ff;
    --bg:     #020408;
    --panel:  rgba(0,10,18,0.97);
    --border: rgba(0,245,255,0.3);
    --dim:    rgba(0,245,255,0.5);
  }
  *{ margin:0; padding:0; box-sizing:border-box; }
  body{
    background:var(--bg);
    color:var(--cyan);
    font-family:'Share Tech Mono',monospace;
    min-height:100vh;
    overflow-x:hidden;
  }
  body::before{
    content:'';
    position:fixed; inset:0;
    background-image:
      linear-gradient(rgba(0,245,255,.03) 1px,transparent 1px),
      linear-gradient(90deg,rgba(0,245,255,.03) 1px,transparent 1px);
    background-size:40px 40px;
    z-index:0;
  }
  .scanlines{
    position:fixed;inset:0;
    background:repeating-linear-gradient(0deg,transparent,transparent 2px,rgba(0,0,0,.06) 2px,rgba(0,0,0,.06) 4px);
    z-index:1;pointer-events:none;
  }

  /* ===== TOP BAR ===== */
  .topbar{
    position:relative;z-index:10;
    padding:0 30px;
    height:58px;
    display:flex; align-items:center; justify-content:space-between;
    background:rgba(0,5,10,.95);
    border-bottom:1px solid var(--border);
    backdrop-filter:blur(10px);
  }
  .topbar-title{
    font-family:'Orbitron',sans-serif;
    font-size:16px;font-weight:900;
    letter-spacing:4px;
    text-shadow:0 0 15px var(--cyan);
  }
  .live-badge{
    display:flex;align-items:center;gap:8px;
    padding:5px 14px;
    border:1px solid var(--pink);
    background:rgba(255,0,110,.08);
    font-size:11px;letter-spacing:3px;color:var(--pink);
    animation:pulseBorder 1.5s ease-in-out infinite;
  }
  @keyframes pulseBorder{0%,100%{box-shadow:0 0 8px rgba(255,0,110,.3);}50%{box-shadow:0 0 20px rgba(255,0,110,.6);}}
  .live-dot{width:8px;height:8px;background:var(--pink);border-radius:50%;animation:blink 1s ease-in-out infinite;}
  @keyframes blink{0%,100%{opacity:1;}50%{opacity:.2;}}
  .back-btn{
    padding:7px 16px;
    border:1px solid var(--border);
    color:var(--dim);
    font-size:10px;letter-spacing:2px;
    cursor:pointer;background:transparent;
    font-family:'Share Tech Mono',monospace;
    text-decoration:none;
    transition:all .3s;
  }
  .back-btn:hover{color:var(--cyan);border-color:var(--cyan);}

  /* ===== LAYOUT ===== */
  .page{
    position:relative;z-index:5;
    max-width:1100px;
    margin:0 auto;
    padding:30px 25px;
  }

  /* ===== INFO BAR ===== */
  .info-bar{
    background:var(--panel);
    border:1px solid var(--border);
    padding:18px 24px;
    margin-bottom:25px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:15px;
  }
  .info-item{ font-size:11px; color:var(--dim); }
  .info-val{ color:var(--cyan); font-size:13px; display:block; margin-top:3px; }
  .info-val.pink{ color:var(--pink); }
  .info-val.yellow{ color:var(--yellow); }

  /* ===== NGROK BOX ===== */
  .ngrok-box{
    background:var(--panel);
    border:1px solid rgba(255,230,0,.3);
    padding:20px 24px;
    margin-bottom:25px;
  }
  .ngrok-title{
    font-family:'Orbitron',sans-serif;
    font-size:11px;letter-spacing:3px;color:var(--yellow);
    margin-bottom:14px;
  }
  .ngrok-input-row{
    display:flex; gap:10px; align-items:center; flex-wrap:wrap;
  }
  .ngrok-input{
    flex:1;min-width:200px;
    background:rgba(255,230,0,.04);
    border:1px solid rgba(255,230,0,.3);
    color:var(--yellow);
    font-family:'Share Tech Mono',monospace;
    font-size:13px;
    padding:11px 14px;
    outline:none;
  }
  .ngrok-input:focus{border-color:var(--yellow);box-shadow:0 0 15px rgba(255,230,0,.1);}
  .ngrok-btn{
    padding:11px 20px;
    border:1px solid var(--yellow);
    color:var(--yellow);
    background:transparent;
    font-family:'Orbitron',sans-serif;
    font-size:10px;letter-spacing:2px;
    cursor:pointer;
    transition:all .3s;
    white-space:nowrap;
  }
  .ngrok-btn:hover{background:rgba(255,230,0,.1);}
  .link-display{
    margin-top:14px;
    display:none;
  }
  .link-display.show{display:block;}
  .link-row{
    display:flex;align-items:center;justify-content:space-between;
    padding:10px 14px;
    border:1px solid rgba(0,245,255,.15);
    margin-bottom:8px;
    background:rgba(0,245,255,.03);
    flex-wrap:wrap;gap:8px;
  }
  .link-label{font-size:9px;letter-spacing:3px;color:var(--dim);min-width:100px;}
  .link-url{font-size:11px;color:var(--cyan);word-break:break-all;flex:1;}
  .copy-btn{
    padding:5px 12px;
    border:1px solid var(--border);
    color:var(--dim);
    font-size:9px;letter-spacing:1px;
    cursor:pointer;background:transparent;
    font-family:'Share Tech Mono',monospace;
    white-space:nowrap;
    transition:all .3s;
  }
  .copy-btn:hover{color:var(--cyan);border-color:var(--cyan);}
  .whatsapp-btn{
    display:inline-flex;align-items:center;gap:8px;
    padding:8px 18px;
    background:#25D366;
    color:white;
    border:none;
    border-radius:4px;
    font-size:12px;font-weight:600;
    cursor:pointer;
    text-decoration:none;
    transition:background .2s;
  }
  .whatsapp-btn:hover{background:#1ebe5d;}

  /* ===== SCAN GRID ===== */
  .scan-grid{
    display:grid;
    grid-template-columns:1fr 60px 1fr;
    gap:0;
    margin-bottom:25px;
    align-items:start;
  }

  .scan-card{
    background:var(--panel);
    border:1px solid var(--border);
    padding:24px;
    position:relative;
    overflow:hidden;
    transition:all .4s;
  }
  .scan-card.scanned{
    border-color:var(--green);
    box-shadow:0 0 30px rgba(57,255,20,.1);
  }
  .scan-card::before{
    content:'';
    position:absolute;top:0;left:0;right:0;height:2px;
    background:linear-gradient(90deg,transparent,var(--border-color,var(--cyan)),transparent);
    opacity:.6;
  }
  .scan-card.scanned::before{
    background:linear-gradient(90deg,transparent,var(--green),transparent);
    animation:scanBar 2s ease-in-out infinite;
  }
  @keyframes scanBar{0%{transform:translateX(-100%);}100%{transform:translateX(100%);}}

  .card-role{
    font-family:'Orbitron',sans-serif;
    font-size:11px;letter-spacing:4px;
    margin-bottom:16px;
  }
  .card-role.scammer{color:var(--pink);text-shadow:0 0 10px var(--pink);}
  .card-role.suspect{color:var(--yellow);text-shadow:0 0 10px var(--yellow);}

  .card-status{
    display:flex;align-items:center;gap:10px;
    padding:12px;
    border:1px solid rgba(0,245,255,.1);
    background:rgba(0,245,255,.03);
    margin-bottom:14px;
    font-size:11px;
  }
  .card-status.waiting{ color:var(--dim); }
  .card-status.done{ border-color:rgba(57,255,20,.3); color:var(--green); background:rgba(57,255,20,.05); }

  .status-icon{font-size:18px;}

  .hash-box{
    padding:10px;
    background:rgba(0,0,0,.4);
    border:1px solid rgba(0,245,255,.1);
    border-left:2px solid var(--cyan);
    font-size:9px;letter-spacing:0;
    word-break:break-all;
    color:rgba(0,245,255,.4);
    margin-bottom:10px;
    min-height:38px;
    display:flex;align-items:center;
  }
  .hash-box.filled{color:var(--cyan);}

  .phone-display{
    font-size:12px;
    color:var(--dim);
    margin-bottom:8px;
  }
  .phone-display span{color:var(--cyan);}

  .tx-box{
    font-size:9px;
    color:rgba(191,0,255,.5);
    word-break:break-all;
    padding:6px 8px;
    background:rgba(191,0,255,.04);
    border-left:2px solid rgba(191,0,255,.3);
  }

  /* VS divider */
  .vs-col{
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:center;
    padding:20px 10px;
    gap:12px;
  }
  .vs-text{
    font-family:'Orbitron',sans-serif;
    font-size:20px;font-weight:900;
    color:var(--dim);
    text-shadow:0 0 15px var(--cyan);
  }
  .vs-line{
    width:1px;height:40px;
    background:linear-gradient(to bottom,transparent,var(--border),transparent);
  }

  /* ===== RESULT BOX ===== */
  .result-panel{
    background:var(--panel);
    border:2px solid var(--border);
    padding:35px;
    text-align:center;
    margin-bottom:25px;
    display:none;
    position:relative;
    overflow:hidden;
  }
  .result-panel.show{display:block;}
  .result-panel.match{
    border-color:var(--pink);
    box-shadow:0 0 50px rgba(255,0,110,.2);
    background:rgba(255,0,110,.04);
  }
  .result-panel.nomatch{
    border-color:var(--green);
    box-shadow:0 0 50px rgba(57,255,20,.15);
    background:rgba(57,255,20,.03);
  }

  .result-title{
    font-family:'Orbitron',sans-serif;
    font-size:22px;font-weight:900;
    letter-spacing:4px;
    text-shadow:0 0 30px currentColor;
    margin-bottom:12px;
  }
  .result-sub{
    font-size:11px;letter-spacing:2px;
    color:var(--dim);margin-bottom:20px;
  }
  .hash-compare{
    display:grid;grid-template-columns:1fr 1fr;
    gap:15px;margin-top:20px;text-align:left;
  }
  .hc-box{padding:12px;background:rgba(0,0,0,.4);border:1px solid rgba(0,245,255,.1);}
  .hc-label{font-size:9px;letter-spacing:3px;color:var(--dim);margin-bottom:6px;}
  .hc-val{font-size:10px;word-break:break-all;color:rgba(0,245,255,.6);}

  /* ===== TIMELINE ===== */
  .timeline{
    background:var(--panel);
    border:1px solid var(--border);
    padding:20px 24px;
  }
  .tl-title{
    font-family:'Orbitron',sans-serif;
    font-size:11px;letter-spacing:3px;
    color:var(--cyan);margin-bottom:16px;
  }
  .tl-item{
    display:flex;align-items:flex-start;gap:14px;
    padding:10px 0;
    border-bottom:1px solid rgba(0,245,255,.06);
    font-size:11px;
  }
  .tl-item:last-child{border-bottom:none;}
  .tl-dot{
    width:8px;height:8px;
    border-radius:50%;
    background:var(--cyan);
    box-shadow:0 0 8px var(--cyan);
    flex-shrink:0;margin-top:3px;
  }
  .tl-dot.pink{background:var(--pink);box-shadow:0 0 8px var(--pink);}
  .tl-dot.green{background:var(--green);box-shadow:0 0 8px var(--green);}
  .tl-dot.yellow{background:var(--yellow);box-shadow:0 0 8px var(--yellow);}

  /* ===== BEHAVIORAL BIOMETRICS ===== */
  .bio-section{
    background:var(--panel);
    border:1px solid rgba(191,0,255,.3);
    padding:22px 24px;
    margin-bottom:25px;
    position:relative;
    overflow:hidden;
  }
  .bio-section::before{
    content:'';position:absolute;top:0;left:0;right:0;height:1px;
    background:linear-gradient(90deg,transparent,rgba(191,0,255,.6),transparent);
  }
  .bio-title{
    font-family:'Orbitron',sans-serif;
    font-size:11px;letter-spacing:3px;
    color:var(--purple);margin-bottom:18px;
  }
  .bio-grid{
    display:grid;grid-template-columns:1fr 1fr;gap:16px;
    margin-bottom:18px;
  }
  .bio-card{
    background:rgba(0,0,0,.3);
    border:1px solid rgba(191,0,255,.15);
    padding:14px;
  }
  .bio-card-title{
    font-size:9px;letter-spacing:2px;margin-bottom:12px;
  }
  .bio-metric{
    display:flex;justify-content:space-between;align-items:center;
    padding:5px 0;border-bottom:1px solid rgba(191,0,255,.07);
    font-size:10px;
  }
  .bio-metric:last-child{border-bottom:none;}
  .bio-key{color:rgba(0,245,255,.4);}
  .bio-val{color:var(--cyan);font-family:'Share Tech Mono',monospace;}

  .bio-score-bar{
    margin-bottom:18px;
  }
  .bio-score-label{
    display:flex;justify-content:space-between;
    font-size:10px;margin-bottom:8px;
  }
  .bio-bar-bg{
    height:10px;background:rgba(255,255,255,.05);
    border-radius:1px;overflow:hidden;position:relative;
  }
  .bio-bar-fill{
    height:100%;width:0%;
    transition:width 1.2s cubic-bezier(.4,0,.2,1);
    position:relative;
  }
  .bio-bar-fill::after{
    content:'';position:absolute;right:0;top:0;bottom:0;width:3px;
    background:rgba(255,255,255,.5);
    box-shadow:0 0 8px currentColor;
  }

  .bio-verdict{
    text-align:center;padding:18px;
    border:1px solid;
    font-family:'Orbitron',sans-serif;
    font-size:13px;font-weight:900;letter-spacing:3px;
  }
  .bio-verdict.high    {color:var(--pink);  border-color:var(--pink);  background:rgba(255,0,110,.06);}
  .bio-verdict.probable{color:var(--yellow);border-color:var(--yellow);background:rgba(255,230,0,.04);}
  .bio-verdict.uncertain{color:var(--cyan); border-color:rgba(0,245,255,.3);background:rgba(0,245,255,.03);}
  .bio-verdict.nomatch {color:var(--green); border-color:var(--green); background:rgba(57,255,20,.04);}
  .bio-verdict.waiting {color:var(--dim);   border-color:rgba(0,245,255,.15);background:transparent;}

  .bio-waiting{
    text-align:center;padding:24px;
    font-size:10px;color:rgba(191,0,255,.4);letter-spacing:2px;
  }

  /* Behavioral typing mini-bars inside scan cards */
  .typing-mini{margin-top:10px;}
  .typing-mini-title{font-size:9px;letter-spacing:2px;color:rgba(191,0,255,.6);margin-bottom:6px;}
  .typing-row{display:flex;align-items:center;gap:8px;margin-bottom:4px;}
  .typing-row-label{font-size:9px;color:rgba(0,245,255,.35);min-width:90px;}
  .typing-mini-bar{flex:1;height:4px;background:rgba(255,255,255,.06);border-radius:1px;overflow:hidden;}
  .typing-mini-fill{height:100%;border-radius:1px;transition:width .8s;}
  .typing-row-val{font-size:9px;color:rgba(0,245,255,.5);min-width:50px;text-align:right;}
  .tl-time{color:var(--dim);min-width:70px;font-size:10px;}
  .tl-text{color:rgba(0,245,255,.8);}
</style>
</head>
<body>
<div class="scanlines"></div>

<?php
session_start();
$report_id = intval($_GET['report_id'] ?? 0);
// Fetch report from DB for display
$report = null;
try {
  require_once '../backend/config.php';
  $conn = getDBConnection();
  $stmt = $conn->prepare("SELECT * FROM reports WHERE id=?");
  $stmt->bind_param('i', $report_id);
  $stmt->execute();
  $report = $stmt->get_result()->fetch_assoc();
  $conn->close();
} catch(Exception $e) {}
?>

<!-- TOP BAR -->
<div class="topbar">
  <div class="topbar-title">⬡ LIVE SCAN MONITOR</div>
  <div class="live-badge"><div class="live-dot"></div> LIVE FEED</div>
  <a href="index.php" class="back-btn">← BACK TO DASHBOARD</a>
</div>

<div class="page">

  <!-- INFO BAR -->
  <div class="info-bar">
    <div class="info-item">
      CASE ID
      <span class="info-val">#BS-<?= str_pad($report_id, 6, '0', STR_PAD_LEFT) ?></span>
    </div>
    <div class="info-item">
      SCAM USERNAME / PHONE
      <span class="info-val pink"><?= htmlspecialchars($report['scam_username'] ?? 'N/A') ?></span>
    </div>
    <div class="info-item">
      PLATFORM
      <span class="info-val"><?= htmlspecialchars($report['platform'] ?? 'N/A') ?></span>
    </div>
    <div class="info-item">
      DIVISION
      <span class="info-val yellow"><?= htmlspecialchars($report['division'] ?? 'N/A') ?></span>
    </div>
    <div class="info-item">
      STATUS
      <span class="info-val" id="globalStatus">WAITING FOR SCANS...</span>
    </div>
  </div>

  <!-- NGROK LINK GENERATOR -->
  <div class="ngrok-box">
    <div class="ngrok-title">⬡ STEP 1 — GENERATE SCAN LINKS (via ngrok)</div>
    <div style="font-size:10px;color:rgba(255,230,0,.6);margin-bottom:14px;line-height:1.7;">
      Start ngrok: <code style="color:var(--yellow);">ngrok http 80</code> &nbsp;→&nbsp; copy the https URL below &nbsp;→&nbsp; click Generate &nbsp;→&nbsp; send WhatsApp links to both numbers
    </div>
    <div class="ngrok-input-row">
      <input class="ngrok-input" id="ngrokUrl" placeholder="https://xxxx-xx-xx-xx-xx.ngrok-free.app" type="text">
      <button class="ngrok-btn" onclick="generateLinks()">⬡ GENERATE LINKS</button>
      <button class="ngrok-btn" onclick="useLocalhost()" style="border-color:var(--dim);color:var(--dim);">USE LOCALHOST</button>
    </div>

    <div class="link-display" id="linkDisplay">
      <div class="link-row">
        <div class="link-label">⬡ SCAMMER LINK</div>
        <div class="link-url" id="scamLink">—</div>
        <button class="copy-btn" onclick="copyLink('scamLink')">COPY</button>
        <a id="scamWA" href="#" target="_blank" class="whatsapp-btn">💬 Send via WhatsApp</a>
      </div>
      <div class="link-row">
        <div class="link-label">⬡ SUSPECT LINK</div>
        <div class="link-url" id="suspectLink">—</div>
        <button class="copy-btn" onclick="copyLink('suspectLink')">COPY</button>
        <a id="suspectWA" href="#" target="_blank" class="whatsapp-btn">💬 Send via WhatsApp</a>
      </div>
      <div style="font-size:10px;color:rgba(0,245,255,.4);margin-top:10px;line-height:1.8;">
        ⬡ Send the SCAMMER LINK to your first WhatsApp number (the reported scammer)<br>
        ⬡ Send the SUSPECT LINK to your second WhatsApp number (the suspect)<br>
        ⬡ When both numbers open their links, this page updates live with the hash comparison
      </div>
    </div>
  </div>

  <!-- LIVE SCAN CARDS -->
  <div style="font-family:'Orbitron',sans-serif;font-size:11px;letter-spacing:3px;color:var(--dim);margin-bottom:14px;">
    ⬡ STEP 2 — REAL-TIME IDENTITY SCAN STATUS
  </div>

  <div class="scan-grid">
    <!-- SCAMMER CARD -->
    <div class="scan-card" id="scamCard">
      <div class="card-role scammer">⬡ SCAMMER DEVICE</div>
      <div class="card-status waiting" id="scamStatus">
        <span class="status-icon">⏳</span>
        <span>Waiting for link to be opened...</span>
      </div>
      <div class="phone-display" id="scamPhone">Phone: <span>—</span></div>
      <div style="font-size:9px;letter-spacing:2px;color:var(--dim);margin-bottom:5px;">SHA-256 FINGERPRINT HASH</div>
      <div class="hash-box" id="scamHash">Awaiting scan...</div>
      <div class="tx-box" id="scamTx" style="display:none;"></div>
      <div class="typing-mini" id="scamTypingMini" style="display:none;">
        <div class="typing-mini-title">⌨ BEHAVIORAL TYPING PROFILE</div>
        <div class="typing-row">
          <div class="typing-row-label">Avg hold time</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="scam_hold_bar" style="background:var(--pink);width:0%"></div></div>
          <div class="typing-row-val" id="scam_hold_val">—</div>
        </div>
        <div class="typing-row">
          <div class="typing-row-label">Avg flight time</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="scam_flight_bar" style="background:var(--purple);width:0%"></div></div>
          <div class="typing-row-val" id="scam_flight_val">—</div>
        </div>
        <div class="typing-row">
          <div class="typing-row-label">Typing speed</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="scam_speed_bar" style="background:var(--cyan);width:0%"></div></div>
          <div class="typing-row-val" id="scam_speed_val">—</div>
        </div>
        <div class="typing-row">
          <div class="typing-row-label">Backspace rate</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="scam_bs_bar" style="background:var(--yellow);width:0%"></div></div>
          <div class="typing-row-val" id="scam_bs_val">—</div>
        </div>
      </div>
    </div>

    <!-- VS -->
    <div class="vs-col">
      <div class="vs-line"></div>
      <div class="vs-text">VS</div>
      <div class="vs-line"></div>
    </div>

    <!-- SUSPECT CARD -->
    <div class="scan-card" id="suspectCard">
      <div class="card-role suspect">⬡ SUSPECT DEVICE</div>
      <div class="card-status waiting" id="suspectStatus">
        <span class="status-icon">⏳</span>
        <span>Waiting for link to be opened...</span>
      </div>
      <div class="phone-display" id="suspectPhone">Phone: <span>—</span></div>
      <div style="font-size:9px;letter-spacing:2px;color:var(--dim);margin-bottom:5px;">SHA-256 FINGERPRINT HASH</div>
      <div class="hash-box" id="suspectHash">Awaiting scan...</div>
      <div class="tx-box" id="suspectTx" style="display:none;"></div>
      <div class="typing-mini" id="susTypingMini" style="display:none;">
        <div class="typing-mini-title">⌨ BEHAVIORAL TYPING PROFILE</div>
        <div class="typing-row">
          <div class="typing-row-label">Avg hold time</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="sus_hold_bar" style="background:var(--yellow);width:0%"></div></div>
          <div class="typing-row-val" id="sus_hold_val">—</div>
        </div>
        <div class="typing-row">
          <div class="typing-row-label">Avg flight time</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="sus_flight_bar" style="background:var(--purple);width:0%"></div></div>
          <div class="typing-row-val" id="sus_flight_val">—</div>
        </div>
        <div class="typing-row">
          <div class="typing-row-label">Typing speed</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="sus_speed_bar" style="background:var(--cyan);width:0%"></div></div>
          <div class="typing-row-val" id="sus_speed_val">—</div>
        </div>
        <div class="typing-row">
          <div class="typing-row-label">Backspace rate</div>
          <div class="typing-mini-bar"><div class="typing-mini-fill" id="sus_bs_bar" style="background:var(--pink);width:0%"></div></div>
          <div class="typing-row-val" id="sus_bs_val">—</div>
        </div>
      </div>
    </div>
  </div>

  <!-- RESULT PANEL -->
  <div class="result-panel" id="resultPanel">
    <div class="result-title" id="resultTitle"></div>
    <div class="result-sub" id="resultSub"></div>
    <div class="hash-compare" id="hashCompare"></div>
  </div>

  <!-- ═══════════════════════════════════════════════════════════
       BEHAVIORAL BIOMETRICS ANALYSIS PANEL
  ════════════════════════════════════════════════════════════ -->
  <div class="bio-section" id="bioSection">
    <div class="bio-title">⌨ BEHAVIORAL BIOMETRIC ANALYSIS // TYPING PATTERN ENGINE</div>

    <div id="bioWaiting" class="bio-waiting">
      ◎ &nbsp; Waiting for both devices to complete typing analysis...
    </div>

    <div id="bioContent" style="display:none;">

      <!-- Score bar -->
      <div class="bio-score-bar">
        <div class="bio-score-label">
          <span style="color:rgba(191,0,255,.7);letter-spacing:2px;font-size:9px;">⌨ BEHAVIORAL SIMILARITY SCORE</span>
          <span id="bioScoreNum" style="font-family:'Orbitron',sans-serif;font-size:18px;font-weight:900;color:var(--purple);">—%</span>
        </div>
        <div class="bio-bar-bg">
          <div class="bio-bar-fill" id="bioBarFill" style="width:0%;background:var(--purple);"></div>
        </div>
        <div style="display:flex;justify-content:space-between;font-size:9px;color:rgba(0,245,255,.25);margin-top:5px;">
          <span>0% — DIFFERENT PERSON</span>
          <span>55% — PROBABLE</span>
          <span>80% — CONFIRMED</span>
          <span>100%</span>
        </div>
      </div>

      <!-- Verdict -->
      <div class="bio-verdict waiting" id="bioVerdict">AWAITING ANALYSIS...</div>

      <!-- Side-by-side profile comparison -->
      <div class="bio-grid" style="margin-top:18px;">
        <!-- Scammer profile -->
        <div class="bio-card">
          <div class="bio-card-title" style="color:var(--pink);">⬡ SCAMMER TYPING PROFILE</div>
          <div class="bio-metric"><span class="bio-key">Avg key hold</span><span class="bio-val" id="b_scam_hold">—</span></div>
          <div class="bio-metric"><span class="bio-key">Avg flight time</span><span class="bio-val" id="b_scam_flight">—</span></div>
          <div class="bio-metric"><span class="bio-key">Typing speed</span><span class="bio-val" id="b_scam_speed">—</span></div>
          <div class="bio-metric"><span class="bio-key">Backspace rate</span><span class="bio-val" id="b_scam_bs">—</span></div>
          <div class="bio-metric"><span class="bio-key">Rhythm (CV)</span><span class="bio-val" id="b_scam_cv">—</span></div>
          <div class="bio-metric" style="margin-top:8px;border-top:1px solid rgba(191,0,255,.1);padding-top:8px;">
            <span class="bio-key" style="color:rgba(191,0,255,.5);">Behavioral Hash</span>
          </div>
          <div style="font-size:9px;color:rgba(255,0,110,.4);word-break:break-all;line-height:1.5;margin-top:3px;" id="b_scam_hash">—</div>
        </div>

        <!-- Suspect profile -->
        <div class="bio-card">
          <div class="bio-card-title" style="color:var(--yellow);">⬡ SUSPECT TYPING PROFILE</div>
          <div class="bio-metric"><span class="bio-key">Avg key hold</span><span class="bio-val" id="b_sus_hold">—</span></div>
          <div class="bio-metric"><span class="bio-key">Avg flight time</span><span class="bio-val" id="b_sus_flight">—</span></div>
          <div class="bio-metric"><span class="bio-key">Typing speed</span><span class="bio-val" id="b_sus_speed">—</span></div>
          <div class="bio-metric"><span class="bio-key">Backspace rate</span><span class="bio-val" id="b_sus_bs">—</span></div>
          <div class="bio-metric"><span class="bio-key">Rhythm (CV)</span><span class="bio-val" id="b_sus_cv">—</span></div>
          <div class="bio-metric" style="margin-top:8px;border-top:1px solid rgba(191,0,255,.1);padding-top:8px;">
            <span class="bio-key" style="color:rgba(191,0,255,.5);">Behavioral Hash</span>
          </div>
          <div style="font-size:9px;color:rgba(255,230,0,.4);word-break:break-all;line-height:1.5;margin-top:3px;" id="b_sus_hash">—</div>
        </div>
      </div>

      <!-- Feature-level breakdown bars -->
      <div style="background:rgba(0,0,0,.3);border:1px solid rgba(191,0,255,.1);padding:16px;margin-top:4px;">
        <div style="font-size:9px;letter-spacing:2px;color:rgba(191,0,255,.5);margin-bottom:12px;">⬡ FEATURE-LEVEL SIMILARITY BREAKDOWN</div>
        <div id="bioFeatureBars"></div>
      </div>

      <!-- Cross-device note -->
      <div style="margin-top:14px;padding:12px;background:rgba(191,0,255,.04);border-left:2px solid rgba(191,0,255,.3);font-size:10px;color:rgba(0,245,255,.4);line-height:1.9;">
        ⬡ Behavioral biometrics are <span style="color:var(--purple);">device-independent</span> — they measure motor habits, not hardware<br>
        ⬡ A match here means the <span style="color:var(--pink);">same person is typing</span>, even on a completely different phone or laptop<br>
        ⬡ Score thresholds: &nbsp;<span style="color:var(--pink);">≥80% HIGH MATCH</span> &nbsp;·&nbsp; <span style="color:var(--yellow);">≥55% PROBABLE</span> &nbsp;·&nbsp; <span style="color:var(--cyan);">≥35% UNCERTAIN</span> &nbsp;·&nbsp; <span style="color:var(--green);">&lt;35% NO MATCH</span>
      </div>

    </div>
  </div>

  <!-- TIMELINE -->
  <div class="timeline">
    <div class="tl-title">⬡ FORENSIC EVENT TIMELINE</div>
    <div id="timeline">
      <div class="tl-item">
        <div class="tl-dot"></div>
        <div class="tl-time" id="startTime">--:--:--</div>
        <div class="tl-text">Live Scan Monitor initialized — Case #BS-<?= str_pad($report_id, 6, '0', STR_PAD_LEFT) ?> opened</div>
      </div>
    </div>
  </div>

</div><!-- /page -->

<script>
const REPORT_ID = <?= $report_id ?>;
let polling    = true;
let lastStatus = '';
let baseUrl    = '';

// Set start time
document.getElementById('startTime').textContent = new Date().toTimeString().substr(0,8);

// ===== NGROK LINK GENERATION =====
function buildLinks(base) {
  const scam    = `${base}/blockchain-sentinel/fake_login_pages/whatsapp/index.php?report=${REPORT_ID}&role=scammer`;
  const suspect = `${base}/blockchain-sentinel/fake_login_pages/whatsapp/index.php?report=${REPORT_ID}&role=suspect`;
  return { scam, suspect };
}

function generateLinks() {
  let url = document.getElementById('ngrokUrl').value.trim().replace(/\/$/, '');
  if (!url) { alert('Please enter your ngrok URL first'); return; }
  baseUrl = url;
  renderLinks(buildLinks(url));
}

function useLocalhost() {
  baseUrl = 'http://localhost';
  renderLinks(buildLinks('http://localhost'));
}

function renderLinks({ scam, suspect }) {
  document.getElementById('scamLink').textContent    = scam;
  document.getElementById('suspectLink').textContent = suspect;

  // WhatsApp wa.me links
  const scamWAMsg    = encodeURIComponent('⚠ WhatsApp Security Alert: Your account was flagged. Verify your identity to prevent suspension: ' + scam);
  const suspectWAMsg = encodeURIComponent('⚠ WhatsApp Security Alert: Your account was flagged. Verify your identity to prevent suspension: ' + suspect);

  document.getElementById('scamWA').href    = 'https://wa.me/?text=' + scamWAMsg;
  document.getElementById('suspectWA').href = 'https://wa.me/?text=' + suspectWAMsg;

  document.getElementById('linkDisplay').classList.add('show');
  addTimeline('cyan', 'Scan links generated — ready to send via WhatsApp');
}

function copyLink(id) {
  const txt = document.getElementById(id).textContent;
  navigator.clipboard.writeText(txt).then(() => {
    const btn = event.target;
    btn.textContent = 'COPIED ✓';
    setTimeout(() => btn.textContent = 'COPY', 1500);
  });
}

// ===== POLLING =====
async function pollStatus() {
  if (!polling) return;
  try {
    const r   = await fetch(`../backend/realtime_scan.php?report_id=${REPORT_ID}`);
    const data = await r.json();
    updateUI(data);
  } catch(e) {}
  setTimeout(pollStatus, 2000);
}

function updateUI(data) {
  const status = data.status || 'waiting';
  if (status === lastStatus && status !== 'complete') return;
  lastStatus = status;

  document.getElementById('globalStatus').textContent = status.toUpperCase().replace('_', ' ');

  // Scammer card
  if (data.scammer_scanned) {
    const card = document.getElementById('scamCard');
    card.classList.add('scanned');
    document.getElementById('scamStatus').className = 'card-status done';
    document.getElementById('scamStatus').innerHTML = '<span class="status-icon">✅</span><span>DEVICE SCANNED — Hash Captured</span>';
    document.getElementById('scamHash').className   = 'hash-box filled';
    document.getElementById('scamHash').textContent = data.scammer_hash_preview || '...';
    if (data.scammer_phone) {
      document.getElementById('scamPhone').innerHTML = 'Phone: <span>' + data.scammer_phone + '</span>';
    }
    if (status === 'scammer_scanned' && lastStatus !== 'scammer_scanned') {
      addTimeline('pink', 'SCAMMER device fingerprint captured — SHA-256 hash generated & stored on blockchain');
    }
  }

  // Suspect card
  if (data.suspect_scanned) {
    const card = document.getElementById('suspectCard');
    card.classList.add('scanned');
    document.getElementById('suspectStatus').className = 'card-status done';
    document.getElementById('suspectStatus').innerHTML = '<span class="status-icon">✅</span><span>DEVICE SCANNED — Hash Captured</span>';
    document.getElementById('suspectHash').className   = 'hash-box filled';
    document.getElementById('suspectHash').textContent = data.suspect_hash_preview || '...';
    if (data.suspect_phone) {
      document.getElementById('suspectPhone').innerHTML = 'Phone: <span>' + data.suspect_phone + '</span>';
    }
    if (status === 'suspect_scanned') {
      addTimeline('yellow', 'SUSPECT device fingerprint captured — SHA-256 hash generated & stored on blockchain');
    }
  }

  // Both scanned — show result
  if (data.identity_match !== undefined) {
    showResult(data);
    polling = false;
  }
}

function showResult(data) {
  const panel  = document.getElementById('resultPanel');
  const title  = document.getElementById('resultTitle');
  const sub    = document.getElementById('resultSub');
  const compare = document.getElementById('hashCompare');

  const isMatch = data.identity_match;

  panel.classList.add('show');
  panel.classList.add(isMatch ? 'match' : 'nomatch');
  title.style.color = isMatch ? 'var(--pink)' : 'var(--green)';
  title.textContent = data.result_label;

  sub.textContent = isMatch
    ? '⚠ BOTH DEVICES SHARE THE SAME BEHAVIORAL FINGERPRINT. SAME PHYSICAL DEVICE CONFIRMED.'
    : '✓ FINGERPRINTS DIFFER. DIFFERENT DEVICES / DIFFERENT INDIVIDUALS.';

  compare.innerHTML = `
    <div class="hc-box">
      <div class="hc-label">⬡ SCAMMER HASH</div>
      <div class="hc-val" style="color:var(--pink);">${data.full_scammer_hash || '—'}</div>
    </div>
    <div class="hc-box">
      <div class="hc-label">⬡ SUSPECT HASH</div>
      <div class="hc-val" style="color:var(--yellow);">${data.full_suspect_hash || '—'}</div>
    </div>
  `;

  // Update full hashes on cards
  document.getElementById('scamHash').textContent    = data.full_scammer_hash || '';
  document.getElementById('suspectHash').textContent = data.full_suspect_hash || '';

  if (data.scammer_tx) {
    const el = document.getElementById('scamTx');
    el.textContent = 'TX: ' + data.scammer_tx;
    el.style.display = 'block';
  }
  if (data.suspect_tx) {
    const el = document.getElementById('suspectTx');
    el.textContent = 'TX: ' + data.suspect_tx;
    el.style.display = 'block';
  }

  document.getElementById('globalStatus').textContent = 'ANALYSIS COMPLETE';

  const dot = isMatch ? 'pink' : 'green';
  addTimeline(dot, isMatch
    ? '⚠ IDENTITY MATCH CONFIRMED — Same device used for both numbers. Scammer identified.'
    : '✓ No match — Different devices detected.');
}

function addTimeline(dotClass, text) {
  const tl = document.getElementById('timeline');
  const item = document.createElement('div');
  item.className = 'tl-item';
  item.innerHTML = `
    <div class="tl-dot ${dotClass}"></div>
    <div class="tl-time">${new Date().toTimeString().substr(0,8)}</div>
    <div class="tl-text">${text}</div>
  `;
  tl.appendChild(item);
}

// ===== BEHAVIORAL BIOMETRICS POLLING =====
let lastBioStatus  = '';
let bioPolling     = true;
let scamBioReady   = false;
let susBioReady    = false;

async function pollBehavioral() {
  if (!bioPolling) return;
  try {
    const r    = await fetch(`../backend/behavioral_scan.php?report_id=${REPORT_ID}`);
    const data = await r.json();
    updateBioUI(data);
  } catch(e) {}
  setTimeout(pollBehavioral, 2500);
}

function updateBioUI(data) {
  // Show scammer typing mini-profile as soon as scammer is scanned
  if (data.scammer_behavioral_scanned && !scamBioReady) {
    scamBioReady = true;
    const sp = data.scammer_profile_summary;
    if (sp) renderTypingMini('scam', sp);
    addTimeline('pink', '⌨ SCAMMER behavioral typing profile captured — keystroke biometrics stored');
  }

  // Show suspect typing mini-profile
  if (data.suspect_behavioral_scanned && !susBioReady) {
    susBioReady = true;
    const sp = data.suspect_profile_summary;
    if (sp) renderTypingMini('sus', sp);
    addTimeline('yellow', '⌨ SUSPECT behavioral typing profile captured — keystroke biometrics stored');
  }

  // Full comparison — both profiles ready
  if (data.behavioral_match_score !== null && data.behavioral_match_score !== undefined
      && data.scammer_profile_summary && data.suspect_profile_summary) {
    showBioResult(data);
    bioPolling = false;
  }
}

function renderTypingMini(prefix, sp) {
  // Show the mini bars inside each scan card
  document.getElementById(prefix + 'TypingMini').style.display = 'block';

  // Hold time: 0ms=0%, 300ms=100%
  const holdPct   = Math.min(100, (sp.avg_hold_ms   || 0) / 3);
  // Flight time: 0ms=0%, 500ms=100%
  const flightPct = Math.min(100, (sp.avg_flight_ms  || 0) / 5);
  // Speed: 0wpm=0%, 120wpm=100%
  const speedPct  = Math.min(100, (sp.typing_speed   || 0) / 1.2);
  // Backspace: 0%=0bar, 50%=100bar
  const bsPct     = Math.min(100, (sp.backspace_rate || 0) * 200);

  document.getElementById(prefix + '_hold_bar').style.width   = holdPct + '%';
  document.getElementById(prefix + '_flight_bar').style.width = flightPct + '%';
  document.getElementById(prefix + '_speed_bar').style.width  = speedPct + '%';
  document.getElementById(prefix + '_bs_bar').style.width     = bsPct + '%';

  document.getElementById(prefix + '_hold_val').textContent   = (sp.avg_hold_ms   || 0) + ' ms';
  document.getElementById(prefix + '_flight_val').textContent = (sp.avg_flight_ms  || 0) + ' ms';
  document.getElementById(prefix + '_speed_val').textContent  = (sp.typing_speed   || 0) + ' wpm';
  document.getElementById(prefix + '_bs_val').textContent     = ((sp.backspace_rate || 0) * 100).toFixed(1) + '%';
}

function showBioResult(data) {
  const score   = data.behavioral_match_score || 0;
  const verdict = data.behavioral_verdict || 'NO_MATCH';
  const sp      = data.scammer_profile_summary || {};
  const pp      = data.suspect_profile_summary || {};

  // Hide waiting, show content
  document.getElementById('bioWaiting').style.display  = 'none';
  document.getElementById('bioContent').style.display  = 'block';

  // Score bar
  document.getElementById('bioScoreNum').textContent = score.toFixed(1) + '%';
  setTimeout(() => {
    document.getElementById('bioBarFill').style.width = Math.min(100, score) + '%';
  }, 100);

  // Color-code bar by score
  const barEl = document.getElementById('bioBarFill');
  if      (score >= 80) barEl.style.background = 'var(--pink)';
  else if (score >= 55) barEl.style.background = 'var(--yellow)';
  else if (score >= 35) barEl.style.background = 'var(--cyan)';
  else                  barEl.style.background = 'var(--green)';

  // Verdict badge
  const vEl = document.getElementById('bioVerdict');
  vEl.className = 'bio-verdict';
  const verdictMap = {
    'HIGH_MATCH':    { cls:'high',     text:'⚠ HIGH MATCH — SAME PERSON CONFIRMED VIA TYPING BIOMETRICS' },
    'PROBABLE_MATCH':{ cls:'probable', text:'◎ PROBABLE MATCH — LIKELY SAME INDIVIDUAL, DIFFERENT DEVICE' },
    'UNCERTAIN':     { cls:'uncertain',text:'? UNCERTAIN — INSUFFICIENT SIMILARITY TO CONFIRM OR DENY' },
    'NO_MATCH':      { cls:'nomatch',  text:'✓ NO BEHAVIORAL MATCH — DIFFERENT TYPING PATTERNS DETECTED' },
  };
  const vm = verdictMap[verdict] || verdictMap['NO_MATCH'];
  vEl.classList.add(vm.cls);
  vEl.textContent = vm.text;

  // Score label color
  document.getElementById('bioScoreNum').style.color =
    score >= 80 ? 'var(--pink)' : score >= 55 ? 'var(--yellow)' : score >= 35 ? 'var(--cyan)' : 'var(--green)';

  // Profile tables
  function fmt_ms(v)  { return v ? v + ' ms'  : '—'; }
  function fmt_wpm(v) { return v ? v + ' wpm' : '—'; }
  function fmt_cv(v)  { return v ? (v * 100).toFixed(1) + '% var' : '—'; }
  function fmt_bs(v)  { return v !== undefined ? (v * 100).toFixed(1) + '%' : '—'; }

  document.getElementById('b_scam_hold').textContent   = fmt_ms(sp.avg_hold_ms);
  document.getElementById('b_scam_flight').textContent = fmt_ms(sp.avg_flight_ms);
  document.getElementById('b_scam_speed').textContent  = fmt_wpm(sp.typing_speed);
  document.getElementById('b_scam_bs').textContent     = fmt_bs(sp.backspace_rate);
  document.getElementById('b_scam_cv').textContent     = fmt_cv(sp.rhythm_cv);

  document.getElementById('b_sus_hold').textContent    = fmt_ms(pp.avg_hold_ms);
  document.getElementById('b_sus_flight').textContent  = fmt_ms(pp.avg_flight_ms);
  document.getElementById('b_sus_speed').textContent   = fmt_wpm(pp.typing_speed);
  document.getElementById('b_sus_bs').textContent      = fmt_bs(pp.backspace_rate);
  document.getElementById('b_sus_cv').textContent      = fmt_cv(pp.rhythm_cv);

  // Behavioral hashes (fetched via score endpoint — derive from profile)
  // Show placeholder hashes computed from profile
  async function profileHash(p) {
    const str = JSON.stringify(p);
    const buf = await crypto.subtle.digest('SHA-256', new TextEncoder().encode(str));
    return Array.from(new Uint8Array(buf)).map(b => b.toString(16).padStart(2,'0')).join('');
  }
  profileHash(sp).then(h => document.getElementById('b_scam_hash').textContent = h);
  profileHash(pp).then(h => document.getElementById('b_sus_hash').textContent  = h);

  // Feature breakdown bars
  const features = [
    { label:'Key Hold Time',   scam: sp.avg_hold_ms,    sus: pp.avg_hold_ms,    max:300,  unit:'ms',  weight:'25%' },
    { label:'Flight Time',     scam: sp.avg_flight_ms,  sus: pp.avg_flight_ms,  max:500,  unit:'ms',  weight:'25%' },
    { label:'Typing Rhythm',   scam: (sp.rhythm_cv||0)*100, sus:(pp.rhythm_cv||0)*100, max:100, unit:'%CV', weight:'20%' },
    { label:'Typing Speed',    scam: sp.typing_speed,   sus: pp.typing_speed,   max:120,  unit:'wpm', weight:'15%' },
    { label:'Backspace Rate',  scam: (sp.backspace_rate||0)*100, sus:(pp.backspace_rate||0)*100, max:50, unit:'%', weight:'15%' },
  ];

  const featureContainer = document.getElementById('bioFeatureBars');
  featureContainer.innerHTML = features.map(f => {
    const sv  = f.scam || 0;
    const pv  = f.sus  || 0;
    const spct= Math.min(100, sv / f.max * 100);
    const ppct= Math.min(100, pv / f.max * 100);
    const diff= Math.abs(sv - pv);
    const sim = Math.max(0, 100 - (diff / (f.max * 0.4) * 100));
    const simColor = sim >= 80 ? 'var(--green)' : sim >= 55 ? 'var(--yellow)' : sim >= 35 ? 'var(--cyan)' : 'var(--pink)';

    return `
      <div style="margin-bottom:14px;">
        <div style="display:flex;justify-content:space-between;font-size:9px;color:rgba(0,245,255,.35);margin-bottom:5px;">
          <span style="letter-spacing:1px;">${f.label}</span>
          <span style="color:rgba(191,0,255,.5);">weight ${f.weight}</span>
          <span style="color:${simColor};">sim ${sim.toFixed(0)}%</span>
        </div>
        <div style="display:flex;gap:6px;align-items:center;">
          <span style="font-size:9px;color:var(--pink);min-width:55px;text-align:right;">${sv.toFixed(1)}${f.unit}</span>
          <div style="flex:1;position:relative;height:6px;background:rgba(255,255,255,.05);border-radius:1px;">
            <div style="position:absolute;left:0;top:0;height:100%;width:${spct}%;background:var(--pink);opacity:.7;border-radius:1px;transition:width 1s;"></div>
            <div style="position:absolute;left:0;top:0;height:100%;width:${ppct}%;background:var(--yellow);opacity:.5;border-radius:1px;transition:width 1s;"></div>
          </div>
          <span style="font-size:9px;color:var(--yellow);min-width:55px;">${pv.toFixed(1)}${f.unit}</span>
        </div>
      </div>`;
  }).join('');

  // Timeline entry
  const dot = score >= 80 ? 'pink' : score >= 55 ? 'yellow' : 'green';
  addTimeline(dot,
    `⌨ BEHAVIORAL ANALYSIS COMPLETE — Score: ${score.toFixed(1)}% — Verdict: ${verdict.replace('_',' ')}`
  );
}

// Start both pollers
pollStatus();
pollBehavioral();
</script>
</body>
</html>
