<?php
require_once __DIR__ . '/config.php';
require_login();

$me = (int)$_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: post_new.php');
    exit;
}

$body = trim($_POST['body'] ?? '');
if ($body === '') {
    exit('本文が空です');
}

try {
    // 1) 投稿本文を保存
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, body) VALUES (:u, :b)");
    $stmt->execute([':u' => $me, ':b' => $body]);
    $postId = (int)$pdo->lastInsertId();

    // 2) 画像（0〜4枚）を保存して post_images に登録
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $count = count($_FILES['images']['name']);

        if ($count > 4) {
            exit('画像は最大4枚までです');
        }

        for ($i = 0; $i < $count; $i++) {
            if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) {
                exit('画像アップロードに失敗しました');
            }

            $tmp = $_FILES['images']['tmp_name'][$i];

            $info = getimagesize($tmp);
            if ($info === false) {
                exit('画像ファイルではありません');
            }

            $name = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . "_{$i}.jpg";
            $savePath = __DIR__ . '/../upload/image/' . $name;

            if (!move_uploaded_file($tmp, $savePath)) {
                exit('画像の保存に失敗しました（権限を確認）');
            }

            // public/ から見える相対パス
            $publicPath = '../upload/image/' . $name;

            $stmt = $pdo->prepare("INSERT INTO post_images (post_id, image_path) VALUES (:p, :path)");
            $stmt->execute([':p' => $postId, ':path' => $publicPath]);
        }
    }

    header('Location: timeline.php');
    exit;

} catch (Exception $e) {
    exit("投稿に失敗: " . $e->getMessage());
}
