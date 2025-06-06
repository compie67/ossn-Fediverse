<?php
/**
 * pages/fediverse/followers.php
 * Endpoint voor ophalen van volgers in ActivityPub-formaat
 * Door Eric Redegeld – nlsociaal.nl
 */

require_once ossn_route()->com . 'FediverseBridge/helpers/fediversebridge_log.php';

header('Content-Type: application/activity+json');

// Gebruikersnaam ophalen uit route
global $FediversePages;
$username = $FediversePages[1] ?? null;

if (!$username) {
    http_response_code(400);
    echo json_encode(['error' => ossn_print('fediversebridge:followers:error:missing')]);
    exit;
}

$user = ossn_user_by_username($username);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => ossn_print('fediversebridge:followers:error:notfound')]);
    exit;
}

// Bestand met volgers ophalen
$followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");

$followers = [
    '@context'     => 'https://www.w3.org/ns/activitystreams',
    'id'           => ossn_site_url("fediverse/followers/{$username}"),
    'type'         => 'OrderedCollection',
    'totalItems'   => 0,
    'orderedItems' => []
];

if (file_exists($followers_file)) {
    $data = json_decode(file_get_contents($followers_file), true);
    if (is_array($data)) {
        $followers['orderedItems'] = array_values($data);
        $followers['totalItems'] = count($data);
    } else {
        fediversebridge_log(ossn_print('fediversebridge:followers:log:invalidjson', [$username]));
    }
}

echo json_encode($followers, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
