<?php

declare(strict_types=1);

require __DIR__ . '/includes/trial-form.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html', true, 303);
    exit;
}

$result = kinfaku_validate_trial_form($_POST);

if (!$result['ok']) {
    $error = isset($result['errors']['_spam']) ? 'spam' : 'invalid';
    header('Location: index.html?form_error=' . $error . '#form_section', true, 303);
    exit;
}

$send = kinfaku_send_trial_form($result['data']);

if (!$send['ok']) {
    header('Location: index.html?form_error=send#form_section', true, 303);
    exit;
}

header('Location: thanks.html', true, 303);
exit;
