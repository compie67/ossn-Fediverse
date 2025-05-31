<?php
/**
 * pages/fediverse/inbox.php
 * ActivityPub inbox endpoint for receiving messages
 * Created by Eric Redegeld – nlsociaal.nl
 */

$username = $GLOBALS['FediversePages'][1] ?? null;

// Validate username
if (!$username) {
    header("HTTP/1.1 400 Bad Request");
    echo "Username missing";
    exit;
}

// Read the incoming JSON payload
$raw = file_get_contents('php://input');
if (empty($raw)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Empty request body";
    exit;
}

// Decode JSON
$data = json_decode($raw, true);
if (!is_array($data)) {
    header("HTTP/1.1 400 Bad Request");
    echo "Invalid JSON";
    exit;
}

// Log message type
$type = $data['type'] ?? 'Unknown';
fediversebridge_log("INBOX received for {$username} | Type: {$type}");

// Ignore certain activity types
$ignore_types = ['Delete', 'Block'];
if (in_array($type, $ignore_types)) {
    fediversebridge_log("{$type} ignored from {$data['actor']}");
    header("HTTP/1.1 202 Accepted");
    echo "Ignored {$type}";
    return;
}

// Ensure inbox directory exists
$inbox_dir = ossn_get_userdata("components/FediverseBridge/inbox/{$username}/");
if (!is_dir($inbox_dir)) {
    mkdir($inbox_dir, 0755, true);
}

// Save incoming message to disk
$filename = $inbox_dir . uniqid('', true) . '.json';
file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
fediversebridge_log("Message saved: {$filename}");

// Log specific known activity types
if ($type === 'Like' && isset($data['actor'], $data['object'])) {
    fediversebridge_log("Like from {$data['actor']} on {$data['object']}");
}
if ($type === 'Announce' && isset($data['actor'], $data['object'])) {
    fediversebridge_log("Announce by {$data['actor']} on {$data['object']}");
}
if ($type === 'Create' && isset($data['object']['inReplyTo'])) {
    fediversebridge_log("Reply from {$data['actor']} to {$data['object']['inReplyTo']}");
}

// Handle Follow activity
if ($type === 'Follow' && isset($data['actor'], $data['object'])) {
    $actor = $data['actor'];
    fediversebridge_log("New follower: {$actor}");

    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");
    $followers = file_exists($followers_file) ? json_decode(file_get_contents($followers_file), true) : [];

    if (!in_array($actor, $followers)) {
        $followers[] = $actor;
        file_put_contents($followers_file, json_encode($followers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fediversebridge_log("Follower added to {$followers_file}");
    }

    // Send Accept response
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
        fediversebridge_log("Accept sent to {$actor} | HTTP {$httpcode}");
    } else {
        fediversebridge_log("Accept not sent – signing headers missing");
    }
}

// Handle Undo Follow activity
if ($type === 'Undo' && isset($data['object']['type']) && $data['object']['type'] === 'Follow') {
    $actor = $data['object']['actor'] ?? null;
    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");

    if ($actor && file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true);
        if (($key = array_search($actor, $followers)) !== false) {
            unset($followers[$key]);
            file_put_contents($followers_file, json_encode(array_values($followers), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fediversebridge_log("{$actor} removed from {$username}'s followers");
        }
    }
}

// Return confirmation
header("HTTP/1.1 202 Accepted");
echo "Received";
