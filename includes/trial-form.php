<?php

declare(strict_types=1);

const KINFAKU_TRIAL_TARGET_PRODUCT = '金融ファクシミリ新聞（2週間無料トライアル）';

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

    if ($postal1 === '') {
        $errors['postal_code_1'] = '郵便番号（上3桁）を入力してください。';
    } elseif (strlen($postal1) !== 3) {
        $errors['postal_code_1'] = '郵便番号（上3桁）を正しく入力してください。';
    }
    if ($postal2 === '') {
        $errors['postal_code_2'] = '郵便番号（下4桁）を入力してください。';
    } elseif (strlen($postal2) !== 4) {
        $errors['postal_code_2'] = '郵便番号（下4桁）を正しく入力してください。';
    }
    if ($address1 === '') {
        $errors['address_line_1'] = '住所を入力してください。';
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

    $subject = '【金ファク トライアルお申込み】';
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

    $subject = '【金融ファクシミリ新聞】トライアルお申し込みありがとうございます。';
    $body = kinfaku_build_trial_autoreply_mail_body($data);
    $headers = kinfaku_build_mail_headers($from, $fromName);

    kinfaku_send_mail($data['email'], $subject, $body, $headers);
}

/**
 * @param array<string, string> $data
 */
function kinfaku_build_trial_admin_mail_body(array $data): string
{
    $lines = [
        '金融ファクシミリ新聞 LP より、トライアルお申込みがありました。',
        '',
        '【お申込情報】',
        '',
    ];

    foreach (kinfaku_trial_mail_fields($data) as $label => $value) {
        $lines[] = '■ ' . $label;
        $lines[] = $value;
        $lines[] = '';
    }

    $lines[] = '【送信日時】';
    $lines[] = date('Y-m-d H:i:s');
    $lines[] = '';
    $lines[] = '【送信者IPアドレス】';
    $lines[] = $_SERVER['REMOTE_ADDR'] ?? '（取得不可）';

    return implode("\n", $lines);
}

/**
 * @param array<string, string> $data
 */
function kinfaku_build_trial_autoreply_mail_body(array $data): string
{
    $lines = [
        $data['name'] . ' 様',
        '',
        '金ファク電子版のトライアルお申し込みを頂きありがとうございます。追って弊社営業からご連絡いたします。',
        '',
        '【お申込情報】',
        '',
    ];

    foreach (kinfaku_trial_mail_fields($data) as $label => $value) {
        $lines[] = '■ ' . $label;
        $lines[] = $value;
        $lines[] = '';
    }

    $lines[] = '※本メールは送信専用です。返信いただいてもお答えできません。';
    $lines[] = '';
    $lines[] = '────────────────';
    $lines[] = '金融ファクシミリ新聞社';
    $lines[] = 'https://kinfaku.jp/';

    return implode("\n", $lines);
}

/**
 * @param array<string, string> $data
 * @return array<string, string>
 */
function kinfaku_trial_mail_fields(array $data): array
{
    return [
        '名前' => $data['name'],
        'ふりがな' => $data['furigana'],
        '会社名' => $data['company'],
        '部署名' => kinfaku_format_optional_field($data['department']),
        '役職名' => '（未入力）',
        '郵便番号' => kinfaku_format_postal_code($data),
        '住所' => kinfaku_format_address($data),
        '電話番号' => $data['tel'],
        'FAX番号' => '（未入力）',
        'メールアドレス' => $data['email'],
        '対象商品' => KINFAKU_TRIAL_TARGET_PRODUCT,
        'ご質問' => kinfaku_format_referral_source($data['referral_source']),
    ];
}

function kinfaku_format_referral_source(string $referral): string
{
    if ($referral === '') {
        return '（未入力）';
    }

    return KINFAKU_REFERRAL_LABELS[$referral] ?? $referral;
}

/**
 * @param array<string, string> $data
 */
function kinfaku_format_postal_code(array $data): string
{
    if ($data['postal_code_1'] === '' && $data['postal_code_2'] === '') {
        return '（未入力）';
    }

    return $data['postal_code_1'] . '-' . $data['postal_code_2'];
}

/**
 * @param array<string, string> $data
 */
function kinfaku_format_address(array $data): string
{
    $postal = kinfaku_format_postal_code($data);
    $parts = array_filter([
        $postal !== '（未入力）' ? '〒' . $postal : '',
        $data['address_line_1'],
        $data['address_line_2'],
    ]);

    return $parts !== [] ? implode("\n", $parts) : '（未入力）';
}

function kinfaku_format_optional_field(string $value): string
{
    return $value !== '' ? $value : '（未入力）';
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
