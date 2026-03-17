<?php
defined('ABSPATH') || exit;

function saasphere_customizer_register($wp_customize) {
    $wp_customize->add_section('saasphere_general', [
        'title'    => __('SaaSphere - Général', 'saasphere'),
        'priority' => 30,
    ]);

    $wp_customize->add_setting('saasphere_company_name', ['default' => 'SaaSphere', 'sanitize_callback' => 'sanitize_text_field']);
    $wp_customize->add_control('saasphere_company_name', [
        'label'   => __('Nom de l\'entreprise', 'saasphere'),
        'section' => 'saasphere_general',
        'type'    => 'text',
    ]);

    $wp_customize->add_setting('saasphere_primary_color', ['default' => '#6366f1', 'sanitize_callback' => 'sanitize_hex_color']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'saasphere_primary_color', [
        'label'   => __('Couleur principale', 'saasphere'),
        'section' => 'saasphere_general',
    ]));

    $wp_customize->add_setting('saasphere_accent_color', ['default' => '#8b5cf6', 'sanitize_callback' => 'sanitize_hex_color']);
    $wp_customize->add_control(new WP_Customize_Color_Control($wp_customize, 'saasphere_accent_color', [
        'label'   => __('Couleur d\'accent', 'saasphere'),
        'section' => 'saasphere_general',
    ]));
}
add_action('customize_register', 'saasphere_customizer_register');

function saasphere_custom_css() {
    $primary = get_theme_mod('saasphere_primary_color', '#6366f1');
    $accent  = get_theme_mod('saasphere_accent_color', '#8b5cf6');
    echo '<style>:root{--ss-primary:' . esc_attr($primary) . ';--ss-accent:' . esc_attr($accent) . '}</style>';
}
add_action('wp_head', 'saasphere_custom_css');
