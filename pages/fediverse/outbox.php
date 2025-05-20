<?php
/**
 * pages/fediverse/outbox.php
 * 🇳🇱 ActivityPub endpoint dat de openbare outbox van een gebruiker toont
 * 🇬🇧 ActivityPub endpoint showing the public outbox of a user
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

// 📄 Stel het juiste Content-Type in voor ActivityStreams JSON
// 📄 Set proper Content-Type for ActivityStreams JSON
header('Content-Type: application/activity+json');

// 🧭 Haal gebruikersnaam op uit routersegment: /fediverse/outbox/{username}
// 🧭 Extract username from route
global $FediversePages;
$username = $FediversePages[1] ?? null;

if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => 'Gebruikersnaam ontbreekt / Missing username']);
    exit;
}

// 📁 Pad naar de outbox-directory van de gebruiker
// 📁 Path to user's outbox directory
$dir = ossn_get_userdata("components/FediverseBridge/outbox/{$username}/");

if (!is_dir($dir)) {
    http_response_code(404);
    echo json_encode(['error' => 'Outbox niet gevonden / Outbox not found']);
    exit;
}

// 📦 Laad alle ActivityPub JSON-bestanden uit de outbox
// 📦 Load all ActivityPub-compatible JSON messages from outbox
$items = [];
foreach (glob("{$dir}*.json") as $file) {
    $json = json_decode(file_get_contents($file), true);
    if (is_array($json)) {
        $items[] = $json;
    }
}

// 🔃 Sorteer items op publicatiedatum (nieuwste eerst)
// 🔃 Sort items by publication date (descending)
usort($items, function($a, $b) {
    return strtotime($b['published'] ?? 'now') <=> strtotime($a['published'] ?? 'now');
});

// 📤 Stel het ActivityPub outbox-object samen
// 📤 Build the ActivityPub outbox response
$outbox = [
    '@context' => 'https://www.w3.org/ns/activitystreams',
    'id' => ossn_site_url("fediverse/outbox/{$username}"),
    'type' => 'OrderedCollection',
    'totalItems' => count($items),
    'orderedItems' => $items
];

// 📤 Retourneer het resultaat als JSON
// 📤 Return the final outbox JSON
echo json_encode($outbox, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
