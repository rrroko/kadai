<?php
require_once __DIR__ . '/config.php';
require_login(); // ログインしないと入れないように

// 1) 投稿（画像は取らない）
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
$stmt = $pdo->query($sql);
$posts = $stmt->fetchAll();

// 2) 画像をまとめて取得
$postIds = array_column($posts, 'id');
$imagesByPost = [];

if (count($postIds) > 0) {
    $in = implode(',', array_fill(0, count($postIds), '?'));
    $stmt = $pdo->prepare(
        "SELECT post_id, image_path
         FROM post_images
         WHERE post_id IN ($in)
         ORDER BY id ASC"
    );
    $stmt->execute($postIds);

    foreach ($stmt->fetchAll() as $row) {
        $imagesByPost[(int)$row['post_id']][] = $row['image_path'];
    }
}
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>全投稿</title>
</head>
<body>
<h1>全投稿一覧（フォロー不要）</h1>

<nav>
  <a href="timeline.php">フォローTL</a> 
  <a href="profile.php">ユーザー一覧</a> 
  <a href="post_new.php">投稿</a> 
  <a href="logout.php">ログアウト</a>
</nav>

<p>ログイン中：<?php echo h($_SESSION['username'] ?? ''); ?></p>
<hr>

<?php if (count($posts) === 0): ?>
  <p>投稿がまだありません。</p>
<?php endif; ?>

<?php foreach ($posts as $p): ?>
  <div style="border:1px solid #ccc; padding:10px; margin:10px 0;">
    <div>
      <strong><?php echo h($p['username']); ?></strong>
      <span style="color:#666;"><?php echo h($p['created_at']); ?></span>
    </div>

    <div style="white-space:pre-wrap;"><?php echo h($p['body']); ?></div>

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
