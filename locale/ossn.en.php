<?php
/**
 * OSSN Component: FediverseBridge
 * English language file (cleaned, no emojis)
 * By Eric Redegeld – nlsociaal.nl
 */

ossn_register_languages('en', array(
    // Admin menu
    'fediversebridge:optinusers' => 'Fediverse Opt-in Users',
    'fediversebridge:adminmenu' => 'Fediverse Opt-in Users',
    'fediversebridge:admin:optinusers:title' => 'Fediverse Opt-in Users',
    'fediversebridge:admin:optinusers:nousers' => 'No users have opted in yet.',
    'fediversebridge:admin:optinusers:view' => 'View Profile',

    // Profile page opt-in
    'fediversebridge:menu:optin' => 'Fediverse',
    'fediversebridge:optin:profile:title' => 'Fediverse Opt-in',
    'fediversebridge:optin:profile:enabled' => 'You are currently participating in the Fediverse.',
    'fediversebridge:optin:profile:disabled' => 'You have disabled Fediverse integration.',
    'fediversebridge:optin:profile:checkbox' => 'I want to participate in the Fediverse',
    'fediversebridge:optin:profile:save' => 'Save',
    'fediversebridge:optin:profile:sharetip' => 'Share this address so others can follow you via Mastodon or other Fediverse platforms.',

    // Buttons
    'fediversebridge:optin:profile:enablebtn' => 'Enable',
    'fediversebridge:optin:profile:disablebtn' => 'Disable',
    'fediversebridge:optin:block:btn' => 'Block',
    'fediversebridge:optin:block:placeholder' => 'actor URI (e.g., https://...)',
    'fediversebridge:optin:block:title' => 'Block a specific actor',
    'fediversebridge:optin:block:success' => 'Blocked actor: %s',

    // Feedback messages
    'fediversebridge:optin:profile:success' => 'Fediverse opt-in enabled for %s.',
    'fediversebridge:optin:profile:error' => 'Fediverse opt-in disabled for %s.',
    'fediversebridge:nousers' => 'No users have enabled Fediverse opt-in.',

    // Interactions
    'fediversebridge:like:by' => 'Like by %s on %s (%s)',
    'fediversebridge:announce:by' => 'Boost by %s of <a href="%s" target="_blank">%s</a> (%s)',
    'fediversebridge:replies:title' => 'Replies Received',
    'fediversebridge:reply:by' => 'Reply by %s (%s)',
    'fediversebridge:reply:inreplyto' => 'In reply to',
    'fediversebridge:likes:title' => 'Likes Received',
    'fediversebridge:announces:title' => 'Boosts (Announce)',
    'fediversebridge:ownreplies:title' => 'Replies to Your Posts',
    'fediversebridge:followers:title' => 'Followers',

    // Thread UI
    'fediversebridge:thread:title' => 'Thread for post %s',
    'fediversebridge:thread:collapse' => 'Collapse thread',
    'fediversebridge:thread:expand' => 'Expand thread',

    // Debug info
    'fediversebridge:debug:title' => '[DEBUG]',
    'fediversebridge:debug:username' => 'User: %s',
    'fediversebridge:debug:privatekey' => 'Private key: %s',
    'fediversebridge:debug:publickey' => 'Public key: %s',
    'fediversebridge:debug:outbox' => 'Outbox directory: %s',
    'fediversebridge:debug:optinfile' => 'Opt-in JSON: %s',
    'fediversebridge:debug:userguid' => 'User GUID: %s',

    // Errors
    'fediversebridge:error:usernotfound' => 'User not found.',
    'fediversebridge:error:pageinvalid' => 'Invalid Fediverse page.',

    // Installation / activation
    'fediversebridge:enable:log:dir:created' => 'Created folder: %s',
    'fediversebridge:enable:log:dir:failed' => 'Failed to create folder: %s',
    'fediversebridge:enable:log:key:priv:created' => 'Private key created: %s.pem',
    'fediversebridge:enable:log:key:pub:created' => 'Public key created: %s.pubkey',
    'fediversebridge:enable:log:key:pub:failed' => 'Warning: could not extract public key for %s',
    'fediversebridge:enable:log:key:gen:failed' => 'Failed to generate OpenSSL key for %s',
    'fediversebridge:enable:log:optin:created' => 'Opt-in file created for %s',
    'fediversebridge:enable:log:outbox:test' => 'Test message saved to: %s',
    'fediversebridge:enable:log:install:done' => 'INSTALLATION: FediverseBridge successfully enabled',

    // Inbox logs
    'fediversebridge:inbox:error:nouser' => 'No username provided',
    'fediversebridge:inbox:error:method' => 'Only POST requests are allowed',
    'fediversebridge:inbox:error:contenttype' => 'Invalid Content-Type: %s',
    'fediversebridge:inbox:error:body' => 'Empty or invalid body',
    'fediversebridge:inbox:error:json' => 'Could not parse JSON',
    'fediversebridge:inbox:error:signature' => 'Invalid signature for %s',
    'fediversebridge:inbox:ignored' => 'Actor %s is not a known follower of %s, message ignored.',
    'fediversebridge:inbox:received' => 'INBOX received for %s | Type: %s',
    'fediversebridge:inbox:stored' => 'Message stored at %s',
    'fediversebridge:inbox:like' => 'Like received from %s on %s',
    'fediversebridge:inbox:announce' => 'Announce received from %s on %s',
    'fediversebridge:inbox:create' => 'Create (Note) received from %s',
    'fediversebridge:inbox:create:reply' => 'Reply stored for post %s in %s',
    'fediversebridge:inbox:create:noguid' => 'No GUID found in inReplyTo: %s',
    'fediversebridge:inbox:create:skip' => 'Create is not a reply to a local post, skipped.',
    'fediversebridge:inbox:follow' => 'New follower: %s',
    'fediversebridge:inbox:follow:added' => 'Follower added to %s',
    'fediversebridge:inbox:undo' => 'Undo %s by %s for %s',

    // Federated Note (note.php)
    'fediversebridge:note:log:visit' => 'note.php visited: username=%s, guid=%s',
    'fediversebridge:note:error:invalid' => 'Invalid request format',
    'fediversebridge:note:error:user' => 'User not found',
    'fediversebridge:note:error:post' => 'Post not found',
    'fediversebridge:note:error:mismatch' => 'Post does not belong to this user',

    // Followers endpoint
    'fediversebridge:followers:error:missing' => 'Username missing',
    'fediversebridge:followers:error:notfound' => 'User not found',
    'fediversebridge:followers:log:invalidjson' => 'followers.json for %s is invalid',
    'fediversebridge:log:nofollowersfile' => 'No followers.json for %s, using fallback inboxes.',
    'fediversebridge:log:nooptinfile' => 'No opt-in file for %s, post will not be federated.',
    'fediversebridge:log:invalidfollowersfile' => 'Invalid or corrupted followers.json for %s.',

    // Outbox
    'fediversebridge:outbox:error:missing' => 'Username missing',
    'fediversebridge:outbox:error:notfound' => 'Outbox not found',

    // Avatar proxy
    'fediversebridge:avatar:error:badrequest' => 'Invalid request',
    'fediversebridge:avatar:error:missing' => 'Missing GUID or filename',
    'fediversebridge:avatar:error:notfound' => 'Avatar not found',
    'fediversebridge:avatar:error:missing_data' => 'Invalid avatar request: missing data.',
    'fediversebridge:avatar:error:notfound_user' => 'No avatar found for this user.',

    // Media proxy
    'fediversebridge:proxy:error:missing' => 'Missing GUID or filename',
    'fediversebridge:proxy:error:invalidobj' => 'Object not found or invalid',
    'fediversebridge:proxy:error:filenotfound' => 'File not found',
    'fediversebridge:proxy:log:show' => 'Serving %s (%s, %s bytes) from object %s',

    // Actor profile errors
    'fediversebridge:actor:error:missing' => 'Username missing',
    'fediversebridge:actor:error:notfound' => 'User not found',
    'fediversebridge:actor:error:nopubkey' => 'Public key missing',

    // Handler
    'fediversebridge:optin:profile:findable' => 'Discoverable in the Fediverse',
    'fediversebridge:profile:header' => 'Fediverse Handler Info',
    'fediversebridge:profile:actorurl' => 'Actor URL',
    'fediversebridge:profile:webfinger' => 'WebFinger',

    // Optin preview tool
    'fediversebridge:check:title' => 'Inspect external Fediverse post',
    'fediversebridge:check:btn' => 'Inspect URL',
    'fediversebridge:check:trying' => 'Trying to retrieve ActivityPub data...',
    'fediversebridge:check:fail' => 'Failed to retrieve the URL.',
    'fediversebridge:check:invalidjson' => 'Could not parse valid JSON.',
    'fediversebridge:check:success' => 'Post successfully retrieved!',

    // Signing logs
    'fediversebridge:log:key:missing' => 'Private key not found for %s: %s',
    'fediversebridge:log:inbox:invalid' => 'Invalid inbox URL: %s',
    'fediversebridge:log:openssl:loadfail' => 'OpenSSL failed to load key for %s',
    'fediversebridge:log:openssl:signfail' => 'Signing failed for %s. OpenSSL error: %s',
    'fediversebridge:log:accept:start' => 'Sending Accept activity to %s for %s',
    'fediversebridge:log:accept:headersfail' => 'Failed to create headers for Accept to %s',
    'fediversebridge:log:accept:curlfail' => 'cURL error sending Accept to %s: %s',
    'fediversebridge:log:accept:success' => 'Accept sent to %s. HTTP status: %s. Response: %s',

    // Actor messages
    'fediversebridge:actor:error:missing_url' => 'Username missing in the URL.',
    'fediversebridge:actor:error:notfound_user' => 'User not found.',
    'fediversebridge:actor:error:nopubkey_available' => 'No public key available.',
    'fediversebridge:actor:summary' => 'User on nlsociaal.nl (@%s)',
));
