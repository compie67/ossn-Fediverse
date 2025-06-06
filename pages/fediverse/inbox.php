<?php
/**
 * pages/fediverse/inbox.php
 * Verwerkt inkomende ActivityPub-berichten (Like, Follow, Create, Announce, Undo)
 * Door Eric Redegeld – nlsociaal.nl
 */

require_once ossn_route()->com . 'FediverseBridge/helpers/fediversebridge_log.php';
require_once ossn_route()->com . 'FediverseBridge/helpers/verify.php';
require_once ossn_route()->com . 'FediverseBridge/helpers/sign.php';
require_once ossn_route()->com . 'FediverseBridge/helpers/followers.php';

// Logging van ruwe inhoud en headers
$raw = file_get_contents('php://input');
file_put_contents('/tmp/inbox_debug.log', "BODY:\n{$raw}\n\n", FILE_APPEND);
file_put_contents('/tmp/inbox_debug.log', "HEADERS:\n" . json_encode(getallheaders(), JSON_PRETTY_PRINT) . "\n\n", FILE_APPEND);

// Gebruikersnaam uit URL
$username = $GLOBALS['FediversePages'][1] ?? null;
if (!$username) {
    header("HTTP/1.1 400 Bad Request");
    echo ossn_print('fediversebridge:inbox:error:nouser');
    exit;
}

// Alleen POST toegestaan
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    echo ossn_print('fediversebridge:inbox:error:method');
    exit;
}

// Content-Type controleren
$ctype = $_SERVER['CONTENT_TYPE'] ?? ($_SERVER['HTTP_CONTENT_TYPE'] ?? '');
if (
    stripos($ctype, 'application/activity+json') === false &&
    stripos($ctype, 'application/ld+json') === false &&
    stripos($ctype, 'application/json') === false
) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:error:contenttype', [$ctype]));
    header("HTTP/1.1 406 Not Acceptable");
    echo json_encode(['error' => 'Only ActivityPub-compatible JSON accepted']);
    exit;
}

// Lege of ongeldige JSON
if (empty($raw)) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:error:body'));
    header("HTTP/1.1 400 Bad Request");
    echo ossn_print('fediversebridge:inbox:error:body');
    exit;
}

$data = json_decode($raw, true);
if (!is_array($data)) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:error:json') . " Ruw: {$raw}");
    header("HTTP/1.1 400 Bad Request");
    echo ossn_print('fediversebridge:inbox:error:json');
    exit;
}

// Signature check
$incoming_headers = getallheaders();
fediversebridge_log("DEBUG: Incoming headers: " . json_encode($incoming_headers));

if (!fediversebridge_verify_incoming_signature($raw, $incoming_headers)) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:error:signature', [$username]));
    header("HTTP/1.1 403 Forbidden");
    echo "Invalid signature";
    exit;
}

// Type & actor bepalen
$type  = $data['type'] ?? 'Onbekend';
$actor = $data['actor'] ?? 'onbekend';

// Alleen acties van volgers toestaan (behalve Follow zelf)
if ($type !== 'Follow' && !fediversebridge_actor_is_follower($username, $actor)) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:ignored', [$actor, $username]));
    http_response_code(200);
    echo json_encode(['status' => 'ignored']);
    exit;
}

fediversebridge_log(ossn_print('fediversebridge:inbox:received', [$username, $type]));

// Inbox opslaan
$inbox_dir = ossn_get_userdata("components/FediverseBridge/inbox/{$username}/");
if (!is_dir($inbox_dir)) {
    mkdir($inbox_dir, 0755, true);
}
file_put_contents($inbox_dir . '/' . uniqid() . '.json', json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

// Like
if ($type === 'Like' && isset($data['object'])) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:like', [$actor, $data['object']]));
}

// Boost / Announce
if ($type === 'Announce' && isset($data['object'])) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:announce', [$actor, $data['object']]));
}

// Reply verwerken
if ($type === 'Create' && isset($data['object']) && is_array($data['object']) && ($data['object']['type'] ?? '') === 'Note') {
    $object  = $data['object'];
    $replyto = $object['inReplyTo'] ?? null;

    fediversebridge_log("REPLYTO: {$replyto}");
    fediversebridge_log(ossn_print('fediversebridge:inbox:create', [$actor]));

    if ($replyto && str_contains($replyto, '/fediverse/note/')) {
        if (preg_match('#/note/[^/]+/([0-9]+)#', $replyto, $matches)) {
            $original_guid = (int)$matches[1];
            $replies_dir   = ossn_get_userdata("components/FediverseBridge/replies/{$original_guid}/");

            if (!is_dir($replies_dir)) {
                mkdir($replies_dir, 0755, true);
            }

            $reply_file = $replies_dir . '/' . uniqid('reply_') . '.json';
            file_put_contents($reply_file, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            fediversebridge_log(ossn_print('fediversebridge:inbox:create:reply', [$original_guid, $reply_file]));
        } else {
            fediversebridge_log("Kon geen GUID vinden in inReplyTo: {$replyto}");
        }
    } else {
        fediversebridge_log("Geen geldige inReplyTo gevonden of onbekend pad.");
    }
}

// Volger toevoegen + Accept sturen
if ($type === 'Follow' && isset($actor)) {
    fediversebridge_log(ossn_print('fediversebridge:inbox:follow', [$actor]));

    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");
    $followers = [];

    if (file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true) ?? [];
    }

    if (!in_array($actor, $followers)) {
        $followers[] = $actor;
        file_put_contents($followers_file, json_encode($followers, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        fediversebridge_log(ossn_print('fediversebridge:inbox:follow:added', [$followers_file]));
    }

    fediversebridge_send_accept($actor, $username, $data);
}

// Undo
if ($type === 'Undo' && isset($data['object']['type'])) {
    $undone_type = $data['object']['type'];
    $target      = $data['object']['object'] ?? null;
    fediversebridge_log(ossn_print('fediversebridge:inbox:undo', [$undone_type, $actor, $target]));
}

http_response_code(200);
echo json_encode(['status' => 'ok']);
exit;
