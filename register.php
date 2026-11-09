<?php
require_once 'includes/functions.php';
if (current_user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (mb_strlen($username) < 3 || mb_strlen($username) > 30 || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 10) {
        $error = 'Use a username of 3–30 characters, a valid email and a password of at least 10 characters.';
    } else {
        $q = $pdo->prepare('SELECT id FROM users WHERE email = ? OR username = ?');
        $q->execute([$email, $username]);
        if ($q->fetch()) $error = 'That email or username is already registered.';
        else {
            $ins = $pdo->prepare('INSERT INTO users (username,email,password_hash) VALUES (?,?,?)');
            $ins->execute([$username,$email,password_hash($password,PASSWORD_DEFAULT)]);
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$pdo->lastInsertId();
            redirect('dashboard.php');
        }
    }
}
page_header('Create Agent Account');
?>
<div class="auth-wrap"><form class="auth-card" method="post"><div class="eyebrow">NEW RECRUIT REGISTRATION</div><h1>Create your agent ID</h1><?php if($error):?><div class="notice error"><?=e($error)?></div><?php endif;?>
<input type="hidden" name="csrf_token" value="<?=e(csrf_token())?>">
<label>Agent username<input name="username" minlength="3" maxlength="30" required value="<?=e($_POST['username']??'')?>"></label>
<label>Email address<input type="email" name="email" required value="<?=e($_POST['email']??'')?>"></label>
<label>Password<input type="password" name="password" minlength="10" required autocomplete="new-password"></label>
<button class="btn primary full">Create account</button><p class="muted">Already registered? <a href="login.php">Log in</a></p></form></div>
<?php page_footer(); ?>
