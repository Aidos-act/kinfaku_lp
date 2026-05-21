<?php

declare(strict_types=1);

const KINFAKU_REFERRAL_LABELS = [
    'search' => 'Google Yahoo! などの検索結果',
    'social' => 'Facebook Instagram',
    'news_site' => 'ビジネス・経済系ニュースサイトの紹介',
    'referral' => '知人・他経営者からの紹介',
    'past_subscriber' => '過去に試読・購読したことがある',
    'other' => 'その他',
];

/**
 * @return array<string, string>
 */
function kinfaku_trial_form_config(): array
{
    static $config = null;

    if ($config !== null) {
        return $config;
    }

    $path = dirname(__DIR__) . '/config.php';
    if (!is_file($path)) {
        $path = dirname(__DIR__) . '/config.example.php';
    }

    $loaded = require $path;
    if (!is_array($loaded)) {
        throw new RuntimeException('Invalid config.php format.');
    }

    $config = $loaded;
    return $config;
}

/**
 * @param array<string, mixed> $input
 * @return array{ok: bool, errors: array<string, string>, data: array<string, string>}
 */
function kinfaku_validate_trial_form(array $input): array
{
    $errors = [];

    if (!empty($input['company_url'])) {
        $errors['_spam'] = '送信に失敗しました。';
        return ['ok' => false, 'errors' => $errors, 'data' => []];
    }

    $name = kinfaku_trim_field($input['name'] ?? '');
    $furigana = kinfaku_trim_field($input['furigana'] ?? '');
    $company = kinfaku_trim_field($input['company'] ?? '');
    $department = kinfaku_trim_field($input['department'] ?? '');
    $email = kinfaku_trim_field($input['email'] ?? '');
    $tel = kinfaku_trim_field($input['tel'] ?? '');
    $postal1 = kinfaku_digits_only($input['postal_code_1'] ?? '', 3);
    $postal2 = kinfaku_digits_only($input['postal_code_2'] ?? '', 4);
    $address1 = kinfaku_trim_field($input['address_line_1'] ?? '');
    $address2 = kinfaku_trim_field($input['address_line_2'] ?? '');
    $referral = kinfaku_trim_field($input['referral_source'] ?? '');

    if ($name === '') {
        $errors['name'] = '氏名を入力してください。';
    }
    if ($furigana === '') {
        $errors['furigana'] = 'ふりがなを入力してください。';
    }
    if ($company === '') {
        $errors['company'] = '会社名を入力してください。';
    }
    if ($email === '') {
        $errors['email'] = 'メールアドレスを入力してください。';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'メールアドレスの形式が正しくありません。';
    }
    if ($tel === '') {
        $errors['tel'] = '電話番号を入力してください。';
    }

    if ($postal1 !== '' && strlen($postal1) !== 3) {
        $errors['postal_code_1'] = '郵便番号（上3桁）を正しく入力してください。';
    }
    if ($postal2 !== '' && strlen($postal2) !== 4) {
        $errors['postal_code_2'] = '郵便番号（下4桁）を正しく入力してください。';
    }

    if ($referral !== '' && !isset(KINFAKU_REFERRAL_LABELS[$referral])) {
        $errors['referral_source'] = '選択内容が正しくありません。';
    }

    $data = [
        'name' => $name,
        'furigana' => $furigana,
        'company' => $company,
        'department' => $department,
        'email' => $email,
        'tel' => $tel,
        'postal_code_1' => $postal1,
        'postal_code_2' => $postal2,
        'address_line_1' => $address1,
        'address_line_2' => $address2,
        'referral_source' => $referral,
    ];

    return [
        'ok' => $errors === [],
        'errors' => $errors,
        'data' => $data,
    ];
}

/**
 * @param array<string, string> $data
 * @return array{ok: bool, error?: string}
 */
function kinfaku_send_trial_form(array $data): array
{
    $config = kinfaku_trial_form_config();
    $to = $config['mail_to'] ?? '';
    $from = $config['mail_from'] ?? '';
    $fromName = $config['mail_from_name'] ?? '金融ファクシミリ新聞社';

    if ($to === '' || $from === '') {
        return ['ok' => false, 'error' => 'メール設定が完了していません。'];
    }

    $subject = '【金ファク】無料トライアルお申込み';
    $body = kinfaku_build_trial_admin_mail_body($data);
    $headers = kinfaku_build_mail_headers($from, $fromName, $data['email']);

    if (!kinfaku_send_mail($to, $subject, $body, $headers)) {
        return ['ok' => false, 'error' => 'メールの送信に失敗しました。時間をおいて再度お試しください。'];
    }

    $autoReply = $config['send_autoreply'] ?? true;
    if ($autoReply) {
        kinfaku_send_trial_form_autoreply($data, $config);
    }

    return ['ok' => true];
}

/**
 * @param array<string, string> $data
 */
function kinfaku_send_trial_form_autoreply(array $data, array $config): void
{
    $from = $config['mail_from'] ?? '';
    $fromName = $config['mail_from_name'] ?? '金融ファクシミリ新聞社';
    if ($from === '' || ($data['email'] ?? '') === '') {
        return;
    }

    $subject = '【金融ファクシミリ新聞】無料トライアルお申込みを受け付けました';
    $body = <<<TEXT
{$data['name']} 様

この度は金融ファクシミリ新聞の無料トライアルにお申込みいただき、ありがとうございます。
以下の内容でお申込みを受け付けました。

3営業日以内に、アクセス用IDをメールでお送りします。
今しばらくお待ちください。

※ 無料トライアル終了後に、自動で有料契約へ移行することはありません。

────────────────
金融ファクシミリ新聞社
TEXT;

    $headers = kinfaku_build_mail_headers($from, $fromName);
    kinfaku_send_mail($data['email'], $subject, $body, $headers);
}

/**
 * @param array<string, string> $data
 */
function kinfaku_build_trial_admin_mail_body(array $data): string
{
    $postal = '';
    if ($data['postal_code_1'] !== '' || $data['postal_code_2'] !== '') {
        $postal = $data['postal_code_1'] . '-' . $data['postal_code_2'];
    }

    $addressParts = array_filter([
        $postal !== '' && $postal !== '-' ? '〒' . $postal : '',
        $data['address_line_1'],
        $data['address_line_2'],
    ]);
    $address = $addressParts !== [] ? implode("\n", $addressParts) : '（未入力）';

    $referral = '（未回答）';
    if ($data['referral_source'] !== '') {
        $referral = KINFAKU_REFERRAL_LABELS[$data['referral_source']] ?? $data['referral_source'];
    }

    $lines = [
        '金融ファクシミリ新聞 LP より、無料トライアルお申込みがありました。',
        '',
        '■ 氏名',
        $data['name'],
        '',
        '■ ふりがな',
        $data['furigana'],
        '',
        '■ 会社名',
        $data['company'],
        '',
        '■ 部署名',
        $data['department'] !== '' ? $data['department'] : '（未入力）',
        '',
        '■ メールアドレス',
        $data['email'],
        '',
        '■ 電話番号',
        $data['tel'],
        '',
        '■ ご住所',
        $address,
        '',
        '■ 金融ファクシミリ新聞をどのように知りましたか？',
        $referral,
        '',
        '送信日時: ' . date('Y-m-d H:i:s'),
    ];

    return implode("\n", $lines);
}

/**
 * @return array<int, string>
 */
function kinfaku_build_mail_headers(string $from, string $fromName, ?string $replyTo = null): array
{
    $encodedFromName = kinfaku_encode_mime_header($fromName);
    $headers = [
        'MIME-Version: 1.0',
        'Content-Type: text/plain; charset=UTF-8',
        'Content-Transfer-Encoding: 8bit',
        'From: ' . $encodedFromName . ' <' . $from . '>',
    ];

    if ($replyTo !== null && $replyTo !== '') {
        $headers[] = 'Reply-To: ' . $replyTo;
    }

    return $headers;
}

function kinfaku_send_mail(string $to, string $subject, string $body, array $headers): bool
{
    if (function_exists('mb_language')) {
        mb_language('Japanese');
        mb_internal_encoding('UTF-8');
        $subject = kinfaku_encode_mime_header($subject);
    }

    return mail($to, $subject, $body, implode("\r\n", $headers));
}

function kinfaku_encode_mime_header(string $text): string
{
    if (function_exists('mb_encode_mimeheader')) {
        return mb_encode_mimeheader($text, 'UTF-8', 'B');
    }

    return '=?UTF-8?B?' . base64_encode($text) . '?=';
}

function kinfaku_trim_field(mixed $value): string
{
    return trim(strip_tags((string) $value));
}

function kinfaku_digits_only(mixed $value, int $maxLength): string
{
    $digits = preg_replace('/\D/u', '', (string) $value) ?? '';
    return substr($digits, 0, $maxLength);
}
