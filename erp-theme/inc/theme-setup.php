<?php
defined('ABSPATH') || exit;

function saasphere_setup() {
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('custom-logo', [
        'height'      => 40,
        'width'       => 180,
        'flex-height' => true,
        'flex-width'  => true,
    ]);
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('editor-styles');
    add_theme_support('wp-block-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');

    register_nav_menus([
        'primary'   => __('Navigation principale', 'saasphere'),
        'dashboard' => __('Navigation dashboard', 'saasphere'),
        'footer'    => __('Navigation pied de page', 'saasphere'),
    ]);

    load_theme_textdomain('saasphere', SAASPHERE_THEME_DIR . '/languages');
}
add_action('after_setup_theme', 'saasphere_setup');

function saasphere_register_sidebars() {
    register_sidebar([
        'name'          => __('Dashboard Sidebar', 'saasphere'),
        'id'            => 'dashboard-sidebar',
        'before_widget' => '<div id="%1$s" class="ss-widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="ss-widget-title">',
        'after_title'   => '</h3>',
    ]);
}
add_action('widgets_init', 'saasphere_register_sidebars');
