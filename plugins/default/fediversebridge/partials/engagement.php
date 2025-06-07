<?php
/**
 * partials/engagement.php
 * 🇳🇱 Engagement-overzicht: replies, likes, boosts, volgers
 * 🇬🇧 Engagement summary: replies, likes, boosts, followers
 *
 * Aangeroepen vanuit optin.php
 */

if (!isset($username) || !isset($inbox_dir) || !isset($replies_root) || !isset($followers_file)) {
    echo "<p style='color:red;'>⚠️ Missing context for engagement overview.</p>";
    return;
}

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

// 📌 REACTIES
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

// 💗 LIKES
if (!empty($likes)) {
    echo "<h4>" . ossn_print('fediversebridge:likes:title') . "</h4><ul style='background:#fff3f3;padding:10px;border:1px solid #f5c2c7;'>";
    foreach ($likes as $like) {
        echo "<li>" . ossn_print('fediversebridge:like:by', [htmlspecialchars($like['from']), htmlspecialchars($like['target']), $like['time']]) . "</li>";
    }
    echo "</ul>";
}

// 🔁 BOOSTS
if (!empty($announces)) {
    echo "<h4>" . ossn_print('fediversebridge:announces:title') . "</h4><ul style='background:#e6f4ff;padding:10px;border:1px solid #b6d8ff;'>";
    foreach ($announces as $ann) {
        $target = htmlspecialchars($ann['target']);
        echo "<li>" . ossn_print('fediversebridge:announce:by', [htmlspecialchars($ann['from']), $target, $target, $ann['time']]) . "</li>";
    }
    echo "</ul>";
}

// 📥 REPLIES OP EIGEN POSTS
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
            if (function_exists('render_collapsible_thread')) {
                render_collapsible_thread($guid, $entries);
            }
        }
    }
}

// 👥 VOLGERS
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
?>
