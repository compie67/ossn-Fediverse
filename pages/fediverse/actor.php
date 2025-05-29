<?php
/**
 * pages/fediverse/actor.php
 * 🇬🇧 Returns the ActivityPub actor profile of an OSSN user
 * 🇳🇱 Retourneert het ActivityPub-profiel van een OSSN-gebruiker
 * Created by Eric Redegeld – open source version for nlsociaal.nl
 */

// 🧾 Response type: ActivityPub actor JSON
header('Content-Type: application/activity+json');

// 🔍 🇬🇧 Extract username from URL segment / 🇳🇱 Haal gebruikersnaam uit URL
$username = $GLOBALS['FediversePages'][1] ?? null;
if (!$username) {
    http_response_code(404);
    echo json_encode(['error' => 'Username missing / Gebruikersnaam ontbreekt']);
    return;
}

// 🔐 🇬🇧 Fetch user object / 🇳🇱 Haal OSSN gebruiker op
$user = ossn_user_by_username($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found / Gebruiker niet gevonden']);
    return;
}

// 🌍 🇬🇧 Build core URLs / 🇳🇱 Genereer URL's voor ActivityPub
$site        = ossn_site_url();
$actor_id    = "{$site}fediverse/actor/{$username}";
$inbox       = "{$site}fediverse/inbox/{$username}";
$outbox      = "{$site}fediverse/outbox/{$username}";
$followers   = "{$site}fediverse/followers/{$username}";
$profile_url = "{$site}u/{$username}";
$note_stub   = "{$site}fediverse/note/";

// 🔑 🇬🇧 Load public key / 🇳🇱 Laad publieke sleutel
$public_key_file = ossn_get_userdata("components/FediverseBridge/private/{$username}.pubkey");
if (!file_exists($public_key_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Public key missing / Publieke sleutel ontbreekt']);
    return;
}
$pubkey = trim(file_get_contents($public_key_file));

// 📛 🇬🇧 User display name / 🇳🇱 Weergavenaam van gebruiker
$name = trim("{$user->first_name} {$user->last_name}") ?: $username;

// 📝 🇬🇧 Summary for profile / 🇳🇱 Samenvatting uit taalbestand
$summary = ossn_print('fediversebridge:user:summary') ?: "Fediverse user";

// 📦 🇬🇧 Build actor object / 🇳🇱 Bouw het ActivityPub-profiel
$actor = [
    '@context' => [
        'https://www.w3.org/ns/activitystreams',
        'https://w3id.org/security/v1'
    ],
    'id' => $actor_id,
    'type' => 'Person',
    'preferredUsername' => $username,
    'name' => $name,
    'summary' => $summary,
    'inbox' => $inbox,
    'outbox' => $outbox,
    'followers' => $followers,
    'url' => $profile_url,
    'manuallyApprovesFollowers' => false,
    'discoverable' => true,
    'bot' => false,
    'endpoints' => [
        'sharedInbox' => $inbox
    ],
    'replies' => $note_stub,
    'publicKey' => [
        'id' => "{$actor_id}#main-key",
        'owner' => $actor_id,
        'publicKeyPem' => $pubkey
    ]
];

// 🖼️ 🇬🇧 Avatar image / 🇳🇱 Profielfoto instellen
$icon_url = "{$site}components/FediverseBridge/images/default-avatar.jpg";

// 🔍 🇬🇧 Attempt to find user's real avatar / 🇳🇱 Probeer echte avatar van gebruiker te vinden
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

// 📤 🇬🇧 Output ActivityPub JSON / 🇳🇱 Geef JSON terug
echo json_encode($actor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
