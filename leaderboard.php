<?php
require_once 'includes/functions.php';
$user=require_login();
$q=$pdo->query("SELECT username,xp FROM users WHERE role='student' ORDER BY xp DESC, username ASC LIMIT 50");
$rows=$q->fetchAll();
page_header('Leaderboard');
?>
<div class="eyebrow">ACADEMY RANKINGS</div><h1>Agent leaderboard</h1><p class="muted">Compare progress with fellow students. Rankings are based on earned XP.</p><section class="panel table-wrap"><table><thead><tr><th>RANK</th><th>AGENT</th><th>EXPERIENCE</th></tr></thead><tbody><?php foreach($rows as $i=>$r):?><tr><td>#<?= $i+1?></td><td><?=e($r['username'])?></td><td><?= (int)$r['xp']?> XP</td></tr><?php endforeach;?></tbody></table></section>
<?php page_footer(); ?>
