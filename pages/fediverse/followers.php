<?php
/**
 * pages/fediverse/followers.php
 * 🇳🇱 ActivityPub endpoint voor het ophalen van volgers van een gebruiker
 * 🇬🇧 ActivityPub endpoint returning the followers list of a given user
 *
 * Gemaakt door Eric Redegeld – nlsociaal.nl
 */

// 📄 Stel correcte Content-Type in voor ActivityStreams JSON
// 📄 Set the correct Content-Type for ActivityStreams JSON
header('Content-Type: application/activity+json');

// 🧭 Haal gebruikersnaam op uit routersegment /fediverse/followers/{username}
// 🧭 Extract username from route
global $FediversePages;
$username = $FediversePages[1] ?? null;

// ❌ Geen gebruikersnaam opgegeven
// ❌ No username provided
if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Gebruikersnaam ontbreekt / Username missing']);
    exit;
}

// 🔍 Controleer of gebruiker bestaat in OSSN
// 🔍 Validate user exists in OSSN
$user = ossn_user_by_username($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Gebruiker niet gevonden / User not found']);
    exit;
}

// 📁 Pad naar followers-bestand met actor-URL's
// 📁 Path to JSON file with actor URLs of followers
$followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");

// 📦 Start van de ActivityStreams OrderedCollection structuur
// 📦 Begin ActivityStreams OrderedCollection response structure
$followers = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => ossn_site_url("fediverse/followers/{$username}"),
    'type' => 'OrderedCollection',
    'totalItems' => 0,
    'orderedItems' => []
];

// 📥 Voeg volgers toe als followers.json geldig is
// 📥 Add followers if followers.json exists and is valid
if (file_exists($followers_file)) {
    $data = json_decode(file_get_contents($followers_file), true);

    if (is_array($data)) {
        $followers['orderedItems'] = array_values($data); // 🎯 Actor-URL's van volgers
        $followers['totalItems'] = count($data);           // 🔢 Totaal
    } else {
        // ⚠️ JSON is corrupt of geen array
        // ⚠️ JSON is corrupt or invalid
        fediversebridge_log("⚠️ followers.json voor {$username} is ongeldig");
    }
}

// 📤 Stuur JSON terug als gestructureerde ActivityPub-OrderedCollection
// 📤 Return structured ActivityPub OrderedCollection response
echo json_encode($followers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
