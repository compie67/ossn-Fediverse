<?php
/**
 * enable.php – Activation logic for FediverseBridge
 * Created by Eric Redegeld for nlsociaal.nl
 */

// 📁 Base data path for the component
$base_path = ossn_get_userdata('components/FediverseBridge');
$log_file  = "{$base_path}/logs/fediverse.log";

// 📁 Create required subfolders
$dirs = ['logs', 'private', 'outbox', 'followers', 'optin'];
foreach ($dirs as $dir) {
    $path = "{$base_path}/{$dir}";
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            error_log("❌ Failed to create directory: {$path}");
        }
    }
}

// 👥 Identify users:
// In production: based on opt-in files
// In dev/test: manually add a central user (e.g., 'admin')
$users = [];

$optin_dir = "{$base_path}/optin";
if (is_dir($optin_dir)) {
    $files = scandir($optin_dir);
    foreach ($files as $file) {
        if (str_ends_with($file, '.json')) {
            $username = basename($file, '.json');
            $users[] = $username;
        }
    }
}

// ➕ Optionally add system-level fallback users
$users = array_unique(array_merge($users, ['admin']));

// 🧾 Start log
file_put_contents($log_file, date('c') . " ⚙️ ENABLE started – users: " . implode(', ', $users) . "\n", FILE_APPEND);

// 🔁 Generate keys and folders per user
foreach ($users as $username) {
    $priv_dir     = "{$base_path}/private";
    $outbox_dir   = "{$base_path}/outbox/{$username}";
    $follower_dir = "{$base_path}/followers/{$username}";

    $privkey_file = "{$priv_dir}/{$username}.pem";
    $pubkey_file  = "{$priv_dir}/{$username}.pubkey";

    // 🔐 Generate key pair if not already present
    if (!file_exists($privkey_file)) {
        $res = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        ]);

        if (!$res) {
            file_put_contents($log_file, date('c') . " ❌ Failed to generate OpenSSL key for {$username}\n", FILE_APPEND);
            continue;
        }

        openssl_pkey_export($res, $privout);
        file_put_contents($privkey_file, $privout);
        chmod($privkey_file, 0600); // Restrict permissions on private key

        file_put_contents($log_file, date('c') . " 🔐 Private key created: {$username}.pem\n", FILE_APPEND);

        $pubout = openssl_pkey_get_details($res);
        if (isset($pubout['key'])) {
            file_put_contents($pubkey_file, $pubout['key']);
            file_put_contents($log_file, date('c') . " 🔑 Public key created: {$username}.pubkey\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, date('c') . " ⚠️ Failed to extract public key for {$username}\n", FILE_APPEND);
        }
    }

    // 📁 Create outbox and followers folder
    foreach ([$outbox_dir, $follower_dir] as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                file_put_contents($log_file, date('c') . " ❌ Failed to create directory: {$dir}\n", FILE_APPEND);
            }
        }
    }

    // 📨 Optional: Write test post in outbox if not already present
    $jsonfile = "{$outbox_dir}/enable-test.json";
    if (!file_exists($jsonfile)) {
        $now         = date('c');
        $actor       = ossn_site_url("fediverse/actor/{$username}");
        $outbox_base = ossn_site_url("fediverse/outbox/{$username}");
        $note_id     = "{$outbox_base}#note-enable";
        $activity_id = "{$outbox_base}#activity-enable";
        $public_url  = ossn_site_url("shared_content/post/enable/preview/" . time());

        $note = [
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            'id'           => $note_id,
            'type'         => 'Note',
            'summary'      => null,
            'attributedTo' => $actor,
            'to'           => ['https://www.w3.org/ns/activitystreams#Public'],
            'content'      => "🧪 Test message from enable.php (user: {$username})<br /><a href='{$public_url}' target='_blank'>🔗 nlsociaal.nl</a>",
            'published'    => $now,
            'url'          => $public_url
        ];

        $activity = [
            '@context'  => 'https://www.w3.org/ns/activitystreams',
            'id'        => $activity_id,
            'type'      => 'Create',
            'actor'     => $actor,
            'published' => $now,
            'to'        => ['https://www.w3.org/ns/activitystreams#Public'],
            'object'    => $note
        ];

        file_put_contents($jsonfile, json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        file_put_contents($log_file, date('c') . " ✅ Test message written: {$jsonfile}\n", FILE_APPEND);
    }
}

// ✅ Finish install logging
file_put_contents($log_file, date('c') . " ✅ INSTALL: FediverseBridge successfully enabled\n", FILE_APPEND);
