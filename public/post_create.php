<?php
//これでconfig.phpを呼んで共通設定（セッションスタートとDBやxss対策、ログインしないと見れないように）
//DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
require_once __DIR__ . '/config.php';
//ログインしてる人だけが入れるようにログインしていない人はlogin.phpに移動
require_login();

//ログイン中のユーザーIDを取り出して$meに
$me = (int)$_SESSION['user_id'];

//POST以外のアクセス拒否（直アクセス拒否）
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: post_new.php');
    exit;
}

//post_new.phpのbodyから本文を受け取る、空かチェック
$body = trim($_POST['body'] ?? '');
if ($body === '') {
    exit('本文が空です');
}

//失敗したら止めるためにtry-catch
try {
    //投稿本文を保存 postsへINSERT
    $stmt = $pdo->prepare("INSERT INTO posts (user_id, body) VALUES (:u, :b)");
    $stmt->execute([':u' => $me, ':b' => $body]);
    $postId = (int)$pdo->lastInsertId();  //投稿をINSERTするとDBがposts.idを作るから、それをlastInsertIdで取っている

    //画像を保存してpost_imagesに登録
    if (isset($_FILES['images']) && is_array($_FILES['images']['name'])) {
        $count = count($_FILES['images']['name']);

        // 保存先フォルダ（public/upload/image）を選択
        //DIRは絶対パス __DIR__ = /var/www/html/publicみたいになる
        $saveDir = __DIR__ . '/upload/image';
        if (!is_dir($saveDir)) {
            // フォルダが無いなら作る
            if (!mkdir($saveDir, 0775, true)) {
                exit('画像保存フォルダの作成に失敗しました');
            }
        }

        //画像を１枚ずつ処理するループ
        //errorコードを見る
        for ($i = 0; $i < $count; $i++) {
            $err = $_FILES['images']['error'][$i];

            // 未選択はスキップ　その枠にファイルなし
            if ($err === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            //未選択以外はエラー失敗
            if ($err !== UPLOAD_ERR_OK) {
                exit('画像アップロードに失敗しました');
            }

            //アップロード直後PHPファイルをサーバの一時場所に置く　その場所が$tmp
            //tmpファイルのパスを取る
            $tmp = $_FILES['images']['tmp_name'][$i];

            // 画像かどうかチェック .phpなど画像以外弾く
            $info = getimagesize($tmp);
            if ($info === false) {
                exit('画像ファイルではありません');
            }

            // 保存ファイル名をユニークに（被り防止）
            $name = date('Ymd_His') . '_' . bin2hex(random_bytes(6)) . "_{$i}.jpg";
            $savePath = $saveDir . '/' . $name;

            //tmp→保存先へ移動　ここがアップロードの本体
            if (!move_uploaded_file($tmp, $savePath)) {
                exit('画像の保存に失敗しました（権限を確認）');
            }

            //DBに入れるのはpublicから見えるパス
            //timeline.php の <img src="..."> でそのまま使える形にする
            $publicPath = 'upload/image/' . $name;

            //$stmtはSQLの命令を実行する
            //post_imagesに登録　投稿との紐付け
            $stmt = $pdo->prepare(
                "INSERT INTO post_images (post_id, image_path)
                 VALUES (:p, :path)"
            );
            $stmt->execute([':p' => $postId, ':path' => $publicPath]);
        }
    }

    //成功したらtimeline.php
    header('Location: timeline.php');
    //処理終了
    exit;

} catch (Exception $e) {
    exit("投稿に失敗: " . $e->getMessage());
}
