<?php
require_once 'includes/functions.php';
if (current_user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $q = $pdo->prepare('SELECT id,password_hash FROM users WHERE email = ?');
    $q->execute([$email]); $user = $q->fetch();
    if ($user && password_verify($password,$user['password_hash'])) {
        session_regenerate_id(true); $_SESSION['user_id'] = (int)$user['id']; redirect('dashboard.php');
    }
    $error = 'Email or password is incorrect.';
}
page_header('Agent Login');
?>
<div class="auth-wrap"><form class="auth-card" method="post"><div class="eyebrow">SECURE ACCESS TERMINAL</div><h1>Agent login</h1><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?>
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label>Email address<input type="email" name="email" required autocomplete="username"></label>
<label>Password<input type="password" name="password" required autocomplete="current-password"></label>
<button class="btn primary full">Enter academy</button><p class="muted">New recruit? <a href="register.php">Create an account</a></p></form></div>
<?php page_footer(); ?>
