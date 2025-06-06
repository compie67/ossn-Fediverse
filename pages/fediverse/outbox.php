<?php
/**
 * pages/fediverse/outbox.php
 * ActivityPub endpoint dat de openbare outbox van een gebruiker toont
 * Door Eric Redegeld – nlsociaal.nl (met taalondersteuning)
 */

header('Content-Type: application/activity+json');

global $FediversePages;
$username = $FediversePages[1] ?? null;

if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => ossn_print('fediversebridge:outbox:error:missing')]);
    exit;
}

$dir = ossn_get_userdata("components/FediverseBridge/outbox/{$username}/");

if (!is_dir($dir)) {
    http_response_code(404);
    echo json_encode(['error' => ossn_print('fediversebridge:outbox:error:notfound')]);
    exit;
}

$items = [];
foreach (glob("{$dir}*.json") as $file) {
    $json = json_decode(file_get_contents($file), true);
    if (is_array($json)) {
        $items[] = $json;
    }
}

// Nieuwste eerst sorteren op 'published' veld
usort($items, function($a, $b) {
    return strtotime($b['published'] ?? 'now') <=> strtotime($a['published'] ?? 'now');
});

$outbox = [
    '@context'     => 'https://www.w3.org/ns/activitystreams',
    'id'           => ossn_site_url("fediverse/outbox/{$username}"),
    'type'         => 'OrderedCollection',
    'totalItems'   => count($items),
    'orderedItems' => $items
];

echo json_encode($outbox, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
