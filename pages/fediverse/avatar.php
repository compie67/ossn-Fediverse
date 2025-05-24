<?php
/**
 * pages/fediverse/avatar.php
 * 🇳🇱 Veilige proxy voor OSSN-gebruikersavatars
 * 🇬🇧 Secure proxy for OSSN user profile avatars
 *
 * Gemaakt door Eric Redegeld voor nlsociaal.nl
 */

header('Content-Type: image/jpeg'); // fallback content-type

$guid = (int) input('guid');
$filename = basename(input('file')); // beveiligt tegen path traversal

if (!$guid || !$filename) {
    http_response_code(400);
    echo '❌ Ongeldige aanvraag (GUID of bestandsnaam ontbreekt)';
    return;
}

// 🔍 Zoek naar avatar in profielmap
$dir = ossn_get_userdata("user/{$guid}/profile/photo/");
$path = "{$dir}{$filename}";

// Als het bestand bestaat, serveer het
if (file_exists($path)) {
    $mime = mime_content_type($path);
    $size = filesize($path);
    header("Content-Type: {$mime}");
    header("Content-Length: {$size}");
    header("Cache-Control: public, max-age=604800");
    readfile($path);
    exit;
}

// ❌ Anders: serveer standaard avatar
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

// ❌ Niets gevonden
http_response_code(404);
echo '❌ Avatar niet gevonden';
