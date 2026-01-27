<?php
require_once __DIR__ . '/config.php';
require_login();

$me = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $target = (int)($_POST['target_user_id'] ?? 0);

    if ($target > 0 && $target !== $me) {
        if ($action === 'follow') {
            $stmt = $pdo->prepare(
                "INSERT IGNORE INTO follows (follower_id, followee_id)
                 VALUES (:me, :t)"
            );
            $stmt->execute([':me' => $me, ':t' => $target]);
        } elseif ($action === 'unfollow') {
            $stmt = $pdo->prepare(
                "DELETE FROM follows
                 WHERE follower_id = :me AND followee_id = :t"
            );
            $stmt->execute([':me' => $me, ':t' => $target]);
        }
    }

    header('Location: profile.php');
    exit;
}

$stmt = $pdo->prepare("SELECT followee_id FROM follows WHERE follower_id = :me");
$stmt->execute([':me' => $me]);
$followingIds = array_column($stmt->fetchAll(), 'followee_id');
$followingSet = array_flip($followingIds);

$stmt = $pdo->prepare("SELECT id, username FROM users WHERE id <> :me ORDER BY id DESC");
$stmt->execute([':me' => $me]);
$users = $stmt->fetchAll();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>ユーザー一覧</title>
</head>
<body>
<h1>ユーザー一覧（フォロー）</h1>

<nav>
  <a href="timeline.php">フォローTL</a> |
  <a href="all_posts.php">全投稿</a> |
  <a href="post_new.php">投稿</a> |
  <a href="logout.php">ログアウト</a>
</nav>

<p>ログイン中：<?php echo h($_SESSION['username'] ?? ''); ?></p>
<hr>

<ul>
<?php foreach ($users as $u): ?>
  <li>
    <?php echo h($u['username']); ?>
    <?php $isFollowing = isset($followingSet[$u['id']]); ?>

    <form method="post" style="display:inline;">
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
