<?php
/**
 * plugins/default/fediversebridge/optin.php
 * Profielpagina Fediverse – inclusief opt-in, reacties en volgers
 * Door Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page();
}

require_once(ossn_route()->com . 'FediverseBridge/helpers/note.php');

$user     = $params['user'];
$username = $user->username;
$viewer   = ossn_loggedin_user();

if (!$viewer || ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin())) {
    ossn_error_page();
}

$base_path      = ossn_get_userdata("components/FediverseBridge");
$optin_file     = "{$base_path}/optin/{$username}.json";
$private_file   = "{$base_path}/private/{$username}.pem";
$public_file    = "{$base_path}/private/{$username}.pubkey";
$outbox_dir     = "{$base_path}/outbox/{$username}/";
$inbox_dir      = "{$base_path}/inbox/{$username}/";
$followers_file = "{$base_path}/followers/{$username}.json";
$actor_url      = ossn_site_url("fediverse/actor/{$username}");
$domain         = parse_url(ossn_site_url(), PHP_URL_HOST);
$optedin        = file_exists($optin_file);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wilt_optin = input('fediverse_optin') === 'yes';

    if ($wilt_optin && !$optedin) {
        foreach ([dirname($optin_file), dirname($private_file), $outbox_dir] as $path) {
            if (!is_dir($path)) mkdir($path, 0755, true);
        }

        $res = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($res, $privout);
        file_put_contents($private_file, $privout);
        $pubout = openssl_pkey_get_details($res);
        file_put_contents($public_file, $pubout['key']);

        file_put_contents($optin_file, json_encode([
            'enabled' => true,
            'actor_url' => $actor_url,
        ]));

        $now = date('c');
        $note = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => ossn_site_url("fediverse/outbox/{$username}#note-first"),
            'type' => 'Note',
            'attributedTo' => $actor_url,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'content' => sprintf(ossn_print('fediversebridge:note:first'), $username, $domain),
            'published' => $now,
        ];

        $activity = [
            '@context' => 'https://www.w3.org/ns/activitystreams',
            'id' => ossn_site_url("fediverse/outbox/{$username}#activity-first"),
            'type' => 'Create',
            'actor' => $actor_url,
            'published' => $now,
            'to' => ['https://www.w3.org/ns/activitystreams#Public'],
            'object' => $note,
        ];

        file_put_contents("{$outbox_dir}/first.json", json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        ossn_trigger_message(sprintf(ossn_print('fediversebridge:optin:profile:success'), $username), 'success');
    } elseif (!$wilt_optin && $optedin) {
        if (file_exists($optin_file)) unlink($optin_file);
        if (file_exists($private_file)) unlink($private_file);
        if (file_exists($public_file)) unlink($public_file);
        if (is_dir($outbox_dir)) {
            array_map('unlink', glob("{$outbox_dir}/*.json"));
            rmdir($outbox_dir);
        }
        ossn_trigger_message(sprintf(ossn_print('fediversebridge:optin:profile:error'), $username), 'error');
    }

    redirect(REF);
}
?>

<div class="ossn-profile-extra-menu fediverse-optin-page">
    <h3><?php echo ossn_print('fediversebridge:menu:optin'); ?></h3>

    <?php if ($optedin): ?>
        <p class="alert alert-success">
            <?php echo ossn_print('fediversebridge:optin:profile:enabled'); ?>
        </p>
    <?php else: ?>
        <p class="alert alert-danger">
            <?php echo ossn_print('fediversebridge:optin:profile:disabled'); ?>
        </p>
    <?php endif; ?>

    <form method="post">
        <div>
            <input type="checkbox" id="fediverse_optin" name="fediverse_optin" value="yes" <?php if ($optedin) echo 'checked'; ?>>
            <label for="fediverse_optin"><?php echo ossn_print('fediversebridge:optin:profile:checkbox'); ?></label>
        </div>
        <br>
        <input type="submit" class="btn btn-primary" value="<?php echo ossn_print('fediversebridge:optin:profile:save'); ?>" />
    </form>

    <pre class="bg-light p-2 mt-3">
<?php echo ossn_print('fediversebridge:debug:title'); ?>
<?php printf(ossn_print('fediversebridge:debug:username'), $username); ?>
<?php printf(ossn_print('fediversebridge:debug:privatekey'), file_exists($private_file) ? 'OK' : 'MISSING'); ?>
<?php printf(ossn_print('fediversebridge:debug:publickey'), file_exists($public_file) ? 'OK' : 'MISSING'); ?>
<?php printf(ossn_print('fediversebridge:debug:outbox'), is_dir($outbox_dir) ? 'OK' : 'MISSING'); ?>
<?php printf(ossn_print('fediversebridge:debug:optinfile'), file_exists($optin_file) ? 'OK' : 'MISSING'); ?>
<?php printf(ossn_print('fediversebridge:debug:userguid'), $user->guid); ?>
    </pre>

    <div class="bg-info-subtle p-3 border mt-4 rounded">
        <h4>🔗 <?php echo ossn_print('fediversebridge:optin:profile:findable'); ?></h4>
        <p><strong>@<?php echo $username; ?>@<?php echo $domain; ?></strong></p>
        <p><a href="<?php echo $actor_url; ?>" target="_blank"><?php echo $actor_url; ?></a></p>
        <p>🔎 WebFinger:<br />
            <a href="https://<?php echo $domain; ?>/.well-known/webfinger?resource=acct:<?php echo $username; ?>@<?php echo $domain; ?>" target="_blank">
                .well-known/webfinger?resource=acct:<?php echo $username; ?>@<?php echo $domain; ?>
            </a>
        </p>
    </div>

    <?php
    $likes = [];
    $announces = [];

    if (is_dir($inbox_dir)) {
        foreach (glob("{$inbox_dir}/*.json") as $file) {
            $json = json_decode(file_get_contents($file), true);
            if (!is_array($json)) continue;
            if (($json['type'] ?? '') === 'Like') {
                $likes[] = $json['object'] ?? '';
            }
            if (($json['type'] ?? '') === 'Announce') {
                $announces[] = $json['object'] ?? '';
            }
        }
    }

    if (!empty($likes)) {
        echo "<h4>" . ossn_print('fediversebridge:likes:title') . "</h4><ul>";
        foreach ($likes as $object) {
            $guid = fediversebridge_extract_guid_from_note_url($object);
            $note = htmlspecialchars($object);
            $link = fediversebridge_note_to_public_post_url($guid);
            echo "<li>❤️ <a href='{$note}' target='_blank'>{$note}</a> → <a href='{$link}' target='_blank'>" . ossn_print('fediversebridge:viewon', [$domain]) . "</a></li>";
        }
        echo "</ul>";
    }

    if (!empty($announces)) {
        echo "<h4>" . ossn_print('fediversebridge:announces:title') . "</h4><ul>";
        foreach ($announces as $object) {
            $guid = fediversebridge_extract_guid_from_note_url($object);
            $note = htmlspecialchars($object);
            $link = fediversebridge_note_to_public_post_url($guid);
            echo "<li>🔁 <a href='{$note}' target='_blank'>{$note}</a> → <a href='{$link}' target='_blank'>" . ossn_print('fediversebridge:viewon', [$domain]) . "</a></li>";
        }
        echo "</ul>";
    }

    if (file_exists($followers_file)) {
        $followers = json_decode(file_get_contents($followers_file), true);
        if (is_array($followers) && !empty($followers)) {
            echo "<h4>" . ossn_print('fediversebridge:followers:title') . "</h4><ul class='bg-light p-2 border'>";
            foreach ($followers as $f) {
                $safe = htmlspecialchars($f);
                echo "<li><a href='{$safe}' target='_blank'>{$safe}</a></li>";
            }
            echo "</ul>";
        }
    }
    ?>
</div>
