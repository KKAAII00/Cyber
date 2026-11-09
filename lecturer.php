<?php
require_once 'includes/functions.php';
$teacher=require_role('lecturer');
$students=(int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn();
$completed=(int)$pdo->query("SELECT COUNT(*) FROM mission_completions")->fetchColumn();
$attempts=(int)$pdo->query("SELECT COUNT(*) FROM mission_attempts")->fetchColumn();
$top=$pdo->query("SELECT username,xp FROM users WHERE role='student' ORDER BY xp DESC LIMIT 10")->fetchAll();
$progress=$pdo->query("SELECT u.username,COUNT(mc.id) AS completed,u.xp FROM users u LEFT JOIN mission_completions mc ON mc.user_id=u.id WHERE u.role='student' GROUP BY u.id ORDER BY u.username")->fetchAll();
page_header('Lecturer Dashboard');
?>
<div class="eyebrow">INSTRUCTOR CONSOLE</div><h1>Class progress overview</h1><p class="muted">Read-only learning analytics for student accounts. This page does not expose passwords or private credentials.</p>
<div class="stats-grid"><div class="stat-card"><span>STUDENT ACCOUNTS</span><strong><?=$students?></strong></div><div class="stat-card"><span>MISSION COMPLETIONS</span><strong><?=$completed?></strong></div><div class="stat-card"><span>ATTEMPTS RECORDED</span><strong><?=$attempts?></strong></div></div>
<div class="two-col"><section class="panel"><h2>Top agents</h2><?php foreach($top as $i=>$s):?><div class="list-row"><span>#<?= $i+1?> <?=e($s['username'])?></span><strong><?= (int)$s['xp']?> XP</strong></div><?php endforeach;?></section><section class="panel"><h2>Student progress</h2><?php foreach($progress as $s):?><div class="list-row"><span><?=e($s['username'])?></span><strong><?= (int)$s['completed']?> missions · <?= (int)$s['xp']?> XP</strong></div><?php endforeach;?></section></div>
<?php page_footer(); ?>
