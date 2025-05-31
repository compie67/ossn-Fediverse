<?php
/**
 * plugins/default/fediversebridge/optin.php
 * Fediverse profile page with opt-in, replies, likes and followers
 *
 * Created by Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page();
}

$user     = $params['user'];
$username = $user->username;
$viewer   = ossn_loggedin_user();

// Access control: only the user or admin
if (!$viewer || ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin())) {
    ossn_error_page();
}

// Paths and constants
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

// Handle opt-in form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wants_optin = input('fediverse_optin') === 'yes';

    if ($wants_optin) {
        if (!is_dir(dirname($optin_file))) mkdir(dirname($optin_file), 0755, true);

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
            'actor_url' => $actor_url
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        $now = date('c');
        $note = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => "{$actor_url}#note-first",
            'type' => 'Note',
            'attributedTo' => $actor_url,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'cc' => [$actor_url, "{$actor_url}/followers"],
            'content' => "Hello Fediverse! I am {$username} on {$domain}.",
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

        file_put_contents("{$outbox_dir}/first.json", json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fediversebridge_log("Opt-in activated for {$username}");
        ossn_trigger_message("Fediverse participation enabled", 'success');
    } else {
        foreach ([$optin_file, $private_file, $public_file] as $f) {
            if (file_exists($f)) unlink($f);
        }
        if (is_dir($outbox_dir)) {
            array_map('unlink', glob("{$outbox_dir}/*.json"));
            rmdir($outbox_dir);
        }
        fediversebridge_log("Opt-in disabled for {$username}");
        ossn_trigger_message("Fediverse participation disabled", 'error');
    }

    redirect(REF);
}
?>

<div class="ossn-profile-extra-menu fediverse-optin-page">
    <h3>Fediverse</h3>

    <?php if ($optedin): ?>
        <p class="ossn-message-success">✅ You are currently participating in the Fediverse. Other users can follow you and receive your posts.</p>
    <?php else: ?>
        <p class="ossn-message-error">❌ You are not participating in the Fediverse.</p>
    <?php endif; ?>

    <form method="post">
        <input type="checkbox" id="fediverse_optin" name="fediverse_optin" value="yes" <?php if ($optedin) echo 'checked'; ?>>
        <label for="fediverse_optin">I want to participate in the Fediverse</label>
        <br><br>
        <input type="submit" class="btn btn-primary" value="Save" />
    </form>

    <div style="background: #f0f8ff; padding: 15px; border: 1px solid #a1c4e7; margin-top: 20px; border-radius: 5px;">
        <h4>🔗 Your Fediverse identity</h4>
        <p><strong>@<?php echo $username . '@' . $domain; ?></strong></p>
        <p>Share this link to let others follow you:</p>
        <p><a href="<?php echo $actor_url; ?>" target="_blank"><?php echo $actor_url; ?></a></p>
    </div>

    <?php
    // Gather inbox activity
    $interactions = [];

    if (is_dir($inbox_dir)) {
        foreach (glob("{$inbox_dir}/*.json") as $file) {
            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) continue;

            $type   = $json['type'] ?? '';
            $actor  = $json['actor'] ?? 'unknown';

            if ($type === 'Like' && isset($json['object'])) {
                $interactions[] = [
                    'type' => 'Like',
                    'author' => $actor,
                    'target' => $json['object'],
                    'content' => '',
                    'time' => $json['published'] ?? ''
                ];
            }

            if ($type === 'Create' && isset($json['object']['type']) && $json['object']['type'] === 'Note') {
                $interactions[] = [
                    'type' => isset($json['object']['inReplyTo']) ? 'Reply' : 'Post',
                    'author' => $actor,
                    'target' => $json['object']['inReplyTo'] ?? '',
                    'content' => strip_tags($json['object']['content'] ?? ''),
                    'time' => $json['published'] ?? ''
                ];
            }
        }
    }

    if (!empty($interactions)) {
        echo "<h4>💬 Activity</h4><table class='table'>";
        echo "<thead><tr><th>Type</th><th>From</th><th>To</th><th>Content</th></tr></thead><tbody>";
        foreach ($interactions as $row) {
            echo "<tr>";
            echo "<td>{$row['type']}</td>";
            echo "<td><a href='{$row['author']}' target='_blank'>" . htmlspecialchars($row['author']) . "</a></td>";
            echo "<td><a href='{$row['target']}' target='_blank'>" . htmlspecialchars(basename($row['target'])) . "</a></td>";
            echo "<td>" . htmlspecialchars($row['content']) . "</td>";
            echo "</tr>";
        }
        echo "</tbody></table>";
    }

    // Show followers
    if (file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true);
        if (is_array($followers) && !empty($followers)) {
            echo "<h4>👥 Followers</h4><ul>";
            foreach ($followers as $f) {
                $safe = htmlspecialchars($f);
                echo "<li><a href='{$safe}' target='_blank'>{$safe}</a></li>";
            }
            echo "</ul>";
        }
    }
    ?>
</div>
