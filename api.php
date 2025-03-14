<?php
if (!defined('ABSPATH')) exit;

// Configurazione VRM API
define('VRM_API_AUTH', 'https://vrmapi.victronenergy.com/v2/auth');
define('VRM_API_INSTALLATIONS', 'https://vrmapi.victronenergy.com/v2/installations/'); 

// Autenticazione VRM API
function authenticate_vrm($username, $password) {
    $body = ['username' => $username, 'password' => $password];
    $response = wp_remote_post(VRM_API_AUTH . '/login', [
        'body' => json_encode($body),
        'headers' => ['Content-Type' => 'application/json']
    ]);

    if (is_wp_error($response)){
        error_log($response->get_error_message());
        return false;
    } 

    return json_decode(wp_remote_retrieve_body($response), true);
}

add_action('wp_ajax_proxy_auth', 'proxy_auth_handler');
add_action('wp_ajax_nopriv_proxy_auth', 'proxy_auth_handler');

function proxy_auth_handler() {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    $api_response = authenticate_vrm($username, $password);

    if ($api_response) {
        if (isset($api_response['token'])) {
            $proxy_token = bin2hex(random_bytes(16));
            $api_token = $api_response['token'];
            $idUser = $api_response['idUser'];
            $token_lifetime= get_option('proxy_vrm_token_life');
            if($token_lifetime <= 0){
                $token_lifetime = 60;
            }
            set_transient("proxy_token_$proxy_token", [
                'api_token' => $api_token,
                'idUser' => $idUser
            ], 120);
            setcookie("proxy_token", $proxy_token, time()+120, "/", "", true, false);

            wp_send_json_success(['proxy_token' => $proxy_token, 'success' => true]);
        }
    } else {
        wp_send_json_error(['message' => 'Credenziali errate o errore di autenticazione']);
    }
}

// Verifica il proxy-token
function verify_proxy_token($proxy_token) {
    $data = get_transient("proxy_token_$proxy_token");
    return isset($data['api_token']) ? true : false;
}

function validate_proxy_token() {
    $proxyToken = $_POST['proxy_token'] ?? '';
    $isValid = verify_proxy_token($proxyToken);
    wp_send_json(['valid' => $isValid]);
}

add_action('wp_ajax_validate_proxy_token', 'validate_proxy_token');
add_action('wp_ajax_nopriv_validate_proxy_token', 'validate_proxy_token');


// Endpoint per inoltrare richieste a VRM
add_action('wp_ajax_proxy_get_battery_data', 'proxy_get_battery_data');
add_action('wp_ajax_nopriv_proxy_get_battery_data', 'proxy_get_battery_data');
function proxy_get_battery_data(){

    $proxy_token = $_POST['proxy_token'] ?? '';
    
    $data = get_transient("proxy_token_$proxy_token");
    if (!$data) {
        wp_send_json_error(['message' => $data . 'Data Invalid']);
        return;
    }
    $api_token = $data['api_token'];
    if(!$api_token){
        wp_send_json_error(['message' => $api_token . 'APi Invalid']);
        return;
    }
    $batteryData = [
           'soc' => rand(0, 100), // SOC tra 0 e 100%
            'voltage' => round(mt_rand(1150, 1350) / 100, 1), // Tensione tra 11.5V e 13.5V
            'current' => round(mt_rand(-200, 200) / 10, 1), // Corrente tra -20A e 20A
            'power' => round(mt_rand(0, 500) / 10, 1), // Potenza tra 0W e 50W
            'consumedAh' => rand(35 , 50), // Ah consumati tra 0 e 100
            'timeToGo' => 3600, // Secondi rimanenti tra 0 e 2 ore
            'alarm' => (bool)rand(0, 1), // Allarme attivo o meno
            'alarmReason' => (rand(0, 1) ? 'Surriscaldamento' : 'Bassa tensione'), // Motivo dell'allarme casuale
            'temperature' => rand(50, 60) 
        ];

        wp_send_json_success(['data' => $batteryData, 'success' => true]);
    }



// Logout e blacklist token
add_action('wp_ajax_proxy_logout', 'proxy_logout');
function proxy_logout() {
    ob_clean();
    $proxy_token = $_POST['proxy_token'] ?? '';
    $data = get_transient("proxy_token_$proxy_token");
    if (!$data) {
        wp_send_json_error([
            'message' => 'Token non valido o scaduto',
            'success' => false
        ]);
    }

    $api_token = $data['api_token'];
    if (!$api_token) {
        wp_send_json_error([
            'message' => 'Token non valido o scaduto',
            'success' => false
        ]);
    }

    $api_url = VRM_API_AUTH . '/logout';
    $response = wp_remote_get($api_url, [
        'headers' => ['x-authorization' => "Token $api_token"]
    ]);

    if (is_wp_error($response)) {
        wp_send_json_error([
            'message' => 'Logout Non Eseguito',
            'success' => false
        ]);
    } else {
        delete_transient("proxy_token_$proxy_token");
        setcookie("proxy_token", "", time() - 3600, "/", "", false, true);
        wp_send_json_success([
            'message' => 'Logout eseguito',
            'success' => true
        ]);
    }
};


