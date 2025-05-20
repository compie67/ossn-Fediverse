<?php
/**
 * pages/fediverse/inbox.php
 * 🇳🇱 Verwerkt inkomende ActivityPub-berichten
 * 🇬🇧 Handles incoming ActivityPub messages
 *
 * Gemaakt door Eric Redegeld – nlsociaal.nl
 */

// 🧭 Haal gebruikersnaam uit route: /fediverse/inbox/{username}
// 🧭 Extract username from URL segment
$username = $GLOBALS['FediversePages'][1] ?? null;

if (!$username) {
    header("HTTP/1.1 400 Bad Request");
    echo "❌ Geen gebruikersnaam opgegeven / No username specified";
    exit;
}

// 📥 Haal de ruwe JSON body op van de POST-request
// 📥 Read raw input from HTTP POST
$raw = file_get_contents('php://input');

if (empty($raw)) {
    header("HTTP/1.1 400 Bad Request");
    echo "❌ Ongeldige JSON of ontbrekend type / Empty or invalid body";
    exit;
}

// 📦 Decodeer JSON-gegevens
// 📦 Decode JSON input into array
$data = json_decode($raw, true);
if (!is_array($data)) {
    header("HTTP/1.1 400 Bad Request");
    echo "❌ Ongeldige JSON / Invalid JSON";
    exit;
}

// 📄 Bepaal het type activiteit (Like, Follow, etc.)
// 📄 Determine activity type
$type = $data['type'] ?? 'Onbekend';
fediversebridge_log("📥 INBOX voor {$username} ontvangen | Type: {$type}");

// 💾 Sla volledige bericht op in inbox-directory
// 💾 Save full message to inbox directory
$inbox_dir = ossn_get_userdata("components/FediverseBridge/inbox/{$username}/");
if (!is_dir($inbox_dir)) {
    mkdir($inbox_dir, 0755, true);
}
$filename = $inbox_dir . '/' . uniqid() . '.json';
file_put_contents($filename, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
fediversebridge_log("📩 Bericht opgeslagen in {$filename}");

// ❤️ Verwerk Like-bericht
// ❤️ Handle Like activity
if ($type === 'Like' && isset($data['actor'], $data['object'])) {
    $from = $data['actor'];
    $to = $data['object'];
    fediversebridge_log("❤️ Like ontvangen van {$from} op {$to}");
}

// 🔁 Verwerk Announce (Boost/Share)
// 🔁 Handle Announce activity
if ($type === 'Announce' && isset($data['actor'], $data['object'])) {
    $from = $data['actor'];
    $to = $data['object'];
    fediversebridge_log("🔁 Announce ontvangen van {$from} op {$to}");
}

// 💬 Verwerk Create-reply (bij inReplyTo)
// 💬 Handle reply to post
if ($type === 'Create' && isset($data['object']['inReplyTo'])) {
    $from = $data['actor'] ?? 'onbekend';
    $replyto = $data['object']['inReplyTo'];
    fediversebridge_log("💬 Antwoord ontvangen van {$from} op {$replyto}");
}

// 👤 Verwerk Follow-verzoek
// 👤 Handle Follow request
if ($type === 'Follow' && isset($data['actor'], $data['object'])) {
    $actor = $data['actor'];
    fediversebridge_log("👤 Nieuwe follower: {$actor}");

    // ➕ Voeg toe aan followers-lijst
    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");
    $followers = [];

    if (file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true);
        if (!is_array($followers)) {
            $followers = [];
        }
    }

    if (!in_array($actor, $followers)) {
        $followers[] = $actor;
        file_put_contents($followers_file, json_encode($followers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fediversebridge_log("✅ Follower toegevoegd aan {$followers_file}");
    }

    // 📤 Verstuur een Accept-bericht terug
    $accept = [
        '@context' => 'https://www.w3.org/ns/activitystreams',
        'id' => ossn_site_url("fediverse/accept/{$username}#".uniqid()),
        'type' => 'Accept',
        'actor' => ossn_site_url("fediverse/actor/{$username}"),
        'object' => $data
    ];

    $json = json_encode($accept, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $headers = fediversebridge_sign_request($actor . '/inbox', $json, $username);

    if ($headers) {
        $ch = curl_init($actor . '/inbox');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fediversebridge_log("📨 Accept teruggestuurd naar {$actor} | HTTP {$httpcode}");
    } else {
        fediversebridge_log("⚠️ Accept niet verzonden – headers niet gegenereerd.");
    }
}

// 🚫 Verwerk Undo van een Follow (unfollow)
// 🚫 Handle Undo activity (unfollow)
if ($type === 'Undo' && isset($data['object']['type']) && $data['object']['type'] === 'Follow') {
    $actor = $data['object']['actor'] ?? null;

    if ($actor) {
        $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");

        if (file_exists($followers_file)) {
            $followers = json_decode(file_get_contents($followers_file), true);

            if (is_array($followers)) {
                $index = array_search($actor, $followers);
                if ($index !== false) {
                    unset($followers[$index]);
                    $followers = array_values($followers); // 🔁 Herindexeren
                    file_put_contents($followers_file, json_encode($followers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
                    fediversebridge_log("🚫 Unfollow: {$actor} verwijderd uit followers van {$username}");
                }
            }
        }
    }
}

// ✅ Afronden met bevestiging
// ✅ Finish with HTTP 202 response
header("HTTP/1.1 202 Accepted");
echo "✅ Ontvangen";
