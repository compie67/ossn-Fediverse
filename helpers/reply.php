<?php
/**
 * helpers/reply.php
 * Versturen van een reply als ActivityPub Create/Note met inReplyTo
 * Door Eric Redegeld – nlsociaal.nl
 * Deze helper is voorbereid voor toekomstige reply-functionaliteit
 */

require_once __DIR__ . '/sign.php';

/**
 * Verstuur een reply via ActivityPub
 *
 * @param string $from_username OSSN-gebruikersnaam van afzender
 * @param string $inReplyTo_url URL van het originele bericht waarop wordt gereageerd
 * @param string $reply_content HTML-inhoud van de reply
 * @return bool true als succesvol verzonden
 */
function fediversebridge_send_reply($from_username, $inReplyTo_url, $reply_content) {
    $actor_url = ossn_site_url("fediverse/actor/{$from_username}");
    $now = date('c');

    // Originele post ophalen
    $original = @file_get_contents($inReplyTo_url);
    if (!$original) {
        fediversebridge_log("Kan originele post niet ophalen: {$inReplyTo_url}");
        return false;
    }

    $original_json = json_decode($original, true);
    $original_actor = $original_json['attributedTo'] ?? null;
    if (!$original_actor) {
        fediversebridge_log("Geen actor gevonden in originele post: {$inReplyTo_url}");
        return false;
    }

    // Inbox van originele actor ophalen
    $actor_data = @file_get_contents($original_actor);
    if (!$actor_data) {
        fediversebridge_log("Kon actor niet ophalen: {$original_actor}");
        return false;
    }

    $actor_json = json_decode($actor_data, true);
    $inbox = $actor_json['inbox'] ?? null;
    if (!$inbox) {
        fediversebridge_log("Geen inbox gevonden voor actor: {$original_actor}");
        return false;
    }

    // Note opstellen
    $note_id = ossn_site_url("fediverse/reply/{$from_username}/" . time());
    $note = [
        '@context'     => 'https://www.w3.org/ns/activitystreams',
        'id'           => $note_id,
        'type'         => 'Note',
        'attributedTo' => $actor_url,
        'inReplyTo'    => $inReplyTo_url,
        'to'           => ['https://www.w3.org/ns/activitystreams#Public'],
        'content'      => $reply_content,
        'published'    => $now
    ];

    // Create-activiteit bouwen
    $activity = [
        '@context'  => 'https://www.w3.org/ns/activitystreams',
        'id'        => $note_id . '/activity',
        'type'      => 'Create',
        'actor'     => $actor_url,
        'published' => $now,
        'to'        => ['https://www.w3.org/ns/activitystreams#Public'],
        'object'    => $note
    ];

    $body = json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    $headers = fediversebridge_sign_request($inbox, $body, $from_username);
    if (!$headers) {
        fediversebridge_log("Kon reply niet ondertekenen");
        return false;
    }

    // Versturen via cURL
    $ch = curl_init($inbox);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HEADER, true);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    fediversebridge_log("Reply verzonden naar {$inbox} | Status: {$http_code}");
    file_put_contents(
        ossn_get_userdata("components/FediverseBridge/logs/fediverse.log"),
        date('c') . " Reply response: \n{$response}\n\n",
        FILE_APPEND
    );

    return in_array($http_code, [200, 202], true);
}
