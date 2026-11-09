<?php
require_once 'includes/functions.php';
$user = require_login();
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$q = $pdo->prepare('SELECT * FROM missions WHERE id = ?');
$q->execute([$id]);
$mission = $q->fetch();
if (!$mission) {
    http_response_code(404);
    exit('Mission not found.');
}

$games = ['Phishing Inbox', 'Password Vault', 'Forensic Timeline', 'Cipher Lab', 'Incident Response'];
if (in_array($mission['game_type'], $games, true)) {
    redirect('game.php?id=' . $id);
}
if ($mission['game_type'] === 'Network Simulation') {
    redirect('netgame.php?id=' . $id);
}

$data = json_decode($mission['content_json'], true);
if (!$data || empty($data['questions'])) {
    exit('Mission content is unavailable.');
}

$adaptiveLevels = [
    'Phishing Detective' => [
        [
            'name' => 'Advanced',
            'questions' => [
                [
                    'question' => 'A student entered their password into a page linked from an urgent email. What should they do first?',
                    'options' => [
                        'Report it through a known official channel, then secure the account through the trusted portal',
                        'Reply to the email and ask whether the page was genuine',
                        'Wait to see whether the account behaves differently'
                    ],
                    'answer' => 0,
                    'explanation' => 'Report the possible exposure using a trusted channel, then follow the official account recovery process. Do not reuse the suspicious link.'
                ],
                [
                    'question' => 'A message displays the correct campus logo, but its sender address is unfamiliar. What is the strongest way to verify it?',
                    'options' => [
                        'Trust it because the logo looks familiar',
                        'Contact the organization using a known official address or portal',
                        'Open the link in a private browser window'
                    ],
                    'answer' => 1,
                    'explanation' => 'Logos can be copied. Verify unexpected requests independently through a trusted channel.'
                ]
            ]
        ],
        [
            'name' => 'Expert',
            'questions' => [
                [
                    'question' => 'Several students report similar credential requests, but the messages have different display names. What is the best next step?',
                    'options' => [
                        'Preserve and report the messages through the approved process, then let responders compare their details',
                        'Forward the messages to classmates so everyone can inspect them',
                        'Delete them immediately without reporting'
                    ],
                    'answer' => 0,
                    'explanation' => 'Preserved reports allow authorized responders to compare indicators while limiting further exposure.'
                ],
                [
                    'question' => 'A student shared a one-time sign-in code after a caller claimed to be support. Which response is safest?',
                    'options' => [
                        'Share the next code only if the caller knows the student’s name',
                        'Use a known official channel to report it and secure the account; never disclose another code',
                        'Post the code in the class channel so the team can check it'
                    ],
                    'answer' => 1,
                    'explanation' => 'One-time codes are secrets. Report the disclosure and use the official account-security process.'
                ]
            ]
        ]
    ],
    'Password Master' => [
        [
            'name' => 'Advanced',
            'questions' => [
                [
                    'question' => 'A password manager account is protected by a strong unique password. Which additional setup most improves account recovery readiness?',
                    'options' => [
                        'Enable a supported second factor and store recovery codes securely',
                        'Reuse the manager password on the student portal',
                        'Send the master password to a trusted classmate'
                    ],
                    'answer' => 0,
                    'explanation' => 'A second factor adds protection, and securely stored recovery codes help avoid lockout without sharing credentials.'
                ],
                [
                    'question' => 'A service announces that user passwords may have been exposed. What should a user do?',
                    'options' => [
                        'Change the affected unique password through the official service and review account sessions',
                        'Change every account to the same new password',
                        'Wait until someone reports unauthorized access'
                    ],
                    'answer' => 0,
                    'explanation' => 'Secure the affected account through its trusted channel and review available session controls. Unique passwords limit spillover.'
                ]
            ]
        ],
        [
            'name' => 'Expert',
            'questions' => [
                [
                    'question' => 'An organization must choose between storing passwords with reversible encryption or a dedicated password-hashing scheme. Which is appropriate?',
                    'options' => [
                        'A modern password-hashing scheme with a unique salt and appropriate work factor',
                        'Reversible encryption so staff can retrieve forgotten passwords',
                        'A fast unsalted hash so sign-ins use fewer resources'
                    ],
                    'answer' => 0,
                    'explanation' => 'Password verification should use a purpose-built, slow password hash with a unique salt, not a recoverable password store.'
                ],
                [
                    'question' => 'A user asks support to email their existing password because they forgot it. What should support do?',
                    'options' => [
                        'Send it after confirming the user’s birth date',
                        'Use the approved password-reset flow without revealing or retrieving the old password',
                        'Send it to the user’s manager instead'
                    ],
                    'answer' => 1,
                    'explanation' => 'Passwords should not be retrievable or shared. Use a verified reset process that sets a new secret.'
                ]
            ]
        ]
    ],
    'Network Defender' => [
        [
            'name' => 'Advanced',
            'questions' => [
                [
                    'question' => 'A fictional lab endpoint contacts an unfamiliar destination, but only one device is implicated. What is the most proportionate response?',
                    'options' => [
                        'Validate the alert with authorized telemetry, then apply scoped containment if confirmed',
                        'Block all internet access for the entire academy immediately',
                        'Ignore it until several devices show the same alert'
                    ],
                    'answer' => 0,
                    'explanation' => 'Confirm the alert with approved data and keep containment scoped to the affected asset when appropriate.'
                ],
                [
                    'question' => 'A teammate proposes deleting network logs to reduce noise during an investigation. What should you do?',
                    'options' => [
                        'Preserve relevant logs and follow the incident process for filtering or analysis',
                        'Delete them if the endpoint is already isolated',
                        'Copy all logs to a personal device'
                    ],
                    'answer' => 0,
                    'explanation' => 'Relevant logs support investigation and should be handled through approved, access-controlled procedures.'
                ]
            ]
        ],
        [
            'name' => 'Expert',
            'questions' => [
                [
                    'question' => 'A containment rule could disrupt a safety-critical training service. What is the responsible next action?',
                    'options' => [
                        'Coordinate with the authorized operations lead and follow the approved containment plan',
                        'Apply the broadest block immediately without informing anyone',
                        'Leave the alert unaddressed until the exercise ends'
                    ],
                    'answer' => 0,
                    'explanation' => 'Coordinate with authorized operations and safety staff so containment does not create additional risk.'
                ],
                [
                    'question' => 'An alert is not yet confirmed, and unrelated systems share the same network segment. Which action best balances safety and evidence?',
                    'options' => [
                        'Document and validate the signal, then make the narrowest justified change under the incident plan',
                        'Scan external systems to identify the destination owner',
                        'Disable all network monitoring to prevent false positives'
                    ],
                    'answer' => 0,
                    'explanation' => 'Use authorized telemetry and proportionate, documented actions; do not expand investigation beyond approved scope.'
                ]
            ]
        ]
    ],
    'Digital Forensics' => [
        [
            'name' => 'Advanced',
            'questions' => [
                [
                    'question' => 'Two audit records conflict about when a simulated account was accessed. How should the investigator proceed?',
                    'options' => [
                        'Preserve both records, note their sources and time context, and verify against authorized evidence',
                        'Delete the record that seems less likely',
                        'Choose one timestamp and present it as certain'
                    ],
                    'answer' => 0,
                    'explanation' => 'Keep source context, document uncertainty, and verify using authorized evidence before drawing conclusions.'
                ],
                [
                    'question' => 'A teammate wants to inspect a student’s unrelated personal files while investigating a training account. What is appropriate?',
                    'options' => [
                        'Limit collection to relevant, authorized evidence and follow privacy procedures',
                        'Copy the full device so nothing is missed',
                        'Share the files with the team for an informal review'
                    ],
                    'answer' => 0,
                    'explanation' => 'Investigations should minimize collection and access to what is relevant and authorized.'
                ]
            ]
        ],
        [
            'name' => 'Expert',
            'questions' => [
                [
                    'question' => 'An evidence file must be transferred to an authorized investigator. What best supports trustworthy handling?',
                    'options' => [
                        'Use the approved restricted transfer method and document who handled it and when',
                        'Send it through a personal chat for speed',
                        'Rename it and remove its source notes'
                    ],
                    'answer' => 0,
                    'explanation' => 'Controlled transfer and documented handling help preserve evidence integrity and accountability.'
                ],
                [
                    'question' => 'A timeline contains one confirmed event and one unverified interpretation. How should the report present them?',
                    'options' => [
                        'Clearly distinguish confirmed observations from hypotheses and cite their sources',
                        'Combine them into one definitive conclusion',
                        'Omit all uncertainty so the report is easier to read'
                    ],
                    'answer' => 0,
                    'explanation' => 'Separating facts from interpretations makes a report accurate, fair and easier to verify.'
                ]
            ]
        ]
    ],
    'Cryptography' => [
        [
            'name' => 'Advanced',
            'questions' => [
                [
                    'question' => 'A team needs to detect whether a downloaded training file changed. Which property should it verify?',
                    'options' => [
                        'Compare a trusted cryptographic hash of the file',
                        'Encrypt the file with a Caesar cipher',
                        'Rename the file and check whether it opens'
                    ],
                    'answer' => 0,
                    'explanation' => 'A trusted cryptographic hash can help verify file integrity; it does not itself encrypt or authenticate the source.'
                ],
                [
                    'question' => 'Why must encryption keys be protected separately from encrypted data?',
                    'options' => [
                        'Anyone who obtains the key may be able to decrypt the protected data',
                        'The key is only needed to make the file name readable',
                        'Encryption remains safe even when its key is public'
                    ],
                    'answer' => 0,
                    'explanation' => 'Key management is essential: access to the key can undermine confidentiality.'
                ]
            ]
        ],
        [
            'name' => 'Expert',
            'questions' => [
                [
                    'question' => 'A system needs confidentiality and tamper detection for stored data. What should its design rely on?',
                    'options' => [
                        'A reviewed authenticated-encryption approach with securely managed keys',
                        'A home-made cipher and a key saved beside every file',
                        'A fast hash alone, because hashes encrypt data'
                    ],
                    'answer' => 0,
                    'explanation' => 'Authenticated encryption is designed to provide confidentiality and integrity when implemented and keyed correctly.'
                ],
                [
                    'question' => 'A developer suggests inventing a new cipher instead of using a reviewed standard. What is the strongest concern?',
                    'options' => [
                        'Unreviewed designs can contain subtle weaknesses; use established, reviewed cryptography',
                        'Standard algorithms are always too slow for any application',
                        'A secret algorithm removes the need to protect its key'
                    ],
                    'answer' => 0,
                    'explanation' => 'Use established cryptographic designs and sound key management rather than relying on secrecy or an unreviewed algorithm.'
                ]
            ]
        ]
    ],
    'Final Incident Response' => [
        [
            'name' => 'Advanced',
            'questions' => [
                [
                    'question' => 'A suspicious email, possible account exposure and one endpoint alert may be related. What should the response lead do?',
                    'options' => [
                        'Coordinate triage, contain confirmed affected assets, preserve relevant evidence and communicate through approved channels',
                        'Delete all related accounts and logs immediately',
                        'Assume all three signals are unrelated and close the incident'
                    ],
                    'answer' => 0,
                    'explanation' => 'A coordinated, evidence-based response can reduce impact while preserving the information needed to understand scope.'
                ],
                [
                    'question' => 'A clean backup is available, but the suspected access path has not been addressed. What is the safest recovery choice?',
                    'options' => [
                        'Keep the affected asset contained until the access path is addressed and recovery is validated',
                        'Restore it immediately because the backup is clean',
                        'Reconnect it and watch for another alert'
                    ],
                    'answer' => 0,
                    'explanation' => 'A clean backup does not remove the cause of compromise. Validate containment and recovery before reconnecting.'
                ]
            ]
        ],
        [
            'name' => 'Expert',
            'questions' => [
                [
                    'question' => 'The incident may involve sensitive student records, but the scope is uncertain. What should the lead communicate?',
                    'options' => [
                        'State confirmed facts and uncertainties separately, restrict evidence access, and follow privacy escalation procedures',
                        'Share all records with the response room so everyone can decide',
                        'Announce a confirmed breach before the evidence is reviewed'
                    ],
                    'answer' => 0,
                    'explanation' => 'Use careful, factual communication and limit evidence access while following the designated privacy and escalation process.'
                ],
                [
                    'question' => 'A containment step could interrupt a critical service. Who should be involved before changing its state?',
                    'options' => [
                        'The authorized operations or safety lead, following the incident plan',
                        'Any student who is available',
                        'No one; broad containment is always harmless'
                    ],
                    'answer' => 0,
                    'explanation' => 'Coordinate potentially disruptive containment with authorized operations and safety owners.'
                ]
            ]
        ]
    ]
];

$baseQuestions = $data['questions'];
$levels = [['name' => 'Foundation', 'questions' => $baseQuestions]];
foreach ($adaptiveLevels[$mission['game_type']] ?? [] as $level) {
    $levels[] = $level;
}
$questions = [];
$questionLevels = [];
$clientLevels = [];
foreach ($levels as $levelIndex => $level) {
    $clientLevels[$levelIndex] = ['name' => $level['name'], 'questions' => []];
    foreach ($level['questions'] as $question) {
        $questions[] = $question;
        $questionLevels[] = $levelIndex;
        $clientLevels[$levelIndex]['questions'][] = [
            'serverIndex' => count($questions) - 1,
            'question' => $question['question'],
            'options' => array_values($question['options'])
        ];
    }
}
$totalQuestions = count($questions);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'track_answer') {
    header('Content-Type: application/json; charset=utf-8');
    verify_csrf();
    $questionIndex = filter_var($_POST['question_index'] ?? null, FILTER_VALIDATE_INT);
    $selected = filter_var($_POST['answer'] ?? null, FILTER_VALIDATE_INT);
    if ($questionIndex === false || $selected === false || !isset($questions[$questionIndex]) || !isset($questions[$questionIndex]['options'][$selected])) {
        http_response_code(400);
        echo json_encode(['error' => 'That answer could not be recorded. Refresh the mission and try again.']);
        exit;
    }
    $question = $questions[$questionIndex];
    $levelIndex = $questionLevels[$questionIndex];
    $correct = $selected === (int)$question['answer'];
    $elapsedSeconds = filter_var($_POST['elapsed_seconds'] ?? '0', FILTER_VALIDATE_INT);
    if ($elapsedSeconds === false || $elapsedSeconds < 0) {
        http_response_code(400);
        echo json_encode(['error' => 'The answer timing could not be recorded. Please retry.']);
        exit;
    }
    $elapsedSeconds = min($elapsedSeconds, 86400);
    $detail = sprintf(
        '%s round, answer %d: %s (%d sec) — %s',
        $levels[$levelIndex]['name'],
        $questionIndex + 1,
        $correct ? 'correct' : 'review',
        $elapsedSeconds,
        $question['question']
    );
    record_learning_activity((int)$user['id'], (int)$mission['id'], 'question_answered', $detail);
    echo json_encode([
        'correct' => $correct,
        'explanation' => $question['explanation'],
        'level' => $levels[$levelIndex]['name']
    ], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    record_learning_activity((int)$user['id'], (int)$mission['id'], 'mission_view', 'Opened quiz mission');
}
$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $answers = $_POST['answers'] ?? null;
    if (!is_array($answers) || count($answers) !== $totalQuestions) {
        http_response_code(400);
        exit('Please answer every question before submitting the mission.');
    }
    $score = 0;
    $review = [];
    foreach ($questions as $i => $question) {
        $selected = filter_var($answers[$i] ?? null, FILTER_VALIDATE_INT);
        if ($selected === false || !isset($question['options'][$selected])) {
            http_response_code(400);
            exit('One or more answers are invalid. Return to the mission and try again.');
        }
        $correct = $selected === (int)$question['answer'];
        if ($correct) {
            $score++;
        }
        $levelIndex = $questionLevels[$i];
        $review[] = [
            'question' => $question['question'],
            'level' => $levels[$levelIndex]['name'],
            'correct' => $correct,
            'explanation' => $question['explanation'],
            'chosen' => $question['options'][$selected],
            'answer' => $question['options'][$question['answer']]
        ];
    }
    $points = (int)floor((int)$mission['xp_reward'] * ($score / $totalQuestions));
    $pdo->beginTransaction();
    try {
        $attempt = $pdo->prepare('INSERT INTO mission_attempts(user_id,mission_id,score,total_questions,xp_earned) VALUES(?,?,?,?,?)');
        $attempt->execute([$user['id'], $mission['id'], $score, $totalQuestions, $points]);
        $first = award_xp_once((int)$user['id'], (int)$mission['id'], $points);
        award_badges((int)$user['id']);
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
    record_learning_activity(
        (int)$user['id'],
        (int)$mission['id'],
        'quiz_submitted',
        'Completed ' . $totalQuestions . ' questions across ' . count($levels) . ' difficulty levels; score ' . $score . '/' . $totalQuestions
    );
    $_SESSION['last_result'] = [
        'score' => $score,
        'total' => $totalQuestions,
        'points' => $first ? $points : 0,
        'first' => $first,
        'review' => $review,
        'title' => $mission['title']
    ];
    redirect('play.php?id=' . $id . '&result=1');
}

page_header($mission['title']);
?>
<a class="back-link" href="missions.php">← Mission map</a>
<div class="eyebrow">CHAPTER <?= (int)$mission['chapter'] ?> / <?= e($mission['game_type']) ?></div>
<h1><?= e($mission['title']) ?></h1>
<p class="muted"><?= e($mission['description']) ?></p>
<?php if (isset($_GET['result']) && !empty($_SESSION['last_result'])): $r = $_SESSION['last_result']; unset($_SESSION['last_result']); ?>
<section class="result-panel">
  <div class="eyebrow">MISSION REPORT</div>
  <h2><?= e($r['title']) ?></h2>
  <div class="score-display"><?= (int)$r['score'] ?> / <?= (int)$r['total'] ?></div>
  <p><?= $r['first'] ? 'First completion reward: +' . (int)$r['points'] . ' XP' : 'Mission replay recorded. First-completion XP has already been awarded.' ?></p>
  <?php foreach ($r['review'] as $item): ?>
    <div class="review-item">
      <strong><?= $item['correct'] ? '✓ Correct' : '✕ Review this answer' ?> — <?= e($item['question']) ?></strong>
      <small class="muted"><?= e($item['level']) ?> level</small>
      <p>Your answer: <?= e($item['chosen']) ?></p>
      <p>Best answer: <?= e($item['answer']) ?></p>
      <small><?= e($item['explanation']) ?></small>
      <div class="copilot-card">
        <button class="btn secondary copilot-ask" type="button" data-question="<?= e($item['question']) ?>">Ask AI Copilot to explain</button>
        <div class="copilot-answer" aria-live="polite"></div>
      </div>
    </div>
  <?php endforeach; ?>
  <a class="btn primary" href="missions.php">Return to mission map</a>
</section>
<?php else: ?>
  <?php if (!empty($data['briefing'])): ?>
    <div class="simulation-box">
      <div class="eyebrow">MISSION BRIEFING</div>
      <p><?= e($data['briefing']) ?></p>
      <?php if (!empty($data['evidence'])): ?>
        <div class="evidence">
          <strong><?= e($data['evidence']['label']) ?></strong>
          <p><?= e($data['evidence']['text']) ?></p>
        </div>
      <?php endif; ?>
    </div>
  <?php endif; ?>
  <section class="question-card camera-card" aria-labelledby="cameraTitle">
    <div class="eyebrow">OPTIONAL CAMERA PREVIEW</div>
    <h2 id="cameraTitle">Camera stays under your control</h2>
    <p class="muted">Camera access is optional. If enabled, video stays in this browser tab and is not recorded, uploaded, or analyzed. You can turn it off at any time. The quiz records your answers and approximate time per answer; it does not track eye movement or inspect other device activity.</p>
    <div class="camera-controls">
      <button class="btn secondary" id="cameraStart" type="button">Enable camera preview</button>
      <button class="btn secondary" id="cameraStop" type="button" disabled>Turn camera off</button>
      <span id="cameraStatus" class="muted" role="status">Camera is off.</span>
    </div>
    <video id="cameraPreview" autoplay muted playsinline hidden></video>
  </section>
  <form method="post" class="quiz-form" id="adaptiveQuiz">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <section class="question-card" id="roundIntro">
      <div class="question-number" id="roundLabel"></div>
      <h2 id="roundTitle"></h2>
      <p class="muted" id="roundDescription"></p>
      <button class="btn primary" id="startRound" type="button">Start round</button>
    </section>
    <section class="question-card" id="questionPanel" hidden>
      <div class="question-number" id="questionNumber"></div>
      <h3 id="questionText"></h3>
      <div id="answerOptions"></div>
      <button class="btn primary" id="checkAnswer" type="button" disabled>Check answer</button>
      <p id="answerFeedback" aria-live="polite"></p>
      <div class="copilot-card" id="questionCopilot" hidden>
        <button class="btn secondary copilot-ask" id="copilotAsk" type="button">Ask AI Copilot for a hint</button>
        <div class="copilot-answer" aria-live="polite"></div>
      </div>
      <button class="btn primary" id="continueQuestion" type="button" hidden>Continue</button>
    </section>
    <section class="question-card" id="missionComplete" hidden>
      <div class="question-number">ALL LEVELS COMPLETE</div>
      <h2>Submit your investigation</h2>
      <p class="muted">Your answers have been recorded. Submit to save your mission score and XP.</p>
      <button class="btn primary" type="submit">Submit investigation</button>
    </section>
  </form>
<?php endif; ?>
<style>
.copilot-card{margin-top:14px;padding:12px;border:1px solid #26476c;border-radius:9px;background:#0a1628}
.copilot-answer{margin-top:8px;color:#d9eaff;line-height:1.6;white-space:pre-wrap}
.copilot-answer:empty{display:none}
.question-card[hidden],.copilot-card[hidden]{display:none}
.camera-card h2{margin-bottom:8px}
.camera-controls{display:flex;align-items:center;gap:8px;flex-wrap:wrap}
#cameraPreview{display:block;width:min(100%,360px);margin-top:12px;border-radius:10px;background:#050b13;transform:scaleX(-1)}
#cameraPreview[hidden]{display:none}
</style>
<script>
const COPILOT_CSRF=<?= json_encode(csrf_token()) ?>;
const TRACK_CSRF=<?= json_encode(csrf_token()) ?>;
const MISSION_ID=<?= (int)$mission['id'] ?>;
const LEVELS=<?= json_encode($clientLevels, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_INVALID_UTF8_SUBSTITUTE) ?>;
function shuffled(items){
  const result=[...items];
  for(let i=result.length-1;i>0;i--){const j=Math.floor(Math.random()*(i+1));[result[i],result[j]]=[result[j],result[i]];}
  return result;
}
const RANDOMIZED_LEVELS=LEVELS.map(level=>({
  ...level,
  questions:shuffled(level.questions.map(item=>({
    ...item,
    options:shuffled(item.options.map((text,index)=>({text,index})))
  })))
}));
let cameraStream=null;
const cameraStart=document.getElementById('cameraStart');
if(cameraStart){
  const cameraStop=document.getElementById('cameraStop'),cameraStatus=document.getElementById('cameraStatus');
  const cameraPreview=document.getElementById('cameraPreview');
  function stopCamera(){
    cameraStream?.getTracks().forEach(track=>track.stop());
    cameraStream=null;cameraPreview.srcObject=null;cameraPreview.hidden=true;
    cameraStart.disabled=false;cameraStop.disabled=true;cameraStatus.textContent='Camera is off.';
  }
  cameraStart.addEventListener('click',async()=>{
    cameraStart.disabled=true;cameraStatus.textContent='Waiting for camera permission…';
    try{
      if(!navigator.mediaDevices?.getUserMedia)throw new Error('Camera preview requires a supported browser and a secure page (HTTPS or localhost).');
      cameraStream=await navigator.mediaDevices.getUserMedia({video:true,audio:false});
      cameraPreview.srcObject=cameraStream;cameraPreview.hidden=false;
      cameraStop.disabled=false;cameraStatus.textContent='Camera preview is on in this tab only. It is not being recorded or analyzed.';
    }catch(error){
      cameraStream?.getTracks().forEach(track=>track.stop());cameraStream=null;
      cameraStart.disabled=false;
      cameraStatus.textContent=error.message||'Camera permission was not granted. You can continue the quiz without a camera.';
    }
  });
  cameraStop.addEventListener('click',stopCamera);
  window.addEventListener('pagehide',stopCamera);
}
const form=document.getElementById('adaptiveQuiz');
if(form){
  const intro=document.getElementById('roundIntro'),panel=document.getElementById('questionPanel');
  const complete=document.getElementById('missionComplete'),levelLabel=document.getElementById('roundLabel');
  const levelTitle=document.getElementById('roundTitle'),levelDescription=document.getElementById('roundDescription');
  const questionNumber=document.getElementById('questionNumber'),questionText=document.getElementById('questionText');
  const answerOptions=document.getElementById('answerOptions'),checkButton=document.getElementById('checkAnswer');
  const feedback=document.getElementById('answerFeedback'),continueButton=document.getElementById('continueQuestion');
  const copilotCard=document.getElementById('questionCopilot'),copilotButton=document.getElementById('copilotAsk');
  let levelIndex=0,questionIndex=0,flatIndex=0,selectedAnswer=null,questionStartedAt=0;
  const levelIntroText=[
    'Review the briefing, then answer the foundational questions.',
    'You cleared the foundation. The next questions require applying the concepts to less obvious situations.',
    'Advanced round complete. The expert level tests careful, coordinated decisions in more complex situations.'
  ];
  function showIntro(){
    const level=LEVELS[levelIndex];
    intro.hidden=false;panel.hidden=true;complete.hidden=true;
    levelLabel.textContent='DIFFICULTY '+level.name.toUpperCase()+' / LEVEL '+(levelIndex+1)+' OF '+LEVELS.length;
    levelTitle.textContent=levelIndex===0?'Foundation round':level.name+' challenge unlocked';
    levelDescription.textContent=levelIntroText[levelIndex]||'A new, more challenging question set is ready.';
    document.getElementById('startRound').textContent=levelIndex===0?'Start foundation round':'Start '+level.name+' round';
  }
  function showQuestion(){
    const level=RANDOMIZED_LEVELS[levelIndex],item=level.questions[questionIndex];
    intro.hidden=true;panel.hidden=false;complete.hidden=true;
    questionNumber.textContent='DIFFICULTY '+level.name.toUpperCase()+' · QUESTION '+(questionIndex+1)+' / '+level.questions.length+' · OVERALL '+(flatIndex+1);
    questionText.textContent=item.question;
    answerOptions.replaceChildren();
    item.options.forEach(choice=>{
      const label=document.createElement('label'),input=document.createElement('input'),span=document.createElement('span');
      label.className='choice';input.type='radio';input.name='answers['+item.serverIndex+']';input.value=String(choice.index);
      span.textContent=choice.text;label.append(input,span);answerOptions.append(label);
      input.addEventListener('change',()=>{selectedAnswer=choice.index;checkButton.disabled=false;});
    });
    selectedAnswer=null;feedback.textContent='';feedback.className='';continueButton.hidden=true;
    checkButton.hidden=false;checkButton.disabled=true;checkButton.textContent='Check answer';
    questionStartedAt=Date.now();
    copilotCard.hidden=false;
    const copilotOutput=copilotCard.querySelector('.copilot-answer');copilotOutput.textContent='';
    copilotButton.disabled=false;
  }
  async function checkAnswer(){
    if(selectedAnswer===null)return;
    checkButton.disabled=true;checkButton.textContent='Recording answer…';
    const payload=new FormData();
    payload.append('action','track_answer');payload.append('csrf_token',TRACK_CSRF);
    payload.append('question_index',String(RANDOMIZED_LEVELS[levelIndex].questions[questionIndex].serverIndex));
    payload.append('answer',String(selectedAnswer));
    payload.append('elapsed_seconds',String(Math.max(0,Math.round((Date.now()-questionStartedAt)/1000))));
    try{
      const response=await fetch(location.href,{method:'POST',body:payload});
      const result=await response.json();
      if(!response.ok)throw new Error(result.error||'Your answer could not be recorded. Please retry.');
      answerOptions.querySelectorAll('input').forEach(input=>input.disabled=true);
      const savedAnswer=document.createElement('input');
      savedAnswer.type='hidden';savedAnswer.name='answers['+RANDOMIZED_LEVELS[levelIndex].questions[questionIndex].serverIndex+']';savedAnswer.value=String(selectedAnswer);
      form.append(savedAnswer);
      feedback.className=result.correct?'ok':'bad';
      feedback.textContent=(result.correct?'Correct. ':'Review: ')+result.explanation+' Your answer was recorded.';
      checkButton.hidden=true;continueButton.hidden=false;
    }catch(error){
      feedback.className='bad';feedback.textContent=error.message||'Your answer could not be recorded. Please retry.';
      checkButton.disabled=false;checkButton.textContent='Retry recording';
    }
  }
  document.getElementById('startRound').addEventListener('click',showQuestion);
  checkButton.addEventListener('click',checkAnswer);
  continueButton.addEventListener('click',()=>{
    flatIndex++;
    if(questionIndex<LEVELS[levelIndex].questions.length-1){questionIndex++;showQuestion();return;}
    if(levelIndex<LEVELS.length-1){levelIndex++;questionIndex=0;showIntro();return;}
    intro.hidden=true;panel.hidden=true;complete.hidden=false;
  });
  showIntro();
}
document.querySelectorAll('.copilot-ask').forEach(button=>button.addEventListener('click',async()=>{
  const card=button.closest('.copilot-card'),output=card.querySelector('.copilot-answer');
  const question=button.dataset.question||document.getElementById('questionText')?.textContent||'';
  const options=button.dataset.question?'':Array.from(document.querySelectorAll('#answerOptions label span'),span=>span.textContent).join(' | ');
  const payload=new FormData();payload.append('csrf_token',COPILOT_CSRF);payload.append('mission_id',String(MISSION_ID));
  payload.append('prompt','Give me a helpful hint for this training question without revealing the answer: '+question+(options?' Options: '+options:''));
  button.disabled=true;output.textContent='Thinking…';
  try{
    const response=await fetch('copilot.php',{method:'POST',body:payload});
    const result=await response.json();if(!response.ok)throw new Error(result.error||'The copilot could not answer right now.');
    output.textContent=result.answer;
  }catch(error){output.textContent=error.message;}finally{button.disabled=false;}
}));
</script>
<?php page_footer(); ?>
