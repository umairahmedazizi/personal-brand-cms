<?php
// copy to config.php and fill in. config.php is git-ignored.

return [
    'db' => [
        // sqlite for local, mysql for hostinger
        'driver' => 'sqlite',
        'sqlite_path' => __DIR__ . '/data/app.sqlite',

        // mysql creds from hPanel
        'mysql_host' => 'localhost',
        'mysql_name' => 'u123456789_mk',
        'mysql_user' => 'u123456789_mk',
        'mysql_pass' => 'CHANGE_ME',
        'mysql_charset' => 'utf8mb4',
    ],

    'site' => [
        'name' => 'Adrian Cole',
        'url'  => 'https://adriancole.example',   // no trailing slash
    ],

    'mail' => [
        'admin_to'   => 'hello@adriancole.example',   // where enquiries land
        'from'       => 'no-reply@adriancole.example',  // use an address on your domain
        'from_name'  => 'adriancole.example',
        'auto_reply' => true,
        // smtp optional, off = use php mail()
        'smtp' => [
            'enabled' => false,
            'host'    => 'smtp.hostinger.com',
            'port'    => 465,
            'secure'  => 'ssl',   // ssl or tls
            'user'    => 'no-reply@adriancole.example',
            'pass'    => 'CHANGE_ME',
        ],
    ],

    // first admin, seeded on first run. change password after.
    'admin' => [
        'email'    => 'admin@adriancole.example',
        'password' => 'change-this-now',
        'name'     => 'Adrian Cole',
    ],

    // chatbot (groq). key from console.groq.com/keys, blank = off
    'groq' => [
        'enabled'     => true,
        'api_key'     => '',
        'endpoint'    => 'https://api.groq.com/openai/v1/chat/completions',
        'model'       => 'llama-3.3-70b-versatile',
        'max_tokens'  => 700,
        'temperature' => 0.4,
        'rate_per_min' => 6,
        'rate_per_day' => 120,
        'max_input_chars' => 1500,
    ],

    'debug' => false,
];
