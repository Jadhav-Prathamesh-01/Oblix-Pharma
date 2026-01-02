<?php
/**
 * Oblix Health Theme Functions
 */

// Theme Setup
function oblix_theme_setup() {
    // Add theme support
    add_theme_support('title-tag');
    add_theme_support('post-thumbnails');
    add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption'));
}
add_action('after_setup_theme', 'oblix_theme_setup');

// Enqueue Styles and Scripts
function oblix_enqueue_assets() {
    // Add Sen font from Google Fonts
    wp_enqueue_style('sen-font', 'https://fonts.googleapis.com/css2?family=Sen:wght@400;600;700;800&display=swap', array(), null);
    
    // Main stylesheet
    wp_enqueue_style('oblix-style', get_stylesheet_uri(), array(), '1.0');
    
    // Custom JavaScript for tabs
    wp_enqueue_script('oblix-tabs', get_template_directory_uri() . '/js/tabs.js', array(), '1.0', true);
}
add_action('wp_enqueue_scripts', 'oblix_enqueue_assets');
?>
