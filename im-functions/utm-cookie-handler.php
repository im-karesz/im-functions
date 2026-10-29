<?php
/**
 * UTM, GCLID és User Journey (Bejárt útvonal) tárolása sessionStorage-ban és sütikben
 * Instant Head Execution & CookieScript kompatibilis kiadás
 */

// Kliens oldali (JS) tárolás futtatása az oldal legtetején (wp_head, 1-es prioritás)
add_action('wp_head', function() {
    if (is_admin()) return;
    ?>
    <script id="im-user-journey-tracker">
    (function() {
        try {
            var utmKeys = ['utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid'];
            var urlParams = new URLSearchParams(window.location.search);

            // 1. MENTÉS SESSIONSTORAGE-BA (Azonnal az URL-ből)
            utmKeys.forEach(function(key) {
                if (urlParams.has(key)) {
                    var val = urlParams.get(key);
                    if (val) {
                        sessionStorage.setItem('im_' + key, val);
                    }
                }
            });

            // 2. USER JOURNEY RÖGZÍTÉSE
            var currentPath = window.location.pathname;
            var pathHistory = [];
            try {
                pathHistory = JSON.parse(sessionStorage.getItem('im_user_journey') || '[]');
            } catch(e) { pathHistory = []; }

            if (!Array.isArray(pathHistory)) pathHistory = [];

            if (pathHistory.length === 0 || pathHistory[pathHistory.length - 1] !== currentPath) {
                pathHistory.push(currentPath);
                if (pathHistory.length > 10) pathHistory.shift();
                sessionStorage.setItem('im_user_journey', JSON.stringify(pathHistory));
            }

            // 3. AZONNALI SÜTI KIÍRÁS (Azonnal lefut a <head>-ben, 0 másodperc csúszással)
            function writeCookiesNow() {
                try {
                    var historyArr = [];
                    try { historyArr = JSON.parse(sessionStorage.getItem('im_user_journey') || '[]'); } catch(e){}
                    var formattedJourney = Array.isArray(historyArr) ? historyArr.join(' -> ') : '';

                    if (formattedJourney) {
                        document.cookie = "im_user_journey=" + encodeURIComponent(formattedJourney) + "; path=/; max-age=86400; SameSite=Lax";
                    }

                    utmKeys.forEach(function(key) {
                        var storedVal = sessionStorage.getItem('im_' + key);
                        if (storedVal) {
                            document.cookie = "im_" + key + "=" + encodeURIComponent(storedVal) + "; path=/; max-age=86400; SameSite=Lax";
                        }
                    });
                } catch(e) {}
            }

            // Azonnal kiírjuk a sütiket a lapbetöltés 0. pillanatában
            writeCookiesNow();

            // 4. FORM MEZŐK KITÖLTÉSE ÉS ESEMÉNYEK (DOMReady után)
            function syncAll() {
                writeCookiesNow();
                try {
                    var historyArr = [];
                    try { historyArr = JSON.parse(sessionStorage.getItem('im_user_journey') || '[]'); } catch(e){}
                    var formattedJourney = Array.isArray(historyArr) ? historyArr.join(' -> ') : '';

                    var journeyTargets = document.querySelectorAll('#user_journey, #utvonal_elso, #utvonal_masodik, input[name="user_journey"], input[name="fields[utvonal_elso]"], input[name="fields[utvonal_masodik]"], .bejart-utvonal-field');
                    journeyTargets.forEach(function(input) { input.value = formattedJourney; });

                    utmKeys.forEach(function(key) {
                        var storedVal = sessionStorage.getItem('im_' + key);
                        if (storedVal) {
                            var utmTargets = document.querySelectorAll('#' + key + ', input[name="' + key + '"], input[name="fields[' + key + ']"]');
                            utmTargets.forEach(function(input) { input.value = storedVal; });
                        }
                    });
                } catch(e) {}
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', syncAll);
            } else {
                syncAll();
            }

            // Biztonsági hálók kattintásnál és form beküldésnél
            document.addEventListener('click', function() { setTimeout(writeCookiesNow, 100); }, true);
            document.addEventListener('submit', syncAll, true);
            document.addEventListener('wpcf7beforesubmit', syncAll, true);

        } catch(globalErr) {
            console.error('IM Tracking Error:', globalErr);
        }
    })();
    </script>
    <?php
}, 1);

// Shortcode: UTM paraméterek megjelenítése
function utm_params_shortcode() {
    $utm_keys = array('utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content');
    $utm_data = '';

    foreach ($utm_keys as $key) {
        $val = '';
        if (isset($_COOKIE['im_' . $key])) {
            $val = sanitize_text_field(urldecode($_COOKIE['im_' . $key]));
        } elseif (isset($_COOKIE[$key])) {
            $val = sanitize_text_field(urldecode($_COOKIE[$key]));
        }

        if (!empty($val)) {
            $label = strtoupper(str_replace('_', ' ', $key));
            $utm_data .= $label . ": " . $val . "\n";
        }
    }

    return !empty($utm_data) ? $utm_data : "Nincsenek UTM paraméterek.";
}
add_shortcode('utm_params', 'utm_params_shortcode');

// Shortcode: GCLID megjelenítése külön
function gclid_param_shortcode() {
    $gclid_val = '';
    if (isset($_COOKIE['im_gclid'])) {
        $gclid_val = sanitize_text_field(urldecode($_COOKIE['im_gclid']));
    } elseif (isset($_COOKIE['gclid'])) {
        $gclid_val = sanitize_text_field(urldecode($_COOKIE['gclid']));
    }

    return !empty($gclid_val) ? "GCLID: " . $gclid_val : "Nincs GCLID.";
}
add_shortcode('gclid_param', 'gclid_param_shortcode');

// Shortcode: User Journey (Bejárt útvonal) megjelenítése
function user_journey_shortcode() {
    if (isset($_COOKIE['im_user_journey']) && !empty($_COOKIE['im_user_journey'])) {
        return sanitize_text_field(urldecode($_COOKIE['im_user_journey']));
    }
    return "Közvetlen látogatás / Nincs előzmény";
}
add_shortcode('user_journey', 'user_journey_shortcode');

// CF7 Email Shortcode támogatás
add_filter('wpcf7_mail_components', 'process_utm_shortcode_in_email', 10, 1);
function process_utm_shortcode_in_email($components) {
    if (isset($components['body'])) {
        $components['body'] = do_shortcode($components['body']);
    }
    return $components;
}
