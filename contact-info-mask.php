<?php
// Elérhetőségek elrejtése és felfedése funkció

function im_contact_info_mask_enqueue_scripts() {
    wp_enqueue_script('im-contact-info-mask', plugin_dir_url(__FILE__) . 'contact-info-mask.js', array('jquery'), '1.0', true);
    wp_enqueue_style('im-contact-info-mask', plugin_dir_url(__FILE__) . 'contact-info-mask.css');
}
add_action('wp_enqueue_scripts', 'im_contact_info_mask_enqueue_scripts');