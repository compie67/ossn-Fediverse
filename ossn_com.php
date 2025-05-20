<?php
/**
 * OSSN Component: FediverseBridge
 * 🇳🇱 Gemaakt door Eric Redegeld voor nlsociaal.nl
 * 🇬🇧 Created by Eric Redegeld for nlsociaal.nl
 */

require_once __DIR__ . '/helpers/followers.php';
require_once __DIR__ . '/helpers/sign.php';

function fediversebridge_log($msg) {
    $logfile = ossn_get_userdata('components/FediverseBridge/logs/fediverse.log');
    if (file_exists($logfile) && filesize($logfile) > 1024 * 1024) {
        rename($logfile, $logfile . '.' . time() . '.bak');
    }
    file_put_contents($logfile, date('c') . " {$msg}\n", FILE_APPEND);
}

function fediversebridge_init() {
    fediversebridge_log("✅ INIT: FediverseBridge geladen");

    ossn_register_admin_sidemenu(
        'fediversebridge_optinusers',
        'Fediverse Opt-in Users',
        'fediverse-admin/optinusers',
        'admin'
    );

    ossn_register_callback('page', 'load:profile', 'fediversebridge_add_profile_menu');
    ossn_register_page('fediversebridge', 'fediversebridge_internal_handler');
    ossn_register_page('fediverse', 'fediversebridge_ossn_style_handler');
    ossn_register_page('well-known', 'fediversebridge_wellknown_handler');
    ossn_register_page('fediverse-admin', 'fediversebridge_admin_handler');
      // CSS toevoegen aan admin
    ossn_extend_view('ossn/admin/head', 'css/fediversebridge');

    ossn_register_callback('wall', 'post:created', 'fediversebridge_wall_post_to_fediverse');
}

function fediversebridge_admin_handler($pages) {
    if ($pages[0] === 'optinusers') {
        include_once __DIR__ . '/pages/admin/optinusers.php';
        return true;
    }
    return false;
}

function fediversebridge_add_profile_menu() {
    $user = ossn_user_by_guid(ossn_get_page_owner_guid());
    $viewer = ossn_loggedin_user();

    if ($user && $viewer && $user->guid === $viewer->guid) {
        ossn_register_menu_link(
            'fediverse_optin',
            'Fediverse',
            ossn_site_url("fediversebridge/optin/{$user->username}"),
            'user_timeline'
        );
    }
}

function fediversebridge_internal_handler($pages) {
    if ($pages[0] === 'optin' && isset($pages[1])) {
        $username = basename($pages[1]);
        $user = ossn_user_by_username($username);
        if (!$user) return ossn_error_page();

        $title = "Fediverse";
        $contents['content'] = ossn_plugin_view('fediversebridge/optin', ['user' => $user]);
        $content = ossn_set_page_layout('contents', $contents);
        echo ossn_view_page($title, $content);
        return true;
    }
    return false;
}

function fediversebridge_ossn_style_handler($pages) {
    global $FediversePages;
    $FediversePages = $pages;
    $h = $pages[0] ?? null;

    switch ($h) {
        case 'actor':     include_once __DIR__ . '/pages/fediverse/actor.php'; return true;
        case 'outbox':    include_once __DIR__ . '/pages/fediverse/outbox.php'; return true;
        case 'inbox':     include_once __DIR__ . '/pages/fediverse/inbox.php'; return true;
        case 'followers': include_once __DIR__ . '/pages/fediverse/followers.php'; return true;
        case 'webfinger': include_once __DIR__ . '/pages/fediverse/webfinger.php'; return true;
        default:          ossn_error_page(); return false;
    }
}

function fediversebridge_wellknown_handler($pages) {
    if ($pages[0] === 'webfinger') {
        include_once __DIR__ . '/pages/well-known/webfinger.php';
        return true;
    }
    ossn_error_page(); return false;
}

function fediversebridge_wall_post_to_fediverse($callback, $type, $params) {
    if (!isset($params['object_guid'])) return;

    $post = ossn_get_object($params['object_guid']);
    if (!$post || $post->type !== 'user') return;

    $data = json_decode($post->description, true);
    $content = isset($data['post']) ? trim($data['post']) : '';
    if (strpos($content, '#') === false || empty($content)) return;

    $user = ossn_user_by_guid($post->owner_guid);
    if (!$user) return;
    $username = $user->username;

    $optin_file = ossn_get_userdata("components/FediverseBridge/optin/{$username}.json");
    if (!file_exists($optin_file)) {
        fediversebridge_log("⏩ {$username} niet ge-opt-in – bericht niet gefedereerd");
        return;
    }

    $actor = ossn_site_url("fediverse/actor/{$username}");
    $outbox_base = ossn_site_url("fediverse/outbox/{$username}");
    $activity_id = "{$outbox_base}#activity-{$post->guid}";
    $now = date('c');
    $ossn_url = ossn_site_url("shared_content/post/{$post->guid}/" . time());

    $safe_content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

    $note = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $ossn_url,
        'type' => 'Note',
        'summary' => null,
        'attributedTo' => $actor,
        'to' => ['https://www.w3.org/ns/activitystreams#Public'],
        'content' => $safe_content . "<br /><a href='{$ossn_url}' rel='nofollow noopener' target='_blank'>🔗 Klik hier voor meer op nlsociaal.nl</a>",
        'published' => $now,
        'url' => $ossn_url
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

    $outbox_dir = ossn_get_userdata("components/FediverseBridge/outbox/{$username}/");
    if (!is_dir($outbox_dir)) mkdir($outbox_dir, 0755, true);
    $file = "{$outbox_dir}{$post->guid}.json";
    file_put_contents($file, json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    fediversebridge_log("📨 #post opgeslagen in {$file}");

    $json = json_encode($activity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $inboxes = fediversebridge_get_followers_inboxes($username);
    foreach ($inboxes as $inbox_url) {
        $headers = fediversebridge_sign_request($inbox_url, $json, $username);
        if (!$headers) {
            fediversebridge_log("❌ Geen headers gegenereerd – {$inbox_url} overgeslagen");
            continue;
        }

        $ch = curl_init($inbox_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        fediversebridge_log("📤 Verzonden naar {$inbox_url} | HTTP {$httpcode}");
    }
}

ossn_register_callback('ossn', 'init', 'fediversebridge_init');
