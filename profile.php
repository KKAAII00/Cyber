<?php
require_once 'includes/functions.php';
$user=require_login();$level=level_info((int)$user['xp']);
$q=$pdo->prepare('SELECT b.name,b.description,ub.earned_at FROM user_badges ub JOIN badges b ON b.id=ub.badge_id WHERE ub.user_id=? ORDER BY ub.earned_at');$q->execute([$user['id']]);$badges=$q->fetchAll();
page_header('Agent Profile');
?>
<div class="eyebrow">PERSONNEL FILE / AGENT ID #<?= (int)$user['id']?></div><h1><?=e($user['username'])?></h1><div class="profile-grid"><section class="panel profile-card"><div class="avatar">CG</div><h2><?=e($level['rank'])?></h2><p>Level <?= (int)$level['level']?> · <?= (int)$user['xp']?> XP</p><p class="muted"><?=e($user['email'])?></p><p class="muted">Joined <?=e($user['created_at'])?></p></section><section class="panel"><div class="eyebrow">UNLOCKABLE COLLECTION</div><h2>Badges & equipment</h2><?php foreach($badges as $b):?><div class="badge-row"><span class="badge-icon">✦</span><div><strong><?=e($b['name'])?></strong><small><?=e($b['description'])?></small></div></div><?php endforeach;?><?php if(!$badges):?><p class="muted">Your collection is empty. Complete missions to unlock badges. Character skins and equipment cosmetics are reserved for a later content update.</p><?php endif;?></section></div>
<?php page_footer(); ?>
