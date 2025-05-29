<?php
/**
 * helpers/generate_key.php
 * Created by Eric Redegeld for nlsociaal.nl
 *
 * Purpose:
 * Generates a 2048-bit RSA keypair for a user in the /private directory
 * Required for signing ActivityPub messages
 */

/**
 * Generates new RSA key pair if not already present for a user
 *
 * @param string $username OSSN username
 */
function fediversebridge_generate_keys($username) {
    // Path to key storage
    $base = ossn_get_userdata('components/FediverseBridge');
    $priv_dir = "{$base}/private";

    // Create /private directory if it doesn't exist
    if (!is_dir($priv_dir)) {
        mkdir($priv_dir, 0755, true);
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG && function_exists('fediversebridge_log')) {
            fediversebridge_log("📂 Created private folder: {$priv_dir}");
        }
    }

    // File paths for private and public key
    $priv_file = "{$priv_dir}/{$username}.pem";
    $pub_file  = "{$priv_dir}/{$username}.pubkey";

    // Skip if keys already exist
    if (file_exists($priv_file) && file_exists($pub_file)) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG && function_exists('fediversebridge_log')) {
            fediversebridge_log("🟢 Keys already exist for {$username}, skipping generation.");
        }
        return;
    }

    // Generate new 2048-bit RSA key
    $privkey = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    // Error handling
    if (!$privkey) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG && function_exists('fediversebridge_log')) {
            fediversebridge_log("❌ Failed to generate private key for {$username}");
        }
        return;
    }

    // Export private key to string
    if (!openssl_pkey_export($privkey, $privout)) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG && function_exists('fediversebridge_log')) {
            fediversebridge_log("❌ Failed to export private key for {$username}");
        }
        return;
    }

    // Extract public key details
    $pubdetails = openssl_pkey_get_details($privkey);
    if (!$pubdetails || !isset($pubdetails['key'])) {
        if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG && function_exists('fediversebridge_log')) {
            fediversebridge_log("❌ Failed to extract public key for {$username}");
        }
        return;
    }

    // Store keys to disk
    file_put_contents($priv_file, $privout);
    file_put_contents($pub_file, $pubdetails['key']);

    // Log result
    if (defined('FEDIVERSEBRIDGE_DEBUG') && FEDIVERSEBRIDGE_DEBUG && function_exists('fediversebridge_log')) {
        fediversebridge_log("✅ generate_keys: Private key saved to {$priv_file}");
        fediversebridge_log("✅ generate_keys: Public key saved to {$pub_file}");
    }
}
