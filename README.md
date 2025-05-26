With your help, i make more cool modules
https://buy.stripe.com/6oE6pS1V2eie0p27ss

Please help me with the VPS.

**
Sources for the Fediverse Integration:
WordPress Fediverse Plugin:
Inspired by WordPress plugins that allow integration with federated networks, providing seamless content sharing.

ActivityPub:
A decentralized social networking protocol used for federating social platforms. It enables servers to share content, posts, and user data in a decentralized manner across various networks (e.g., Mastodon, Pleroma).

WebFinger:
A protocol used to retrieve account information in a decentralized manner. It helps in identifying users across federated networks.

Fediverse Libraries and Documentation:
Various resources like the ActivityPub specification and Fediverse documentation guide the development of decentralized social applications.

Open Source Examples:
There are many open-source implementations that provide a starting point for building Fediverse-compatible platforms, including examples from platforms like Mastodon and Pleroma.

It’s been a fun journey working with these tools and concepts, experimenting with decentralized technologies, and finding creative ways to connect different networks in a privacy-conscious manner. The integration has allowed for an exciting new direction in developing federated social networks.
**

🇳🇱 FediverseBridge voor OSSN – Uitleg
FediverseBridge is een OSSN-module die gebruikers van jouw sociale netwerk (zoals nlsociaal.nl) koppelt aan het bredere Fediverse – het gedecentraliseerde netwerk van Mastodon, Friendica, Pleroma en meer.

✨ Wat doet deze module?
👤 Gebruikers kunnen opt-in kiezen via hun profielinstellingen.

🔐 De module genereert per gebruiker automatisch RSA-sleutels en een Fediverse-identiteit (@gebruikersnaam@jouwdomein.nl).

📝 Elke OSSN-wallpost met een hashtag (#) wordt automatisch doorgestuurd naar de volgers op het Fediverse via het ActivityPub-protocol.

💬 Replies en likes vanaf bijvoorbeeld Mastodon worden ontvangen en getoond in het profiel van de gebruiker.

✅ Een Follow wordt automatisch beantwoord met een Accept, en de actor wordt opgeslagen in een followers.json bestand.

📂 Berichten worden opgeslagen in ossn_data/components/FediverseBridge/outbox/gebruikersnaam/.

🔐 Veilig en decentraal
Gebruikers kunnen zich op elk moment afmelden.

Er is geen centrale afhankelijkheid van externe API's of platforms.

Berichten zijn publiek, maar alleen opt-in gebruikers worden gefedereerd.

🇬🇧 FediverseBridge for OSSN – Overview
FediverseBridge is an OSSN module that connects your social network (e.g. nlsociaal.nl) to the broader Fediverse — the decentralized network of Mastodon, Friendica, Pleroma, and others.

✨ What does this module do?
👤 Users can opt in via their profile settings.

🔐 Upon opt-in, the module generates RSA keys and a Fediverse identity (@username@yourdomain.nl) for the user.

📝 Any OSSN wall post containing a hashtag (#) is automatically published to the user’s Fediverse followers using the ActivityPub protocol.

💬 Replies and likes from platforms like Mastodon are received and displayed in the user’s profile.

✅ Any Follow is responded to with an Accept, and the actor is saved in a followers.json file.

📂 Posts are saved in ossn_data/components/FediverseBridge/outbox/username/.

🔐 Secure and decentralized
Users can opt out at any time.

No dependency on third-party APIs or services.

Posts are public, but only opted-in users are federated.

🌍 Why It Matters
This project bridges OSSN to the larger Fediverse — making nlsociaal.nl a real player in the decentralized web.
It respects user consent, uses open protocols, and doesn't track, spy, or profile.

