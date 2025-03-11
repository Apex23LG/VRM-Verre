<?php
if (!defined('ABSPATH')) exit;

// "database" (temporaneamente una lista per sviluppo)
$proxy_tokens = [];

$api_token = ''; // Token di autenticazione VRM

// Configurazione VRM API
define('VRM_API_URL', 'https://vrmapi.victronenergy.com/v2/auth/login');
define('VRM_API_GET_DATA', 'https://vrmapi.example.com/data'); 

// Autenticazione VRM API
function authenticate_vrm($username, $password, $twoFactorCode = null) {
    $body = ['username' => $username, 'password' => $password];
    if ($twoFactorCode) {
        $body['twoFactorCode'] = $twoFactorCode;
    }

    $response = wp_remote_post(VRM_API_URL, [
        'body' => json_encode($body),
        'headers' => ['Content-Type' => 'application/json']
    ]);

    if (is_wp_error($response)) return false;

    return json_decode(wp_remote_retrieve_body($response), true);
}

// Endpoint AJAX per il login dal form
add_action('wp_ajax_proxy_auth', 'proxy_auth_handler');
add_action('wp_ajax_nopriv_proxy_auth', 'proxy_auth_handler');

function proxy_auth_handler() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $twoFactorCode = $_POST['twoFactorCode'] ?? null;

    $api_response = authenticate_vrm($username, $password, $twoFactorCode);

    if ($api_response && isset($api_response['verification_sent'])) {
        if ($api_response['verification_sent'] === false && isset($api_response['token'])) {
            // Login riuscito, restituisci il token
            $proxy_token = bin2hex(random_bytes(16));
            $api_token = $api_response['token'];
            $token_lifetime= get_option('proxy_vrm_token_life');
            set_transient("proxy_token_$proxy_token", $api_token, $token_lifetime*60);

            setcookie("proxy_token", $proxy_token, time() + $token_lifetime*60, "/", "", false, true);

            wp_send_json_success(['proxy_token' => $proxy_token, 'redirect' => site_url('/dashboard')]);
        } elseif ($api_response['verification_sent'] === true) {
            // Richiede 2FA, mostra il form per il codice
            wp_send_json_success(['requires2FA' => true, 'message' => 'Inserisci il codice 2FA']);
        }
    } else {
        wp_send_json_error(['message' => 'Credenziali errate o errore di autenticazione']);
    }
}

// Verifica il proxy-token
function verify_proxy_token($proxy_token) {
    return get_transient("proxy_token_$proxy_token") ?: false;
}

// Endpoint per inoltrare richieste a VRM
add_action('wp_ajax_proxy_get_data', 'proxy_get_data');
add_action('wp_ajax_nopriv_proxy_get_data', 'proxy_get_data');

function proxy_get_data() {
    $proxy_token = $_POST['proxy_token'] ?? '';
    $api_token = verify_proxy_token($proxy_token);

    if (!$api_token) {
        wp_send_json_error(['message' => 'Token non valido o scaduto']);
    }

    $response = wp_remote_get(VRM_API_GET_DATA, [
        'headers' => ['Authorization' => "Bearer $api_token"]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Errore nella richiesta a VRM']);
    }

    wp_send_json_success(['data' => json_decode(wp_remote_retrieve_body($response), true)]);
}

// Logout e blacklist token
add_action('wp_ajax_proxy_logout', function() {
    $proxy_token = $_POST['proxy_token'] ?? '';
    delete_transient("proxy_token_$proxy_token");
    setcookie("proxy_token", "", time() - 3600, "/", "", false, true);
    wp_send_json_success(['message' => 'Logout eseguito']);
});
