<?php
/**
 * helpers/followers.php
 * Created by Eric Redegeld for nlsociaal.nl
 *
 * Provides helper functions to retrieve the inbox URLs of followers
 * Only active (opt-in) users are federated
 */

/**
 * Fetches the inbox URLs of all Fediverse followers for a given OSSN user
 *
 * @param string $username OSSN username
 * @return array Inbox URLs of followers or fallback list
 */
function fediversebridge_get_followers_inboxes($username) {
    $base = ossn_get_userdata("components/FediverseBridge");
    $optin_file = "{$base}/optin/{$username}.json";

    // User has not opted in → do not federate
    if (!file_exists($optin_file)) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG) {
            fediversebridge_log("⛔️ No opt-in file for {$username}, post will not be federated.");
        }
        return [];
    }

    $followers_file = "{$base}/followers/{$username}.json";

    // No followers file → fallback to known public inboxes
    if (!file_exists($followers_file)) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG) {
            fediversebridge_log("ℹ️ No followers.json for {$username}, using fallback inboxes.");
        }
        return fediversebridge_fallback_inboxes();
    }

    $followers = json_decode(file_get_contents($followers_file), true);
    if (!is_array($followers)) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG) {
            fediversebridge_log("⚠️ Invalid followers.json for {$username}");
        }
        return [];
    }

    // Convert each actor URL to its inbox endpoint
    return array_map(function($actor_url) {
        return rtrim($actor_url, '/') . '/inbox';
    }, $followers);
}

/**
 * Returns fallback inboxes when no valid followers list is found
 *
 * @return array
 */
function fediversebridge_fallback_inboxes() {
    return [
        'https://mastodon.social/inbox',
        'https://mastodon.nl/inbox',
        'https://mastodon.education/inbox',
        'https://pleroma.envs.net/inbox',
        'https://diaspod.org/inbox',
        'https://iviv.hu/inbox'
    ];
}
