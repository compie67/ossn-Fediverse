<?php
/**
 * helper: bluesky.php
 * 🇳🇱 Beheert Bluesky-handles per gebruiker
 * 🇬🇧 Handles Bluesky handles per user
 * optional future
 */

function fediversebridge_get_bluesky_handle($username) {
    $file = ossn_get_userdata("components/FediverseBridge/bluesky/{$username}.txt");
    if (file_exists($file)) {
        return trim(file_get_contents($file));
    }
    return '';
}

function fediversebridge_set_bluesky_handle($username, $handle) {
    $dir = ossn_get_userdata("components/FediverseBridge/bluesky/");
    if (!is_dir($dir)) mkdir($dir, 0755, true);
    file_put_contents("{$dir}{$username}.txt", trim($handle));
}
