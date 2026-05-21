<?php

declare(strict_types=1);

/**
 * Copy this file to config.php and set your mail addresses.
 */
return [
    // Admin inbox for trial applications
    'mail_to' => 'trial@example.com',

    // Envelope / From address (must be allowed on your host)
    'mail_from' => 'noreply@example.com',
    'mail_from_name' => '金融ファクシミリ新聞社',

    // Send confirmation email to the applicant
    'send_autoreply' => true,
];
