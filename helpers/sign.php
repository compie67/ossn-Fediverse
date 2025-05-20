<?php
/**
 * helpers/sign.php
 * 🇳🇱 Gemaakt door Eric Redegeld voor nlsociaal.nl
 * 🇬🇧 Created by Eric Redegeld for nlsociaal.nl
 *
 * 🧾 🇳🇱 Ondertekent ActivityPub-verzoeken met RSA HTTP Signature headers
 * 🧾 🇬🇧 Signs ActivityPub requests with RSA HTTP Signature headers
 *
 * 🔐 🇳🇱 Gebruikt de private key (*.pem) per gebruiker
 * 🔐 🇬🇧 Uses per-user private key (*.pem) for signing
 */

/**
 * 🇳🇱 Genereert ondertekende HTTP headers voor POST-verzending naar Fediverse inbox
 * 🇬🇧 Generates signed HTTP headers for POST delivery to a Fediverse inbox
 *
 * @param string $inbox           🇳🇱 Doel-inbox URL / 🇬🇧 Target inbox URL
 * @param string $body            🇳🇱 JSON-bericht dat verstuurd wordt / 🇬🇧 JSON body to send
 * @param string $actor_username  🇳🇱 Gebruikersnaam van afzender / 🇬🇧 Sender username (should have .pem key)
 * @return array|null             🇳🇱 Array van headers of null bij fout / 🇬🇧 Headers array or null on failure
 */
function fediversebridge_sign_request($inbox, $body, $actor_username = 'admin') {
    // 📁 Pad naar de private key
    // 📁 Path to private key
    $key_path = ossn_get_userdata("components/FediverseBridge/private/{$actor_username}.pem");

    if (!file_exists($key_path)) {
        fediversebridge_log("❌ Private key niet gevonden voor {$actor_username}: {$key_path}");
        return null;
    }

    // 🆔 Key ID die Mastodon en co gebruiken om public key te vinden
    // 🆔 Public key identifier used by recipients (points to actor)
    $key_id = ossn_site_url("fediverse/actor/{$actor_username}#main-key");

    // 🕒 Datum in HTTP-opmaak
    // 🕒 Current GMT date in HTTP format
    $date = gmdate('D, d M Y H:i:s T');

    // 🔐 Digest header = hash van de JSON body
    // 🔐 Digest header = hash of JSON body as required by spec
    $digest = 'SHA-256=' . base64_encode(hash('sha256', $body, true));

    // 🌐 Parse inbox-URL om host en path op te splitsen
    // 🌐 Parse inbox URL to extract host and path
    $url_parts = parse_url($inbox);
    if (!isset($url_parts['host'])) {
        fediversebridge_log("❌ Ongeldige inbox-URL: {$inbox}");
        return null;
    }

    $path = $url_parts['path'] ?? '/inbox';
    $request_target = 'post ' . $path;

    // 📜 Headers die ondertekend worden volgens ActivityPub HTTP Signature spec
    // 📜 Headers to be signed according to ActivityPub HTTP Signature spec
    $signature_headers = "(request-target) host date digest";

    // 🧾 Opbouw van string die ondertekend wordt
    // 🧾 The canonical string to be signed
    $signature_string = <<<SIG
(request-target): {$request_target}
host: {$url_parts['host']}
date: {$date}
digest: {$digest}
SIG;

    // 🔐 Laad en verwerk de private key
    // 🔐 Load and prepare the private key
    $private_key = file_get_contents($key_path);
    $pkey = openssl_pkey_get_private($private_key);

    if (!$pkey) {
        fediversebridge_log("❌ OpenSSL kon sleutel niet inlezen voor {$actor_username}");
        return null;
    }

    // ✍️ Onderteken de string met SHA-256
    // ✍️ Sign the canonical string using SHA-256
    if (!openssl_sign($signature_string, $signature, $pkey, OPENSSL_ALGO_SHA256)) {
        fediversebridge_log("❌ Ondertekenen mislukt voor {$actor_username}");
        return null;
    }

    $signature_b64 = base64_encode($signature);

    // ✅ Header array retourneren
    // ✅ Return final headers array
    return [
        'Date: ' . $date,
        'Host: ' . $url_parts['host'],
        'Content-Type: application/activity+json',
        'Digest: ' . $digest,
        'Signature: keyId="' . $key_id . '",algorithm="rsa-sha256",headers="' . $signature_headers . '",signature="' . $signature_b64 . '"',
        'User-Agent: FediverseBridge/1.0'
    ];
}
