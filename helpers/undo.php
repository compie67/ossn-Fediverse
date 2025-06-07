<?php
/**
 * FediverseBridge – Undo Follow-verzending bij uitschrijving
 * Door Eric Redegeld – nlsociaal.nl
 */

function fediversebridge_send_undo_follows($username) {
    $base = ossn_get_userdata("components/FediverseBridge");
    $followers_file = "{$base}/followers/{$username}.json";
    $private_key_file = "{$base}/private/{$username}.pem";
    $actor = ossn_site_url("fediverse/actor/{$username}");

    if (!file_exists($followers_file) || !file_exists($private_key_file)) {
        return false;
    }

    $followers = json_decode(file_get_contents($followers_file), true);
    if (!is_array($followers)) {
        return false;
    }

    $private_key = file_get_contents($private_key_file);
    if (!$private_key) {
        return false;
    }

    foreach ($followers as $follower) {
        $inbox = rtrim($follower, '/') . '/inbox';

        $undo = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => "{$actor}/#undo-" . uniqid(),
            'type' => 'Undo',
            'actor' => $actor,
            'object' => [
                'type' => 'Follow',
                'actor' => $follower,
                'object' => $actor
            ]
        ];

        $json = json_encode($undo, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        $date = gmdate('D, d M Y H:i:s') . ' GMT';
        $digest = 'SHA-256=' . base64_encode(hash('sha256', $json, true));

        $parsed = parse_url($inbox);
        $host = $parsed['host'];
        $path = $parsed['path'];

        $string_to_sign = "(request-target): post {$path}\nhost: {$host}\ndate: {$date}\ndigest: {$digest}";
        openssl_sign($string_to_sign, $signature, $private_key, OPENSSL_ALGO_SHA256);
        $sig_b64 = base64_encode($signature);

        $headers = [
            "Host: {$host}",
            "Date: {$date}",
            "Digest: {$digest}",
            "Content-Type: application/activity+json",
            'Signature: keyId="' . $actor . '#main-key",algorithm="rsa-sha256",headers="(request-target) host date digest",signature="' . $sig_b64 . '"'
        ];

        $ch = curl_init($inbox);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("📤 UNDO sent from @{$username} to {$follower} | Status: {$status} | Error: {$error}");
        }
    }

    return true;
}
