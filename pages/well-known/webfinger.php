<?php
/**
 * pages/well-known/webfinger.php
 * 🇳🇱 WebFinger endpoint voor federatie (vertaalt @user@domein naar actor-profiel)
 * 🇬🇧 WebFinger endpoint for federation – maps @user@domain to ActivityPub actor
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

// 📄 Content-Type voor WebFinger (JRD JSON)
// 📄 Correct Content-Type for WebFinger responses
header('Content-Type: application/jrd+json');

// 📥 Haal de resource-parameter op uit querystring: ?resource=acct:user@domain
// 📥 Extract ?resource=acct:... from query
$username = $_GET['resource'] ?? '';
if (!str_starts_with($username, 'acct:')) {
    http_response_code(400);
    echo json_encode(['error' => 'Ongeldige resource / Invalid resource']);
    exit;
}

// 🔍 Verwijder 'acct:' en splits op @
$username = substr($username, 5);
$parts = explode('@', $username);

// 🌐 Bepaal domein van de OSSN-site
// 🌐 Determine current site domain
$local_domain = parse_url(ossn_site_url(), PHP_URL_HOST);

// 🔐 Domeincheck: alleen lokale gebruikers worden geaccepteerd
// 🔐 Ensure this request is for a local user only
if (count($parts) !== 2 || strtolower($parts[1]) !== strtolower($local_domain)) {
    http_response_code(404);
    echo json_encode(['error' => 'Gebruiker hoort niet bij dit domein / User not on this domain']);
    exit;
}

// 👤 Haal OSSN-gebruiker op via gebruikersnaam
// 👤 Lookup OSSN user by username
$user = ossn_user_by_username($parts[0]);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'Gebruiker niet gevonden / User not found']);
    exit;
}

// 🧭 Bepaal URL naar het ActivityPub-profiel van de gebruiker
// 🧭 Build full actor URL for the user
$actor_url = ossn_site_url("fediverse/actor/{$user->username}");

// 📦 Stel WebFinger JSON-response samen
// 📦 Build the final WebFinger JRD-compliant response
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
