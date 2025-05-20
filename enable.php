<?php
/**
 * enable.php – Activatie van FediverseBridge
 * 🇳🇱 Gemaakt door Eric Redegeld voor nlsociaal.nl
 * 🇬🇧 Created by Eric Redegeld for nlsociaal.nl
 */

// 📁 Basispad voor componentdata
// 📁 Base path for component user data
$base_path = ossn_get_userdata('components/FediverseBridge');
$log_file = "{$base_path}/logs/fediverse.log";

// 📁 Vereiste submappen aanmaken
// 📁 Create required subdirectories
$dirs = ['logs', 'private', 'outbox', 'inbox', 'followers', 'optin'];
foreach ($dirs as $dir) {
    $path = "{$base_path}/{$dir}";
    if (!is_dir($path)) {
        if (!mkdir($path, 0755, true)) {
            error_log("❌ Kan map {$path} niet aanmaken");
        }
    }
}

// 👤 Statische gebruikersinstellingen voor eerste setup (voor demo/test)
// 👤 Static users for initial setup (for demo/test purposes)
$users = ['admin', 'testsociaal'];

foreach ($users as $username) {
    // 📁 Paden per gebruiker
    // 📁 User-specific directories
    $priv_dir     = "{$base_path}/private";
    $outbox_dir   = "{$base_path}/outbox/{$username}";
    $inbox_dir    = "{$base_path}/inbox/{$username}";
    $follower_dir = "{$base_path}/followers/{$username}";

    $privkey_file = "{$priv_dir}/{$username}.pem";
    $pubkey_file  = "{$priv_dir}/{$username}.pubkey";

    // 🔐 Genereer sleutelpaar als nog niet aanwezig
    // 🔐 Generate key pair if not already exists
    if (!file_exists($privkey_file)) {
        $res = openssl_pkey_new([
            "private_key_bits" => 2048,
            "private_key_type" => OPENSSL_KEYTYPE_RSA
        ]);

        if (!$res) {
            file_put_contents($log_file, date('c') . " ❌ OpenSSL key genereren mislukt voor {$username}\n", FILE_APPEND);
            continue;
        }

        // 🔒 Exporteer private key naar bestand
        // 🔒 Export private key to file
        openssl_pkey_export($res, $privout);
        file_put_contents($privkey_file, $privout);
        file_put_contents($log_file, date('c') . " 🔐 Private key aangemaakt: {$username}.pem\n", FILE_APPEND);

        // 🔑 Exporteer public key naar bestand
        // 🔑 Export public key to file
        $pubout = openssl_pkey_get_details($res);
        if (isset($pubout['key'])) {
            file_put_contents($pubkey_file, $pubout['key']);
            file_put_contents($log_file, date('c') . " 🔑 Public key aangemaakt: {$username}.pubkey\n", FILE_APPEND);
        } else {
            file_put_contents($log_file, date('c') . " ⚠️ Public key extractie mislukt voor {$username}\n", FILE_APPEND);
        }
    }

    // 📁 Inbox, outbox en followers map per gebruiker aanmaken
    // 📁 Create inbox, outbox and followers directories per user
    foreach ([$outbox_dir, $inbox_dir, $follower_dir] as $dir) {
        if (!is_dir($dir)) {
            if (!mkdir($dir, 0755, true)) {
                file_put_contents($log_file, date('c') . " ❌ Map {$dir} kon niet aangemaakt worden\n", FILE_APPEND);
            }
        }
    }

    // 📨 Maak een test-ActivityPub bericht voor controle
    // 📨 Create a test ActivityPub message for verification
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
        'summary' => null, // ✅ Mastodon verwacht dit veld
        'attributedTo' => $actor,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        'content' => "🧪 Testbericht vanuit enable.php (user: {$username})<br /><a href='{$public_url}' target='_blank'>🔗 nlsociaal.nl</a>",
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

    // 💾 Sla testbericht op in JSON-formaat in de outbox
    // 💾 Save test message as JSON in user's outbox
    $jsonfile = "{$outbox_dir}/enable-test.json";
    file_put_contents($jsonfile, json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    file_put_contents($log_file, date('c') . " ✅ Testbericht geschreven: {$jsonfile}\n", FILE_APPEND);
}

// ✅ Installatie afgerond
// ✅ Installation completed
file_put_contents($log_file, date('c') . " ✅ INSTALL: FediverseBridge succesvol geactiveerd\n", FILE_APPEND);
