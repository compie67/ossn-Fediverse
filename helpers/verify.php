<?php
// helpers/verify.php
/**
 * 🇳🇱 Gemaakt door Eric Redegeld voor nlsociaal.nl
 * 🇬🇧 Created by Eric Redegeld for nlsociaal.nl
 *
 *  🇳🇱 Verifieert inkomende ActivityPub-verzoeken met RSA HTTP Signature headers
 *  🇬🇧 Verifies incoming ActivityPub requests with RSA HTTP Signature headers
 */

// Logging function assumed to be available from ossn_com.php or inbox.php
if (!function_exists('fediversebridge_log')) {
    function fediversebridge_log($msg) {
        // Fallback log if not loaded, should not happen in production
        error_log(date('c') . " [FediverseBridge-VERIFY] {$msg}\n", 3, 'php_error.log');
    }
}

/**
 * 🇳🇱 Verifieert de HTTP Signature van een inkomend ActivityPub-verzoek.
 * 🇬🇧 Verifies the HTTP Signature of an incoming ActivityPub request.
 *
 * @param string $raw_body 🇳🇱 De ruwe JSON body van het verzoek / 🇬🇧 The raw JSON body of the request.
 * @param array $headers 🇳🇱 Array van inkomende HTTP headers (via getallheaders()) / 🇬🇧 Array of incoming HTTP headers.
 * @return bool 🇳🇱 True als de handtekening geldig is, anders false / 🇬🇧 True if signature is valid, false otherwise.
 */
function fediversebridge_verify_incoming_signature($raw_body, $headers) {
    fediversebridge_log(" START: Verifying incoming request signature.");
    


    // getallheaders() often returns headers with Title-Case, so use 'Signature'
    $signature_header = $headers['Signature'] ?? $headers['signature'] ?? null; // Also check lowercase just in case
    if (!$signature_header) {
        fediversebridge_log(" Signature header missing.");
        return false;
    }

    // 1. Parse the Signature header (more robust parsing)
    $params = [];
    // Using an alternative approach to parse the comma-separated key="value" pairs
    foreach (explode(',', $signature_header) as $param) {
        $parts = explode('=', trim($param), 2); // Split by first '='
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $value = trim($parts[1], '"'); // Remove surrounding quotes
            $params[$key] = $value;
        }
    }

    $key_id = $params['keyId'] ?? null;
    $algorithm = $params['algorithm'] ?? null;
    $signed_headers_str = $params['headers'] ?? null;
    $received_signature_b64 = $params['signature'] ?? null;

    if (!$key_id || !$algorithm || !$signed_headers_str || !$received_signature_b64) {
        // Log more verbosely here to see which one is missing
        fediversebridge_log(" Malformed Signature header components. keyId: " . ($key_id ?? 'NULL') . ", algo: " . ($algorithm ?? 'NULL') . ", headers: " . ($signed_headers_str ?? 'NULL') . ", sig: " . (isset($received_signature_b64) ? substr($received_signature_b64, 0, 10) . '...' : 'NULL'));
        return false;
    }

    // Check algorithm (only rsa-sha256 for now, as used by Mastodon for HTTP Signatures)
    if (strtolower($algorithm) !== 'rsa-sha256') { // Case-insensitive check
        fediversebridge_log(" Unsupported signature algorithm: {$algorithm}. Expected rsa-sha256.");
        return false;
    }

    $signed_headers_names = explode(' ', $signed_headers_str);

    // 2. Retrieve Public Key from key_id
    $public_key_pem = fediversebridge_get_public_key_from_actor_id($key_id);
    if (!$public_key_pem) {
        fediversebridge_log(" Failed to retrieve public key for keyId: {$key_id}. Cannot verify signature.");
        return false;
    }
    $public_key = openssl_pkey_get_public($public_key_pem);
    if (!$public_key) {
        fediversebridge_log(" Failed to load public key PEM from retrieved key for {$key_id}.");
        return false;
    }

    // 3. Reconstruct the signing string
    $signing_string_lines = [];
    foreach ($signed_headers_names as $header_name) {
        $header_name = trim(strtolower($header_name)); // Normalize header name for comparison

        if ($header_name === '(request-target)') {
            $request_method = strtolower($_SERVER['REQUEST_METHOD']);
            $request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
            $signing_string_lines[] = "(request-target): {$request_method} {$request_uri}";
        } elseif ($header_name === 'host') {
            $signing_string_lines[] = "host: " . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : $_SERVER['SERVER_NAME']);
        } elseif ($header_name === 'digest') {
            // Recompute digest from the raw_body for verification, don't trust incoming
            $computed_digest = 'SHA-256=' . base64_encode(hash('sha256', $raw_body, true));
            // Check for 'Digest' header (Title-Case)
            $incoming_digest = $headers['Digest'] ?? null;
            if ($incoming_digest !== $computed_digest) {
                fediversebridge_log(" Digest mismatch. Incoming: " . ($incoming_digest ?? 'N/A') . " Computed: {$computed_digest}.");
                return false; // Digest must match
            }
            $signing_string_lines[] = "digest: " . $computed_digest;
        } elseif ($header_name === 'date') {
            // Check for 'Date' header (Title-Case)
            $signing_string_lines[] = "date: " . ($headers['Date'] ?? '');
        } else {
            // For other headers like 'content-type', etc.
            // Map common header names to their typical $_SERVER or getallheaders() casing.
            $canonical_header_name = str_replace(' ', '-', ucwords(str_replace('-', ' ', $header_name)));
            if (isset($headers[$canonical_header_name])) {
                $signing_string_lines[] = "{$header_name}: " . $headers[$canonical_header_name];
            } else {
                fediversebridge_log("⚠️ Header '{$header_name}' not found in incoming request for signature string reconstruction. This header was listed in 'headers' parameter of signature.");
                // If a header listed in 'headers' is missing, it's a critical failure for signature verification.
                return false;
            }
        }
    }
    $signing_string = implode("\n", $signing_string_lines);
    fediversebridge_log("📝 Reconstructed signing string for verification:\n" . $signing_string);

    $decoded_signature = base64_decode($received_signature_b64);
    if ($decoded_signature === false) {
        fediversebridge_log(" Failed to base64 decode signature.");
        return false;
    }

    // 4. Verify the signature
    $verified = openssl_verify($signing_string, $decoded_signature, $public_key, OPENSSL_ALGO_SHA256);

    if ($verified === 1) {
        fediversebridge_log("✅ Signature verified successfully.");
        return true;
    } elseif ($verified === 0) {
        fediversebridge_log("🚫 Signature verification failed. (Incorrect signature)");
    } else {
        fediversebridge_log("❌ Error during signature verification: " . openssl_error_string());
    }

    return false;
}

/**
 * Helper to fetch public key from an ActivityPub actor ID/URL
 * This will require an HTTP GET request to the $actor_id URL.
 * You might want to cache these keys for performance.
 *
 * @param string $key_id The keyId from the signature header, often an actor URL.
 * @return string|null Public key in PEM format, or null on failure.
 */
function fediversebridge_get_public_key_from_actor_id($key_id) {
    // Basic URL parsing to get the actor URL (often the key_id itself)
    // If key_id is "actor_url#keyname", strip the #keyname part
    $actor_url = strtok($key_id, '#');

    fediversebridge_log(" Attempting to fetch public key from actor URL: {$actor_url}");

    // Make an HTTP GET request to $actor_url to fetch the actor object.
    // Set 'Accept: application/activity+json' or similar headers.
    $ch = curl_init($actor_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Explicitly set Accept headers for ActivityPub, JSON-LD, and generic JSON
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/activity+json, application/ld+json; profile="https://www.w3.org/ns/activitystreams", application/json'
    ]);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10); // Increased timeout slightly
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5); // Increased connect timeout slightly
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true); // Follow redirects, important for some instances
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true); // Always verify SSL in production
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        fediversebridge_log("❌ cURL error fetching actor object from {$actor_url}: {$curl_error}");
        return null;
    }

    if ($http_code !== 200) {
        fediversebridge_log("❌ Failed to fetch actor object from {$actor_url}. HTTP Code: {$http_code}. Response: " . substr($response, 0, 200));
        return null;
    }

    $actor_data = json_decode($response, true);
    if (!is_array($actor_data)) {
        fediversebridge_log("❌ Failed to parse actor JSON from {$actor_url}. Response was: " . substr($response, 0, 200));
        return null;
    }

    // Extract the public key PEM
    $public_key = $actor_data['publicKey']['publicKeyPem'] ?? null;

    if (!$public_key) {
        fediversebridge_log("❌ public key PEM not found in actor object for {$actor_url}. Actor data keys: " . implode(', ', array_keys($actor_data)));
        return null;
    }

    fediversebridge_log("✅ Successfully fetched public key for {$key_id}.");
    return $public_key;
}
