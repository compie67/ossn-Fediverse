<?php
/**
 * enable.php – Activation script for FediverseBridge
 * Gemaakt door Eric Redegeld voor nlsociaal.nl – met taalondersteuning
 */

$base_path = ossn_get_userdata('components/FediverseBridge');
$log_file  = "{$base_path}/logs/fediverse.log";

// Vereiste mappen
$dirs = ['logs', 'private', 'outbox', 'inbox', 'followers', 'optin', 'replies', 'blocked'];
foreach ($dirs as $dir) {
    $path = "{$base_path}/{$dir}";
    if (!is_dir($path)) {
        if (mkdir($path, 0755, true)) {
            file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:dir:created', [$path]) . "\n", FILE_APPEND);
        } else {
            error_log("Failed to create directory: {$path}");
            file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:dir:failed', [$path]) . "\n", FILE_APPEND);
        }
    }
}

// Initialisatie admin gebruiker
$username = 'admin';
$privkey_file = "{$base_path}/private/{$username}.pem";
$pubkey_file  = "{$base_path}/private/{$username}.pubkey";

// Sleutelpaar genereren
if (!file_exists($privkey_file)) {
    $res = openssl_pkey_new([
        "private_key_bits" => 2048,
        "private_key_type" => OPENSSL_KEYTYPE_RSA
    ]);
    if ($res) {
        openssl_pkey_export($res, $privout);
        file_put_contents($privkey_file, $privout);
        file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:key:priv:created', [$username]) . "\n", FILE_APPEND);

        $pubout = openssl_pkey_get_details($res);
        if (isset($pubout['key'])) {
            file_put_contents($pubkey_file, $pubout['key']);
            file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:key:pub:created', [$username]) . "\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:key:pub:failed', [$username]) . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:key:gen:failed', [$username]) . "\n", FILE_APPEND);
    }
}

// Submappen voor admin gebruiker
$subdirs = ["outbox/{$username}", "inbox/{$username}", "followers/{$username}", "blocked/{$username}"];
foreach ($subdirs as $dir) {
    $fullpath = "{$base_path}/{$dir}";
    if (!is_dir($fullpath)) {
        if (mkdir($fullpath, 0755, true)) {
            file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:dir:created', [$fullpath]) . "\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:dir:failed', [$fullpath]) . "\n", FILE_APPEND);
        }
    }
}

// Opt-in bestand
$optin_file = "{$base_path}/optin/{$username}.json";
$actor_url  = ossn_site_url("fediverse/actor/{$username}");

if (!file_exists($optin_file)) {
    $optin_data = [
        'enabled' => true,
        'actor_url' => $actor_url,
    ];
    file_put_contents($optin_file, json_encode($optin_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:optin:created', [$username]) . "\n", FILE_APPEND);
}

// Testbericht aanmaken
$outbox_base = ossn_site_url("fediverse/outbox/{$username}");
$note_id     = "{$outbox_base}#note-enable";
$activity_id = "{$outbox_base}#activity-enable";
$now         = date('c');
$public_url  = ossn_site_url("shared_content/post/enable/preview/" . time());

$note = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => $note_id,
    'type' => 'Note',
    'summary' => null,
    'attributedTo' => $actor_url,
    'to' => ['https://www.w3.org/ns/activitystreams#Public'],
    'content' => "Test message from enable.php (user: {$username})<br /><a href='{$public_url}' target='_blank'>nlsociaal.nl</a>",
    'published' => $now,
    'url' => $public_url
];

$activity = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => $activity_id,
    'type' => 'Create',
    'actor' => $actor_url,
    'published' => $now,
    'to' => ['https://www.w3.org/ns/activitystreams#Public'],
    'object' => $note
];

$jsonfile = "{$base_path}/outbox/{$username}/enable-test.json";
file_put_contents($jsonfile, json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:outbox:test', [$jsonfile]) . "\n", FILE_APPEND);

// Afsluiting
file_put_contents($log_file, date('c') . ' ' . ossn_print('fediversebridge:enable:log:install:done') . "\n", FILE_APPEND);
