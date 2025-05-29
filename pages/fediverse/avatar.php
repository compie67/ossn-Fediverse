<?php
/**
 * pages/fediverse/avatar.php
 * 🇬🇧 Secure proxy for OSSN user profile avatars
 * 🇳🇱 Veilige proxy voor OSSN-gebruikersavatars
 *
 * Created by Eric Redegeld for nlsociaal.nl
 */

// 🖼️ Default fallback: serve as JPEG unless found otherwise
header('Content-Type: image/jpeg');

// 📥 Input sanitization – prevent path traversal
$guid = (int) input('guid');
$filename = basename(input('file'));

if (!$guid || !$filename) {
    http_response_code(400);
    echo '❌ Invalid request (missing GUID or filename)';
    return;
}

// 🔍 🇬🇧 Locate avatar file / 🇳🇱 Zoek naar avatarbestand in profielmap
$dir = ossn_get_userdata("user/{$guid}/profile/photo/");
$path = "{$dir}{$filename}";

// ✅ 🇬🇧 Serve actual avatar if found / 🇳🇱 Toon echte avatar indien aanwezig
if (file_exists($path)) {
    $mime = mime_content_type($path);
    $size = filesize($path);
    header("Content-Type: {$mime}");
    header("Content-Length: {$size}");
    header("Cache-Control: public, max-age=604800"); // cache 7 days
    readfile($path);
    exit;
}

// 🔁 🇬🇧 Serve fallback image / 🇳🇱 Toon standaard avatar bij ontbreken
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

// ❌ 🇬🇧 Nothing found / 🇳🇱 Geen afbeelding gevonden
http_response_code(404);
echo '❌ Avatar not found';
