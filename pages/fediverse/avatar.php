<?php
/**
 * pages/fediverse/avatar.php
 * Secure proxy for OSSN user profile avatars
 * Created by Eric Redegeld for nlsociaal.nl
 */

// Default content type (will be overwritten if image is found)
header('Content-Type: image/jpeg');

// Sanitize input parameters to prevent path traversal
$guid = (int) input('guid');
$filename = basename(input('file'));

if (!$guid || !$filename) {
    http_response_code(400);
    echo 'Invalid request (missing GUID or filename)';
    return;
}

// Try to locate the avatar file in the user profile photo directory
$dir = ossn_get_userdata("user/{$guid}/profile/photo/");
$path = "{$dir}{$filename}";

// If the requested avatar exists, serve it with correct headers
if (file_exists($path)) {
    $mime = mime_content_type($path);
    $size = filesize($path);
    header("Content-Type: {$mime}");
    header("Content-Length: {$size}");
    header("Cache-Control: public, max-age=604800"); // 7 days cache
    readfile($path);
    exit;
}

// If not found, fall back to the default avatar image
$fallback = __DIR__ . '/../../components/FediverseBridge/images/default-avatar.jpg';
if (file_exists($fallback)) {
    $mime = mime_content_type($fallback);
    $size = filesize($fallback);
    header("Content-Type: {$mime}");
    header("Content-Length: {$size}");
    header("Cache-Control: public, max-age=604800");
    readfile($fallback);
    exit;
}

// Nothing found; return 404 error
http_response_code(404);
echo 'Avatar not found';
