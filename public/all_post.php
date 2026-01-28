<?php
//これでconfig.phpを呼んで共通設定（セッションスタートとDBやxss対策、ログインしないと見れないように）
//DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
require_once __DIR__ . '/config.php';
//ログインしてる人だけが入れるようにログインしていない人はlogin.phpに移動
require_login();

//投稿一覧（画像以外）
//posts＝投稿、　users＝ユーザー、 users.usernameを取得、 follows＝フォロー関係　follower_id＝フォローしている人(自分)   followee_id＝フォローされている人（相手）
//JOINがpostsとusersをつないでいる　postsとfollowsをつなぐ
$sql = "
SELECT
  p.id,
  p.body,
  p.created_at,
  u.username
FROM posts p
JOIN users u ON u.id = p.user_id
ORDER BY p.created_at DESC
LIMIT 50
";
//実行して配列に
$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll();

//画像をまとめて取得する
//投稿ID一覧を作る
$postIds = array_column($posts, 'id');
//今は入れないあとで入れるよう　下で入れている
$imagesByPost = [];

if (count($postIds) > 0) {
    //どのIDの投稿を取るか
    $in = implode(',', array_fill(0, count($postIds), '?'));
    //画像を取るSQL
    $stmt = $pdo->prepare(
        "SELECT post_id, image_path
         FROM post_images
         WHERE post_id IN ($in)
         ORDER BY id ASC"
    );
    $stmt->execute($postIds);

    //投稿ID→画像配列の形に組み換え
    foreach ($stmt->fetchAll() as $row) {
        $imagesByPost[(int)$row['post_id']][] = $row['image_path'];
    }
}
?>
<!--ここからHTML-->
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>全投稿</title>
</head>
<body>
<h1>全投稿一覧</h1>

<nav>
  <a href="timeline.php">タイムライン</a> 
  <a href="profile.php">ユーザー一覧</a> 
  <a href="post_new.php">投稿</a> 
  <a href="logout.php">ログアウト</a>
</nav>

<p>ログイン中：<?php echo h($_SESSION['username'] ?? ''); ?></p>
<hr>

<!--投稿が０件のときに表示-->
<?php if (count($posts) === 0): ?>
  <p>投稿がありません</p>
<?php endif; ?>

<!--投稿ループ、１件ずつ表示-->
<?php foreach ($posts as $p): ?>
  <div style="border:1px solid #ccc; padding:10px; margin:10px 0;">
    <div>
        <!--usernameと日時-->
      <strong><?php echo h($p['username']); ?></strong>
      <span style="color:#666;"><?php echo h($p['created_at']); ?></span>
    </div>

    <!--改行をそのまま表示してくれる-->
    <div style="white-space:pre-wrap;"><?php echo h($p['body']); ?></div>

    <!--画像表示-->
    <?php
      $imgs = $imagesByPost[(int)$p['id']] ?? [];
      foreach ($imgs as $path):
    ?>
      <img src="<?php echo h($path); ?>" alt="image" style="max-width:200px; margin:4px;">
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>

</body>
</html>
