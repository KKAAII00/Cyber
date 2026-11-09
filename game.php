<?php
require_once 'includes/functions.php';
$user = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$q = $pdo->prepare('SELECT * FROM missions WHERE id=?'); $q->execute([$id]); $mission = $q->fetch();
$types = [
    'Phishing Inbox' => 'phishing',
    'Password Vault' => 'vault',
    'Forensic Timeline' => 'forensic',
    'Cipher Lab' => 'cipher',
    'Incident Response' => 'incident'
];
if (!$mission || !isset($types[$mission['game_type']])) { http_response_code(404); exit('Game not found.'); }
$kind = $types[$mission['game_type']];
if ($_SERVER['REQUEST_METHOD'] !== 'POST') record_learning_activity((int)$user['id'], (int)$mission['id'], 'mission_view', 'Opened interactive game');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $score  = max(0, min(100, (int)($_POST['score'] ?? 0)));
    $points = (int)floor(((int)$mission['xp_reward']) * ($score / 100));
    $pdo->beginTransaction();
    try {
        $a = $pdo->prepare('INSERT INTO mission_attempts(user_id,mission_id,score,total_questions,xp_earned) VALUES(?,?,?,?,?)');
        $a->execute([$user['id'], $mission['id'], $score, 100, $points]);
        $first = award_xp_once((int)$user['id'], (int)$mission['id'], $points);
        award_badges((int)$user['id']);
        $pdo->commit();
    } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
    record_learning_activity((int)$user['id'], (int)$mission['id'], 'game_completed', 'Completed interactive game with score ' . $score . '/100');
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'xp' => $first ? $points : 0, 'first' => $first]);
    exit;
}
page_header($mission['title']);
?>
<style>
.g-box{background:#0b1e33;border:1px solid #1d6fa5;border-radius:10px;padding:16px;margin-bottom:14px}
.g-grid{display:grid;grid-template-columns:300px 1fr;gap:14px}
@media(max-width:800px){.g-grid{grid-template-columns:1fr}}
.g-item{padding:10px;border:1px solid #16314d;border-radius:8px;margin-bottom:8px;cursor:pointer;background:#0f2740}
.g-item.sel{border-color:#4ade80}.g-item.done{opacity:.55}
.g-item small{display:block;color:#7fb4d6}
.g-btn{padding:8px 14px;border-radius:6px;border:1px solid #1d6fa5;background:#15406b;color:#fff;cursor:pointer;margin:4px 4px 4px 0}
.g-btn.red{background:#7f1d1d;border-color:#ef4444}.g-btn.green{background:#14532d;border-color:#22c55e}
.g-btn:disabled{opacity:.4;cursor:default}
.ok{color:#4ade80}.bad{color:#f87171}.warn{color:#f5b301}
.g-link{font-family:monospace;color:#93c5fd;text-decoration:underline;cursor:help}
.g-score{font-size:20px;font-weight:bold}
.g-in{width:100%;padding:8px;border-radius:6px;border:1px solid #1d6fa5;background:#07131f;color:#fff;font-family:monospace}
.g-rule{font-size:13px}
</style>
<a class="back-link" href="missions.php">← Mission map</a>
<div class="eyebrow">CHAPTER <?= (int)$mission['chapter'] ?> / <?= e($mission['game_type']) ?></div>
<h1><?= e($mission['title']) ?></h1>
<p class="muted"><?= e($mission['description']) ?></p>
<div class="g-box"><span class="g-score">SCORE: <span id="sc">0</span>/100</span> <span id="status" class="muted" style="margin-left:12px"></span></div>
<div id="game"></div>
<div id="end" class="g-box" style="display:none"></div>
<section class="g-box copilot-box">
  <h2>AI Learning Copilot</h2>
  <p class="muted">Ask for a hint or an explanation about this mission. Your prompt and the fictional mission context are sent to the configured AI provider.</p>
  <label for="copilotPrompt">Your question</label>
  <textarea id="copilotPrompt" rows="2" maxlength="500" class="g-in" placeholder="Why is this email suspicious?"></textarea>
  <button id="copilotAsk" class="g-btn green" type="button">Ask Copilot</button>
  <div id="copilotAnswer" class="copilot-answer" aria-live="polite"></div>
</section>

<script>
const KIND=<?= json_encode($kind) ?>, CSRF=<?= json_encode(csrf_token()) ?>, MISSION_ID=<?= (int)$mission['id'] ?>;
const $=id=>document.getElementById(id);
let score=0;
function setScore(v){score=Math.max(0,Math.min(100,Math.round(v)));$('sc').textContent=score;}
function finish(){
  const fd=new FormData();fd.append('csrf_token',CSRF);fd.append('score',score);
  const end=$('end');end.style.display='block';end.textContent='Saving result…';
  fetch(location.href,{method:'POST',body:fd}).then(r=>r.json()).then(r=>{
    end.innerHTML='<b>MISSION COMPLETE</b> — Score '+score+'/100. '+(r.first?'First completion reward: +'+r.xp+' XP. ':'Replay recorded (first-completion XP already awarded). ')+'<a class="btn small primary" href="missions.php">Mission map</a>';
  }).catch(()=>end.textContent='Could not save result.');
}
function el(t,c,h){const e=document.createElement(t);if(c)e.className=c;if(h!==undefined)e.textContent=h;return e;}
document.getElementById('copilotAsk').addEventListener('click',async()=>{
  const button=document.getElementById('copilotAsk'),answer=document.getElementById('copilotAnswer'),form=new FormData();
  form.append('csrf_token',CSRF);form.append('mission_id',MISSION_ID);form.append('prompt',document.getElementById('copilotPrompt').value);
  button.disabled=true;answer.textContent='Thinking…';
  try{
    const response=await fetch('copilot.php',{method:'POST',body:form}),data=await response.json();
    if(!response.ok)throw new Error(data.error||'The copilot could not answer right now.');
    answer.textContent=data.answer;
  }catch(error){answer.textContent=error.message;}finally{button.disabled=false;}
});

/* ---------- PHISHING INBOX ---------- */
function phishing(){
  const M=[
   {f:'security@paypa1-support.com',s:'URGENT: Your account is locked',b:'We detected suspicious activity. Verify within 24 hours or your account will be closed permanently.',l:'https://paypa1-verify.com/login',p:true,w:'Look-alike domain (the "l" is a number 1), urgent threat, and a link to an unrelated site.'},
   {f:'hr@cyberguard.academy',s:'Timetable update for Week 8',b:'Hi all, the Week 8 lab timetable has been updated. Please check the portal.',l:'https://cyberguard.academy/timetable',p:false,w:'Real academy domain, no pressure, link matches the sender.'},
   {f:'ceo@cyberguard-academy.co',s:'Quick favour — keep this private',b:'I am in a meeting. Please buy 5 gift cards for a client and send me the codes. Do not tell anyone.',l:'(no link)',p:true,w:'CEO fraud: secrecy, gift cards, slightly different domain (.co with hyphen).'},
   {f:'no-reply@github.com',s:'New sign-in from Chrome on Windows',b:'We noticed a new sign-in. If this was you, no action is needed.',l:'https://github.com/settings/security',p:false,w:'Genuine domain, informational, link goes to the same domain.'},
   {f:'prize@lucky-draw.biz',s:'You WON a laptop!!!',b:'Congratulations! Pay just $5 shipping to claim your free laptop today.',l:'http://lucky-draw.biz/claim',p:true,w:'Too good to be true, asks for payment, unknown sender.'},
   {f:'it-helpdesk@cyberguard.academy',s:'Password expires in 3 days',b:'Please reset your password using the secure link below to avoid losing access.',l:'http://cyberguard.academy.reset-pass.xyz/login',p:true,w:'Sender looks real but the link\'s real domain is reset-pass.xyz — the academy name is only a subdomain.'}
  ];
  let cur=0,done=0,right=0;
  const root=$('game'),g=el('div','g-grid'),list=el('div'),view=el('div','g-box');g.append(list,view);root.append(g);
  function drawList(){list.innerHTML='';M.forEach((m,i)=>{const d=el('div','g-item'+(i===cur?' sel':'')+(m.d?' done':''));d.append(el('b','',m.s),el('small','',m.f));d.onclick=()=>{cur=i;drawList();drawMail();};list.append(d);});}
  function drawMail(){
    const m=M[cur];view.innerHTML='';
    view.append(el('div','muted','From: '+m.f),el('h3','',m.s),el('p','',m.b));
    const lp=el('p');lp.append('Link: ');const a=el('span','g-link',m.l);a.title='Hover shows the real destination: '+m.l;lp.append(a);view.append(lp);
    if(m.d){view.append(el('p',m.ok?'ok':'bad',(m.ok?'✓ Correct. ':'✕ Wrong. ')+m.w));return;}
    const r=el('button','g-btn red','Report as phishing'),s=el('button','g-btn green','Mark as safe');
    r.onclick=()=>judge(true);s.onclick=()=>judge(false);view.append(r,s);
  }
  function judge(sayPhish){
    const m=M[cur];m.d=true;m.ok=(sayPhish===m.p);done++;if(m.ok)right++;
    setScore(right/M.length*100);$('status').textContent=done+'/'+M.length+' emails checked';
    drawList();drawMail();
    if(done===M.length)finish();
  }
  drawList();drawMail();$('status').textContent='Inspect each email: sender, urgency, link.';
}

/* ---------- PASSWORD VAULT ---------- */
function vault(){
  const COMMON=['password','123456','qwerty','letmein','admin','welcome','iloveyou'];
  const A=[{n:'Student Email',u:'agent_nova',min:12},{n:'Bank Portal',u:'nova.finance',min:14},{n:'Social Media',u:'novagram',min:12},{n:'Work VPN',u:'nova-vpn',min:16}];
  let secured=0,penalty=0;
  const root=$('game');
  function rules(pw,a){
    const low=pw.toLowerCase();
    return [
     ['At least '+a.min+' characters',pw.length>=a.min],
     ['Uppercase letter',/[A-Z]/.test(pw)],['Lowercase letter',/[a-z]/.test(pw)],
     ['A number',/\d/.test(pw)],['A symbol',/[^A-Za-z0-9]/.test(pw)],
     ['No common word',!COMMON.some(c=>low.includes(c))],
     ['Does not contain username',!low.includes(a.u.toLowerCase().replace(/[^a-z]/g,''))]
    ];
  }
  A.forEach(a=>{
    const box=el('div','g-box');box.append(el('h3','','🔒 '+a.n),el('div','muted','Username: '+a.u));
    const inp=el('input','g-in');inp.placeholder='Create a new password';const rl=el('div'),msg=el('p'),b=el('button','g-btn green','Secure account');
    function upd(){rl.innerHTML='';rules(inp.value,a).forEach(([t,ok])=>rl.append(el('div','g-rule '+(ok?'ok':'bad'),(ok?'✓ ':'✕ ')+t)));}
    inp.oninput=upd;upd();
    b.onclick=()=>{
      if(rules(inp.value,a).every(r=>r[1])){secured++;b.disabled=true;inp.disabled=true;msg.className='ok';msg.textContent='Account secured ✓ Tip: also enable MFA and use a password manager.';
        setScore(secured/A.length*100-penalty);$('status').textContent=secured+'/'+A.length+' accounts secured';
        if(secured===A.length)finish();}
      else{penalty+=5;setScore(secured/A.length*100-penalty);msg.className='bad';msg.textContent='Rejected: password is too weak. (-5)';}
    };
    box.append(inp,rl,msg,b);root.append(box);
  });
  $('status').textContent='Secure all four accounts. Weak submissions cost points.';
}

/* ---------- FORENSIC TIMELINE ---------- */
function forensic(){
  const E=[
   {t:'09:58',x:'40 failed SSH logins for user svc-backup from 203.0.113.9'},
   {t:'10:02',x:'Successful SSH login for svc-backup from 203.0.113.9'},
   {t:'10:07',x:'New admin account "sysupd" created'},
   {t:'10:11',x:'2 GB outbound transfer to 203.0.113.9'},
   {t:'10:15',x:'System log files deleted'}
  ];
  const order=E.map((e,i)=>i).sort(()=>Math.random()-.5);
  const root=$('game'),box=el('div','g-box');box.append(el('h3','','Step 1 — Put the log in chronological order'),el('p','muted','Timestamps are hidden. Use the clues in each event and the arrows to reorder, like a real investigator would.'));
  const list=el('div');box.append(list);
  function draw(){list.innerHTML='';order.forEach((idx,pos)=>{const d=el('div','g-item');d.append(el('span','',E[idx].x));
    const up=el('button','g-btn','↑'),dn=el('button','g-btn','↓');
    up.onclick=()=>{if(pos>0){[order[pos],order[pos-1]]=[order[pos-1],order[pos]];draw();}};
    dn.onclick=()=>{if(pos<order.length-1){[order[pos],order[pos+1]]=[order[pos+1],order[pos]];draw();}};
    const r=el('div');r.append(up,dn);d.append(r);list.append(d);});}
  draw();
  const sub=el('button','g-btn green','Submit timeline'),res=el('p');box.append(sub,res);root.append(box);
  const q=el('div','g-box');q.style.display='none';root.append(q);
  sub.onclick=()=>{
    let c=0;order.forEach((idx,pos)=>{if(idx===pos)c++;});
    sub.disabled=true;setScore(c/E.length*60);
    res.className=c===E.length?'ok':'warn';
    res.textContent=c===E.length?'Perfect order!':c+'/'+E.length+' in the right place. Correct order: '+E.map(e=>e.t+' '+e.x).join(' → ');
    q.style.display='block';q.append(el('h3','','Step 2 — How did the attacker most likely get in?'));
    [['A zero-day exploit in the OS',0],['Brute-forcing a weak service-account password',1],['An insider with a USB drive',0],['SQL injection on the website',0]].forEach(([t,ok])=>{
      const b=el('button','g-btn',t);b.onclick=()=>{
        q.querySelectorAll('button').forEach(x=>x.disabled=true);
        if(ok){setScore(score+40);q.append(el('p','ok','✓ Correct: many failed logins followed by a success points to brute force. Fix: strong passwords, lockout, MFA, no password-based SSH for service accounts.'));}
        else q.append(el('p','bad','✕ Not quite. Many failed logins followed by a success from the same IP indicates brute-forcing a weak password.'));
        finish();};
      q.append(b);});
  };
  $('status').textContent='Reconstruct the attack.';
}

/* ---------- CIPHER LAB ---------- */
function cipher(){
  const M=[['KHOOR','HELLO'],['JXDUG','GUARD'],['VDIH','SAFE'],['WUDLQ','TRAIN']];
  let answered=0,right=0;
  const root=$('game'),box=el('div','g-box');
  box.append(el('h3','','Decode four training messages'),el('p','muted','Each message uses a Caesar shift of 3. Shift every letter back three places.'));
  M.forEach(([encoded,answer],i)=>{
    const row=el('div','g-box'),label=el('label','','Message '+(i+1)+': '+encoded+' ');
    const input=el('input','g-in');input.autocomplete='off';input.setAttribute('aria-label','Decoded message '+(i+1));
    const button=el('button','g-btn green','Check'),result=el('p');
    button.onclick=()=>{
      if(button.disabled)return;
      button.disabled=true;input.disabled=true;answered++;
      const correct=input.value.trim().toUpperCase()===answer;
      if(correct)right++;
      result.className=correct?'ok':'bad';
      result.textContent=correct?'Correct.':'Not quite. The decoded word is '+answer+'.';
      setScore(right/M.length*100);
      $('status').textContent=answered+'/'+M.length+' messages decoded';
      if(answered===M.length)finish();
    };
    label.append(input);row.append(label,button,result);box.append(row);
  });
  root.append(box);$('status').textContent='Decode each message using the stated shift.';
}

/* ---------- INCIDENT RESPONSE ---------- */
function incident(){
  const BANK=[
    {
      name:'Responder',
      questions:[
        {q:'One training workstation shows a simulated ransomware note. What is the safest first move?',o:['Isolate the affected training workstation and notify the incident lead','Turn off every system in the academy','Open the note attachment to find its source'],a:0,e:'Contain the affected training endpoint and notify the response lead. This is a fictional scenario; no files are encrypted.'},
        {q:'A monitoring alert reports an unfamiliar login, but its source is not confirmed. What should you do?',o:['Verify the alert in approved monitoring and preserve its details','Disable all student accounts immediately','Assume it is a false alarm and delete it'],a:0,e:'Verify the alert through a trusted monitoring source and preserve evidence before deciding on broader action.'},
        {q:'When should a recovered training computer return to the simulated network?',o:['After the response plan verifies it is clean and safe to reconnect','As soon as the warning disappears','Before anyone reviews the incident logs'],a:0,e:'Recovery should follow the approved plan and include verification before reconnecting.'},
        {q:'A teammate asks you to send them the affected student’s password to speed up recovery. What is the safe response?',o:['Do not share credentials; use the approved account recovery process','Send it only in the team chat','Post it in the incident log'],a:0,e:'Never share passwords. Use the authorized recovery process and preserve a record of the response.'}
      ]
    },
    {
      name:'Advanced',
      questions:[
        {q:'A simulated ransomware alert affects one workstation, and that workstation can reach shared training backups. What should the response team prioritize?',o:['Contain the workstation and protect backup access under the incident plan','Immediately restore every machine from the reachable backups','Delete the alert and wait for more devices to fail'],a:0,e:'Contain the affected endpoint and protect backup systems from further exposure while the team verifies scope and recovery options.'},
        {q:'A student reports approving an unexpected MFA prompt, followed by a suspicious admin-session alert. What is the best coordinated action?',o:['Use the identity response process to revoke suspicious sessions, secure the account, and preserve audit logs','Ask the student to approve another prompt to test it','Delete the account before preserving any evidence'],a:0,e:'Secure the account and revoke suspect sessions through approved identity controls while retaining logs for investigation.'},
        {q:'An outbound-transfer alert may be a false positive, but the destination is not recognized. What should responders do next?',o:['Check authorized telemetry to establish scope, then apply a narrowly scoped containment action if confirmed','Scan unrelated public networks for the same destination','Block all internet traffic across the organization immediately'],a:0,e:'Validate the signal using authorized telemetry and choose a proportionate, scoped containment action.'},
        {q:'A team member proposes wiping the affected training device before collecting logs. What is the main concern?',o:['Wiping may destroy evidence needed to understand scope and cause','Wiping always improves evidence quality','Logs are not useful once a device is isolated'],a:0,e:'Preserve relevant evidence before remediation when it is safe and consistent with the incident plan.'}
      ]
    },
    {
      name:'Expert',
      questions:[
        {q:'An industrial training controller reports abnormal commands while the lab is running a safety-critical exercise. What should the incident lead do?',o:['Coordinate with the authorized operations/safety lead and follow the lab containment plan before changing controller state','Power off every controller immediately without coordination','Continue the exercise and investigate after class'],a:0,e:'In operational environments, coordinate containment with authorized safety/operations leads; avoid unplanned changes that could create hazards.'},
        {q:'A known-good backup is available, but the team has not confirmed whether the attacker’s access path is closed. What is the safest recovery decision?',o:['Keep the asset contained, investigate and close the access path, then validate before restoration','Restore immediately because the backup is known-good','Reconnect the original device and monitor it casually'],a:0,e:'A clean backup alone is not enough. Address the access path and validate the recovery before reconnecting.'},
        {q:'An incident may involve sensitive student records. How should the response team handle evidence?',o:['Preserve only relevant evidence with restricted access and follow the designated privacy/legal process','Copy all student files to a shared room folder','Send screenshots to personal accounts for safekeeping'],a:0,e:'Minimize access to relevant evidence, maintain controlled handling, and follow the organization’s privacy and legal procedures.'},
        {q:'Two sources disagree about whether training data left the simulated environment. What should the incident commander report?',o:['Separate confirmed facts from uncertainty, document sources, and continue scoped verification','State that exfiltration definitely happened to be safe','Close the incident because the evidence conflicts'],a:0,e:'Communicate what is known and unknown, keep a traceable record, and continue a scoped investigation.'}
      ]
    }
  ];
  const QUESTIONS_PER_LEVEL=3,TOTAL_QUESTIONS=BANK.length*QUESTIONS_PER_LEVEL;
  let level=0,index=0,right=0,round=[],stageReady=false;
  const root=$('game'),box=el('div','g-box');
  box.append(el('div','eyebrow','TABLETOP SIMULATION — NO REAL SYSTEMS OR FILES ARE AFFECTED'));
  const levelLabel=el('div','question-number'),question=el('h3'),options=el('div'),feedback=el('p'),next=el('button','g-btn green','Continue');
  box.append(levelLabel,question,options,feedback,next);root.append(box);
  function shuffled(items){
    const result=[...items];
    for(let i=result.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[result[i],result[j]]=[result[j],result[i]];}
    return result;
  }
  function startLevel(){
    round=shuffled(BANK[level].questions).slice(0,QUESTIONS_PER_LEVEL);
    index=0;draw();
  }
  function draw(){
    const item=round[index];levelLabel.textContent='DIFFICULTY '+BANK[level].name.toUpperCase()+' / LEVEL '+(level+1)+' OF '+BANK.length;
    question.textContent='Decision '+(index+1)+' / '+QUESTIONS_PER_LEVEL+': '+item.q;
    options.innerHTML='';feedback.textContent='';next.disabled=true;
    next.textContent='Continue';
    const choices=shuffled(item.o.map((text,choice)=>({text,correct:choice===item.a})));
    choices.forEach(choice=>{
      const button=el('button','g-btn',choice.text);
      button.onclick=()=>{
        options.querySelectorAll('button').forEach(b=>b.disabled=true);
        if(choice.correct)right++;
        setScore(right/TOTAL_QUESTIONS*100);
        feedback.className=choice.correct?'ok':'bad';feedback.textContent=(choice.correct?'Correct. ':'Review: ')+item.e;
        next.disabled=false;$('status').textContent='Level '+(level+1)+' · decision '+(index+1)+'/'+QUESTIONS_PER_LEVEL;
      };
      options.append(button);
    });
  }
  next.onclick=()=>{
    if(stageReady){stageReady=false;startLevel();return;}
    if(index<QUESTIONS_PER_LEVEL-1){index++;draw();return;}
    if(level<BANK.length-1){
      level++;index=0;stageReady=true;
      next.textContent='Generate Level '+(level+1)+' — '+BANK[level].name;
      next.disabled=false;
      question.textContent='Level '+(level+1)+' challenge ready';
      options.replaceChildren();
      feedback.className='muted';
      feedback.textContent='You cleared the previous round. The next round adds more complex evidence, coordination, and recovery decisions.';
      $('status').textContent='Ready to advance to '+BANK[level].name+' difficulty.';
      return;
    }
    finish();
  };
  startLevel();$('status').textContent='Answer the Responder round to unlock Advanced and Expert challenges.';
}
({phishing,vault,forensic,cipher,incident})[KIND]();
</script>
<?php page_footer(); ?>
