<?php
/**
 * pages/fediverse/followers.php
 * 🇬🇧 ActivityPub endpoint returning the followers list of a given user
 * 🇳🇱 ActivityPub endpoint voor het ophalen van volgers van een gebruiker
 *
 * Created by Eric Redegeld – nlsociaal.nl
 */

// 📄 Set proper ActivityStreams content type
header('Content-Type: application/activity+json');

// 🧭 Extract username from URL: /fediverse/followers/{username}
global $FediversePages;
$username = $FediversePages[1] ?? null;

// ❌ No username provided
if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Username missing']);
    exit;
}

// 🔍 Check if user exists
$user = ossn_user_by_username($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// 📁 Path to followers file (list of actor URLs)
$followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");

// 📦 Base structure for ActivityStreams OrderedCollection
$followers = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => ossn_site_url("fediverse/followers/{$username}"),
    'type' => 'OrderedCollection',
    'totalItems' => 0,
    'orderedItems' => []
];

// 📥 Load and validate follower list from file
if (file_exists($followers_file)) {
    $data = json_decode(file_get_contents($followers_file), true);

    if (is_array($data)) {
        $followers['orderedItems'] = array_values($data); // Actor URLs
        $followers['totalItems'] = count($data);          // Count
    } else {
        // ⚠️ Log if the JSON is corrupt or invalid
        fediversebridge_log("⚠️ Invalid followers.json for {$username}");
    }
}

// 📤 Return JSON response
echo json_encode($followers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
