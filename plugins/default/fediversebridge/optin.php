<?php
/**
 * plugins/default/fediversebridge/optin.php
 * Profielpagina Fediverse – opt-in, reacties, volgers, likes, boosts en blokkades
 * Door Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page();
}

$user = $params['user'];
$username = $user->username;
$viewer = ossn_loggedin_user();

if (!$viewer || ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin())) {
    ossn_error_page();
}

//  Padconfiguratie
$base_path      = ossn_get_userdata("components/FediverseBridge");
$optin_file     = "{$base_path}/optin/{$username}.json";
$private_file   = "{$base_path}/private/{$username}.pem";
$public_file    = "{$base_path}/private/{$username}.pubkey";
$outbox_dir     = "{$base_path}/outbox/{$username}/";
$inbox_dir      = "{$base_path}/inbox/{$username}/";
$replies_root   = "{$base_path}/replies/";
$followers_file = "{$base_path}/followers/{$username}.json";
$blocked_file   = "{$base_path}/blocked/{$username}.json";
$actor_url      = ossn_site_url("fediverse/actor/{$username}");
$domain         = parse_url(ossn_site_url(), PHP_URL_HOST);

$optedin = file_exists($optin_file);
$blocked = file_exists($blocked_file) ? json_decode(file_get_contents($blocked_file), true) : [];

//  Verwerk formulier
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ✅ Opt-in toggle
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
        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("✅ Opt-in + sleutels + eerste bericht aangemaakt voor {$username}");
        }
        ossn_trigger_message("✅ Fediverse deelname ingeschakeld voor {$username}", 'success');
    } elseif (!$wilt_optin && $optedin) {
        @unlink($optin_file);
        @unlink($private_file);
        @unlink($public_file);
        if (is_dir($outbox_dir)) {
            array_map('unlink', glob("{$outbox_dir}/*.json"));
            @rmdir($outbox_dir);
        }
        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("❌ Opt-in, sleutels en outbox verwijderd voor {$username}");
        }
        ossn_trigger_message("❌ Fediverse deelname uitgeschakeld voor {$username}", 'error');
    }

    //  Blokkade verwerken
    $block = input('block_actor');
    if ($block) {
        $actor_to_block = trim($block);
        if (!in_array($actor_to_block, $blocked)) {
            $blocked[] = $actor_to_block;
            if (!is_dir(dirname($blocked_file))) mkdir(dirname($blocked_file), 0755, true);
            file_put_contents($blocked_file, json_encode($blocked, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            ossn_trigger_message(ossn_print('fediversebridge:optin:block:success', [$actor_to_block]), 'success');
        }
    }

    redirect(REF);
}

//  Helper voor collapsibles
function render_collapsible_thread($guid, $entries) {
    $id = "thread_" . $guid;
    echo "<div style='margin-bottom:10px; border:1px solid #ccc;'>";
    echo "<button onclick=\"document.getElementById('{$id}').classList.toggle('hidden');\" style='width:100%;text-align:left;background:#eee;padding:5px;border:none;\">";
    echo ossn_print('fediversebridge:thread:title', [$guid]) . "</button>";
    echo "<div id='{$id}' class='hidden' style='display:none;padding:10px;'><ul>";
    foreach ($entries as $r) {
        echo "<li><strong>" . htmlspecialchars($r['author']) . "</strong> op <em>{$r['published']}</em><br />";
        echo htmlspecialchars($r['content']) . "</li>";
    }
    echo "</ul></div></div>";
}
?>

<style>.hidden { display: none; }</style>

<div class="ossn-profile-extra-menu fediverse-optin-page">
    <h3><?php echo ossn_print('fediversebridge:optin:profile:title'); ?></h3>

    <?php if ($optedin): ?>
        <p style="background: #dff0d8; padding: 10px; border: 1px solid #c3e6cb;">
            <?php echo ossn_print('fediversebridge:optin:profile:enabled'); ?>
        </p>
    <?php else: ?>
        <p style="background: #f2dede; padding: 10px; border: 1px solid #ebccd1;">
            <?php echo ossn_print('fediversebridge:optin:profile:disabled'); ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <input type="checkbox" id="fediverse_optin" name="fediverse_optin" value="yes" <?php if ($optedin) echo 'checked'; ?>>
        <label for="fediverse_optin"><?php echo ossn_print('fediversebridge:optin:profile:checkbox'); ?></label>
        <br><br>
        <input type="submit" class="btn btn-primary" value="<?php echo ossn_print('fediversebridge:optin:profile:save'); ?>" />
    </form>

    <hr>
    <h4><?php echo ossn_print('fediversebridge:optin:block:title'); ?></h4>
    <form method="post">
        <input type="text" name="block_actor" placeholder="<?php echo ossn_print('fediversebridge:optin:block:placeholder'); ?>" required>
        <input type="submit" class="btn btn-danger btn-sm" value="<?php echo ossn_print('fediversebridge:optin:block:btn'); ?>" />
    </form>

<?php
//  Inboxverwerking
$replies = $likes = $announces = [];

if (is_dir($inbox_dir)) {
    foreach (glob("{$inbox_dir}/*.json") as $file) {
        $json = json_decode(file_get_contents($file), true);
        $actor = $json['actor'] ?? '';
        if (in_array($actor, $blocked)) continue;

        $type = $json['type'] ?? '';

        if ($type === 'Create' && isset($json['object']['type']) && $json['object']['type'] === 'Note') {
            $replies[] = [
                'author' => $actor,
                'content' => strip_tags($json['object']['content'] ?? ''),
                'published' => $json['published'] ?? '',
                'in_reply_to' => $json['object']['inReplyTo'] ?? null,
            ];
        } elseif ($type === 'Like') {
            $likes[] = [
                'from' => $actor,
                'target' => $json['object'] ?? '',
                'time' => $json['published'] ?? '',
            ];
        } elseif ($type === 'Announce') {
            $announces[] = [
                'from' => $actor,
                'target' => $json['object'] ?? '',
                'time' => $json['published'] ?? '',
            ];
        }
    }
}

//  Reacties
if (!empty($replies)) {
    echo "<h4>" . ossn_print('fediversebridge:replies:title') . "</h4><ul style='background:#f9f9f9;padding:10px;border:1px solid #ccc;'>";
    foreach ($replies as $reply) {
        echo "<li>" . ossn_print('fediversebridge:reply:by', [htmlspecialchars($reply['author']), $reply['published']]) . "<br />";
        echo htmlspecialchars($reply['content']);
        if (!empty($reply['in_reply_to'])) {
            echo "<br><small>" . ossn_print('fediversebridge:reply:inreplyto') . " <a href='" . htmlspecialchars($reply['in_reply_to']) . "' target='_blank'>" . htmlspecialchars($reply['in_reply_to']) . "</a></small>";
        }
        echo "</li>";
    }
    echo "</ul>";
}

//  Likes
if (!empty($likes)) {
    echo "<h4>" . ossn_print('fediversebridge:likes:title') . "</h4><ul style='background:#fff3f3;padding:10px;border:1px solid #f5c2c7;'>";
    foreach ($likes as $like) {
        echo "<li>" . ossn_print('fediversebridge:like:by', [htmlspecialchars($like['from']), htmlspecialchars($like['target']), $like['time']]) . "</li>";
    }
    echo "</ul>";
}

//  Boosts
if (!empty($announces)) {
    echo "<h4>" . ossn_print('fediversebridge:announces:title') . "</h4><ul style='background:#e6f4ff;padding:10px;border:1px solid #b6d8ff;'>";
    foreach ($announces as $ann) {
        $target = htmlspecialchars($ann['target']);
        echo "<li>" . ossn_print('fediversebridge:announce:by', [htmlspecialchars($ann['from']), $target, $target, $ann['time']]) . "</li>";
    }
    echo "</ul>";
}

//  Eigen reply threads
if (is_dir($replies_root)) {
    $reply_threads = [];
    foreach (glob("{$replies_root}/*", GLOB_ONLYDIR) as $dir) {
        $guid = basename($dir);
        foreach (glob("{$dir}/*.json") as $file) {
            $json = json_decode(file_get_contents($file), true);
            if ($json['type'] === 'Create' && ($json['object']['type'] ?? '') === 'Note') {
                $reply_threads[$guid][] = [
                    'author' => $json['actor'] ?? 'onbekend',
                    'content' => strip_tags($json['object']['content'] ?? ''),
                    'published' => $json['published'] ?? '',
                ];
            }
        }
    }

    if (!empty($reply_threads)) {
        echo "<h4>" . ossn_print('fediversebridge:ownreplies:title') . "</h4>";
        foreach ($reply_threads as $guid => $entries) {
            render_collapsible_thread($guid, $entries);
        }
    }
}

// Volgers
if (file_exists($followers_file)) {
    $followers = json_decode(file_get_contents($followers_file), true);
    if (is_array($followers) && !empty($followers)) {
        echo "<h4>" . ossn_print('fediversebridge:followers:title') . "</h4><ul style='background:#f9f9f9;padding:10px;border:1px solid #ccc;'>";
        foreach ($followers as $f) {
            $safe = htmlspecialchars($f);
            echo "<li><a href='{$safe}' target='_blank'>{$safe}</a></li>";
        }
        echo "</ul>";
    }
}

//  Fediverse post check
echo "<hr><h4>🕵️‍♂️ " . ossn_print('fediversebridge:check:title') . "</h4>";
echo '<form method="get"><input type="url" name="check_fedi_url" placeholder="https://mastodon.social/@user/123" style="width:60%;" required> ';
echo '<input type="submit" class="btn btn-secondary btn-sm" value="' . ossn_print('fediversebridge:check:btn') . '"></form>';

if ($check = input('check_fedi_url', false)) {
    echo "<div style='margin-top:10px;background:#fcfcfc;padding:10px;border:1px solid #ccc;'>";
    echo "<p><strong>📨 " . ossn_print('fediversebridge:check:trying') . "</strong><br />" . htmlspecialchars($check) . "</p>";

    $ch = curl_init($check);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams"'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 8);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 4);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response || $http_code !== 200) {
        echo "<p style='color:red;'>❌ " . ossn_print('fediversebridge:check:fail') . "<br /><code>" . htmlspecialchars($error ?: "HTTP $http_code") . "</code></p></div>";
    } else {
        $json = json_decode($response, true);
        if (!$json) {
            echo "<p style='color:red;'>❌ " . ossn_print('fediversebridge:check:invalidjson') . "</p></div>";
        } else {
            echo "<p>✅ " . ossn_print('fediversebridge:check:success') . "</p><ul style='list-style:square;margin-left:20px;'>";
            echo "<li><strong>ID:</strong> " . htmlspecialchars($json['id'] ?? '') . "</li>";
            echo "<li><strong>Type:</strong> " . htmlspecialchars($json['type'] ?? '') . "</li>";
            echo "<li><strong>Auteur:</strong> " . htmlspecialchars($json['attributedTo'] ?? '') . "</li>";
            echo "<li><strong>InReplyTo:</strong> " . htmlspecialchars($json['inReplyTo'] ?? '-') . "</li>";
            echo "<li><strong>Public:</strong> " . (in_array('https://www.w3.org/ns/activitystreams#Public', (array)($json['to'] ?? [])) ? '✅ Ja' : '❌ Nee') . "</li>";
            echo "</ul><div style='margin-top:5px;padding:10px;background:#f8f8f8;border:1px dashed #aaa;'>";
            echo "<strong>📝 Inhoud:</strong><br />";
            echo nl2br(htmlspecialchars(strip_tags($json['content'] ?? '(leeg)')));
            echo "</div></div>";
        }
    }
}

//  Debug info
echo "<hr><pre>" . ossn_print('fediversebridge:debug:title') . "\n";
echo ossn_print('fediversebridge:debug:username', [$username]) . "\n";
echo ossn_print('fediversebridge:debug:privatekey', [file_exists($private_file) ? 'OK' : 'MISSING']) . "\n";
echo ossn_print('fediversebridge:debug:publickey', [file_exists($public_file) ? 'OK' : 'MISSING']) . "\n";
echo ossn_print('fediversebridge:debug:outbox', [is_dir($outbox_dir) ? 'OK' : 'MISSING']) . "\n";
echo ossn_print('fediversebridge:debug:optinfile', [file_exists($optin_file) ? 'OK' : 'MISSING']) . "\n";
echo ossn_print('fediversebridge:debug:userguid', [$user->guid]) . "</pre>";
?>
</div>

<div style="background: #f0f8ff; padding: 15px; border: 1px solid #a1c4e7; margin-top: 20px; border-radius: 5px;">
    <h4> <?php echo ossn_print('fediversebridge:optin:profile:findable'); ?></h4>
    <p><strong>@<?php echo $username; ?>@<?php echo $domain; ?></strong></p>
    <p><a href="<?php echo $actor_url; ?>" target="_blank"><?php echo $actor_url; ?></a></p>
    <p>
         WebFinger:<br />
        <a href="https://<?php echo $domain; ?>/.well-known/webfinger?resource=acct:<?php echo $username; ?>@<?php echo $domain; ?>" target="_blank">
            .well-known/webfinger

         WebFinger:<br />
        <a href="https://<?php echo $domain; ?>/.well-known/webfinger?resource=acct:<?php echo $username; ?>@<?php echo $domain; ?>" target="_blank">
            .well-known/webfinger?resource=acct:<?php echo $username; ?>@<?php echo $domain; ?>
        </a>
    </p>
</div>

