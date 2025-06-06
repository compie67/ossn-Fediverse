<?php
/**
 * pages/well-known/webfinger.php
 * WebFinger endpoint voor federatie
 * Door Eric Redegeld – nlsociaal.nl (met taalondersteuning)
 */

header('Content-Type: application/jrd+json');

$username = $_GET['resource'] ?? '';
if (!str_starts_with($username, 'acct:')) {
    http_response_code(400);
    echo json_encode(['error' => ossn_print('fediversebridge:webfinger:error:invalid')]);
    exit;
}

$username = substr($username, 5); // verwijder 'acct:'
$parts = explode('@', $username);

$local_domain = parse_url(ossn_site_url(), PHP_URL_HOST);

// ❌ Fout domein of verkeerd formaat
if (count($parts) !== 2 || strtolower($parts[1]) !== strtolower($local_domain)) {
    http_response_code(404);
    echo json_encode(['error' => ossn_print('fediversebridge:webfinger:error:domain')]);
    exit;
}

// 👤 Gebruiker zoeken
$user = ossn_user_by_username($parts[0]);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => ossn_print('fediversebridge:webfinger:error:notfound')]);
    exit;
}

// 🌟 Actor-profiel samenstellen
$actor_url = ossn_site_url("fediverse/actor/{$user->username}");

echo json_encode([
    'subject' => "acct:{$user->username}@{$local_domain}",
    'aliases' => [
        $actor_url,
        "acct:{$user->username}@{$local_domain}"
    ],
    'links' => [
        [
            'rel'  => 'self',
            'type' => 'application/activity+json',
            'href' => $actor_url
        ],
        [
            'rel'  => 'http://webfinger.net/rel/profile-page',
            'type' => 'text/html',
            'href' => $actor_url
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
