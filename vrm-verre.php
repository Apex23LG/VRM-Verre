<?php
/**
 * Plugin Name: VRM Verre
 * Description: Plugin per l'integrazione delle API VRM su Wordpress
 * Author: Sebyone
 * Version: 1.0
 */

 if (!defined('ABSPATH')) exit; 

 // file api e shortcode 
 require_once plugin_dir_path(__FILE__) . 'api.php';
 require_once plugin_dir_path(__FILE__) . 'includes/shortcodes.php';
 
 // script e stili
 function enqueue_proxy_scripts() {
    if (has_shortcode(get_post()->post_content, 'proxy_auth')) {
        wp_enqueue_script('proxy-auth-js', plugin_dir_url(__FILE__) . 'assets/auth.js', array('jquery'), null, true);
        wp_localize_script('proxy-auth-js', 'proxyAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    if (has_shortcode(get_post()->post_content, 'proxy_dashboard')) {
        wp_enqueue_script('proxy-dashboard-js', plugin_dir_url(__FILE__) . 'assets/dashboard.js', array('jquery'), null, true);
        wp_localize_script('proxy-dashboard-js', 'proxyAjax', array('ajaxurl' => admin_url('admin-ajax.php')));
    }

    wp_enqueue_style('proxy-style', plugin_dir_url(__FILE__) . 'assets/style.css');
}
add_action('wp_enqueue_scripts', 'enqueue_proxy_scripts');
 
 // Admin menu
 function proxy_vrm_add_admin_menu() {
     add_menu_page('Proxy VRM API', 'Proxy VRM API', 'manage_options', 'proxy-vrm', 'proxy_vrm_admin_page');
     
    }
 
 function proxy_vrm_admin_page() {
     ?>
     <div class="wrap">
         <h1>Proxy VRM API</h1>

        <form method="post" action="options.php">
            <?php
            settings_fields('proxy_vrm_settings_group');
            do_settings_sections('proxy-vrm-settings');
            submit_button();
            ?>
        </form>
     </div>
     
     <?php
 }
 add_action('admin_menu', 'proxy_vrm_add_admin_menu');




function proxy_vrm_register_settings() {
    register_setting('proxy_vrm_settings_group', 'proxy_vrm_token_life');

    add_settings_section('proxy_vrm_settings_section', 'Proxy Login Token', null, 'proxy-vrm-settings');

    add_settings_field('proxy_vrm_token_life', 'Scadenza proxy (minuti)', 'proxy_vrm_token_lifecallback', 'proxy-vrm-settings', 'proxy_vrm_settings_section');
}
add_action('admin_init', 'proxy_vrm_register_settings');



function proxy_vrm_token_lifecallback() {
    $token_life = get_option('proxy_vrm_token_life', '');
    echo '<input type="text" name="proxy_vrm_token_life" value="' . esc_attr($token_life) . '" class="regular-text">';
}

function proxy_vrm_data_template() {
    $file_path = plugin_dir_path(__FILE__) . 'assets/panel.html';

    if (file_exists($file_path)) {
        header("Content-Type: text/html");
        echo file_get_contents($file_path);
        exit;
    } else {
        wp_die("File not found", "Error", array("response" => 404));
    }
}
add_action('wp_ajax_proxy_vrm_data_template', 'proxy_vrm_data_template');
add_action('wp_ajax_nopriv_proxy_vrm_data_template', 'proxy_vrm_data_template'); // Allow public access