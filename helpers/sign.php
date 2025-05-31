<?php
/**
 * helpers/sign.php
 * Created by Eric Redegeld for nlsociaal.nl
 *
 * Signs ActivityPub requests using RSA HTTP Signature headers.
 * Each user has a private key (.pem) used to sign outbound requests.
 */

/**
 * Generates signed HTTP headers for POST delivery to a Fediverse inbox.
 *
 * @param string $inbox           Target inbox URL
 * @param string $body            JSON body to be sent
 * @param string $actor_username  Username of the sender (must have a .pem key)
 * @return array|null             Array of headers or null on failure
 */
function fediversebridge_sign_request($inbox, $body, $actor_username = 'admin') {
    $key_path = ossn_get_userdata("components/FediverseBridge/private/{$actor_username}.pem");

    if (!file_exists($key_path)) {
        fediversebridge_log("Private key not found for {$actor_username}: {$key_path}");
        return null;
    }

    $key_id = ossn_site_url("fediverse/actor/{$actor_username}#main-key");
    $date = gmdate('D, d M Y H:i:s T');
    $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));

    $url_parts = parse_url($inbox);
    if (!isset($url_parts['host'])) {
        fediversebridge_log("Invalid inbox URL: {$inbox}");
        return null;
    }

    $path = $url_parts['path'] ?? '/inbox';
    $request_target = 'post ' . $path;

    $signature_headers = "(request-target) host date digest";
    $signature_string = <<<SIG
(request-target): {$request_target}
host: {$url_parts['host']}
date: {$date}
digest: {$digest}
SIG;

    $private_key = file_get_contents($key_path);
    $pkey = openssl_pkey_get_private($private_key);

    if (!$pkey) {
        fediversebridge_log("Failed to load private key for {$actor_username}");
        return null;
    }

    if (!openssl_sign($signature_string, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
        fediversebridge_log("Failed to sign request for {$actor_username}");
        return null;
    }

    $signature_b64 = base64_encode($signature);

    return [
        'Date: ' . $date,
        'Host: ' . $url_parts['host'],
        'Content-Type: application/activity+json',
        'Digest: ' . $digest,
        'Signature: keyId="' . $key_id . '",algorithm="rsa-sha256",headers="' . $signature_headers . '",signature="' . $signature_b64 . '"',
        'User-Agent: FediverseBridge/1.0'
    ];
}
