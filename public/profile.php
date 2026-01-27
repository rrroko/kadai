<?php
//これでconfig.phpを呼んで共通設定（セッションスタートとDBやxss対策、ログインしないと見れないように）
//DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
require_once __DIR__ . '/config.php';
//ログインしてる人だけが入れるようにログインしていない人はlogin.phpに移動
require_login();

//ログイン中のユーザーIDを取り出して$meに
$me = (int)$_SESSION['user_id'];

//POST処理（フォローと解除の処理）
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    //下のHTMLのhiddenに入っている値（follownfollow）のどっちが押されたか
    $action = $_POST['action'] ?? '';
    //target_user_idは相手のユーザーID　　$meと$taragetの関係を書き込む
    $target = (int)($_POST['target_user_id'] ?? 0);

    //変な値を弾く（0やマイナスを弾く） 自分をフォローするの禁止
    if ($target > 0 && $target !== $me) {
        //followの処理　
        //follwsテーブルにfollower_id,followee_idを１行追加　誰がフォローしたか記録
        if ($action === 'follow') {
            //INSERT IGNOREで同じ組み合わせがあるなら、エラーにせず何もしない
            $stmt = $pdo->prepare( 
                "INSERT IGNORE INTO follows (follower_id, followee_id)
                 VALUES (:me, :t)"
            );
            $stmt->execute([':me' => $me, ':t' => $target]);
        } elseif ($action === 'unfollow') {
            //unfollowの処理
            //follwsテーブルにfollower_id,followee_idを１行削除　フォロー記録を消す
            $stmt = $pdo->prepare(
                "DELETE FROM follows
                 WHERE follower_id = :me AND followee_id = :t"
            );
            $stmt->execute([':me' => $me, ':t' => $target]);
        }
    }

    //POST後にリダイレクト
    header('Location: profile.php');
    //処理終了
    exit;
}

//フォロー中のリストを取得
$stmt = $pdo->prepare("SELECT followee_id FROM follows WHERE follower_id = :me");
$stmt->execute([':me' => $me]);
//フォロワーIDだけほしいからarray_columnの形
//fetchAll()にするとフォロー中のユーザーID以外もとってきてしまうのでarray_columnの形で数字だけの形に
$followingIds = array_column($stmt->fetchAll(), 'followee_id');
$followingSet = array_flip($followingIds);
//ユーザーの一覧を取得する（自分以外）
$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id <> :me ORDER BY id DESC");
$stmt->execute([':me' => $me]);
$users = $stmt->fetchAll();
?>
<!--ここからHTML-->
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>ユーザー一覧</title>
</head>
<body>
<h1>ユーザー一覧（フォロー）</h1>

<nav>
  <a href="timeline.php">タイムライン</a> |
  <a href="all_post.php">全投稿</a> |
  <a href="post_new.php">投稿</a> |
  <a href="logout.php">ログアウト</a>
</nav>

<p>ログイン中：<?php echo h($_SESSION['username'] ?? ''); ?></p>
<hr>

<ul>
<?php foreach ($users as $u): ?>
  <li>
    <?php echo h($u['username']); ?>
    <!--フォロー中か、判定-->
    <?php $isFollowing = isset($followingSet[$u['id']]); ?>

    <!--tureならフォロー解除ボタン、falseならフォロー解除ボタン表示-->
    <form method="post" style="display:inline;">
        <!--画面に見えてないけどPOSTに送られる、ボタンを押すとtarget_user_id,actionが送信されて上のPOST処理が動く-->
      <input type="hidden" name="target_user_id" value="<?php echo (int)$u['id']; ?>">
      <?php if ($isFollowing): ?>
        <input type="hidden" name="action" value="unfollow">
        <button type="submit">フォロー解除</button>
      <?php else: ?>
        <input type="hidden" name="action" value="follow">
        <button type="submit">フォローする</button>
      <?php endif; ?>
    </form>
  </li>
<?php endforeach; ?>
</ul>

</body>
</html>
