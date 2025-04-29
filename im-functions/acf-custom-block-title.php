<?php
// ACF Custom Block Title funkció
$options = get_option('im_functions_options');

if (isset($options['acf_custom_block_title']) && $options['acf_custom_block_title']) {
    add_filter('acf/fields/flexible_content/layout_title/name=oldal_szekciok', 'acf_custom_block_title', 10, 4);
}

function acf_custom_block_title($title, $field, $layout, $i) {
    $fields = ['blokk_megnevezese', 'cim', 'blokk', 'felirat', 'azonosito', 'mezo_nev', 'szoveg_blokk', 'kiemelt_szoveg'];
    $block_title = '';

    foreach ($fields as $field_name) {
        $value = ($field_name == 'blokk' ? get_sub_field($field_name)['cim'] : get_sub_field($field_name));
        if (!empty($value)) {
            $block_title = $value;
            break;
        }
    }

    if (in_array($field_name, ['szoveg_blokk', 'kiemelt_szoveg'])) {
        $block_title = mb_strimwidth(wp_strip_all_tags($block_title), 0, 30, '...');
    }

    $block_title = wp_strip_all_tags($block_title);
    $title = '<strong>' . esc_html($title) . '</strong>';
    if ($block_title) {
        $title .= ' - <i>' . esc_html($block_title) . '</i>';
    }

    return $title;
}
?>
