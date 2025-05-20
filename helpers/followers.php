<?php
/**
 * helpers/followers.php
 * 🇳🇱 Gemaakt door Eric Redegeld voor nlsociaal.nl
 * 🇬🇧 Created by Eric Redegeld for nlsociaal.nl
 *
 * ✅ Functie: haalt inbox-URL's op van volgers van een gebruiker op het Fediverse
 * ✅ Alleen actieve (opt-in) gebruikers worden doorgestuurd
 */

/**
 * 🇳🇱 Haalt de inbox-URL's op van alle Fediverse-volgers van een OSSN-gebruiker
 * 🇬🇧 Fetches the inbox URLs of all Fediverse followers for a given OSSN user
 *
 * @param string $username OSSN gebruikersnaam
 * @return array Inbox-URL's of fallback-lijst
 */
function fediversebridge_get_followers_inboxes($username) {
    // 📁 Basispad voor opslag
    // 📁 Base storage path
    $base = ossn_get_userdata("components/FediverseBridge");

    // ✅ Eerst controleren of gebruiker Fediverse-opt-in heeft gedaan
    // ✅ First check if user has opted in for Fediverse publishing
    $optin_file = "{$base}/optin/{$username}.json";
    if (!file_exists($optin_file)) {
        fediversebridge_log("⛔️ Geen opt-in bestand voor {$username}, post wordt niet gefedereerd.");
        return [];
    }

    // 📥 Pad naar followers-bestand
    // 📥 Path to followers file containing actor URLs
    $followers_file = "{$base}/followers/{$username}.json";

    // 🧭 Geen followers.json → gebruik fallback servers
    // 🧭 No followers file → fallback to common Fediverse inboxes
    if (!file_exists($followers_file)) {
        fediversebridge_log("ℹ️ Geen followers.json voor {$username}, verstuur alleen naar fallback inboxes.");
        return [
            'https://mastodon.social/inbox',
            'https://mastodon.nl/inbox',
            'https://mastodon.education/inbox',
            'https://pleroma.envs.net/inbox',     // optioneel Pleroma
            'https://diaspod.org/inbox'           // optioneel Diaspora via bridge
        ];
    }

    // 📄 Bestand inlezen en decoderen
    // 📄 Read and decode followers.json
    $followers = json_decode(file_get_contents($followers_file), true);
    if (!is_array($followers)) {
        fediversebridge_log("⚠️ Followers.json corrupt of ongeldig voor {$username}");
        return [];
    }

    // 🔄 Zet elke actor-URL om naar zijn /inbox endpoint
    // 🔄 Convert each actor URL to its corresponding inbox
    $inboxes = [];
    foreach ($followers as $actor_url) {
        $actor_url = rtrim($actor_url, '/'); // 🇳🇱 Sluitende slash weghalen
        $inboxes[] = "{$actor_url}/inbox";
    }

    return $inboxes;
}
