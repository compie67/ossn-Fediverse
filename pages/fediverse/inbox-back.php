<?php
// pages/fediverse/inbox.php

require_once ossn_route()->com . 'FediverseBridge/helpers/sign.php';

fediversebridge_log("[FEDIVERSE] INBOX.php actief | \$_SERVER['REQUEST_METHOD'] = {$_SERVER['REQUEST_METHOD']}");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    exit;
}

global $FediversePages;
$username = $FediversePages[1] ?? null;
if (!$username) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Username ontbreekt';
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || !isset($data['type'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Ongeldige JSON of ontbrekend type';
    exit;
}

$type = $data['type'];
$id = $data['id'] ?? ('https://shadow.nlsociaal.nl/temp/' . uniqid("act_"));
fediversebridge_log("📥 INBOX voor {$username} ontvangen | Type: {$type}");

$inbox_dir = rtrim(ossn_get_userdata("components/FediverseBridge/inbox/{$username}"), '/');
if (!is_dir($inbox_dir)) mkdir($inbox_dir, 0755, true);

// Veilige bestandsnaam
$filename = preg_replace('/[^a-zA-Z0-9\-_\.]/', '_', basename($id)) . '.json';
file_put_contents("{$inbox_dir}/{$filename}", $raw);
fediversebridge_log("📩 Bericht opgeslagen in {$inbox_dir}/{$filename}");

if ($type === 'Follow' && isset($data['actor'])) {
    $actor = $data['actor'];
    fediversebridge_log("👤 Nieuwe follower: {$actor}");

    // ➕ Voeg toe aan followerslijst
    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");
    $followers = file_exists($followers_file) ? json_decode(file_get_contents($followers_file), true) : [];

    if (!in_array($actor, $followers)) {
        $followers[] = $actor;
        file_put_contents($followers_file, json_encode($followers, JSON_PRETTY_PRINT));
        fediversebridge_log("✅ Nieuwe follower opgeslagen: {$actor}");
    }

    // 🔁 Verstuur Accept
    $accept = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => ossn_site_url("fediverse/outbox/{$username}#accept-" . time()),
        'type' => 'Accept',
        'actor' => ossn_site_url("fediverse/actor/{$username}"),
        'object' => $data,
    ];
    $accept_json = json_encode($accept, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $inbox_url = rtrim($actor, '/') . '/inbox';
    $headers = fediversebridge_sign_request($inbox_url, $accept_json, $username);

    $ch = curl_init($inbox_url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $accept_json);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $result = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    fediversebridge_log("📨 Accept teruggestuurd naar {$actor} | HTTP {$code}");
}

header('HTTP/1.1 202 Accepted');
exit;
