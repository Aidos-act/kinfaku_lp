<?php

declare(strict_types=1);

require __DIR__ . '/includes/trial-form.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html', true, 303);
    exit;
}

$result = kinfaku_validate_trial_form($_POST);

if (!$result['ok']) {
    kinfaku_render_form_response(false, $result['errors']);
    exit;
}

$send = kinfaku_send_trial_form($result['data']);

if (!$send['ok']) {
    kinfaku_render_form_response(false, ['_form' => $send['error'] ?? '送信に失敗しました。']);
    exit;
}

header('Location: thanks.html', true, 303);
exit;

/**
 * @param array<string, string> $errors
 */
function kinfaku_render_form_response(bool $success, array $errors): void
{
    header('Content-Type: text/html; charset=UTF-8');
    ?>
<!DOCTYPE html>
<html lang="ja">
<head>
  <base href="/kinfaku/">
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $success ? '送信完了' : '送信エラー' ?>｜金融ファクシミリ新聞</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>
  <main id="main" style="padding: 4rem 1rem; text-align: center;">
    <div class="inner-container">
      <?php if ($success): ?>
        <h1>お申込みを受け付けました</h1>
        <p>3営業日以内に、アクセス用IDをメールでお送りします。</p>
      <?php else: ?>
        <h1>送信できませんでした</h1>
        <ul style="list-style: none; padding: 0; margin: 1.5rem 0; color: #b92b27;">
          <?php foreach ($errors as $message): ?>
            <li><?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?></li>
          <?php endforeach; ?>
        </ul>
      <?php endif; ?>
      <p><a href="index.html#form_section">フォームに戻る</a></p>
    </div>
  </main>
</body>
</html>
    <?php
}
