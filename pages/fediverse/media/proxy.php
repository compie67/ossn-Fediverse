<?php
/**
 *  Veilige afbeeldingsproxy voor Fediverse
 * 🇳🇱 Toont OSSN-wall afbeeldingen via veilige link (1:1 per bestand)
 *  /fediverse/media/proxy?guid=123&file=bestandsnaam.jpg
 */

$guid = (int) input('guid');
$filename = basename(input('file')); // beveiliging tegen padmanipulatie

if (!$guid || !$filename) {
    fediversebridge_log("proxy.php – GUID of bestand ontbreekt");
    header("HTTP/1.1 400 Bad Request");
    exit('Ongeldige aanvraag');
}

$object = ossn_get_object($guid);
if (!$object || $object->type !== 'user') {
    fediversebridge_log("proxy.php – Ongeldig object voor GUID {$guid}");
    header("HTTP/1.1 404 Not Found");
    exit('Object niet gevonden');
}

// 🔍 Zoek in zowel images/ als multiupload/
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

if (!$path || !file_exists($path)) {
    fediversebridge_log("proxy.php – Bestand niet gevonden: {$filename} in object {$guid}");
    header("HTTP/1.1 404 Not Found");
    exit('❌ Bestand niet gevonden');
}

$mime = mime_content_type($path);
$size = filesize($path);

fediversebridge_log("🖼️ proxy.php – Toont {$filename} ({$mime}, {$size} bytes) uit object {$guid}");

header("Content-Type: {$mime}");
header("Content-Length: {$size}");
header("Cache-Control: public, max-age=604800");
readfile($path);
exit;
