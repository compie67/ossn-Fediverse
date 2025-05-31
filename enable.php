<?php
/**
 * enable.php – Activation script for FediverseBridge
 * Created by Eric Redegeld for nlsociaal.nl
 */

// Base path for component user data
$base_path = ossn_get_userdata('components/FediverseBridge');
$log_file = "{$base_path}/logs/fediverse.log";

// Create required subdirectories
$dirs = ['logs', 'private', 'outbox', 'inbox', 'followers', 'optin'];
foreach ($dirs as $dir) {
    $path = "{$base_path}/{$dir}";
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            error_log("Failed to create directory: {$path}");
        }
    }
}

// Static users for initial setup (for demo/test purposes)
$users = ['admin', 'testsociaal'];

foreach ($users as $username) {
    // Paths per user
    $priv_dir     = "{$base_path}/private";
    $outbox_dir   = "{$base_path}/outbox/{$username}";
    $inbox_dir    = "{$base_path}/inbox/{$username}";
    $follower_dir = "{$base_path}/followers/{$username}";

    $privkey_file = "{$priv_dir}/{$username}.pem";
    $pubkey_file  = "{$priv_dir}/{$username}.pubkey";

    // Generate key pair if not already present
    if (!file_exists($privkey_file)) {
        $res = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        ]);

        if (!$res) {
            file_put_contents($log_file, date('c') . " ERROR: Failed to generate OpenSSL key for {$username}\n", FILE_APPEND);
            continue;
        }

        // Export private key to file
        openssl_pkey_export($res, $privout);
        file_put_contents($privkey_file, $privout);
        file_put_contents($log_file, date('c') . " INFO: Private key created: {$username}.pem\n", FILE_APPEND);

        // Export public key to file
        $pubout = openssl_pkey_get_details($res);
        if (isset($pubout['key'])) {
            file_put_contents($pubkey_file, $pubout['key']);
            file_put_contents($log_file, date('c') . " INFO: Public key created: {$username}.pubkey\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, date('c') . " WARNING: Failed to extract public key for {$username}\n", FILE_APPEND);
        }
    }

    // Create inbox, outbox and followers directories per user
    foreach ([$outbox_dir, $inbox_dir, $follower_dir] as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                file_put_contents($log_file, date('c') . " ERROR: Could not create directory {$dir}\n", FILE_APPEND);
            }
        }
    }

    // Create a test ActivityPub message for verification
    $now = date('c');
    $actor = ossn_site_url("fediverse/actor/{$username}");
    $outbox_base = ossn_site_url("fediverse/outbox/{$username}");

    $note_id = "{$outbox_base}#note-enable";
    $activity_id = "{$outbox_base}#activity-enable";
    $public_url = ossn_site_url("shared_content/post/enable/preview/" . time());

    $note = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $note_id,
        'type' => 'Note',
        'summary' => null,
        'attributedTo' => $actor,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        'content' => "Test message from enable.php (user: {$username})<br /><a href='{$public_url}' target='_blank'>nlsociaal.nl</a>",
        'published' => $now,
        'url' => $public_url
    ];

    $activity = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $activity_id,
        'type' => 'Create',
        'actor' => $actor,
        'published' => $now,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        'object' => $note
    ];

    // Save test message as JSON in user's outbox
    $jsonfile = "{$outbox_dir}/enable-test.json";
    file_put_contents($jsonfile, json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($log_file, date('c') . " INFO: Test message written to: {$jsonfile}\n", FILE_APPEND);
}

// Log activation complete
file_put_contents($log_file, date('c') . " INFO: INSTALL: FediverseBridge activated successfully\n", FILE_APPEND);
