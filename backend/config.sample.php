<?php
// Copy this file to config.php and fill in your values. config.php is git-ignored.
// The user must be hb_app (created by database/roles.sql), NOT the postgres superuser.
return [
    'host'     => 'localhost',
    'port'     => '5432',
    'dbname'   => 'grp03_projectdb',
    'user'     => 'hb_app',
    'password' => 'CHANGE_ME',

    // true = password-reset codes are shown on screen and nothing is sent (testing only).
    // false = codes are sent by email / SMS using the settings below.
    'demo_mode' => true,

    // Email codes: run "composer require phpmailer/phpmailer" inside backend/, then fill this in.
    // Gmail: turn on 2-step verification and create an App Password (use it as 'pass').
    'smtp' => ['host' => 'smtp.gmail.com', 'port' => 587, 'secure' => 'tls', 'user' => '', 'pass' => '', 'from' => ''],

    // SMS codes: Semaphore (semaphore.co, Philippines). Paste your API key.
    'sms'  => ['apikey' => '', 'sender' => ''],
];
