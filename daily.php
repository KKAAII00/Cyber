<?php
require_once 'includes/functions.php';
$user=require_login();
$today=date('Y-m-d');
$q=$pdo->prepare('SELECT d.*,m.title,m.id AS mission_id FROM daily_challenges d JOIN missions m ON m.id=d.mission_id WHERE d.challenge_date=?');$q->execute([$today]);$daily=$q->fetch();
if(!$daily){$pick=$pdo->query('SELECT id FROM missions ORDER BY RAND() LIMIT 1')->fetchColumn();$ins=$pdo->prepare('INSERT IGNORE INTO daily_challenges(challenge_date,mission_id) VALUES(?,?)');$ins->execute([$today,$pick]);$q->execute([$today]);$daily=$q->fetch();}
$c=$pdo->prepare('SELECT id FROM mission_completions WHERE user_id=? AND mission_id=?');$c->execute([$user['id'],$daily['mission_id']]);$done=(bool)$c->fetch();
page_header('Daily Challenge');
?>
<div class="eyebrow">DAILY OPERATIONS / <?=e($today)?></div><h1>One challenge a day.</h1><section class="panel daily-panel"><div class="daily-icon">◈</div><div class="eyebrow">TODAY'S TRAINING ASSIGNMENT</div><h2><?=e($daily['title'])?></h2><p>Complete today's selected mission to practise a cybersecurity skill. Your regular first-completion reward rules still apply.</p><?php if($done):?><span class="complete-tag">MISSION COMPLETED ✓</span><?php endif;?><a class="btn primary" href="play.php?id=<?=(int)$daily['mission_id']?>">Open daily mission</a></section>
<?php page_footer(); ?>
