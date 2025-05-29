<?php
/**
 * pages/fediverse/inbox.php
 * 🇬🇧 Handles incoming ActivityPub messages
 * 🇳🇱 Verwerkt inkomende ActivityPub-berichten
 *
 * Gemaakt door Eric Redegeld – nlsociaal.nl
 */

$username = $GLOBALS['FediversePages'][1] ?? null;

// 🔍 Validatie van gebruikersnaam
if (!$username) {
    header("HTTP/1.1 400 Bad Request");
    echo "❌ No username specified";
    exit;
}

// 📥 Lees binnenkomende JSON payload
$raw = file_get_contents('php://input');
if (empty($raw)) {
    header("HTTP/1.1 400 Bad Request");
    echo "❌ Empty request body";
    exit;
}

// 🧪 Decodeer JSON
$data = json_decode($raw, true);
if (!is_array($data)) {
    header("HTTP/1.1 400 Bad Request");
    echo "❌ Invalid JSON";
    exit;
}

// 🧾 Haal activiteits-type op en log deze
$type = $data['type'] ?? 'Unknown';
fediversebridge_log("📥 INBOX received for {$username} | Type: {$type}");

// 🧹 Negeer bepaalde ongewenste berichttypes
$ignore_types = ['Delete', 'Block'];
if (in_array($type, $ignore_types)) {
    fediversebridge_log("ℹ️ {$type} ignored from {$data['actor']}");
    header("HTTP/1.1 202 Accepted");
    echo "✅ Ignored {$type}";
    return;
}

// 📁 Zorg dat inbox-map bestaat
$inbox_dir = ossn_get_userdata("components/FediverseBridge/inbox/{$username}/");
if (!is_dir($inbox_dir)) {
    mkdir($inbox_dir, 0755, true);
}

// 💾 Sla bericht op als bestand
$filename = $inbox_dir . uniqid('', true) . '.json';
file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
fediversebridge_log("📩 Message saved: {$filename}");

// ❤️ Bekende activiteitentypes loggen
if ($type === 'Like' && isset($data['actor'], $data['object'])) {
    fediversebridge_log("❤️ Like from {$data['actor']} on {$data['object']}");
}
if ($type === 'Announce' && isset($data['actor'], $data['object'])) {
    fediversebridge_log("🔁 Announce by {$data['actor']} on {$data['object']}");
}
if ($type === 'Create' && isset($data['object']['inReplyTo'])) {
    fediversebridge_log("💬 Reply from {$data['actor']} to {$data['object']['inReplyTo']}");
}

// 👥 Afhandelen van Follow-verzoek
if ($type === 'Follow' && isset($data['actor'], $data['object'])) {
    $actor = $data['actor'];
    fediversebridge_log("👤 New follower: {$actor}");

    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");
    $followers = file_exists($followers_file) ? json_decode(file_get_contents($followers_file), true) : [];

    if (!in_array($actor, $followers)) {
        $followers[] = $actor;
        file_put_contents($followers_file, json_encode($followers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fediversebridge_log("✅ Follower added to {$followers_file}");
    }

    // ✅ Verstuur Accept-response
    $accept = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => ossn_site_url("fediverse/accept/{$username}#" . uniqid()),
        'type' => 'Accept',
        'actor' => ossn_site_url("fediverse/actor/{$username}"),
        'object' => $data
    ];

    $json = json_encode($accept, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = fediversebridge_sign_request("{$actor}/inbox", $json, $username);

    if ($headers) {
        $ch = curl_init("{$actor}/inbox");
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fediversebridge_log("📨 Accept sent to {$actor} | HTTP {$httpcode}");
    } else {
        fediversebridge_log("⚠️ Accept not sent – signing headers missing");
    }
}

// 🚫 Undo Follow verwerken
if ($type === 'Undo' && isset($data['object']['type']) && $data['object']['type'] === 'Follow') {
    $actor = $data['object']['actor'] ?? null;
    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");

    if ($actor && file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true);
        if (($key = array_search($actor, $followers)) !== false) {
            unset($followers[$key]);
            file_put_contents($followers_file, json_encode(array_values($followers), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fediversebridge_log("🚫 {$actor} removed from {$username}'s followers");
        }
    }
}

// ✅ Geef bevestiging terug
header("HTTP/1.1 202 Accepted");
echo "✅ Received";
