<?php
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$q = $pdo->prepare('SELECT * FROM missions WHERE id = ?');
$q->execute([$id]);
$mission = $q->fetch();
if (!$mission || $mission['game_type'] !== 'Network Simulation') {
    http_response_code(404);
    exit('Game not found.');
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') record_learning_activity((int)$user['id'], (int)$mission['id'], 'mission_view', 'Opened network simulation');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $score = max(0, min(100, (int)($_POST['score'] ?? 0)));
    $points = (int)floor((int)$mission['xp_reward'] * ($score / 100));
    $pdo->beginTransaction();
    try {
        $attempt = $pdo->prepare('INSERT INTO mission_attempts(user_id,mission_id,score,total_questions,xp_earned) VALUES(?,?,?,?,?)');
        $attempt->execute([$user['id'], $mission['id'], $score, 5, $points]);
        $first = award_xp_once((int)$user['id'], (int)$mission['id'], $points);
        award_badges((int)$user['id']);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    record_learning_activity((int)$user['id'], (int)$mission['id'], 'game_completed', 'Completed network simulation with score ' . $score . '/100');
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'xp' => $first ? $points : 0, 'first' => $first]);
    exit;
}

?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($mission['title']) ?> | CyberGuard Academy</title>
<link rel="stylesheet" href="assets/css/style.css">
<style>
:root{--ng-bg:#030b16;--ng-panel:#061a2b;--ng-line:#0879a1;--ng-cyan:#19d8ff;--ng-green:#16f06a;--ng-amber:#ffc247}
body{background:radial-gradient(ellipse at 50% -20%,#123b5e 0,#061322 50%,#020812 100%);font-family:Manrope,Arial,sans-serif}
.topbar{min-height:60px;padding:9px 3%;background:#030d19;border-color:#0b4968}
.topbar nav a{font-size:11px}
.topbar nav .ng-exit{padding:9px 13px;border:1px solid #1598c0;border-radius:6px;background:#08263a;color:#c9f6ff;font:700 10px 'DM Mono',monospace;text-transform:uppercase;cursor:pointer}
.topbar nav .ng-exit:hover{background:#0b3c58;box-shadow:0 0 12px #00bfff33}
.ng-wrap{width:min(1500px,96%);margin:18px auto 34px}
.ng-heading{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:12px}
.ng-title{min-width:0}.ng-title h1{font-size:clamp(22px,3vw,34px);letter-spacing:-.7px;margin:6px 0}
.ng-title p{margin:0;font-size:12px}
.ng-back{white-space:nowrap;border:1px solid #197292;background:#09253a;color:#c5f5ff;padding:10px 14px;border-radius:6px;font-size:11px;font-weight:800}
.ng-hud{display:grid;grid-template-columns:repeat(4,1fr);gap:8px;margin:12px 0;padding:9px;background:#04101d;border:1px solid #0b5677;border-radius:10px;box-shadow:0 0 25px #009dff1a}
.ng-stat{text-align:center;border-right:1px solid #12415a}.ng-stat:last-child{border:0}
.ng-stat span{display:block;color:#78abc2;font:10px 'DM Mono',monospace;letter-spacing:1px}
.ng-stat strong{display:block;color:#f3fbff;font:700 clamp(18px,2.4vw,27px) 'DM Mono',monospace;margin-top:3px}
.ng-stat strong.green{color:var(--ng-green)}.ng-stat strong.amber{color:var(--ng-amber)}
.ng-layout{display:grid;grid-template-columns:minmax(0,1fr) 310px;gap:12px;align-items:stretch}
.ng-board-shell{min-width:0;background:#030d18;border:1px solid #0d6487;border-radius:11px;overflow:hidden;box-shadow:0 10px 38px #0008}
.ng-board-top{display:flex;align-items:center;justify-content:space-between;gap:8px;padding:10px 14px;background:#071b2b;border-bottom:1px solid #12445d}
.ng-board-top strong{font:11px 'DM Mono',monospace;letter-spacing:1.2px;color:#c8f4ff}
.ng-live{font:10px 'DM Mono',monospace;color:#55ff9a}.ng-live i{display:inline-block;width:7px;height:7px;border-radius:50%;background:#37ff7f;box-shadow:0 0 9px #37ff7f;margin-right:6px}
.ng-map{position:relative;min-height:560px;overflow:hidden;background-color:#04101b;background-image:linear-gradient(#0b345533 1px,transparent 1px),linear-gradient(90deg,#0b345533 1px,transparent 1px),radial-gradient(ellipse at 50% 50%,#0a2737 0,#061522 60%,#030b13 100%);background-size:28px 28px,28px 28px,100% 100%}
.ng-wires{position:absolute;inset:0;width:100%;height:100%;z-index:1;pointer-events:none}
.ng-wires path{fill:none;stroke:#0eea63;stroke-width:2;stroke-opacity:.72;filter:drop-shadow(0 0 3px #00ff65)}
.ng-wires path.alert-wire{stroke:#ffb433;stroke-dasharray:6 5;animation:wirePulse 1s linear infinite}
@keyframes wirePulse{to{stroke-dashoffset:-22}}
.ng-zone{position:absolute;border:1px solid #27c9ff45;border-radius:9px;background:#16a2ff12;z-index:0}
.ng-zone::before{content:attr(data-label);position:absolute;top:7px;left:9px;color:#84c8e3;font:9px 'DM Mono',monospace;letter-spacing:1px}
.zone-user{left:23%;top:7%;width:35%;height:34%;background:#6f24cf24;border-color:#9c4eff55}
.zone-core{left:59%;top:7%;width:35%;height:34%;background:#c5262622;border-color:#ff575755}
.zone-corp{left:3%;top:48%;width:46%;height:46%;background:#1bc55820;border-color:#24ef7755}
.zone-dmz{left:51%;top:48%;width:22%;height:46%;background:#fa9b1d17;border-color:#ffb54d55}
.zone-ics{left:75%;top:48%;width:22%;height:46%;background:#9e7c3929;border-color:#f8ce7455}
.ng-node{position:absolute;z-index:2;transform:translate(-50%,-50%);width:98px;min-height:66px;padding:6px 5px;border:1px solid #2e718c;border-radius:8px;background:linear-gradient(150deg,#12344a,#071522);color:#e5f7ff;cursor:pointer;text-align:center;box-shadow:0 4px 12px #0008;transition:transform .15s,border-color .15s,box-shadow .15s}
.ng-node:hover,.ng-node.selected{transform:translate(-50%,-50%) scale(1.06);border-color:#33e5ff;box-shadow:0 0 17px #05b9ff66}
.ng-node.selected{outline:2px solid #19d8ff88}
.ng-node .ng-icon{display:block;font-size:22px;line-height:24px}.ng-node b{display:block;font:9px 'DM Mono',monospace;line-height:1.25}
.ng-node small{display:block;margin-top:3px;color:#82abc0;font:8px 'DM Mono',monospace}
.ng-node.compromised{border-color:#ffbf40;box-shadow:0 0 15px #ffae2340}
.ng-node.compromised::after{content:'!';position:absolute;right:-5px;top:-8px;width:19px;height:19px;display:grid;place-items:center;background:#ffb629;color:#301800;border:2px solid #fff2c8;border-radius:50%;font-weight:900}
.ng-node.isolated{border-color:#7a94a5;opacity:.63}
.ng-node.protected{border-color:#24f17b;box-shadow:0 0 15px #19e7683b}
.n-internet{left:10%;top:34%}.n-gateway{left:23%;top:46%}.n-work1{left:32%;top:24%}.n-work2{left:47%;top:24%}
.n-termsrv{left:69%;top:24%}.n-wifi{left:58%;top:43%}.n-dmz{left:59%;top:56%}
.n-router{left:15%;top:68%}.n-switch{left:34%;top:66%}.n-ad{left:48%;top:78%}
.n-siem{left:29%;top:87%}.n-mail{left:43%;top:87%}.n-remote{left:68%;top:75%}.n-ics{left:85%;top:66%}.n-plc{left:85%;top:86%}
.ng-board-foot{display:flex;justify-content:space-between;gap:10px;align-items:center;padding:9px 13px;background:#061523;border-top:1px solid #11435a;color:#84aabd;font:9px 'DM Mono',monospace}
.ng-side{display:flex;flex-direction:column;min-height:100%;background:#04111f;border:1px solid #0d6487;border-radius:11px;overflow:hidden;box-shadow:0 10px 38px #0008}
.ng-side-head{padding:14px;background:linear-gradient(100deg,#09253b,#061320);border-bottom:1px solid #116182}
.ng-side-head span{font:9px 'DM Mono',monospace;color:#56dfff;letter-spacing:1.5px}
.ng-side-head h2{margin:5px 0 0;font:700 18px Manrope}
.ng-progress{height:5px;background:#123148;margin-top:11px;border-radius:4px;overflow:hidden}.ng-progress i{display:block;width:0;height:100%;background:linear-gradient(90deg,#08bdfa,#23fa73);transition:width .3s}
.ng-objectives{padding:8px 12px;overflow-y:auto;max-height:286px}
.ng-objective{display:grid;grid-template-columns:24px 1fr;gap:9px;align-items:center;padding:10px 2px;border-bottom:1px solid #102d40;color:#b9cddd;font-size:11px;line-height:1.4}
.ng-objective:last-child{border-bottom:0}.ng-objective .ng-check{width:21px;height:21px;border:1px solid #23617b;border-radius:5px;display:grid;place-items:center;color:#8eb0bf;font:11px 'DM Mono',monospace}
.ng-objective.active{color:#fff}.ng-objective.active .ng-check{border-color:#ffbe47;color:#ffbe47;box-shadow:0 0 10px #ffbe4733}
.ng-objective.done{color:#32ee79}.ng-objective.done .ng-check{background:#087d36;border-color:#31ee75;color:white}
.ng-objective small{display:block;color:#7a9cac;font-size:9px;margin-top:3px}
.ng-actions{padding:12px;border-top:1px solid #123448;background:#061523;margin-top:auto}
.ng-actions h3{margin:0 0 9px;color:#9ac8d9;font:10px 'DM Mono',monospace;letter-spacing:1px}
.ng-action-grid{display:grid;grid-template-columns:1fr 1fr;gap:7px}
.ng-action{min-height:46px;padding:7px 5px;border:1px solid #14516d;border-radius:6px;background:#0b2940;color:#d7f6ff;font:10px 'DM Mono',monospace;cursor:pointer}
.ng-action:hover:not(:disabled){background:#0d4056;border-color:#24c8f2;box-shadow:0 0 10px #00b9ff33}
.ng-action:disabled{opacity:.42;cursor:not-allowed}
.ng-action.primary-action{grid-column:span 2;background:#07552c;border-color:#13d15b;color:#effff4;font-weight:700}
.ng-log{min-height:120px;padding:11px 12px;border-top:1px solid #123448}
.ng-log h3{margin:0 0 7px;font:10px 'DM Mono',monospace;color:#62c7e4;letter-spacing:1px}
.ng-log-list{display:flex;flex-direction:column;gap:6px;max-height:100px;overflow:auto}
.ng-log-entry{font:9px/1.45 'DM Mono',monospace;color:#9bb8c8}.ng-log-entry strong{color:#59ed91}
.ng-selection{min-height:34px;margin:0 0 8px;padding:8px;border:1px solid #173c51;border-radius:5px;background:#05111e;color:#90b3c4;font:9px 'DM Mono',monospace}
.ng-result{display:none;margin:12px 0;padding:16px;border:1px solid #12c85a;border-radius:9px;background:#06281a;color:#d8ffe7}
.ng-result h2{color:#43fa89;font-size:21px;margin:0 0 6px}
.ng-result p{font-size:12px;margin:5px 0 12px}
.ng-result .btn{margin-top:5px}
.ng-copilot{margin:12px 0;padding:16px;border:1px solid #14516d;border-radius:9px;background:#061523}
.ng-copilot h2{margin:0 0 7px;font-size:17px}
.ng-copilot textarea{width:100%;margin:8px 0;padding:10px;border:1px solid #14516d;border-radius:6px;background:#04101d;color:#e5f7ff;font:12px Manrope;resize:vertical}
.ng-copilot-answer{margin-top:8px;color:#c5e1ee;white-space:pre-wrap;line-height:1.55}
.ng-modal-backdrop{display:none;position:fixed;inset:0;z-index:20;background:#020710dd;place-items:center;padding:20px}
.ng-modal{width:min(460px,100%);padding:24px;background:#071b2c;border:1px solid #1cbbe8;border-radius:12px;box-shadow:0 0 40px #00bfff33}
.ng-modal h2{margin:0;color:#fff}.ng-modal p{font-size:13px}.ng-modal-actions{display:flex;justify-content:flex-end;gap:8px;margin-top:18px}
@media(max-width:980px){.ng-layout{grid-template-columns:minmax(0,1fr) 280px}.ng-map{min-height:500px}.ng-node{width:82px;min-height:59px}.ng-node .ng-icon{font-size:19px}.ng-node b{font-size:8px}}
@media(max-width:720px){.ng-wrap{width:96%;margin:12px auto 22px}.ng-heading{align-items:flex-start}.ng-heading p{max-width:560px}.ng-layout{grid-template-columns:1fr}.ng-map{min-height:470px}.ng-side{min-height:0}.ng-objectives{max-height:230px}.ng-node{width:76px;min-height:55px;padding:5px 3px}.ng-node small{font-size:7px}.ng-zone::before{font-size:7px}.ng-action{min-height:42px}.ng-log{min-height:100px}}
@media(max-width:450px){.ng-title h1{font-size:21px}.ng-back{padding:8px;font-size:9px}.ng-map{min-height:410px}.ng-node{width:65px;min-height:51px}.ng-node .ng-icon{font-size:17px;line-height:20px}.ng-node b{font-size:7px}.ng-node small{display:none}.ng-zone::before{font-size:6px}.ng-stat span{font-size:8px}.ng-stat strong{font-size:17px}}
</style>
</head>
<body>
<header class="topbar">
  <a class="brand" href="index.php"><span class="brand-mark">CG</span> CYBERGUARD <small>ACADEMY</small></a>
  <nav><a href="missions.php">Mission Map</a><a href="dashboard.php">Dashboard</a><button class="ng-exit" id="exitGame" type="button">Exit Game</button></nav>
</header>
<main class="ng-wrap">
  <div class="ng-heading">
    <div class="ng-title">
      <div class="eyebrow">LIVE TRAINING SIMULATION / DEFENSIVE OPERATIONS</div>
      <h1><?= e($mission['title']) ?></h1>
      <p><?= e($mission['description']) ?> All devices and activity shown are fictional.</p>
    </div>
    <a class="ng-back" href="missions.php">← MISSION MAP</a>
  </div>
  <section class="ng-hud" aria-label="Game status">
    <div class="ng-stat"><span>TRAINING CREDITS</span><strong>$11,500</strong></div>
    <div class="ng-stat"><span>TURN</span><strong id="turn">01/05</strong></div>
    <div class="ng-stat"><span>MISSION TIMER</span><strong class="amber" id="timer">03:00</strong></div>
    <div class="ng-stat"><span>DEFENSE SCORE</span><strong class="green"><span id="score">0000</span></strong></div>
  </section>
  <div class="ng-layout">
    <section class="ng-board-shell" aria-label="Interactive simulated network map">
      <div class="ng-board-top"><strong>ACADEMY NETWORK / INCIDENT VIEW</strong><span class="ng-live"><i></i>SIMULATION ONLINE</span></div>
      <div class="ng-map" id="networkMap">
        <div class="ng-zone zone-user" data-label="USER WORKSTATIONS"></div>
        <div class="ng-zone zone-core" data-label="SERVER CORE"></div>
        <div class="ng-zone zone-corp" data-label="CORPORATE NETWORK"></div>
        <div class="ng-zone zone-dmz" data-label="DMZ"></div>
        <div class="ng-zone zone-ics" data-label="INDUSTRIAL TRAINING LAB"></div>
        <svg class="ng-wires" viewBox="0 0 1000 560" preserveAspectRatio="none" aria-hidden="true">
          <path d="M100 190 L230 255 L320 255 L320 132 M320 132 L470 132 L470 255 L580 255 L580 315 L690 315 L690 132 M230 255 L230 390 L150 390 L150 415 L340 415 L340 438 L480 438 L480 315 L580 315 M340 415 L340 490 M480 438 L480 490 M690 315 L690 410 L850 410 L850 485 M690 315 L850 315 L850 370"/>
          <path class="alert-wire" d="M690 132 L690 220 L580 255"/>
        </svg>
        <button class="ng-node n-internet" data-node="internet" type="button" aria-label="Internet gateway">
          <span class="ng-icon">🌐</span><b>INTERNET</b><small>EXTERNAL</small>
        </button>
        <button class="ng-node n-gateway" data-node="gateway" type="button" aria-label="Gateway firewall">
          <span class="ng-icon">🧱</span><b>GATEWAY FIREWALL</b><small>PROTECTED</small>
        </button>
        <button class="ng-node n-work1 protected" data-node="workstation-01" type="button" aria-label="User workstation 01">
          <span class="ng-icon">🖥️</span><b>WORKSTATION 01</b><small>SECURE</small>
        </button>
        <button class="ng-node n-work2 compromised" data-node="workstation-07" type="button" aria-label="User workstation 07, suspicious activity">
          <span class="ng-icon">🖥️</span><b>WORKSTATION 07</b><small>ALERT DETECTED</small>
        </button>
        <button class="ng-node n-termsrv" data-node="terminal-server" type="button" aria-label="Terminal server">
          <span class="ng-icon">🗄️</span><b>TERMINAL SERVER</b><small>SERVER CORE</small>
        </button>
        <button class="ng-node n-wifi" data-node="wifi-router" type="button" aria-label="Training Wi-Fi router">
          <span class="ng-icon">📡</span><b>WIFI ROUTER</b><small>INTERNAL</small>
        </button>
        <button class="ng-node n-dmz" data-node="dmz-firewall" type="button" aria-label="DMZ firewall">
          <span class="ng-icon">🧱</span><b>DMZ FIREWALL</b><small>EDGE CONTROL</small>
        </button>
        <button class="ng-node n-router" data-node="corp-router" type="button" aria-label="Corporate router">
          <span class="ng-icon">📶</span><b>CORPORATE ROUTER</b><small>CORE ROUTING</small>
        </button>
        <button class="ng-node n-switch" data-node="core-switch" type="button" aria-label="Core switch">
          <span class="ng-icon">🔀</span><b>CORE SWITCH</b><small>NETWORK CORE</small>
        </button>
        <button class="ng-node n-ad" data-node="ad-server" type="button" aria-label="Directory server">
          <span class="ng-icon">🗄️</span><b>DIRECTORY SERVER</b><small>IDENTITY</small>
        </button>
        <button class="ng-node n-siem" data-node="siem" type="button" aria-label="Security monitoring system">
          <span class="ng-icon">🛡️</span><b>SIEM MONITOR</b><small>EVENTS</small>
        </button>
        <button class="ng-node n-mail" data-node="mail-server" type="button" aria-label="Mail server">
          <span class="ng-icon">✉️</span><b>MAIL SERVER</b><small>MESSAGING</small>
        </button>
        <button class="ng-node n-remote" data-node="remote-user" type="button" aria-label="Remote training user">
          <span class="ng-icon">☁️</span><b>REMOTE USER</b><small>VPN SESSION</small>
        </button>
        <button class="ng-node n-ics" data-node="ics-sensor" type="button" aria-label="Industrial control sensor">
          <span class="ng-icon">📟</span><b>ICS SENSOR</b><small>LAB ONLY</small>
        </button>
        <button class="ng-node n-plc" data-node="training-plc" type="button" aria-label="Training controller">
          <span class="ng-icon">🏭</span><b>TRAINING PLC</b><small>SIMULATED</small>
        </button>
      </div>
      <div class="ng-board-foot"><span>SELECT A NODE, THEN CHOOSE A DEFENSIVE ACTION</span><span>NO REAL NETWORK CONNECTION</span></div>
    </section>
    <aside class="ng-side">
      <div class="ng-side-head"><span>INCIDENT RESPONSE PLAN</span><h2>Mission objectives</h2><div class="ng-progress"><i id="progress"></i></div></div>
      <div class="ng-objectives" id="objectives"></div>
      <div class="ng-actions">
        <h3>AVAILABLE DEFENSE ACTIONS</h3>
        <div class="ng-selection" id="selection">Select a network asset to begin.</div>
        <div class="ng-action-grid">
          <button class="ng-action" data-action="protect" type="button">🛡 Enable endpoint security</button>
          <button class="ng-action" data-action="isolate" type="button">⛔ Isolate endpoint</button>
          <button class="ng-action" data-action="logs" type="button">📋 Preserve audit logs</button>
          <button class="ng-action" data-action="firewall" type="button">🧱 Block suspicious route</button>
          <button class="ng-action primary-action" data-action="ir" type="button">🚨 Activate incident response</button>
        </div>
      </div>
      <div class="ng-log"><h3>OPERATIONS LOG</h3><div class="ng-log-list" id="log" aria-live="polite"></div></div>
    </aside>
  </div>
  <section class="ng-result" id="result" aria-live="polite"></section>
  <section class="ng-copilot">
    <h2>AI Learning Copilot</h2>
    <p class="muted">Ask about the defensive decisions in this fictional simulation. No video or tracking data is sent to the AI.</p>
    <textarea id="copilotPrompt" rows="2" maxlength="500" placeholder="Why should I preserve the logs before recovery?"></textarea>
    <button class="btn secondary" id="copilotAsk" type="button">Ask Copilot</button>
    <div id="copilotAnswer" class="ng-copilot-answer" aria-live="polite"></div>
  </section>
</main>
<div class="ng-modal-backdrop" id="confirm">
  <section class="ng-modal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <h2 id="modalTitle">Exit simulation?</h2>
    <p>Your current progress will not be recorded until all mission objectives are complete.</p>
    <div class="ng-modal-actions"><button class="btn secondary" id="stay" type="button">Keep playing</button><a class="btn primary" href="missions.php">Exit game</a></div>
  </section>
</div>
<script>
const CSRF=<?= json_encode(csrf_token()) ?>;
const MISSION_ID=<?= (int)$mission['id'] ?>;
const OBJECTIVES=[
  {label:'Secure workstation 07',hint:'Select Workstation 07 and enable endpoint security.',node:'workstation-07',action:'protect'},
  {label:'Contain the affected endpoint',hint:'Select Workstation 07 and isolate it from the simulated network.',node:'workstation-07',action:'isolate'},
  {label:'Preserve investigation evidence',hint:'Select the SIEM Monitor and preserve the audit logs.',node:'siem',action:'logs'},
  {label:'Block the suspicious network route',hint:'Select the DMZ Firewall and block the suspicious route.',node:'dmz-firewall',action:'firewall'},
  {label:'Activate incident response',hint:'Select any asset, then activate the incident response plan.',node:null,action:'ir'}
];
const $=id=>document.getElementById(id);
let selected=null,completed=0,score=0,turn=1,remaining=180,timerId=null,ended=false;
function addLog(message,success=false){
  const entry=document.createElement('div');entry.className='ng-log-entry';
  const time=new Date().toLocaleTimeString([],{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  const stamp=document.createElement('strong');stamp.textContent='['+time+'] ';
  entry.append(stamp,document.createTextNode(message));$('log').prepend(entry);
  while($('log').children.length>6)$('log').lastElementChild.remove();
}
function drawObjectives(){
  const root=$('objectives');root.replaceChildren();
  OBJECTIVES.forEach((objective,index)=>{
    const row=document.createElement('div');
    row.className='ng-objective'+(index<completed?' done':index===completed?' active':'');
    const check=document.createElement('span');check.className='ng-check';check.textContent=index<completed?'✓':String(index+1);
    const text=document.createElement('span');text.textContent=objective.label;
    const hint=document.createElement('small');hint.textContent=objective.hint;text.append(hint);
    row.append(check,text);root.append(row);
  });
  $('progress').style.width=(completed/OBJECTIVES.length*100)+'%';
  $('turn').textContent=String(Math.min(turn,OBJECTIVES.length)).padStart(2,'0')+'/'+String(OBJECTIVES.length).padStart(2,'0');
}
function setSelection(node){
  selected=node;
  document.querySelectorAll('.ng-node').forEach(button=>button.classList.toggle('selected',button.dataset.node===node));
  const button=document.querySelector('[data-node="'+node+'"]');
  $('selection').textContent=button?'SELECTED ASSET: '+button.querySelector('b').textContent:'Select an asset to begin.';
  document.querySelectorAll('[data-action]').forEach(action=>action.disabled=ended||!selected);
}
function completeObjective(action){
  const objective=OBJECTIVES[completed];
  if(!selected){addLog('Select a network asset before choosing an action.');return;}
  if(objective.node&&selected!==objective.node){
    addLog('Action not applied. '+objective.hint);
    $('selection').textContent='WRONG ASSET — '+objective.hint;
    return;
  }
  if(objective.action!==action){
    addLog('Action not applied. '+objective.hint);
    $('selection').textContent='WRONG ACTION — '+objective.hint;
    return;
  }
  const nodeButton=document.querySelector('[data-node="'+selected+'"]');
  completed++;turn++;score+=200;
  $('score').textContent=String(score).padStart(4,'0');
  if(window.pendingAction==='protect'){nodeButton.classList.add('protected');nodeButton.querySelector('small').textContent='SECURITY ACTIVE';}
  if(window.pendingAction==='isolate'){nodeButton.classList.add('isolated');nodeButton.querySelector('small').textContent='ISOLATED';}
  if(window.pendingAction==='firewall'){nodeButton.classList.add('protected');nodeButton.querySelector('small').textContent='ROUTE BLOCKED';}
  if(window.pendingAction==='logs')nodeButton.querySelector('small').textContent='EVIDENCE SAVED';
  if(window.pendingAction==='ir')document.querySelector('.ng-live').innerHTML='<i></i>RESPONSE ACTIVE';
  addLog('OBJECTIVE COMPLETE: '+objective.label,true);
  drawObjectives();
  if(completed===OBJECTIVES.length){finishGame();return;}
  $('selection').textContent='Next: '+OBJECTIVES[completed].hint;
}
function finishGame(){
  ended=true;clearInterval(timerId);
  document.querySelectorAll('[data-action]').forEach(button=>button.disabled=true);
  const finalScore=Math.round(score/1000*100);
  const result=$('result');result.style.display='block';result.innerHTML='';
  const heading=document.createElement('h2');heading.textContent='MISSION SUCCEEDED';
  const summary=document.createElement('p');summary.textContent='All network objectives are complete. Defense score: '+finalScore+'/100.';
  result.append(heading,summary);
  result.style.display='block';
  const form=new FormData();form.append('csrf_token',CSRF);form.append('score',finalScore);
  fetch(location.href,{method:'POST',body:form}).then(response=>{
    if(!response.ok)throw new Error('The mission score could not be saved. Please refresh and try again.');
    return response.json();
  }).then(data=>{
    summary.textContent+=' '+(data.first?'First completion reward: +'+data.xp+' XP.':'Replay recorded; first-completion XP was already awarded.');
    const link=document.createElement('a');link.className='btn primary';link.href='missions.php';link.textContent='Return to mission map';result.append(link);
    addLog('MISSION RESULT SAVED TO YOUR PROFILE.',true);
  }).catch(error=>{
    const message=document.createElement('p');message.textContent=error.message;result.append(message);
  });
  result.scrollIntoView({behavior:'smooth',block:'center'});
}
document.querySelectorAll('.ng-node').forEach(node=>node.addEventListener('click',()=>{
  if(!ended){setSelection(node.dataset.node);addLog('Asset selected: '+node.querySelector('b').textContent+'.');}
}));
document.querySelectorAll('[data-action]').forEach(button=>button.addEventListener('click',()=>{
  if(ended)return;
  completeObjective(button.dataset.action);
}));
$('exitGame').addEventListener('click',()=>$('confirm').style.display='grid');
$('stay').addEventListener('click',()=>$('confirm').style.display='none');
$('copilotAsk').addEventListener('click',async()=>{
  const button=$('copilotAsk'),answer=$('copilotAnswer'),form=new FormData();
  form.append('csrf_token',CSRF);form.append('mission_id',MISSION_ID);form.append('prompt',$('copilotPrompt').value);
  button.disabled=true;answer.textContent='Thinking…';
  try{
    const response=await fetch('copilot.php',{method:'POST',body:form}),data=await response.json();
    if(!response.ok)throw new Error(data.error||'The copilot could not answer right now.');
    answer.textContent=data.answer;
  }catch(error){answer.textContent=error.message;}finally{button.disabled=false;}
});
document.addEventListener('keydown',event=>{if(event.key==='Escape')$('confirm').style.display='none';});
drawObjectives();
setSelection(null);
addLog('Simulation started. Work through the response plan.');
timerId=setInterval(()=>{
  if(ended)return;
  remaining=Math.max(0,remaining-1);
  $('timer').textContent=String(Math.floor(remaining/60)).padStart(2,'0')+':'+String(remaining%60).padStart(2,'0');
  if(remaining===0){clearInterval(timerId);$('timer').classList.remove('amber');$('timer').classList.add('green');addLog('Training timer elapsed. Continue the simulation at your own pace.');}
},1000);
</script>
<script src="assets/js/app.js"></script>
</body>
</html>
