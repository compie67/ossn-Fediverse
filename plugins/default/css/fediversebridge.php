<?php
/**
 * plugins/default/css/fediversebridge.php
 * 🇳🇱 Kleine styling voor FediverseBridge opt-in pagina en adminoverzicht
 * 🇬🇧 Basic styling for FediverseBridge opt-in page and admin overview
 *
 * Gemaakt door Eric Redegeld – nlsociaal.nl
 */
?>
<style>
/* 🔐 Gebruiker opt-in blok / User opt-in box */
.fediverse-optin-page {
    border: 1px solid #ddd;
    padding: 15px;
    margin: 10px 0;
    background: #f9f9f9;
    border-radius: 6px;
}

/* Titel van opt-in box / Title inside opt-in box */
.fediverse-optin-page h3 {
    margin-top: 0;
}

/* Formulierruimte onder titel / Spacing for form */
.fediverse-optin-page form {
    margin-top: 10px;
}

/* 💼 Admin gebruikerslijst / Admin list of opted-in users */
.fediverse-optin-admin-list {
    list-style-type: disc;
    padding-left: 20px;
}

/* Afstand tussen gebruikersitems / Spacing between list items */
.fediverse-optin-admin-list li {
    margin-bottom: 6px;
}

/* Links in adminlijst / Links in admin list */
.fediverse-optin-admin-list a {
    text-decoration: none;
    color: #0366d6;
}

/* Hovereffect op links / Hover effect on links */
.fediverse-optin-admin-list a:hover {
    text-decoration: underline;
}
</style>
