<?php
/**
 * pages/fediverse/actor.php
 * 🇳🇱 Retourneert het ActivityPub-profiel van een OSSN-gebruiker
 * 🇬🇧 Returns the ActivityPub actor profile of an OSSN user
 *
 * Gemaakt door Eric Redegeld voor nlsociaal.nl
 */

// 📄 Stel de juiste Content-Type in
// 📄 Set proper content type for ActivityPub JSON
header('Content-Type: application/activity+json');

// 🔍 Haal gebruikersnaam uit URL-segment: /fediverse/actor/{username}
// 🔍 Extract username from FediversePages global
$username = $GLOBALS['FediversePages'][1] ?? null;
if (!$username) {
    http_response_code(404);
    echo json_encode(['error' => 'Gebruikersnaam ontbreekt / Username missing']);
    return;
}

// 🔐 Haal gebruiker op via OSSN API
// 🔐 Get user object using OSSN helper
$user = ossn_user_by_username($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Gebruiker niet gevonden / User not found']);
    return;
}

// 🌐 Basis URL van de site
// 🌐 Base site URL
$site = ossn_site_url();

// 📌 URLs voor actor onderdelen
// 📌 URLs for actor components
$actor_id    = "{$site}fediverse/actor/{$username}";
$inbox       = "{$site}fediverse/inbox/{$username}";
$outbox      = "{$site}fediverse/outbox/{$username}";
$followers   = "{$site}fediverse/followers/{$username}";
$profile_url = "{$site}u/{$username}";

// 🔑 Haal publieke sleutel op uit /private
// 🔑 Fetch public key from disk
$public_key_file = ossn_get_userdata("components/FediverseBridge/private/{$username}.pubkey");
if (!file_exists($public_key_file)) {
    http_response_code(500);
    echo json_encode(['error' => 'Publieke sleutel ontbreekt / Public key missing']);
    return;
}
$pubkey = trim(file_get_contents($public_key_file));

// 📦 Bouw het ActivityPub actor-profiel volgens de specificatie
// 📦 Construct ActivityPub actor object
$actor = [
    '@context' => [
        'https://www.w3.org/ns/activitystreams',
        'https://w3id.org/security/v1'
    ],
    'id' => $actor_id,
    'type' => 'Person',
    'preferredUsername' => $username,
    'name' => "{$user->first_name} {$user->last_name}", // 🧑 Naam van gebruiker / Display name
    'summary' => "Gebruiker van nlsociaal.nl",           // 📝 Korte beschrijving / Optional bio
    'inbox' => $inbox,
    'outbox' => $outbox,
    'followers' => $followers,
    'url' => $profile_url,                               // 🌐 Link naar OSSN-profielpagina
    'manuallyApprovesFollowers' => false,                // ✅ Voor Mastodon compatibiliteit
    'discoverable' => true,                              // 🔍 Vindbaar in zoekresultaten
    'publicKey' => [
        'id' => "{$actor_id}#main-key",
        'owner' => $actor_id,
        'publicKeyPem' => $pubkey
    ],
    // 🖼️ Optioneel: Profielfoto (activeren als URL beschikbaar)
    // 'icon' => [
    //     'type' => 'Image',
    //     'mediaType' => 'image/jpeg',
    //     'url' => "{$site}path/naar/profielfoto.jpg"
    // ]
];

// 📤 Stuur JSON-response
// 📤 Output JSON-encoded actor object
echo json_encode($actor, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
