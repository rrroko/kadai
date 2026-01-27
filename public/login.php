<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $error = 'メールとパスワードを入力してください';
    } else {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = :e");
        $stmt->execute([':e' => $email]);
        $user = $stmt->fetch();

        if (!$user) {
            $error = 'メールまたはパスワードが違います';
        } else {
            if (password_verify($password, $user['password_hash'])) {
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];

                header('Location: timeline.php');
                exit;
            } else {
                $error = 'メールまたはパスワードが違います';
            }
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>ログイン</title>
</head>
<body>
<h1>ログイン</h1>

<?php if ($error !== ''): ?>
  <p style="color:red;"><?php echo h($error); ?></p>
<?php endif; ?>

<form method="post">
  <div>
    <label>メール</label><br>
    <input name="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
  </div>
  <div>
    <label>パスワード</label><br>
    <input type="password" name="password">
  </div>
  <button type="submit">ログイン</button>
</form>

<p><a href="register.php">会員登録はこちら</a></p>
</body>
</html>
