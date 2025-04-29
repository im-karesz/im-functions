<?php
/**
 * UTM paraméterek tárolása sütiben és hozzáadása a Contact Form 7 emailhez shortcode használatával
 */

// UTM paraméterek tárolása sütiben
function utm_store_in_cookie() {
    if (isset($_GET['utm_source']) || isset($_GET['utm_medium']) || isset($_GET['utm_campaign']) || isset($_GET['utm_term']) || isset($_GET['utm_content'])) {

        $utm_params = array(
            'utm_source'   => isset($_GET['utm_source']) ? sanitize_text_field($_GET['utm_source']) : '',
            'utm_medium'   => isset($_GET['utm_medium']) ? sanitize_text_field($_GET['utm_medium']) : '',
            'utm_campaign' => isset($_GET['utm_campaign']) ? sanitize_text_field($_GET['utm_campaign']) : '',
            'utm_term'     => isset($_GET['utm_term']) ? sanitize_text_field($_GET['utm_term']) : '',
            'utm_content'  => isset($_GET['utm_content']) ? sanitize_text_field($_GET['utm_content']) : '',
        );

        $domain = parse_url(home_url(), PHP_URL_HOST);
        $path = COOKIEPATH;

        foreach ($utm_params as $key => $value) {
            if (!empty($value)) {
                if (headers_sent()) {
                    error_log("A fejlécek már elküldésre kerültek.");
                } else {
                    // Secure=true beállítása, mivel az oldal HTTPS-en fut
                    setcookie($key, $value, time() + 30 * DAY_IN_SECONDS, $path, $domain, true, true); // Secure=True, HttpOnly=True
                    error_log("Süti létrehozva: " . $key . " = " . $value);
                }
            }
        }
    } else {
        error_log("Nincs UTM paraméter a URL-ben");
    }
}
add_action('init', 'utm_store_in_cookie');

// Shortcode létrehozása az UTM sütik megjelenítéséhez (csak a nem üres paramétereket mutatja)
function utm_params_shortcode() {
    $utm_data = '';

    // UTM sütik beolvasása
    if (isset($_COOKIE['utm_source']) || isset($_COOKIE['utm_medium']) || isset($_COOKIE['utm_campaign']) || isset($_COOKIE['utm_term']) || isset($_COOKIE['utm_content'])) {

        // Csak akkor adjuk hozzá a sorokat, ha az érték nem üres
        if (!empty($_COOKIE['utm_source'])) {
            $utm_data .= "UTM Source: " . sanitize_text_field($_COOKIE['utm_source']) . "\n";
        }
        if (!empty($_COOKIE['utm_medium'])) {
            $utm_data .= "UTM Medium: " . sanitize_text_field($_COOKIE['utm_medium']) . "\n";
        }
        if (!empty($_COOKIE['utm_campaign'])) {
            $utm_data .= "UTM Campaign: " . sanitize_text_field($_COOKIE['utm_campaign']) . "\n";
        }
        if (!empty($_COOKIE['utm_term'])) {
            $utm_data .= "UTM Term: " . sanitize_text_field($_COOKIE['utm_term']) . "\n";
        }
        if (!empty($_COOKIE['utm_content'])) {
            $utm_data .= "UTM Content: " . sanitize_text_field($_COOKIE['utm_content']) . "\n";
        }
    } else {
        $utm_data = "Nincsenek UTM paraméterek.";
    }

    // HTML törlés, hogy tisztán szövegként jelenjen meg
    return $utm_data;
}
add_shortcode('utm_params', 'utm_params_shortcode');

// A Contact Form 7 email tartalmának szűrése, hogy a shortcode-ok működjenek
add_filter('wpcf7_mail_components', 'process_utm_shortcode_in_email', 10, 1);
function process_utm_shortcode_in_email($components) {
    // Shortcode-ok feldolgozása az email törzsében
    if (isset($components['body'])) {
        $components['body'] = do_shortcode($components['body']);
    }
    return $components;
}