<?php
/**
 * helpers/note.php
 * 🇳🇱 Zet een Fediverse Note-URL om naar de originele OSSN-post-URL
 * 🇬🇧 Converts a Fediverse note URL to the original OSSN post URL
 *
 * Door Eric Redegeld – nlsociaal.nl
 */

/**
 * Convert note URL (e.g. /fediverse/note/beheerder/37) to /shared_content/post/37/timestamp
 * Only if ContentSharing is active and the post exists
 *
 * @param string $note_url Full note URL (e.g. from a Like or Boost object)
 * @return string|null Full OSSN content URL, or null if not available
 */
function fediversebridge_note_to_original_url($note_url) {
	$parsed = parse_url($note_url);
	if (!isset($parsed['path'])) {
		return null;
	}

	// Match: /fediverse/note/username/guid
	if (!preg_match('#/fediverse/note/([^/]+)/([0-9]+)#', $parsed['path'], $matches)) {
		return null;
	}
	$guid = (int)$matches[2];

	// ✅ Laad originele post
	if (!function_exists('ossn_is_component_active') || !ossn_is_component_active('ContentSharing')) {
		return null;
	}
	$post = ossn_get_object($guid);
	if (!$post || $post->type !== 'user' || $post->subtype !== 'wall') {
		return null;
	}

	$timestamp = strtotime($post->time_created);
	if (!$timestamp) {
		$timestamp = time();
	}
	return ossn_site_url("shared_content/post/{$guid}/{$timestamp}");
}
