<?php
/**
 * pages/fediverse/actor.php
 * 🇳🇱 Retourneert het ActivityPub-profiel van een OSSN-gebruiker
 * 🇬🇧 Returns the ActivityPub actor profile of an OSSN user
 *
 * Gemaakt door Eric Redegeld voor nlsociaal.nl
 */

header('Content-Type: application/activity+json');

// 🔍 Haal gebruikersnaam uit URL-segment: /fediverse/actor/{username}
$username = $GLOBALS['FediversePages'][1] ?? null;
if (!$username) {
    http_response_code(404);
    echo json_encode(['error' => 'Gebruikersnaam ontbreekt / Username missing']);
    return;
}

// 🔐 Haal gebruiker op via OSSN API
$user = ossn_user_by_username($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Gebruiker niet gevonden / User not found']);
    return;
}

$site = ossn_site_url();
$actor_id    = "{$site}fediverse/actor/{$username}";
$inbox       = "{$site}fediverse/inbox/{$username}";
$outbox      = "{$site}fediverse/outbox/{$username}";
$followers   = "{$site}fediverse/followers/{$username}";
$profile_url = "{$site}u/{$username}";

// 🔑 Publieke sleutel ophalen
$public_key_file = ossn_get_userdata("components/FediverseBridge/private/{$username}.pubkey");
if (!file_exists($public_key_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Publieke sleutel ontbreekt / Public key missing']);
    return;
}
$pubkey = trim(file_get_contents($public_key_file));

// 📦 Actor-profiel bouwen
$actor = [
    '@context' => [
        'https://www.w3.org/ns/activitystreams',
        'https://w3id.org/security/v1'
    ],
    'id' => $actor_id,
    'type' => 'Person',
    'preferredUsername' => $username,
    'name' => "{$user->first_name} {$user->last_name}",
    'summary' => "Gebruiker van nlsociaal.nl",
    'inbox' => $inbox,
    'outbox' => $outbox,
    'followers' => $followers,
    'url' => $profile_url,
    'manuallyApprovesFollowers' => false,
    'discoverable' => true,
    'publicKey' => [
        'id' => "{$actor_id}#main-key",
        'owner' => $actor_id,
        'publicKeyPem' => $pubkey
    ]
];

// 🖼️ Profielfoto instellen: standaard = fallback
$icon_url = "{$site}components/FediverseBridge/images/default-avatar.jpg";

// 🔍 Probeer bestaande OSSN-avatar te gebruiken
$icon_path = ossn_get_userdata("user/{$user->guid}/profile/photo/");
$icon_file = glob("{$icon_path}larger_*");

if ($icon_file && file_exists($icon_file[0])) {
    $filename = basename($icon_file[0]);
    $icon_url = "{$site}avatar/{$username}/larger/{$filename}";
}

$actor['icon'] = [
    'type' => 'Image',
    'mediaType' => 'image/jpeg',
    'url' => $icon_url
];

// 📤 JSON-response teruggeven
echo json_encode($actor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
