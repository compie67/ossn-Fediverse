<?php
/**
 * plugins/default/fediversebridge/optin.php
 * 🇳🇱 Profielpagina Fediverse – inclusief opt-in, reacties en volgers
 * 🇬🇧 Fediverse profile page – includes opt-in, replies and followers
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page(); // Alleen ingelogden
}

$user = $params['user'];
$username = $user->username;
$viewer = ossn_loggedin_user();

// 🔐 Alleen eigen profiel of admin
if (!$viewer || ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin())) {
    ossn_error_page();
}

// 📁 Padconfiguratie
$base_path      = ossn_get_userdata("components/FediverseBridge");
$optin_file     = "{$base_path}/optin/{$username}.json";
$private_file   = "{$base_path}/private/{$username}.pem";
$public_file    = "{$base_path}/private/{$username}.pubkey";
$outbox_dir     = "{$base_path}/outbox/{$username}/";
$inbox_dir      = "{$base_path}/inbox/{$username}/";
$followers_file = "{$base_path}/followers/{$username}.json";
$actor_url      = ossn_site_url("fediverse/actor/{$username}");
$domain         = parse_url(ossn_site_url(), PHP_URL_HOST);

$optedin = file_exists($optin_file);

if (function_exists('fediversebridge_log')) {
    fediversebridge_log("👤 Opt-in scherm geopend voor {$username}");
}

// 📝 Verwerk formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wilt_optin = input('fediverse_optin') === 'yes';

    if ($wilt_optin && !$optedin) {
        foreach ([dirname($optin_file), dirname($private_file), $outbox_dir] as $path) {
            if (!is_dir($path)) mkdir($path, 0755, true);
        }

        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privout);
        file_put_contents($private_file, $privout);
        $pubout = openssl_pkey_get_details($res);
        file_put_contents($public_file, $pubout['key']);

        file_put_contents($optin_file, json_encode([
            'enabled' => true,
            'actor_url' => $actor_url,
        ]));

        $now = date('c');
        $note = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => ossn_site_url("fediverse/outbox/{$username}#note-first"),
            'type' => 'Note',
            'attributedTo' => $actor_url,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'content' => "👋 Hallo Fediverse! Ik ben {$username} op {$domain}.",
            'published' => $now,
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => ossn_site_url("fediverse/outbox/{$username}#activity-first"),
            'type' => 'Create',
            'actor' => $actor_url,
            'published' => $now,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => $note
        ];

        file_put_contents("{$outbox_dir}/first.json", json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fediversebridge_log("✅ Opt-in + sleutels + eerste bericht aangemaakt voor {$username}");
        ossn_trigger_message("✅ Fediverse deelname ingeschakeld voor {$username}", 'success');
    } elseif (!$wilt_optin && $optedin) {
        if (file_exists($optin_file)) unlink($optin_file);
        if (file_exists($private_file)) unlink($private_file);
        if (file_exists($public_file)) unlink($public_file);
        if (is_dir($outbox_dir)) {
            array_map('unlink', glob("{$outbox_dir}/*.json"));
            rmdir($outbox_dir);
        }
        fediversebridge_log("❌ Opt-in, sleutels en outbox verwijderd voor {$username}");
        ossn_trigger_message("❌ Fediverse deelname uitgeschakeld voor {$username}", 'error');
    }

    redirect(REF);
}
?>

<div class="ossn-profile-extra-menu fediverse-optin-page">
    <h3>Fediverse</h3>

    <?php if ($optedin): ?>
        <p style="background: #dff0d8; padding: 10px; border: 1px solid #c3e6cb;">
            ✅ Je neemt momenteel deel aan het Fediverse. Andere servers kunnen jouw openbare berichten met hashtags ontvangen.
        </p>
    <?php else: ?>
        <p style="background: #f2dede; padding: 10px; border: 1px solid #ebccd1;">
            ❌ Je hebt Fediverse-integratie uitgeschakeld.
        </p>
    <?php endif; ?>

    <form method="post">
        <div>
            <input type="checkbox" id="fediverse_optin" name="fediverse_optin" value="yes" <?php if ($optedin) echo 'checked'; ?>>
            <label for="fediverse_optin">Ik wil deelnemen aan het Fediverse</label>
        </div>
        <br>
        <input type="submit" class="btn btn-primary" value="Opslaan" />
    </form>

    <pre style="background:#eee;padding:5px;margin-top:10px;">
[DEBUG]
Username: <?php echo $username; ?>

Private key: <?php echo file_exists($private_file) ? 'OK' : 'MISSING'; ?>
Public key: <?php echo file_exists($public_file) ? 'OK' : 'MISSING'; ?>
Outbox dir: <?php echo is_dir($outbox_dir) ? 'OK' : 'MISSING'; ?>
Opt-in json: <?php echo file_exists($optin_file) ? 'OK' : 'MISSING'; ?>
User GUID: <?php echo $user->guid; ?>
    </pre>

    <div style="background: #f0f8ff; padding: 15px; border: 1px solid #a1c4e7; margin-top: 20px; border-radius: 5px;">
        <h4>🔗 Vindbaar op het Fediverse</h4>
        <p><strong>@<?php echo $username; ?>@<?php echo $domain; ?></strong></p>
        <p><a href="<?php echo $actor_url; ?>" target="_blank"><?php echo $actor_url; ?></a></p>
        <p>
            🔎 WebFinger:<br />
            <a href="https://<?php echo $domain; ?>/.well-known/webfinger?resource=acct:<?php echo $username; ?>@<?php echo $domain; ?>" target="_blank">
                .well-known/webfinger?resource=acct:<?php echo $username; ?>@<?php echo $domain; ?>
            </a>
        </p>
    </div>

    <?php
    $replies = [];

    if (is_dir($inbox_dir)) {
        foreach (glob("{$inbox_dir}/*.json") as $file) {
            $json = json_decode(file_get_contents($file), true);
            if (isset($json['type']) && $json['type'] === 'Create') {
                $obj = $json['object'] ?? null;
                if (is_array($obj) && ($obj['type'] ?? '') === 'Note') {
                    $replies[] = [
                        'author' => $json['actor'] ?? 'onbekend',
                        'content' => strip_tags($obj['content'] ?? ''),
                        'published' => $json['published'] ?? '',
                        'is_reply' => isset($obj['inReplyTo']),
                    ];
                }
            }
        }
    }

    if (!empty($replies)) {
        echo "<h4>💬 Ontvangen berichten</h4><ul style='background:#f9f9f9;padding:10px;border:1px solid #ccc;'>";
        foreach ($replies as $reply) {
            echo "<li style='margin-bottom:10px;'>";
            echo $reply['is_reply'] ? "<strong>💬 Antwoord</strong>" : "<strong>📝 Bericht</strong>";
            echo " van <strong>" . htmlspecialchars($reply['author']) . "</strong>";
            echo " op <em>{$reply['published']}</em><br />";
            echo htmlspecialchars($reply['content']);
            echo "</li>";
        }
        echo "</ul>";
    }

    if (file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true);
        if (is_array($followers) && !empty($followers)) {
            echo "<h4>👥 Volgers</h4><ul style='background:#f9f9f9;padding:10px;border:1px solid #ccc;'>";
            foreach ($followers as $f) {
                $safe = htmlspecialchars($f);
                echo "<li><a href='{$safe}' target='_blank'>{$safe}</a></li>";
            }
            echo "</ul>";
        }
    }
    ?>
</div>
