<?php
if (!defined('ABSPATH')) exit;

add_shortcode('proxy_auth', function () {
    ob_start();
    ?>
    <div id="proxy-auth-container" class="wp-block-group">
    <div class="wp-block-button">
        <button id="proxy-auth-button" class="wp-block-button__link">Apri il form</button>
    </div>

    <?php
    return ob_get_clean();
});

add_shortcode('proxy_dashboard', function () {
    ob_start();
    ?>
    <div id="loading-overlay">
        <div class="loading-spinner"></div>
        <p>Caricamento in corso...</p>
    </div>

    <div id="proxy-auth-form" class="wp-block-group">
        
        <h2>Login VRM</h2>
        <!-- Form iniziale per username e password -->
        <form id="proxy-form">
            <?php wp_nonce_field('proxy_auth_nonce', 'proxy_auth_nonce_field'); ?>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" class="wp-block-input input-form" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" class="wp-block-input input-form" required>
            <button type="submit" class="wp-block-button__link">Login</button>
        </form>
    </div>

    <div id="proxy-dashboard" class="wp-block-group">
        <h2>Dashboard</h2>
        <button id="logout-btn">Logout</button>

        <div id="vrm-data">
            
        </div>
    </div>

    <?php
    return ob_get_clean();
});
