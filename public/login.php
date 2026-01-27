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
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    //??''で入れた空白だった場合にしたのメッセージを出すように
    if ($email === '' || $password === '') {
        $error = 'メールとパスワードを入力';
    } else {
        //下のコードでDBからユーザーを検索
        //$pdo->prepare()はSQL文を準備する
        //usersテーブルからemailが一致する人を探してid, username, password_hash を取ってくる
        //$stmtはSQLの命令を実行する
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE email = :e");
        //SQLの中に直接emailを入れないようにSQLinjection
        //$stmtはprepareでSQLを準備、executeで実行、fetchで結果をとる
        $stmt->execute([':e' => $email]);
        //1行だけ取る　一人だけでいいから
        $user = $stmt->fetch();

        //ユーザーが存在しない場合  $userは見つかったら配列　見つからなかったらfalseになる
        if (!$user) {
            $error = 'メールまたはパスワードが違います';
        } else {
            //パスワードを照合
            if (password_verify($password, $user['password_hash'])) {
                //成功したらセッションに保存　　これがあるとログインしないと入れないところに入れる
                //文字列で変えてくることがあるので(int)をつけて数値ちして扱う
                $_SESSION['user_id']  = (int)$user['id'];
                $_SESSION['username'] = $user['username'];

                //成功したらtimeline.phpに移動
                header('Location: timeline.php');
                exit;
            //パスワードをが間違えていたらしたのメッセージ
            } else {
                $error = 'パスワードが違います';
            }
        }
    }
}
?>
<!--ここからhtml-->
<!--下のコードでhtmlと認識-->
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>ログイン</title>
</head>
<body>
<h1>ログイン</h1>

<!--エラーを表示する-->
<?php if ($error !== ''): ?>
  <p style="color:red;"><?php echo h($error); ?></p>
<?php endif; ?>

<!--送信する用-->
<form method="post">
  <div>
    <label>メール</label><br>
<!--valueで前回の入力を残してログイン失敗してもメールアドレスをもう一度打たなくて済む-->
    <input name="email" value="<?php echo h($_POST['email'] ?? ''); ?>">
  </div>
  <div>
    <label>パスワード</label><br>
<!--パスワードは残さないように-->
    <input type="password" name="password">
  </div>
  <button type="submit">ログイン</button>
</form>

<p><a href="register.php">会員登録はこちら</a></p>
</body>
</html>
