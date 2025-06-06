<?php
/**
 * pages/fediverse/avatar.php
 * 🇳🇱 Veilige proxy voor OSSN-gebruikersavatars
 * 🇬🇧 Secure proxy for OSSN user profile avatars
 *
 * Gemaakt door Eric Redegeld voor nlsociaal.nl
 */

$guid = (int) input('guid');
$file_input = input('file');

// Fallback expliciet of leeg bestand
$is_fallback = ($file_input === 'fallback');
$filename = $is_fallback ? '' : basename($file_input);

// 📁 Profielpad
$dir = ossn_get_userdata("user/{$guid}/profile/photo/");
$path = "{$dir}{$filename}";

// 🔍 Als bestand bestaat en geen fallback geforceerd is
if (!$is_fallback && $guid && $filename && file_exists($path)) {
    $mime = mime_content_type($path);
    $size = filesize($path);
    header("Content-Type: {$mime}");
    header("Content-Length: {$size}");
    header("Cache-Control: public, max-age=604800");
    readfile($path);
    exit;
}

// 🔄 Fallback
$fallback = ossn_route()->com . 'FediverseBridge/images/default-avatar.jpg';
if (file_exists($fallback)) {
    $mime = mime_content_type($fallback);
    $size = filesize($fallback);
    header("Content-Type: {$mime}");
    header("Content-Length: {$size}");
    header("Cache-Control: public, max-age=604800");
    readfile($fallback);
    exit;
}

// ❌ Geen bestand of fallback
http_response_code(404);
echo '❌ Avatar niet gevonden';
