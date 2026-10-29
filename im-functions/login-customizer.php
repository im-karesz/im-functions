<?php
/**
 * Feature Name: IM Login Customizer
 * Description: Személyre szabható WordPress bejelentkezési oldal (Központi opciókkal meghajtva).
 */

if ( ! defined( 'ABSOLUTE_PATH' ) && ! defined( 'ABSPATH' ) ) {
    exit;
}

function im_apply_custom_login_css() {
    $options  = get_option('im_functions_options');
    $bg_img   = !empty($options['login_bg']) ? $options['login_bg'] : '';
    $logo_img = !empty($options['login_logo']) ? $options['login_logo'] : '';
    $color    = !empty($options['login_color']) ? $options['login_color'] : '#ffc107';
    ?>
    <style type="text/css">
        <?php if ( !empty($bg_img) ) : ?>
        body.login {
            background-image: url('<?php echo esc_url($bg_img); ?>') !important;
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
        }
        <?php endif; ?>

        <?php if ( !empty($logo_img) ) : ?>
        #login h1 a, .login h1 a {
            background-image: url('<?php echo esc_url($logo_img); ?>') !important;
            height: 100px;
            width: 320px;
            background-size: contain;
            background-position: center;
            margin-bottom: 20px;
        }
        <?php endif; ?>

        /* Gomb és Checkbox egymás alá rendezése */
        .login form .forgetmenot {
            float: none !important;
            display: block !important;
            margin-bottom: 20px !important;
            padding: 0 !important;
        }

        /* Felesleges Adatvédelmi link elrejtése a tiszta dizájn érdekében */
        .privacy-policy-page-link {
            display:none !important;
        }

        .login form .submit {
            float: none !important;
            display: block !important;
            clear: both !important;
            padding: 0 !important;
        }

        /* Elsődleges gomb (Bejelentkezés) teljes szélességűvé tétele */
        .wp-core-ui .button-primary {
            display: block !important;
            width: 100% !important;
            height: auto !important;
            padding: 10px 15px !important;
            font-size: 15px !important;
            line-height: 1.4 !important;
            background: <?php echo esc_attr($color); ?> !important;
            border-color: <?php echo esc_attr($color); ?> !important;
            text-shadow: none !important;
            box-shadow: none !important;
            color: #fff !important;
            border-radius: 4px !important;
        }

        .wp-core-ui .button-primary:hover,
        .wp-core-ui .button-primary:focus {
            filter: brightness(85%);
            border-color: <?php echo esc_attr($color); ?> !important;
        }

        /* Bejelentkező doboz alatti linkek színei */
        .login #nav a, .login #backtoblog a {
            color: #fff !important;
            text-shadow: 1px 1px 3px rgba(0,0,0,0.8);
        }

        .login #nav a:hover, .login #backtoblog a:hover {
            color: <?php echo esc_attr($color); ?> !important;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'im_apply_custom_login_css');

// Kliens branding finomítások (Logó linkek)
add_filter('login_headerurl', function() { return home_url(); });
add_filter('login_headertext', function() { return get_bloginfo('name'); });
