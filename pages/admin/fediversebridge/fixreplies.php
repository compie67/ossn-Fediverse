<?php
/**
 * Adminpagina – Controleer en herstel ontbrekende replies-mappen
 * Door Eric Redegeld – nlsociaal.nl
 * in progress
 */

if (!ossn_isAdminLoggedin()) {
    redirect();
}

echo "<div class='ossn-admin-container'>";
echo "<div class='ossn-admin-content'>";
echo "<h2>🛠 Fix missing replies/ maps</h2>";

$base_path = ossn_get_userdata("components/FediverseBridge/replies/");
if (!is_dir($base_path)) {
    if (!mkdir($base_path, 0755, true)) {
        echo "<p style='color:red;'>❌ Kon rootmap <code>replies/</code> niet aanmaken.</p>";
        return;
    }
    echo "<p>✅ Rootmap <code>replies/</code> aangemaakt.</p>";
}

$db = ossn_database_instance();
$objects = $db->select([
    'from' => 'ossn_objects',
    'wheres' => [
        "type='user'",
        "subtype='wall'"
    ],
]);

$total = 0;
$created = 0;

if ($objects) {
    foreach ($objects as $obj) {
        $guid = $obj->guid;
        $dir = "{$base_path}{$guid}/";
        $total++;
        if (!is_dir($dir)) {
            if (mkdir($dir, 0755, true)) {
                echo "<p>🆕 Map aangemaakt: <code>{$guid}</code></p>";
                $created++;
            } else {
                echo "<p style='color:red;'>❌ Mislukt: {$guid}</p>";
            }
        } else {
            echo "<p style='color:gray;'>✅ Bestaat al: <code>{$guid}</code></p>";
        }
    }

    echo "<hr><p><strong>{$created}</strong> nieuwe maps aangemaakt van totaal <strong>{$total}</strong> wallposts.</p>";
} else {
    echo "<p>⚠️ Geen wallposts gevonden in de database.</p>";
}

echo "</div></div>";
