<?php
/**
 * Avatar Proxy – displays user avatar based on GUID and filename
 * Endpoint: /fediverse/avatar?guid=1&file=larger_abc123.jpg
 * Secured against path traversal and includes fallback for missing avatars
 */

$guid = (int) input('guid');
$filename = basename(input('file')); // Prevent directory traversal

if (!$guid || !$filename) {
    header("HTTP/1.1 400 Bad Request");
    echo 'Invalid request';
    return;
}

// Construct path to user profile photo
$base = ossn_get_userdata("user/{$guid}/profile/photo/");
$path = $base . $filename;

if (!file_exists($path)) {
    // Fallback to default avatar
    $fallback = __DIR__ . '/../../../images/default-avatar.jpg';
    if (file_exists($fallback)) {
        $path = $fallback;
    } else {
        header("HTTP/1.1 404 Not Found");
        echo 'Avatar not found';
        return;
    }
}

// Serve the image
$mime = mime_content_type($path);
$size = filesize($path);
header("Content-Type: {$mime}");
header("Content-Length: {$size}");
header("Cache-Control: public, max-age=604800");
readfile($path);
exit;
