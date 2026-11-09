<?php
require_once 'includes/functions.php';
$user=require_login();
$q=$pdo->prepare('SELECT m.*, (mc.id IS NOT NULL) AS completed FROM missions m LEFT JOIN mission_completions mc ON mc.mission_id=m.id AND mc.user_id=? ORDER BY m.chapter,m.id');
$q->execute([$user['id']]); $missions=$q->fetchAll();
$chapters=[];
foreach($missions as $m) $chapters[$m['chapter']][]=$m;
page_header('Mission Map');
?>
<div class="eyebrow">CAMPAIGN MAP / SHADOW PROTOCOL</div><h1>Choose your mission.</h1><p class="muted">Six chapters. One academy. Every challenge uses fictional evidence and safe simulations.</p>
<?php foreach($chapters as $chapter=>$items):?><section class="chapter-block"><div class="chapter-heading"><span>CHAPTER <?= (int)$chapter?></span><h2><?=e($items[0]['chapter_title'])?></h2></div><div class="mission-grid"><?php foreach($items as $m):?><article class="mission-card"><div class="mission-top"><span class="mission-type"><?=e($m['game_type'])?></span><?php if($m['completed']):?><span class="complete-tag">COMPLETED ✓</span><?php endif;?></div><h3><?=e($m['title'])?></h3><p><?=e($m['description'])?></p><div class="mission-bottom"><span>+<?= (int)$m['xp_reward']?> XP</span><a class="btn small <?= $m['completed']?'secondary':'primary'?>" href="play.php?id=<?=(int)$m['id']?>"><?=$m['completed']?'Replay':'Start mission'?></a></div></article><?php endforeach;?></div></section><?php endforeach;?>
<?php page_footer(); ?>
