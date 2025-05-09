# ossn-Fediverse
connect OSSN with Fediverse to post public message overthere

90% done, post the code here for feedback. and explain the working

live!!!! and testing at shadow.nlsociaal.nl with user testsociaal and admin

✨ FediverseBridge for OSSN – From Friction to Federation
FediverseBridge is a component built for Open Source Social Network (OSSN) that connects user posts containing hashtags (#) directly to the Fediverse using the ActivityPub protocol.

🛠 The Idea
The core concept:

When a user posts a message with a hashtag (e.g. #fediverse), that post should also be shared with their followers on Mastodon, Friendica, etc.

No bots. No algorithms. Just real people, real reach.

🔁 The Road to Success
😤 The Struggles
ActivityPub is complex — especially the cryptographic signing of HTTP headers.

Mastodon has strict validations — posts must be signed per user, not globally.

Initial attempts used a central admin key, but that led to HTTP 401 Unauthorized responses.

Webfinger discovery had to be precise — even a missing trailing slash or mismatched host blocked federation.

Sloppy inbox URLs (like inbox//) caused silent failures.

Accepting followers wasn't enough — the correct Accept activity with proper signature had to be returned.

💥 The Breakthroughs
Per-user keypair generation: When users opt in (via checkbox at registration or later), their own private.pem and public.pubkey are generated.

Outbox management: Posts with hashtags are saved as JSON ActivityPub objects in a per-user outbox.

Inbox handling: Follows from Mastodon are accepted and stored in a followers.json list.

Post delivery: Each wall post triggers a Create activity to all known inboxes using per-user signatures.

Test success: Posts now arrive in Mastodon timelines when the user is followed — fully decentralized.

✅ The Current Flow
User signs up, optionally checks a box “also publish to Fediverse”.

If enabled:

Keypair is created

User is discoverable via Webfinger

User writes a post with a hashtag →
#nlsociaal is awesome!

Post is:

Stored locally

Encoded to ActivityPub format

Signed with the user’s private key

Sent to all known followers’ inboxes

🚧 Still To Come
Admin UI to see Fediverse logs and user keys

Allow users to enable/disable Fediverse sharing post-signup

Handle replies, boosts, deletes

🌍 Why It Matters
This project bridges OSSN to the larger Fediverse — making nlsociaal.nl a real player in the decentralized web.
It respects user consent, uses open protocols, and doesn't track, spy, or profile.

