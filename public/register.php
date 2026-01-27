<?php
require_once __DIR__ . '/config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $email === '' || $password === '') {
        $error = '全て入力してください';
    } elseif (strlen($password) < 6) {
        $error = 'パスワードは6文字以上にしてください';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);

        try {
            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password_hash)
                 VALUES (:u, :e, :p)"
            );
            $stmt->execute([
                ':u' => $username,
                ':e' => $email,
                ':p' => $hash,
            ]);

            $_SESSION['user_id']  = (int)$pdo->lastInsertId();
            $_SESSION['username'] = $username;

            header('Location: timeline.php');
            exit;
        } catch (PDOException $e) {
            $error = 'そのユーザー名またはメールは既に使われています';
        }
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>会員登録</title>
</head>
<body>
<h1>会員登録</h1>

<?php if ($error !== ''): ?>
  <p style="color:red;"><?php echo h($error); ?></p>
<?php endif; ?>

<form method="post">
  <div>
    <label>ユーザー名</label><br>
    <input name="username" value="<?php echo h($_POST['username'] ?? ''); ?>">
  </div>
  <div>
    <label>メール</label><br>
    <input name="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
  </div>
  <div>
    <label>パスワード</label><br>
    <input type="password" name="password">
  </div>
  <button type="submit">登録</button>
</form>

<p><a href="login.php">ログインはこちら</a></p>
</body>
</html>
