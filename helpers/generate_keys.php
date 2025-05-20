<?php
/**
 * helpers/generate_key.php
 * 🇳🇱 Gemaakt door Eric Redegeld voor nlsociaal.nl
 * 🇬🇧 Created by Eric Redegeld for nlsociaal.nl
 *
 * 🛠️ Doel / Purpose:
 * 🇳🇱 Genereer een 2048-bit RSA keypair voor een gebruiker in de /private-map
 * 🇬🇧 Generate a 2048-bit RSA keypair for a user in the /private directory
 *
 * 🔐 Nodig voor het ondertekenen van ActivityPub-berichten
 */

/**
 * 🇳🇱 Genereert een nieuw RSA keypair als deze nog niet bestaan
 * 🇬🇧 Generates new RSA key pair if not already present
 *
 * @param string $username De gebruikersnaam in OSSN
 */
function fediversebridge_generate_keys($username) {
    // 📁 Pad naar opslag van sleutels
    // 📁 Path to key storage
    $base = ossn_get_userdata('components/FediverseBridge');
    $priv_dir = "{$base}/private";

    // 📂 Maak /private map aan indien die ontbreekt
    // 📂 Create /private folder if not exists
    if (!is_dir($priv_dir)) {
        mkdir($priv_dir, 0755, true);
        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("📂 private folder aangemaakt: {$priv_dir}");
        }
    }

    // 📄 Bestandsnamen voor keys
    // 📄 File paths for private and public key
    $priv_file = "{$priv_dir}/{$username}.pem";
    $pub_file  = "{$priv_dir}/{$username}.pubkey";

    // ⛔️ Sleutels bestaan al → overslaan
    // ⛔️ Keys already exist → skip generation
    if (file_exists($priv_file) && file_exists($pub_file)) {
        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("🟢 Keys bestaan al voor {$username}, skipping generation.");
        }
        return;
    }

    // ✅ Genereer 2048-bit RSA private key
    // ✅ Generate 2048-bit RSA private key
    $privkey = openssl_pkey_new([
        'private_key_bits' => 2048,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ]);

    // ❌ Als genereren mislukt: loggen en stoppen
    // ❌ On generation failure: log and exit
    if (!$privkey) {
        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("❌ Fout bij openssl_pkey_new() voor {$username}");
        }
        return;
    }

    // 📤 Exporteer private key naar string
    // 📤 Export private key to string
    if (!openssl_pkey_export($privkey, $privout)) {
        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("❌ Kon private key niet exporteren voor {$username}");
        }
        return;
    }

    // 📥 Haal publieke sleutel op uit gegenereerde resource
    // 📥 Extract public key from generated resource
    $pubdetails = openssl_pkey_get_details($privkey);
    if (!$pubdetails || !isset($pubdetails['key'])) {
        if (function_exists('fediversebridge_log')) {
            fediversebridge_log("❌ Kon public key niet ophalen voor {$username}");
        }
        return;
    }

    // 💾 Sla beide sleutels op in bestanden
    // 💾 Store both keys in separate files
    file_put_contents($priv_file, $privout);
    file_put_contents($pub_file, $pubdetails['key']);

    // 📝 Log success
    if (function_exists('fediversebridge_log')) {
        fediversebridge_log("✅ generate_keys: Private key opgeslagen in {$priv_file}");
        fediversebridge_log("✅ generate_keys: Public key opgeslagen in {$pub_file}");
    }
}
