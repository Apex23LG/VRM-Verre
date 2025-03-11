<?php
if (!defined('ABSPATH')) exit;

add_shortcode('proxy_auth', function () {
    ob_start();
    ?>
    <div id="proxy-auth-container" class="wp-block-group">
    <div class="wp-block-button">
        <button id="proxy-auth-button" class="wp-block-button__link">Apri il form</button>
    </div>

    <div id="proxy-auth-overlay">
    <div id="proxy-auth-background"></div>
    <div id="proxy-auth-form" class="wp-block-group">
        <span id="proxy-auth-close">&times;</span>

        <!-- Form iniziale per username e password -->
        <form id="proxy-form">
            <?php wp_nonce_field('proxy_auth_nonce', 'proxy_auth_nonce_field'); ?>
            <label for="username">Username:</label>
            <input type="text" id="username" name="username" class="wp-block-input input-form" required>
            <label for="password">Password:</label>
            <input type="password" id="password" name="password" class="wp-block-input input-form" required>
            <button type="submit" class="wp-block-button__link login-button">Login</button>
        </form>

                <!-- Form per il 2FA (inizialmente nascosto) -->
        <form id="proxy-2fa-form">
            <label for="twoFactorCode">Codice 2FA:</label>
            <input type="text" id="twoFactorCode" name="twoFactorCode" class="wp-block-input input-form" required>
            <button type="submit" class="wp-block-button__link login-button">Verifica</button>
        </form>
    </div>
</div>

    <?php
    return ob_get_clean();
});

add_shortcode('proxy_dashboard', function () {
    ob_start();
    ?>
    <h2>Dashboard</h2>
    <button id="get-data-btn">Recupera dati VRM</button>
    <button id="logout-btn">Logout</button>
    
    <pre id="data-output"></pre>
    <?php
    return ob_get_clean();
});
