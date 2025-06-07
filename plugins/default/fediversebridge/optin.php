<?php
/**
 * plugins/default/fediversebridge/optin.php
 * 🇳🇱 Profielpagina Fediverse – inclusief opt-in, handler, debug, replies en volgers
 * Door Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page();
}

$user       = $params['user'];
$username   = $user->username;
$viewer     = ossn_loggedin_user();

if ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin()) {
    ossn_error_page();
}

//  Padconfiguratie
$base_path      = ossn_get_userdata("components/FediverseBridge");
$optin_file     = "{$base_path}/optin/{$username}.json";
$private_file   = "{$base_path}/private/{$username}.pem";
$public_file    = "{$base_path}/private/{$username}.pubkey";
$outbox_dir     = "{$base_path}/outbox/{$username}/";
$inbox_dir      = "{$base_path}/inbox/{$username}/";
$replies_root   = "{$base_path}/replies/{$user->guid}";
$followers_file = "{$base_path}/followers/{$username}.json";
$blocked_file   = "{$base_path}/blocked/{$username}.json";

// 🔄 Verwerk formulier vóór uitvoer
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $viewer->guid === $user->guid) {
    if (isset($_POST['optin'])) {
        if (!file_exists($private_file)) {
            $keyres = openssl_pkey_new(['private_key_bits' => 2048]);
            openssl_pkey_export($keyres, $privkey);
            file_put_contents($private_file, $privkey);
            $pubkey = openssl_pkey_get_details($keyres)['key'];
            file_put_contents($public_file, $pubkey);
        }
        file_put_contents($optin_file, json_encode(['enabled' => true]));
        ossn_trigger_message(ossn_print('fediversebridge:optin:profile:success', [$username]), 'success');
    } else {
        @unlink($optin_file);
        ossn_trigger_message(ossn_print('fediversebridge:optin:profile:error', [$username]), 'error');
    }
    redirect(ossn_site_url("fediversebridge/optin/{$username}"));
}

// Status bepalen
$opted_in = file_exists($optin_file);

// 🛠️ Debug
echo "<h3>" . ossn_print('fediversebridge:debug:title') . "</h3><ul>";
echo "<li>" . ossn_print('fediversebridge:debug:username', [$username]) . "</li>";
echo "<li>" . ossn_print('fediversebridge:debug:privatekey', [file_exists($private_file) ? '✔️' : '❌']) . "</li>";
echo "<li>" . ossn_print('fediversebridge:debug:publickey', [file_exists($public_file) ? '✔️' : '❌']) . "</li>";
echo "<li>" . ossn_print('fediversebridge:debug:outbox', [is_dir($outbox_dir) ? '✔️' : '❌']) . "</li>";
echo "<li>" . ossn_print('fediversebridge:debug:optinfile', [$opted_in ? '✔️' : '❌']) . "</li>";
echo "<li>" . ossn_print('fediversebridge:debug:userguid', [$user->guid]) . "</li>";
echo "</ul>";

// Handler info
if ($opted_in && file_exists($public_file)) {
    echo "<h3>" . ossn_print('fediversebridge:profile:header') . "</h3><ul>";
    echo "<li><strong>" . ossn_print('fediversebridge:profile:actorurl') . ":</strong> <code>" . ossn_site_url("fediverse/actor/{$username}") . "</code></li>";
    echo "<li><strong>" . ossn_print('fediversebridge:profile:webfinger') . ":</strong> <code>acct:{$username}@" . parse_url(ossn_site_url(), PHP_URL_HOST) . "</code></li>";
    echo "</ul>";
}

// pt-in formulier
$checked = $opted_in ? 'checked' : '';
echo "<form method='post'>";
echo "<label><input type='checkbox' name='optin' {$checked}/> " . ossn_print('fediversebridge:optin:profile:checkbox') . "</label><br><br>";
echo "<input type='submit' class='btn btn-primary' value='" . ossn_print('fediversebridge:optin:profile:save') . "' />";
echo "</form>";

//  Engagement-data
$replies = $likes = $announces = [];
$blocked = file_exists($blocked_file) ? json_decode(file_get_contents($blocked_file), true) : [];

if (is_dir($inbox_dir)) {
    foreach (glob("{$inbox_dir}/*.json") as $file) {
        $json = json_decode(file_get_contents($file), true);
        $actor = $json['actor'] ?? '';
        if (in_array($actor, $blocked)) continue;

        $type = $json['type'] ?? '';
        if ($type === 'Create' && ($json['object']['type'] ?? '') === 'Note') {
            $replies[] = [
                'author' => $actor,
                'content' => strip_tags($json['object']['content'] ?? ''),
                'published' => $json['published'] ?? '',
                'in_reply_to' => $json['object']['inReplyTo'] ?? null,
            ];
        } elseif ($type === 'Like') {
            $likes[] = [
                'from' => $actor,
                'target' => is_array($json['object']) ? ($json['object']['id'] ?? '') : $json['object'],
                'time' => $json['published'] ?? '',
            ];
        } elseif ($type === 'Announce') {
            $announces[] = [
                'from' => $actor,
                'target' => is_array($json['object']) ? ($json['object']['id'] ?? '') : $json['object'],
                'time' => $json['published'] ?? '',
            ];
        }
    }
}

//  REPLIES
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

//  LIKES
if (!empty($likes)) {
    echo "<h4>" . ossn_print('fediversebridge:likes:title') . "</h4><ul style='background:#fff3f3;padding:10px;border:1px solid #f5c2c7;'>";
    foreach ($likes as $like) {
        echo "<li>" . ossn_print('fediversebridge:like:by', [htmlspecialchars($like['from']), htmlspecialchars($like['target']), $like['time']]) . "</li>";
    }
    echo "</ul>";
}

//  BOOSTS
if (!empty($announces)) {
    echo "<h4>" . ossn_print('fediversebridge:announces:title') . "</h4><ul style='background:#e6f4ff;padding:10px;border:1px solid #b6d8ff;'>";
    foreach ($announces as $ann) {
        $target = htmlspecialchars($ann['target']);
        echo "<li>" . ossn_print('fediversebridge:announce:by', [htmlspecialchars($ann['from']), $target, $target, $ann['time']]) . "</li>";
    }
    echo "</ul>";
}

// REPLIES OP EIGEN POSTS
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
            echo "<div style='border:1px solid #ddd;margin-bottom:10px;padding:5px;'>";
            echo "<strong>Post GUID {$guid}</strong><ul>";
            foreach ($entries as $reply) {
                echo "<li><strong>" . htmlspecialchars($reply['author']) . "</strong> ({$reply['published']}):<br/>";
                echo htmlspecialchars($reply['content']) . "</li>";
            }
            echo "</ul></div>";
        }
    }
}

//  VOLGERS
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
