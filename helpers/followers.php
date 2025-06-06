<?php
/**
 * helpers/followers.php
 * Created by Eric Redegeld for nlsociaal.nl
 */

/**
 * Fetches the inbox URLs of all Fediverse followers for a given OSSN user
 *
 * @param string $username OSSN username
 * @return array Inbox URLs or fallback list
 */
function fediversebridge_get_followers_inboxes($username) {
    $base = ossn_get_userdata("components/FediverseBridge");

    // Check if user has opted in
    $optin_file = "{$base}/optin/{$username}.json";
    if (!file_exists($optin_file)) {
        fediversebridge_log(ossn_print('fediversebridge:log:nooptinfile', [$username]));
        return [];
    }

    $followers_file = "{$base}/followers/{$username}.json";

    // No followers.json → use fallback inboxes
    if (!file_exists($followers_file)) {
        fediversebridge_log(ossn_print('fediversebridge:log:nofollowersfile', [$username]));
        return [
            'https://mastodon.social/inbox',
            'https://mastodon.nl/inbox',
            'https://mastodon.education/inbox',
            'https://pleroma.envs.net/inbox',
            'https://diaspod.org/inbox'
        ];
    }

    $followers = json_decode(file_get_contents($followers_file), true);
    if (!is_array($followers)) {
        fediversebridge_log(ossn_print('fediversebridge:log:invalidfollowersfile', [$username]));
        return [];
    }

    $inboxes = [];
    foreach ($followers as $actor_url) {
        $actor_url = rtrim($actor_url, '/');
        $inboxes[] = "{$actor_url}/inbox";
    }

    return $inboxes;
}

/**
 * Checks if a given actor is a known follower of the user
 *
 * @param string $username OSSN username
 * @param string $actor Full actor URL (e.g., https://mastodon.social/users/test)
 * @return bool true if actor is listed
 */
function fediversebridge_actor_is_follower($username, $actor) {
    $followers_file = ossn_get_userdata("components/FediverseBridge/followers/{$username}.json");
    if (!file_exists($followers_file)) {
        return false;
    }

    $followers = json_decode(file_get_contents($followers_file), true);
    if (!is_array($followers)) {
        return false;
    }

    return in_array($actor, $followers);
}
