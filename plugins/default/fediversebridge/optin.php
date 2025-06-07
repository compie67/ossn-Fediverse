<?php
/**
 * plugins/default/fediversebridge/optin.php
 * 🇳🇱 Profielpagina Fediverse – inclusief opt-in, debug, replies en volgers
 * 🇬🇧 Fediverse profile page – includes opt-in, debug, replies and followers
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page();
}

$user     = $params['user'];
$username = $user->username;
$viewer   = ossn_loggedin_user();

if (!$viewer || ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin())) {
    ossn_error_page();
}

// 📁 Padconfiguratie
$base_path     = ossn_get_userdata("components/FediverseBridge");
$optin_file    = "$base_path/optin/{$username}.json";
$private_file  = "$base_path/private/{$username}.pem";
$public_file   = "$base_path/private/{$username}.pubkey";
$outbox_dir    = "$base_path/outbox/{$username}/";
$inbox_dir     = "$base_path/inbox/{$username}/";
$replies_root  = "$base_path/replies/";
$followers_file = "$base_path/followers/{$username}.json";
$blocked_file   = "$base_path/blocked/{$username}.json";

$is_opted_in = file_exists($optin_file);

// ✅ Opt-in toggle verwerken
if ($viewer->guid === $user->guid && isset($_POST['fediverse_optin'])) {
    $optin = (bool) $_POST['fediverse_optin'];
    if ($optin) {
        // ⛓ Sleutels aanmaken indien nodig
        if (!file_exists($private_file)) {
            $keypair = openssl_pkey_new([
                "private_key_bits" => 2048,
                "private_key_type" => OPENSSL_KEYTYPE_RSA,
            ]);
            openssl_pkey_export($keypair, $private_key);
            $key_details = openssl_pkey_get_details($keypair);
            $public_key  = $key_details['key'];

            file_put_contents($private_file, $private_key);
            file_put_contents($public_file, $public_key);
        }

        // 🔒 Opt-in JSON opslaan
        file_put_contents($optin_file, json_encode([
            "enabled" => true,
            "since"   => date(DATE_ATOM)
        ]));

        $is_opted_in = true;
    } else {
        // 🗑 Opt-out
        @unlink($optin_file);
        $is_opted_in = false;
    }
    ossn_trigger_message(ossn_print('fediversebridge:optin:saved'));
}

// 🔐 Debug
if (ossn_isAdminLoggedin()) {
    echo "<div class='ossn-message-box' style='font-size:small;background:#ffffe0;padding:10px;'>";
    echo "<strong>" . ossn_print('fediversebridge:debug:title') . "</strong><br/>";
    echo ossn_print('fediversebridge:debug:username', [$username]) . "<br/>";
    echo ossn_print('fediversebridge:debug:privatekey', [file_exists($private_file) ? '✔️' : 'MISSING']) . "<br/>";
    echo ossn_print('fediversebridge:debug:publickey', [file_exists($public_file) ? '✔️' : 'MISSING']) . "<br/>";
    echo ossn_print('fediversebridge:debug:outbox', [is_dir($outbox_dir) ? '✔️' : 'MISSING']) . "<br/>";
    echo ossn_print('fediversebridge:debug:optinfile', [file_exists($optin_file) ? '✔️' : 'MISSING']) . "<br/>";
    echo ossn_print('fediversebridge:debug:userguid', [$user->guid]);
    echo "</div><br />";
}



// ✅ Formulier
if ($viewer->guid === $user->guid) {
    echo "<form method='post'>";
    echo "<label><input type='checkbox' name='fediverse_optin' value='1'" . ($is_opted_in ? ' checked' : '') . "> " . ossn_print('fediversebridge:optin:label') . "</label><br><br>";
    echo "<input type='submit' value='" . ossn_print('save') . "' class='btn btn-primary btn-sm' />";
    echo "</form><hr />";
}

// 📊 Engagement, likes, replies, volgers, boosts etc.
require_once __DIR__ . '/partials/engagement.php';
