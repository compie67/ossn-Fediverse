<?php
/**
 * plugins/default/fediversebridge/optin.php
 * 🇳🇱 Profielpagina Fediverse met opt-in, Bluesky-handle, replies, likes en volgers
 * 🇬🇧 Fediverse profile page with opt-in, Bluesky handle, replies, likes and followers
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page();
}

$user = $params['user'];
$username = $user->username;
$viewer = ossn_loggedin_user();

// 🔐 Alleen eigenaar of admin mag deze pagina bekijken
if (!$viewer || ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin())) {
    ossn_error_page();
}

// 📁 Padinstellingen
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
$bluesky = '';

// 🎯 Bluesky-handle ophalen
if ($optedin) {
    $json = json_decode(file_get_contents($optin_file), true);
    $bluesky = $json['bluesky'] ?? '';
}

// 📥 Formulierverwerking
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wilt_optin     = input('fediverse_optin') === 'yes';
    $bluesky_input  = trim(input('bluesky_handle'));

    if ($wilt_optin) {
        if (!is_dir(dirname($optin_file))) mkdir(dirname($optin_file), 0755, true);

        // 🔐 Sleutels genereren
        if (!file_exists($private_file)) {
            $res = openssl_pkey_new(['private_key_bits' => 2048]);
            openssl_pkey_export($res, $privout);
            file_put_contents($private_file, $privout);
            $pubout = openssl_pkey_get_details($res);
            file_put_contents($public_file, $pubout['key']);
        }

        if (!is_dir($outbox_dir)) mkdir($outbox_dir, 0755, true);

        file_put_contents($optin_file, json_encode([
            'enabled' => true,
            'actor_url' => $actor_url,
            'bluesky' => $bluesky_input
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        // Welkomstbericht aanmaken
        $first_file = "{$outbox_dir}/first.json";
        if (!file_exists($first_file)) {
            $now = date('c');
            $note = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => "{$actor_url}#note-first",
                'type' => 'Note',
                'attributedTo' => $actor_url,
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'cc' => [$actor_url, "{$actor_url}/followers"],
                'content' => "👋 Hallo Fediverse! Ik ben {$username} op {$domain}.",
                'published' => $now,
                'url' => "{$actor_url}#note-first"
            ];

            $activity = [
                '@context' => 'https://www.w3.org/ns/activitystreams',
                'id' => "{$actor_url}#activity-first",
                'type' => 'Create',
                'actor' => $actor_url,
                'published' => $now,
                'to' => ['https://www.w3.org/ns/activitystreams#Public'],
                'object' => $note
            ];

            file_put_contents($first_file, json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        }

        fediversebridge_log("✅ Opt-in geactiveerd + Bluesky opgeslagen voor {$username}");
        ossn_trigger_message("✅ Fediverse deelname geactiveerd", 'success');
    } else {
        foreach ([$optin_file, $private_file, $public_file] as $f) {
            if (file_exists($f)) unlink($f);
        }
        if (is_dir($outbox_dir)) {
            array_map('unlink', glob("{$outbox_dir}/*.json"));
            rmdir($outbox_dir);
        }
        fediversebridge_log("❌ Opt-in uitgeschakeld voor {$username}");
        ossn_trigger_message("❌ Fediverse deelname uitgeschakeld", 'error');
    }

    redirect(REF);
}
?>

<!-- 🔧 HTML interface -->
<div class="ossn-profile-extra-menu fediverse-optin-page">
    <h3>Fediverse</h3>

    <?php if ($optedin): ?>
        <p class="ossn-message-success">✅ Je neemt momenteel deel aan het Fediverse.</p>
    <?php else: ?>
        <p class="ossn-message-error">❌ Je hebt Fediverse-integratie uitgeschakeld.</p>
    <?php endif; ?>

    <form method="post">
        <div>
            <input type="checkbox" id="fediverse_optin" name="fediverse_optin" value="yes" <?php if ($optedin) echo 'checked'; ?>>
            <label for="fediverse_optin">Ik wil deelnemen aan het Fediverse</label>
        </div>
        <div style="margin-top:10px;">
            <label for="bluesky_handle">Bluesky-handle (optioneel):</label><br>
            <input type="text" name="bluesky_handle" id="bluesky_handle" class="form-control" placeholder="nlsociaal.bsky.social" value="<?php echo htmlspecialchars($bluesky); ?>" />
        </div>
        <br>
        <input type="submit" class="btn btn-primary" value="Opslaan" />
    </form>

    <!-- 🔍 Debuggegevens -->
    <pre style="background:#eee;padding:5px;margin-top:10px;">
[DEBUG]
Username: <?php echo $username; ?>
Private key: <?php echo file_exists($private_file) ? 'OK' : 'MISSING'; ?>
Public key: <?php echo file_exists($public_file) ? 'OK' : 'MISSING'; ?>
Outbox dir: <?php echo is_dir($outbox_dir) ? 'OK' : 'MISSING'; ?>
Opt-in json: <?php echo file_exists($optin_file) ? 'OK' : 'MISSING'; ?>
User GUID: <?php echo $user->guid; ?>
Bluesky: <?php echo $bluesky ?: 'n.v.t.'; ?>
    </pre>

    <!-- 🔵 Bluesky-profiel -->
    <?php if ($bluesky): ?>
        <div style="background:#e8f8ff;padding:10px;margin-top:10px;border:1px solid #99ccee;">
            <strong>🔵 Bluesky:</strong> <a href="https://bsky.app/profile/<?php echo htmlspecialchars($bluesky); ?>" target="_blank">@<?php echo htmlspecialchars($bluesky); ?></a>
        </div>
    <?php endif; ?>

    <!-- 💬 Likes & Replies -->
    <?php
    $interactions = [];

    if (is_dir($inbox_dir)) {
        foreach (glob("{$inbox_dir}/*.json") as $file) {
            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) continue;

            $type = $json['type'] ?? '';
            $actor = $json['actor'] ?? 'onbekend';

            if ($type === 'Like' && isset($json['object'])) {
                $interactions[] = [
                    'type' => '❤️ Like',
                    'author' => $actor,
                    'target' => $json['object'],
                    'content' => '',
                    'time' => $json['published'] ?? ''
                ];
            }

            if ($type === 'Create' && isset($json['object']['type']) && $json['object']['type'] === 'Note') {
                $interactions[] = [
                    'type' => isset($json['object']['inReplyTo']) ? '💬 Reply' : '📝 Bericht',
                    'author' => $actor,
                    'target' => $json['object']['inReplyTo'] ?? '',
                    'content' => strip_tags($json['object']['content'] ?? ''),
                    'time' => $json['published'] ?? ''
                ];
            }
        }
    }

    if (!empty($interactions)) {
        echo "<h4>📨 Ontvangen berichten</h4><table class='table'>";
        echo "<thead><tr><th>Type</th><th>Van</th><th>Op</th><th>Inhoud</th></tr></thead><tbody>";
        foreach ($interactions as $row) {
            echo "<tr>";
            echo "<td>{$row['type']}</td>";
            echo "<td><a href='{$row['author']}' target='_blank'>{$row['author']}</a></td>";
            echo "<td><a href='{$row['target']}' target='_blank'>" . htmlspecialchars(basename($row['target'])) . "</a></td>";
            echo "<td>" . htmlspecialchars($row['content']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }

    // 👥 Volgers tonen
    if (file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true);
        if (is_array($followers) && !empty($followers)) {
            echo "<h4>👥 Volgers</h4><ul>";
            foreach ($followers as $f) {
                $safe = htmlspecialchars($f);
                echo "<li><a href='{$safe}' target='_blank'>{$safe}</a></li>";
            }
            echo "</ul>";
        }
    }
    ?>
</div>
