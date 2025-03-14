<?php
if (!defined('ABSPATH')) exit;

add_shortcode('proxy_auth', function () {

    global $post;
    if (!isset($post) || empty($post->post_content)) {
        return '';
    }
    if (!is_user_logged_in() || !current_user_can('administrator')) {
        return ''; 
    }

    ob_start();
    ?>
    <div id="proxy-auth-container" class="wp-block-group">
        <div class="wp-block-button">
            <button id="proxy-auth-button" class="wp-block-button__link button-vrm" 
                onclick="window.location.href='/mocksite/index.php/vrmdashboard'">
                Monitoraggio
            </button>
        </div>
    </div>
    <?php
    return ob_get_clean();
});

add_shortcode('proxy_dashboard', function () {
    global $post;
    if (!isset($post) || empty($post->post_content)) {
        return '';
    }
    if (!is_user_logged_in() || !current_user_can('administrator')) {
        return ''; 
    }

    ob_start();
    ?>
    <div class = "overlay-page">
        <div id="loading-overlay">
            <div class="loading-spinner"></div>
            <p>Caricamento in corso...</p>
        </div>
    </div>

    <div id="proxy-auth-form" class="wp-block-group">
        
        <h2>Login VRM</h2>
        <!-- Form iniziale per username e password -->
        <form id="proxy-form">
            <?php wp_nonce_field('proxy_auth_nonce', 'proxy_auth_nonce_field'); ?>
            <label for="username">Email:</label>
            <input type="email" id="username" name="username" placeholder="example@gmail.it" class="wp-block-input input-form" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" maxlength="16" class="wp-block-input input-form" required>
            <button type="submit" class="wp-block-button__link">Login</button>
            <p id="form-error-message" class="error-message">Errore di autenticazione: controlla e-mail e password</p>
        </form>
    </div>

    <div id="proxy-dashboard" class="wp-block-group">
        <h2>Dashboard</h2>
        <button id="logout-btn" class="wp-block-button__link">Logout</button>

        <div id="vrm-data">
            
        </div>
    </div>

    <?php
    return ob_get_clean();
});
