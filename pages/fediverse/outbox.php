<?php
/**
 * pages/fediverse/outbox.php
 * 🇬🇧 ActivityPub endpoint showing the public outbox of a user
 * 🇳🇱 ActivityPub endpoint dat de openbare outbox van een gebruiker toont
 *
 * Created by Eric Redegeld – nlsociaal.nl
 */

// 📄 Set proper Content-Type for ActivityStreams JSON
header('Content-Type: application/activity+json');

// 🧭 Extract username from route /fediverse/outbox/{username}
global $FediversePages;
$username = $FediversePages[1] ?? null;

// ❌ No username provided
if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing username / Gebruikersnaam ontbreekt']);
    exit;
}

// 📁 Path to user's outbox directory
$dir = ossn_get_userdata("components/FediverseBridge/outbox/{$username}/");

// ❌ Outbox folder not found
if (!is_dir($dir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Outbox not found / Outbox niet gevonden']);
    exit;
}

// 📦 Load all ActivityPub JSON messages from outbox directory
$items = [];
foreach (glob("{$dir}*.json") as $file) {
    $json = json_decode(file_get_contents($file), true);
    if (is_array($json)) {
        $items[] = $json;
    }
}

// 🔃 Sort by published date, descending
usort($items, function($a, $b) {
    return strtotime($b['published'] ?? 'now') <=> strtotime($a['published'] ?? 'now');
});

// 📤 Build the ActivityPub-compliant outbox structure
$outbox = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => ossn_site_url("fediverse/outbox/{$username}"),
    'type' => 'OrderedCollection',
    'totalItems' => count($items),
    'orderedItems' => $items
];

// 📤 Return as formatted JSON
echo json_encode($outbox, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
