<?php
/**
 * helpers/inbox.php
 * Created by Eric Redegeld for nlsociaal.nl
 *
 * Loads incoming inbox items (likes and replies) from the user inbox directory.
 */

/**
 * Retrieves all relevant inbox items: likes and replies (Notes with inReplyTo)
 *
 * @param string $username
 * @return array
 */
function fediversebridge_get_replies($username) {
    $dir = ossn_get_userdata("components/FediverseBridge/inbox/{$username}/");
    $logfile = ossn_get_userdata("components/FediverseBridge/logs/inbox_debug.log");

    $log = function($msg) use ($logfile) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG) {
            file_put_contents($logfile, date('c') . " {$msg}\n", FILE_APPEND);
        }
    };

    if (!is_dir($dir)) {
        $log("❌ Inbox directory not found for user: {$username}");
        return [];
    }

    $items = [];
    foreach (glob($dir . '*.json') as $file) {
        $raw = file_get_contents($file);
        $json = json_decode($raw, true);

        if (!is_array($json)) {
            $log("⚠️ Invalid JSON in file: {$file}");
            continue;
        }

        $type = $json['type'] ?? 'unknown';
        $log("📩 Processing file {$file} | Type: {$type}");

        // ❤️ LIKE
        if ($type === 'Like' && isset($json['object'])) {
            $items[] = [
                'type' => 'Like',
                'author' => $json['actor'] ?? 'unknown',
                'published' => $json['published'] ?? '',
                'object' => $json['object'],
            ];
            $log("✅ Like by {$json['actor']} on {$json['object']}");
        }

        // 💬 REPLY: Create → Note → inReplyTo
        if ($type === 'Create') {
            $object = $json['object'] ?? null;
            if (is_array($object) && ($object['type'] ?? '') === 'Note' && isset($object['inReplyTo'])) {
                $items[] = [
                    'type' => 'Reply',
                    'author' => $json['actor'] ?? 'unknown',
                    'published' => $json['published'] ?? '',
                    'content' => strip_tags($object['content'] ?? ''),
                    'inReplyTo' => $object['inReplyTo']
                ];
                $log("✅ Reply by {$json['actor']} to {$object['inReplyTo']}");
            } elseif (isset($object['type']) && $object['type'] === 'Note') {
                $log("ℹ️ Note received without inReplyTo – probably not a reply.");
            }
        }
    }

    return $items;
}
