<?php
//これでconfig.phpを呼んで共通設定（セッションスタートとDBやxss対策、ログインしないと見れないように）
//DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
require_once __DIR__ . '/config.php';

//ログイン失敗したときに画面にエラー出す用
$error = '';

//ログインボタン押されたときにログイン処理
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //入力を受け取る  
    // ??''は何も入ってなかったら空白を入れる
    //trimは前後に空白が入っても消す用
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    //??''で入れた空白だった場合にしたのメッセージを出すように
    if ($username === '' || $email === '' || $password === '') {
        $error = '全て入力してください';
        //パスワードのチェック　strlenは文字数を数える
    } elseif (strlen($password) < 3) {
        $error = 'パスワードは3文字以上';
    } else {
        //パスワードをハッシュ化
        $hash = password_hash($password, PASSWORD_DEFAULT);

        //ユーザーをSQLで登録
        //$stmtはprepareでSQLを準備、executeで実行、fetchで結果をとる
        //:u :e :pは穴埋めようの目印  下のexecuteで埋めている
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

            //登録できたらログイン状態にする
            $_SESSION['user_id']  = (int)$pdo->lastInsertId();
            $_SESSION['username'] = $username;

            //timeline.phpに移動
            header('Location: timeline.php');
            //処理終了
            exit;
        } catch (PDOException $e) {
            //すでに使われれ場合にエラー表示
            $error = 'そのユーザー名又はメールアドレスは既に使われています';
        }
    }
}
?>
<!--ここからhtml-->
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>会員登録</title>
</head>
<body>
<h1>会員登録</h1>

<!--エラー表示-->
<?php if ($error !== ''): ?>
  <p style="color:red;"><?php echo h($error); ?></p>
<?php endif; ?>

<form method="post">
  <div>
    <label>ユーザー名</label><br>
    <!--valueで入力保持-->
    <input name="username" value="<?php echo h($_POST['username'] ?? ''); ?>">
  </div>
  <div>
    <label>メール</label><br>
    <!--valueで入力保持-->
    <input name="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
  </div>
  <div>
    <label>パスワード</label><br>
    <!--パスワードは保持なし-->
    <input type="password" name="password">
  </div>
  <button type="submit">登録</button>
</form>

<p><a href="login.php">ログインはこちら</a></p>
</body>
</html>
