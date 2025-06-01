<?php
/**
 * plugins/default/fediversebridge/optin.php
 * Fediverse opt-in profile page (followers, likes, reposts)
 * Author: Eric Redegeld – nlsociaal.nl
 */

if (!ossn_isLoggedIn()) {
    ossn_error_page();
}

$user     = $params['user'];
$username = $user->username;
$viewer   = ossn_loggedin_user();

// Only allow profile owner or admin
if (!$viewer || ($viewer->guid !== $user->guid && !ossn_isAdminLoggedin())) {
    ossn_error_page();
}

// File paths
$base_path      = ossn_get_userdata("components/FediverseBridge");
$optin_file     = "{$base_path}/optin/{$username}.json";
$private_file   = "{$base_path}/private/{$username}.pem";
$public_file    = "{$base_path}/private/{$username}.pubkey";
$outbox_dir     = "{$base_path}/outbox/{$username}/";
$inbox_dir      = "{$base_path}/inbox/{$username}/";
$followers_file = "{$base_path}/followers/{$username}.json";
$domain         = parse_url(ossn_site_url(), PHP_URL_HOST);
$actor_url      = ossn_site_url("fediverse/actor/{$username}");
$optedin        = file_exists($optin_file);

// Handle opt-in form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $wants_optin = input('fediverse_optin') === 'yes';

    if ($wants_optin && !$optedin) {
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
            '@context'     => 'https://www.w3.org/ns/activitystreams',
            'id'           => ossn_site_url("fediverse/outbox/{$username}#note-first"),
            'type'         => 'Note',
            'attributedTo' => $actor_url,
            'to'           => ['https://www.w3.org/ns/activitystreams#Public'],
            'content'      => "Hello Fediverse! I am {$username} on {$domain}.",
            'published'    => $now,
        ];

        $activity = [
            '@context'   => 'https://www.w3.org/ns/activitystreams',
            'id'         => ossn_site_url("fediverse/outbox/{$username}#activity-first"),
            'type'       => 'Create',
            'actor'      => $actor_url,
            'published'  => $now,
            'to'         => ['https://www.w3.org/ns/activitystreams#Public'],
            'object'     => $note
        ];

        file_put_contents("{$outbox_dir}/first.json", json_encode($activity, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        ossn_trigger_message("Fediverse participation enabled for {$username}", 'success');
    } elseif (!$wants_optin && $optedin) {
        @unlink($optin_file);
        @unlink($private_file);
        @unlink($public_file);
        if (is_dir($outbox_dir)) {
            array_map('unlink', glob("{$outbox_dir}/*.json"));
            @rmdir($outbox_dir);
        }
        ossn_trigger_message("Fediverse participation disabled", 'error');
    }

    redirect(REF);
}

// Followers
$followers = [];
if (file_exists($followers_file)) {
    $followers = json_decode(file_get_contents($followers_file), true) ?: [];
}

// Likes & reposts (announces)
$likes = [];
$announces = [];
$replies = []; // Placeholder

if (is_dir($inbox_dir)) {
    foreach (glob("{$inbox_dir}/*.json") as $file) {
        $json = json_decode(file_get_contents($file), true);
        if ($json['type'] === 'Like') {
            $likes[] = $json;
        } elseif ($json['type'] === 'Announce') {
            $announces[] = $json;
        }
        // To enable replies, uncomment and improve below:
        // elseif ($json['type'] === 'Create' && isset($json['object']['inReplyTo'])) {
        //     $replies[] = [
        //         'actor'   => $json['actor'],
        //         'content' => $json['object']['content'] ?? '',
        //         'object'  => $json['object']['inReplyTo'],
        //     ];
        // }
    }
}
?>

<div class="ossn-profile-extra-menu fediverse-optin-page">
    <h3>Fediverse</h3>

    <?php if ($optedin): ?>
        <p class="ossn-message-success">You are currently participating in the Fediverse.</p>
    <?php else: ?>
        <p class="ossn-message-error">You are not participating in the Fediverse.</p>
    <?php endif; ?>

    <form method="post">
        <label>
            <input type="checkbox" name="fediverse_optin" value="yes" <?php if ($optedin) echo 'checked'; ?> />
            Enable Fediverse participation
        </label><br><br>
        <input type="submit" class="btn btn-primary" value="Save" />
    </form>

    <?php if ($optedin): ?>
        <div style="margin-top: 25px; background: #f0f8ff; padding: 15px; border: 1px solid #a1c4e7; border-radius: 5px;">
            <h4>Your Fediverse Identity</h4>
            <p><strong>@<?php echo $username . '@' . $domain; ?></strong></p>
            <p>You can be followed via any Fediverse platform.</p>
        </div>

        <h4 style="margin-top: 25px;">Followers</h4>
        <?php if ($followers): ?>
            <ul>
                <?php foreach ($followers as $follower): ?>
                    <li><a href="<?php echo htmlspecialchars($follower); ?>" target="_blank"><?php echo htmlspecialchars($follower); ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No followers yet.</p>
        <?php endif; ?>

        <h4 style="margin-top: 25px;">Likes</h4>
        <?php if ($likes): ?>
            <ul>
                <?php foreach ($likes as $like): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($like['actor']); ?></strong> liked 
                        <a href="<?php echo htmlspecialchars($like['object']); ?>" target="_blank"><?php echo htmlspecialchars($like['object']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No likes yet.</p>
        <?php endif; ?>

        <h4 style="margin-top: 25px;">Reposts</h4>
        <?php if ($announces): ?>
            <ul>
                <?php foreach ($announces as $announce): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($announce['actor']); ?></strong> reposted 
                        <a href="<?php echo htmlspecialchars($announce['object']); ?>" target="_blank"><?php echo htmlspecialchars($announce['object']); ?></a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No reposts yet.</p>
        <?php endif; ?>

        <!--
        <h4 style="margin-top: 25px;">Replies</h4>
        <?php if ($replies): ?>
            <ul>
                <?php foreach ($replies as $reply): ?>
                    <li>
                        <strong><?php echo htmlspecialchars($reply['actor']); ?></strong>:<br/>
                        <?php echo nl2br(htmlspecialchars($reply['content'])); ?><br/>
                        <a href="<?php echo htmlspecialchars($reply['object']); ?>" target="_blank">View original post</a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No replies yet.</p>
        <?php endif; ?>
        -->
    <?php endif; ?>
</div>
