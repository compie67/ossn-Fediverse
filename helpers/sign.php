<?php
/**
 * helpers/sign.php
 * Door Eric Redegeld – nlsociaal.nl
 *
 * Verzorgt HTTP Signature headers voor ActivityPub-verkeer
 * Gebruikt per-gebruiker private keys (*.pem)
 */

// Fallback logging (alleen actief als hoofdlogger ontbreekt)
if (!function_exists('fediversebridge_log')) {
    function fediversebridge_log($msg) {
        error_log(date('c') . " [FediverseBridge-SIGN] {$msg}\n", 3, 'php_error.log');
    }
}

/**
 * Genereer gesigneerde HTTP headers voor een ActivityPub POST
 *
 * @param string $inbox           Doel-inbox URL
 * @param string $body            JSON body van de activiteit
 * @param string $actor_username  Gebruikersnaam met private key
 * @return array|null             Headers of null bij fout
 */
function fediversebridge_sign_request($inbox, $body, $actor_username = 'admin') {
    $key_path = ossn_get_userdata("components/FediverseBridge/private/{$actor_username}.pem");

    if (!file_exists($key_path)) {
        fediversebridge_log(ossn_print('fediversebridge:log:key:missing', [$actor_username, $key_path]));
        return null;
    }

    $key_id = ossn_site_url("fediverse/actor/{$actor_username}#main-key");
    $date = gmdate('D, d M Y H:i:s T');
    $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));

    $url_parts = parse_url($inbox);
    if (!isset($url_parts['host'])) {
        fediversebridge_log(ossn_print('fediversebridge:log:inbox:invalid', [$inbox]));
        return null;
    }

    $path = $url_parts['path'] ?? '/';
    if (isset($url_parts['query'])) {
        $path .= '?' . $url_parts['query'];
    }
    if (isset($url_parts['fragment'])) {
        $path .= '#' . $url_parts['fragment'];
    }

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
        fediversebridge_log(ossn_print('fediversebridge:log:openssl:loadfail', [$actor_username]));
        return null;
    }

    if (!openssl_sign($signature_string, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
        fediversebridge_log(ossn_print('fediversebridge:log:openssl:signfail', [$actor_username, openssl_error_string()]));
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

/**
 * Verstuur een "Accept" terug naar een follower
 *
 * @param string $follower_actor_url     URL van de volger (actor)
 * @param string $local_username         Lokale gebruikersnaam
 * @param array $original_follow_activity Origineel Follow-object
 * @return bool
 */
function fediversebridge_send_accept($follower_actor_url, $local_username, $original_follow_activity) {
    fediversebridge_log(ossn_print('fediversebridge:log:accept:start', [$follower_actor_url, $local_username]));

    $follower_inbox_url = $follower_actor_url . '/inbox';
    $local_actor_url = ossn_site_url("fediverse/actor/{$local_username}");

    $accept_activity = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => $local_actor_url . '#accept-' . uniqid(),
        'type' => 'Accept',
        'actor' => $local_actor_url,
        'object' => $original_follow_activity,
        'to' => [$follower_actor_url],
    ];

    $json_payload = json_encode($accept_activity, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = fediversebridge_sign_request($follower_inbox_url, $json_payload, $local_username);

    if (!$headers) {
        fediversebridge_log(ossn_print('fediversebridge:log:accept:headersfail', [$follower_inbox_url]));
        return false;
    }

    $ch = curl_init($follower_inbox_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        fediversebridge_log(ossn_print('fediversebridge:log:accept:curlfail', [$follower_inbox_url, $curl_error]));
    } else {
        fediversebridge_log(ossn_print('fediversebridge:log:accept:success', [$follower_inbox_url, $httpcode, substr($response, 0, 100)]));
    }

    return ($httpcode >= 200 && $httpcode < 300);
}
