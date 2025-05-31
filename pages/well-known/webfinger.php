<?php
/**
 * pages/well-known/webfinger.php
 * WebFinger endpoint for federation – maps @user@domain to ActivityPub actor.
 *
 * Made by Eric Redegeld – nlsociaal.nl
 */

header('Content-Type: application/jrd+json');

// Extract the resource from the query: ?resource=acct:user@domain
$username = $_GET['resource'] ?? '';
if (!str_starts_with($username, 'acct:')) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid resource']);
    exit;
}

// Remove 'acct:' and split the string into username and domain
$username = substr($username, 5);
$parts = explode('@', $username);

// Get the domain of the current site
$local_domain = parse_url(ossn_site_url(), PHP_URL_HOST);

// Allow only users from this domain
if (count($parts) !== 2 || strtolower($parts[1]) !== strtolower($local_domain)) {
    http_response_code(404);
    echo json_encode(['error' => 'User not on this domain']);
    exit;
}

// Look up the OSSN user by username
$user = ossn_user_by_username($parts[0]);
if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

// Build the ActivityPub actor URL
$actor_url = ossn_site_url("fediverse/actor/{$user->username}");

// Return a valid JRD (JSON Resource Descriptor) response
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
