<?php
/**
 * OSSN Component: FediverseBridge
 * Developed by Eric Redegeld for nlsociaal.nl
 */

define('FEDIVERSEBRIDGE_DEBUG', false);

// Load helper files
require_once __DIR__ . '/helpers/followers.php';
require_once __DIR__ . '/helpers/sign.php';
require_once __DIR__ . '/helpers/fediversebridge_log.php';
require_once __DIR__ . '/helpers/undo.php';
require_once __DIR__ . '/helpers/note.php';


/**
 * Simple debug logger (rotates if >1MB)
 */
function fediversebridge_log($msg) {
	if (!defined('FEDIVERSEBRIDGE_DEBUG') || !FEDIVERSEBRIDGE_DEBUG) return;
	$logfile = ossn_get_userdata('components/FediverseBridge/logs/fediverse.log');
	if (file_exists($logfile) && filesize($logfile) > 1024 * 1024) {
		rename($logfile, $logfile . '.' . time() . '.bak');
	}
	file_put_contents($logfile, date('c') . " {$msg}\n", FILE_APPEND);
}

/**
 * Component initialization
 */
function fediversebridge_init() {
	fediversebridge_log("FediverseBridge initialized");

	ossn_register_admin_sidemenu('fediversebridge_optinusers', 'Fediverse Opt-in Users', 'fediverse-admin/optinusers', 'admin');

	ossn_register_callback('page', 'load:profile', 'fediversebridge_add_profile_menu');

	ossn_register_page('fediversebridge', 'fediversebridge_internal_handler');
	ossn_register_page('fediverse', 'fediversebridge_ossn_style_handler');
	ossn_register_page('well-known', 'fediversebridge_wellknown_handler');
	ossn_register_page('fediverse-admin', 'fediversebridge_admin_handler');

	ossn_extend_view('ossn/admin/head', 'css/fediversebridge');

	ossn_register_callback('wall', 'post:created', 'fediversebridge_wall_post_to_fediverse');
	ossn_register_callback('post', 'before:delete', 'fediversebridge_on_post_delete');
}

/**
 * Admin page handler
 */
function fediversebridge_admin_handler($pages) {
	if ($pages[0] === 'optinusers') {
		include_once __DIR__ . '/pages/admin/optinusers.php';
		return true;
	}
	return false;
}

/**
 * Adds "Fediverse Opt-in" menu item to user timeline
 */
function fediversebridge_add_profile_menu() {
	$user   = ossn_user_by_guid(ossn_get_page_owner_guid());
	$viewer = ossn_loggedin_user();
	if ($user && $viewer && ($user->guid === $viewer->guid || ossn_isAdminLoggedin())) {
		ossn_register_menu_link(
			'fediverse_optin',
			ossn_print('fediversebridge:menu:optin'),
			ossn_site_url("fediversebridge/optin/{$user->username}"),
			'user_timeline'
		);
	}
}

/**
 * Handles internal pages like /fediversebridge/optin/username
 */
function fediversebridge_internal_handler($pages) {
	if ($pages[0] === 'optin' && isset($pages[1])) {
		$username = basename($pages[1]);
		$user = ossn_user_by_username($username);
		if (!$user) return ossn_error_page();

		$title = ossn_print('fediversebridge:menu:optin');
		$contents['content'] = ossn_plugin_view('fediversebridge/optin', ['user' => $user]);
		$content = ossn_set_page_layout('contents', $contents);
		echo ossn_view_page($title, $content);
		return true;
	}
	return false;
}

/**
 * Handles /fediverse/* routes
 */
function fediversebridge_ossn_style_handler($pages) {
	global $FediversePages;
	$FediversePages = $pages;

	$endpoint = $pages[0] ?? null;
	$username = $pages[1] ?? null;

	if ($username) {
		$_GET['username'] = $username;
	}

	switch ($endpoint) {
		case 'actor':
			include_once __DIR__ . '/pages/fediverse/actor.php'; return true;
		case 'inbox':
			include_once __DIR__ . '/pages/fediverse/inbox.php'; return true;
		case 'outbox':
			include_once __DIR__ . '/pages/fediverse/outbox.php'; return true;
		case 'followers':
			include_once __DIR__ . '/pages/fediverse/followers.php'; return true;
		case 'webfinger':
			include_once __DIR__ . '/pages/fediverse/webfinger.php'; return true;
		case 'media':
			if ($username === 'proxy') {
				include_once __DIR__ . '/pages/fediverse/media/proxy.php'; return true;
			}
			break;
		case 'avatar':
			if ($username === 'proxy') {
				include_once __DIR__ . '/pages/fediverse/avatar/proxy.php'; return true;
			}
			include_once __DIR__ . '/pages/fediverse/avatar.php'; return true;
		case 'note':
			if (isset($pages[1], $pages[2])) {
				$_GET['username'] = $pages[1];
				$_GET['guid']     = $pages[2];
				include_once __DIR__ . '/pages/fediverse/note.php'; return true;
			}
			break;
	}
	return ossn_error_page();
}

/**
 * Handles /.well-known/webfinger
 */
function fediversebridge_wellknown_handler($pages) {
	if ($pages[0] === 'webfinger') {
		include_once __DIR__ . '/pages/well-known/webfinger.php';
		return true;
	}
	return ossn_error_page();
}

/**
 * Publishes wall post to Fediverse
 */
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
		fediversebridge_log("User {$username} has not opted in – skipping federation.");
		return;
	}

	$actor = ossn_site_url("fediverse/actor/{$username}");
	$note_id = ossn_site_url("fediverse/note/{$username}/{$post->guid}");
	$now = date('c');
	$safe_content = htmlspecialchars($content, ENT_QUOTES, 'UTF-8');

	$note = [
		'@context' => 'https://www.w3.org/ns/activitystreams',
		'id' => $note_id,
		'type' => 'Note',
		'attributedTo' => $actor,
		'to' => ['https://www.w3.org/ns/activitystreams#Public'],
		'content' => $safe_content . "<br /><a href='{$note_id}' target='_blank' rel='nofollow noopener'>" .
			ossn_print('fediversebridge:note:viewlink', [parse_url(ossn_site_url(), PHP_URL_HOST)]) .
		"</a>",
		'published' => $now,
		'url' => $note_id
	];

	$activity = [
		'@context' => 'https://www.w3.org/ns/activitystreams',
		'id' => ossn_site_url("fediverse/outbox/{$username}#activity-{$post->guid}"),
		'type' => 'Create',
		'actor' => $actor,
		'published' => $now,
		'to' => ['https://www.w3.org/ns/activitystreams#Public'],
		'object' => $note
	];

	$outbox_dir = ossn_get_userdata("components/FediverseBridge/outbox/{$username}/");
	if (!is_dir($outbox_dir)) mkdir($outbox_dir, 0755, true);
	file_put_contents("{$outbox_dir}{$post->guid}.json", json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
	fediversebridge_log("Federated post saved in {$outbox_dir}{$post->guid}.json");

	$json = json_encode($activity, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
	$inboxes = fediversebridge_get_followers_inboxes($username);
	foreach ($inboxes as $inbox_url) {
		$headers = fediversebridge_sign_request($inbox_url, $json, $username);
		if (!$headers) {
			fediversebridge_log("No headers generated for {$inbox_url}");
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

		fediversebridge_log("Sent to {$inbox_url} – HTTP status {$httpcode}");
	}
}

/**
 * Deletes federated post and notifies followers
 */
function fediversebridge_on_post_delete($callback, $type, $guid) {
	$post = ossn_get_object($guid);
	if (!$post || $post->type !== 'user') return;

	$user = ossn_user_by_guid($post->owner_guid);
	if (!$user) return;

	$username = $user->username;
	$file = ossn_get_userdata("components/FediverseBridge/outbox/{$username}/{$guid}.json");
	if (!file_exists($file)) return;

	$activity = json_decode(file_get_contents($file), true);
	$object_id = $activity['object']['id'] ?? null;
	unlink($file);
	fediversebridge_log("Federated post removed – {$file}");

	if ($object_id) {
		$delete = [
			'@context' => 'https://www.w3.org/ns/activitystreams',
			'id' => ossn_site_url("fediverse/outbox/{$username}#delete-{$guid}"),
			'type' => 'Delete',
			'actor' => ossn_site_url("fediverse/actor/{$username}"),
			'object' => $object_id
		];

		$json = json_encode($delete, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
		$inboxes = fediversebridge_get_followers_inboxes($username);
		foreach ($inboxes as $inbox_url) {
			$headers = fediversebridge_sign_request($inbox_url, $json, $username);
			if (!$headers) continue;

			$ch = curl_init($inbox_url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_exec($ch);
			curl_close($ch);

			fediversebridge_log("Delete activity sent to {$inbox_url}");
		}
	}
}

// Final registration
ossn_register_callback('ossn', 'init', 'fediversebridge_init');
