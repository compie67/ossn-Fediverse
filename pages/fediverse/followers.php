<?php
/**
 * pages/fediverse/followers.php
 * ActivityPub endpoint returning the followers list of a given user
 * Created by Eric Redegeld – nlsociaal.nl
 */

header('Content-Type: application/activity+json');

// Extract username from URL: /fediverse/followers/{username}
global $FediversePages;
$username = $FediversePages[1] ?? null;

// Exit if no username provided
if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Username missing']);
    exit;
}

// Retrieve user object by username
$user = ossn_user_by_username($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Path to the followers list file (stored as JSON with actor URLs)
$followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");

// Initialize base ActivityStreams OrderedCollection structure
$followers = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => ossn_site_url("fediverse/followers/{$username}"),
    'type' => 'OrderedCollection',
    'totalItems' => 0,
    'orderedItems' => []
];

// Load and validate the JSON file with follower actor URLs
if (file_exists($followers_file)) {
    $data = json_decode(file_get_contents($followers_file), true);

    if (is_array($data)) {
        $followers['orderedItems'] = array_values($data);
        $followers['totalItems'] = count($data);
    } else {
        fediversebridge_log("Invalid followers.json for {$username}");
    }
}

// Output the final JSON response
echo json_encode($followers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
