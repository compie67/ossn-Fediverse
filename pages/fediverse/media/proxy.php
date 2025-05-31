<?php
/**
 * Image Proxy for FediverseBridge
 * Secure access to OSSN wall images via object GUID and filename
 * Endpoint: /fediverse/media/proxy?guid=123&file=example.jpg
 */

// Sanitize input to prevent path traversal
$guid     = (int) input('guid');
$filename = basename(input('file'));

if (!$guid || !$filename) {
    header("HTTP/1.1 400 Bad Request");
    exit('Invalid request');
}

// Retrieve the object and ensure it is of type 'user'
$object = ossn_get_object($guid);
if (!$object || $object->type !== 'user') {
    header("HTTP/1.1 404 Not Found");
    exit('Object not found');
}

// Check common directories for uploaded wall images
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
    header("HTTP/1.1 404 Not Found");
    exit('File not found');
}

// Serve the image file
$mime = mime_content_type($path);
$size = filesize($path);

header("Content-Type: {$mime}");
header("Content-Length: {$size}");
header("Cache-Control: public, max-age=604800"); // 7 days
readfile($path);
exit;
