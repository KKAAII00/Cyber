<?php
require_once 'includes/functions.php';
$user=require_login();$message='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();
 if(isset($_POST['create'])){
  $code=strtoupper(bin2hex(random_bytes(3)));
  $ins=$pdo->prepare('INSERT INTO game_rooms(room_code,created_by,status) VALUES(?,?,"waiting")');$ins->execute([$code,$user['id']]);$roomId=(int)$pdo->lastInsertId();
  $join=$pdo->prepare('INSERT INTO room_players(room_id,user_id) VALUES(?,?)');$join->execute([$roomId,$user['id']]);
  redirect('room.php?id='.$roomId);
 }
 if(isset($_POST['join'])){
  $code=strtoupper(trim($_POST['room_code']??''));
  $q=$pdo->prepare('SELECT id FROM game_rooms WHERE room_code=? AND status="waiting"');$q->execute([$code]);$room=$q->fetch();
  if($room){$add=$pdo->prepare('INSERT IGNORE INTO room_players(room_id,user_id) VALUES(?,?)');$add->execute([$room['id'],$user['id']]);redirect('room.php?id='.$room['id']);}
  $message='Room code not found or the room is no longer accepting players.';
 }
}
$q=$pdo->query('SELECT r.id,r.room_code,r.created_at,COUNT(rp.id) AS players FROM game_rooms r LEFT JOIN room_players rp ON rp.room_id=r.id WHERE r.status="waiting" GROUP BY r.id ORDER BY r.created_at DESC LIMIT 10');
page_header('Team Rooms');
?>
<div class="eyebrow">MULTIPLAYER / TEAM TRAINING</div><h1>Train together.</h1><p class="muted">Create a room for classmates or join with a room code. This starter uses a shared room lobby and team score; it is not a real-time socket game yet.</p><?php if($message):?><div class="notice error"><?=e($message)?></div><?php endif;?>
<div class="two-col"><section class="panel"><h2>Create a room</h2><p>Start a safe simulated team exercise and share the room code.</p><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><button class="btn primary" name="create" value="1">Create team room</button></form></section><section class="panel"><h2>Join a room</h2><form method="post"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><label>Room code<input name="room_code" maxlength="6" required placeholder="e.g. A1B2C3"></label><button class="btn secondary" name="join" value="1">Join room</button></form></section></div>
<section class="panel"><h2>Open rooms</h2><?php foreach($q as $r):?><div class="list-row"><span>Room <?=e($r['room_code'])?> · <?= (int)$r['players']?> player(s)</span><a href="room.php?id=<?=(int)$r['id']?>">Open lobby →</a></div><?php endforeach;?></section>
<?php page_footer(); ?>
