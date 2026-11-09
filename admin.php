<?php
require_once 'includes/functions.php';
require_role('admin');
if($_SERVER['REQUEST_METHOD']==='POST'){
 verify_csrf();$uid=(int)($_POST['user_id']??0);$role=$_POST['role']??'student';
 if(in_array($role,['student','lecturer','admin'],true)){$q=$pdo->prepare('UPDATE users SET role=? WHERE id=?');$q->execute([$role,$uid]);flash('Account role updated.','success');}
 redirect('admin.php');
}
$users=$pdo->query('SELECT id,username,email,role,xp,created_at FROM users ORDER BY created_at DESC')->fetchAll();
page_header('Admin Account Management');
?>
<div class="eyebrow">SYSTEM ADMINISTRATION</div><h1>Account roles</h1><p class="muted">Only administrators can change account roles. Assign lecturer access only to trusted teaching staff.</p>
<section class="panel table-wrap"><table><thead><tr><th>USER</th><th>ROLE</th><th>XP</th><th>CHANGE ROLE</th></tr></thead><tbody><?php foreach($users as $u):?><tr><td><?=e($u['username'])?><small class="table-sub"><?=e($u['email'])?></small></td><td><?=e($u['role'])?></td><td><?= (int)$u['xp']?></td><td><form method="post" class="inline-form"><input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>"><input type="hidden" name="user_id" value="<?=(int)$u['id']?>"><select name="role"><option value="student" <?=$u['role']==='student'?'selected':''?>>Student</option><option value="lecturer" <?=$u['role']==='lecturer'?'selected':''?>>Lecturer</option><option value="admin" <?=$u['role']==='admin'?'selected':''?>>Admin</option></select><button class="btn small secondary">Save</button></form></td></tr><?php endforeach;?></tbody></table></section>
<?php page_footer(); ?>
