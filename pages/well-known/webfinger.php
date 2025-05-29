<?php
/**
 * pages/well-known/webfinger.php
 * 🇬🇧 WebFinger endpoint for federation – maps @user@domain to ActivityPub actor
 * 🇳🇱 WebFinger endpoint voor federatie – vertaalt @user@domein naar actor-profiel
 *
 * Made by Eric Redegeld – nlsociaal.nl
 */

// 📄 Set correct Content-Type for WebFinger responses (JRD JSON)
header('Content-Type: application/jrd+json');

// 📥 Extract ?resource=acct:user@domain
$username = $_GET['resource'] ?? '';
if (!str_starts_with($username, 'acct:')) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid resource / Ongeldige resource']);
    exit;
}

// 🔍 Strip 'acct:' and split into username and domain
$username = substr($username, 5);
$parts = explode('@', $username);

// 🌐 Determine current site domain
$local_domain = parse_url(ossn_site_url(), PHP_URL_HOST);

// 🔐 Accept only users of this domain
if (count($parts) !== 2 || strtolower($parts[1]) !== strtolower($local_domain)) {
    http_response_code(404);
    echo json_encode(['error' => 'User not on this domain / Gebruiker hoort niet bij dit domein']);
    exit;
}

// 👤 Look up OSSN user by username
$user = ossn_user_by_username($parts[0]);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found / Gebruiker niet gevonden']);
    exit;
}

// 🧭 Build full ActivityPub actor URL for the user
$actor_url = ossn_site_url("fediverse/actor/{$user->username}");

// 📦 Return JRD-compliant WebFinger JSON response
echo json_encode([
    'subject' => "acct:{$user->username}@{$local_domain}",
    'links' => [
        [
            'rel' => 'self',
            'type' => 'application/activity+json',
            'href' => $actor_url
        ],
        [
            'rel' => 'http://webfinger.net/rel/profile-page',
            'type' => 'text/html',
            'href' => $actor_url
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
