<?php
/**
 * 🎭 Avatar Proxy – toont gebruikersavatar op basis van guid en bestandsnaam
 * 📂 /fediverse/avatar?guid=1&file=larger_abc123.jpg
 * 🔒 Beveiligd tegen padmanipulatie en fallback bij ontbrekende avatar
 */

$guid = (int) input('guid');
$filename = basename(input('file')); // voorkomt padtrucjes

if (!$guid || !$filename) {
    header("HTTP/1.1 400 Bad Request");
    echo '❌ Ongeldige aanvraag';
    return;
}

// 🔍 Zoekpad naar profielavatar
$base = ossn_get_userdata("user/{$guid}/profile/photo/");
$path = $base . $filename;

if (!file_exists($path)) {
    // Fallback naar standaard avatar
    $fallback = __DIR__ . '/../../../images/default-avatar.jpg';
    if (file_exists($fallback)) {
        $path = $fallback;
    } else {
        header("HTTP/1.1 404 Not Found");
        echo '❌ Avatar niet gevonden';
        return;
    }
}

// 🖼️ Toon de afbeelding
$mime = mime_content_type($path);
$size = filesize($path);
header("Content-Type: {$mime}");
header("Content-Length: {$size}");
header("Cache-Control: public, max-age=604800");
readfile($path);
exit;
