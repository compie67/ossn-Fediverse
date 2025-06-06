<?php
/**
 * pages/fediverse/note.php
 * Geeft een individuele federatieve Note terug + bijhorende replies
 * Door Eric Redegeld – nlsociaal.nl
 */

require_once ossn_route()->com . 'FediverseBridge/helpers/fediversebridge_log.php';

header('Content-Type: application/activity+json');

// URL-onderdelen ophalen
$username = $GLOBALS['FediversePages'][1] ?? null;
$guid     = (int) ($GLOBALS['FediversePages'][2] ?? 0);

fediversebridge_log("NOTE VIEW: {$username}/{$guid}");

if (!$username || !$guid) {
	http_response_code(400);
	echo json_encode(['error' => ossn_print('fediversebridge:note:error:invalid')]);
	exit;
}

$user = ossn_user_by_username($username);
if (!$user) {
	http_response_code(404);
	echo json_encode(['error' => ossn_print('fediversebridge:note:error:user')]);
	exit;
}

$post = ossn_get_object($guid);
if (!$post || $post->owner_guid !== $user->guid) {
	fediversebridge_log("❌ Post niet gevonden of eigenaar mismatch voor {$guid}");
	http_response_code(404);
	echo json_encode(['error' => ossn_print('fediversebridge:note:error:post')]);
	exit;
}

// Actor & URLs
$actor     = ossn_site_url("fediverse/actor/{$username}");
$note_url  = ossn_site_url("fediverse/note/{$username}/{$guid}");
$html_url  = ossn_site_url("post/view/{$guid}");
$published = date('c', $post->time_created);

// Content ophalen (mogelijk JSON opgeslagen)
$description = $post->description ?? '';
$decoded     = json_decode($description, true);
$content     = (is_array($decoded) && isset($decoded['post'])) ? $decoded['post'] : $description;

// Note-object
$note = [
	'@context'     => 'https://www.w3.org/ns/activitystreams',
	'id'           => $note_url,
	'type'         => 'Note',
	'attributedTo' => $actor,
	'to'           => ['https://www.w3.org/ns/activitystreams#Public'],
	'content'      => $content,
	'published'    => $published,
	'url'          => $html_url,
];

// Federatieve replies ophalen
$replies_dir = ossn_get_userdata("components/FediverseBridge/replies/{$guid}/");
$items = [];

if (is_dir($replies_dir)) {
	foreach (glob($replies_dir . '*.json') as $file) {
		$json = json_decode(file_get_contents($file), true);
		if (isset($json['object']) && $json['object']['type'] === 'Note') {
			$items[] = [
				'id'           => $json['object']['id'] ?? '',
				'type'         => 'Note',
				'attributedTo' => $json['object']['attributedTo'] ?? '',
				'content'      => $json['object']['content'] ?? '',
				'published'    => $json['object']['published'] ?? '',
				'inReplyTo'    => $json['object']['inReplyTo'] ?? $note_url,
			];
		}
	}
}

$note['replies'] = [
	'type'       => 'Collection',
	'totalItems' => count($items),
	'items'      => $items,
];

// Output
echo json_encode($note, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
exit;
