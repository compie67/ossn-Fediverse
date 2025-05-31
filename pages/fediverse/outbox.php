<?php
/**
 * pages/fediverse/outbox.php
 * ActivityPub endpoint that returns the public outbox of a given user.
 * Created by Eric Redegeld – nlsociaal.nl
 */

header('Content-Type: application/activity+json');

// Extract the username from the route: /fediverse/outbox/{username}
global $FediversePages;
$username = $FediversePages[1] ?? null;

// If no username is provided, return a 400 error
if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username']);
    exit;
}

// Define the path to the user's outbox folder
$dir = ossn_get_userdata("components/FediverseBridge/outbox/{$username}/");

// If the outbox folder does not exist, return a 404 error
if (!is_dir($dir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Outbox not found']);
    exit;
}

// Load all JSON activity files from the user's outbox directory
$items = [];
foreach (glob("{$dir}*.json") as $file) {
    $json = json_decode(file_get_contents($file), true);
    if (is_array($json)) {
        $items[] = $json;
    }
}

// Sort the activities by publish date in descending order
usort($items, function($a, $b) {
    return strtotime($b['published'] ?? 'now') <=> strtotime($a['published'] ?? 'now');
});

// Construct the ActivityStreams outbox response
$outbox = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => ossn_site_url("fediverse/outbox/{$username}"),
    'type' => 'OrderedCollection',
    'totalItems' => count($items),
    'orderedItems' => $items
];

// Return the outbox as formatted JSON
echo json_encode($outbox, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
