<?php
require_once __DIR__ . '/config.php';
require_login();
?>
<!doctype html>
<html lang="ja">
<head>
  <meta charset="utf-8">
  <title>投稿</title>
</head>
<body>
<h1>投稿（画像 最大4枚）</h1>

<nav>
  <a href="timeline.php">フォローTL</a> |
  <a href="all_posts.php">全投稿</a> |
  <a href="profile.php">ユーザー一覧</a> |
  <a href="logout.php">ログアウト</a>
</nav>

<hr>

<form id="postForm" method="post" action="post_create.php" enctype="multipart/form-data">
  <div>
    <label>本文</label><br>
    <textarea name="body" rows="4" cols="50" required></textarea>
  </div>

  <div>
    <label>画像（任意・最大4枚）</label><br>
    <input type="file" id="images" accept="image/*" multiple>
    <input type="file" id="imagesHidden" name="images[]" multiple style="display:none;">
  </div>

  <button type="submit">投稿する</button>
</form>

<script>
async function resizeImageToFile(file, maxW = 1280, maxH = 1280, quality = 0.8) {
  const img = document.createElement('img');
  img.src = URL.createObjectURL(file);
  await new Promise((resolve) => img.onload = resolve);

  let w = img.width, h = img.height;
  const scale = Math.min(maxW / w, maxH / h, 1);
  w = Math.round(w * scale);
  h = Math.round(h * scale);

  const canvas = document.createElement('canvas');
  canvas.width = w;
  canvas.height = h;

  const ctx = canvas.getContext('2d');
  ctx.drawImage(img, 0, 0, w, h);

  const blob = await new Promise((resolve) =>
    canvas.toBlob(resolve, 'image/jpeg', quality)
  );

  return new File([blob], 'upload.jpg', { type: 'image/jpeg' });
}

const form = document.getElementById('postForm');
const imgInput = document.getElementById('images');

form.addEventListener('submit', async (e) => {
  if (!imgInput.files || imgInput.files.length === 0) return;

  const files = Array.from(imgInput.files);
  if (files.length > 4) {
    e.preventDefault();
    alert('画像は最大4枚までです');
    return;
  }

  e.preventDefault();

  const dt = new DataTransfer();
  for (const file of files) {
    const resized = await resizeImageToFile(file);
    dt.items.add(resized);
  }

  const hidden = document.getElementById('imagesHidden');
  hidden.files = dt.files;

  form.submit();
});
</script>

</body>
</html>
