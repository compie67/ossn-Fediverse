<?php
/**
 * pages/fediverse/actor.php
 * Retourneert het ActivityPub-profiel van een OSSN-gebruiker
 * Door Eric Redegeld – nlsociaal.nl
 */

require_once ossn_route()->com . 'FediverseBridge/helpers/fediversebridge_log.php';

// Haal gebruikersnaam uit URL-segment: /fediverse/actor/{username}
$username = $GLOBALS['FediversePages'][1] ?? null;
if (!$username) {
	http_response_code(404);
	echo json_encode(['error' => ossn_print('fediversebridge:actor:error:missing')]);
	exit;
}

// 👉 Voor gewone browsers: redirect naar profielpagina
$accept = $_SERVER['HTTP_ACCEPT'] ?? '';
if (strpos($accept, 'application/activity+json') === false) {
	header("Location: " . ossn_site_url("u/{$username}"));
	exit;
}

// Gebruiker ophalen via OSSN
$user = ossn_user_by_username($username);
if (!$user) {
	http_response_code(404);
	echo json_encode(['error' => ossn_print('fediversebridge:actor:error:notfound')]);
	exit;
}

// URLs opbouwen
$site         = ossn_site_url();
$domain       = parse_url($site, PHP_URL_HOST);
$actor_id     = "{$site}fediverse/actor/{$username}";
$inbox        = "{$site}fediverse/inbox/{$username}";
$outbox       = "{$site}fediverse/outbox/{$username}";
$followers    = "{$site}fediverse/followers/{$username}";
$profile_url  = "{$site}u/{$username}";

// Publieke sleutel ophalen
$public_key_file = ossn_get_userdata("components/FediverseBridge/private/{$username}.pubkey");
if (!file_exists($public_key_file)) {
	http_response_code(500);
	echo json_encode(['error' => ossn_print('fediversebridge:actor:error:nopubkey')]);
	exit;
}
$pubkey = trim(file_get_contents($public_key_file));

// Profielfoto instellen (fallback of avatar)
$icon_url = "{$site}fediverse/avatar?guid={$user->guid}&file=fallback";
$media_type = "image/jpeg"; // default MIME-type

$icon_path = ossn_get_userdata("user/{$user->guid}/profile/photo/");
$icon_file = glob("{$icon_path}larger_*");

if ($icon_file && file_exists($icon_file[0])) {
	$filename = basename($icon_file[0]);
	$icon_url = "{$site}fediverse/avatar?guid={$user->guid}&file={$filename}";
	$media_type = mime_content_type($icon_file[0]);
}

// Actor JSON-profiel opbouwen
$actor = [
	'@context' => [
		'https://www.w3.org/ns/activitystreams',
		'https://w3id.org/security/v1'
	],
	'id' => $actor_id,
	'type' => 'Person',
	'preferredUsername' => $username,
	'name' => trim("{$user->first_name} {$user->last_name}"),
	'summary' => ossn_print('fediversebridge:actor:summary', [$username, $domain]),
	'inbox' => $inbox,
	'outbox' => $outbox,
	'followers' => $followers,
	'url' => $profile_url,
	'manuallyApprovesFollowers' => false,
	'discoverable' => true,
	'icon' => [
		'type' => 'Image',
		'mediaType' => $media_type,
		'url' => $icon_url
	],
	'publicKey' => [
		'id' => "{$actor_id}#main-key",
		'owner' => $actor_id,
		'publicKeyPem' => $pubkey
	]
];

// JSON-response teruggeven
header('Content-Type: application/activity+json');
echo json_encode($actor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
exit;
