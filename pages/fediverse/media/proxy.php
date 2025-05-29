<?php
/**
 * 📦 Image Proxy for FediverseBridge
 * 🇳🇱 Toont veilige toegang tot OSSN wall-afbeeldingen via GUID en bestandsnaam
 * 🇬🇧 Secure image proxy for wall attachments using GUID and filename
 * 📂 Endpoint: /fediverse/media/proxy?guid=123&file=example.jpg
 */

// 🔒 🇳🇱 Beveilig invoer
// 🔒 🇬🇧 Sanitize input to prevent path traversal
$guid     = (int) input('guid');
$filename = basename(input('file'));

if (!$guid || !$filename) {
    // ❌ 🇳🇱 Ongeldige invoer
    // ❌ 🇬🇧 Invalid input
    header("HTTP/1.1 400 Bad Request");
    exit('❌ Invalid request');
}

// 🔍 🇳🇱 Zoek het object en controleer type
// 🔍 🇬🇧 Retrieve object and check type
$object = ossn_get_object($guid);
if (!$object || $object->type !== 'user') {
    header("HTTP/1.1 404 Not Found");
    exit('❌ Object not found');
}

// 📂 🇳🇱 Zoek paden naar mogelijke afbeeldingslocaties
// 📂 🇬🇧 Look in both image and multiupload folders
$search_dirs = [
    ossn_get_userdata("object/{$guid}/ossnwall/images/"),
    ossn_get_userdata("object/{$guid}/ossnwall/multiupload/")
];

$path = null;
foreach ($search_dirs as $dir) {
    $candidate = $dir . $filename;
    if (file_exists($candidate)) {
        $path = $candidate;
        break;
    }
}

// ❌ 🇳🇱 Bestand niet gevonden
// ❌ 🇬🇧 File not found
if (!$path || !file_exists($path)) {
    header("HTTP/1.1 404 Not Found");
    exit('❌ File not found');
}

// 🖼️ 🇳🇱 Toon afbeelding
// 🖼️ 🇬🇧 Output image file
$mime = mime_content_type($path);
$size = filesize($path);

header("Content-Type: {$mime}");
header("Content-Length: {$size}");
header("Cache-Control: public, max-age=604800"); // 7 days
readfile($path);
exit;
