<?php
if (!defined('ABSPATH')) exit;

// "database" (temporaneamente una lista per sviluppo)
$proxy_tokens = [];

$api_token = ''; // Token di autenticazione VRM

// Configurazione VRM API
define('VRM_API_AUTH', 'https://vrmapi.victronenergy.com/v2/auth/login');
define('VRM_API_INSTALLATIONS', 'https://vrmapi.victronenergy.com/v2/installations/'); 

// Autenticazione VRM API
function authenticate_vrm($username, $password, $twoFactorCode = null) {
    $body = ['username' => $username, 'password' => $password];
    if ($twoFactorCode) {
        $body['twoFactorCode'] = $twoFactorCode;
    }

    $response = wp_remote_post(VRM_API_AUTH, [
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
            $idUser = $api_response['idUser'];
            $token_lifetime= get_option('proxy_vrm_token_life');
            set_transient("proxy_token_$proxy_token", [
                'api_token' => $api_token,
                'idUser' => $idUser
            ], $token_lifetime * 60);

            setcookie("proxy_token", $proxy_token, time() + $token_lifetime*60, "/", "", false, true);

            wp_send_json_success(['proxy_token' => $proxy_token, 'redirect' => site_url('/dashboard')]);
        } elseif ($api_response['verification_sent'] === true) {
            // Se richiede 2FA, mostra il form per il codice
            wp_send_json_success(['requires2FA' => true, 'message' => 'Inserisci il codice 2FA']);
        }
    } else {
        wp_send_json_error(['message' => 'Credenziali errate o errore di autenticazione']);
    }
}

// Verifica il proxy-token
function verify_proxy_token($proxy_token) {
    $data = get_transient("proxy_token_$proxy_token");
    return $data['api_token'] ?: false;
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
    /*
    Supposizioni su come worka l'API

    $proxy_token = $_POST['proxy_token'] ?? '';
    $proxy_token = $_POST['idSite'] ?? 1;
    $data = verify_proxy_token($proxy_token);
    if (!$data) {
        wp_send_json_error(['message' => 'Token non valido o scaduto']);
    }
    $api_token = $data['api_token'];
    if(!$api_token){
        wp_send_json_error(['message' => 'Token non valido o scaduto']);
    }
    $idUser = $data['idUser'];
    if(!$idUser){
        wp_send_json_error(['message' => 'Token non valido o scaduto']);
    }
    $api_url = VRM_API_INSTALLATIONS . $idSite . "/widgets/BatterySummary";
    $response = wp_remote_get($api_url, [
        'headers' => ['Authorization' => "Bearer $api_token"]
    ]);
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Errore nella richiesta: $error_message";
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        $response = wp_remote_get($api_url, [
    'headers' => ['Authorization' => "Bearer $api_token"]
    ]);

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Errore nella richiesta: $error_message";
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($data && isset($data['records']['data'])) {
            $batteryData = [];
            foreach ($data['records']['data'] as $property) {
                if (isset($property['code'])) {
                    switch ($property['code']) {
                        case 'soc':
                            $batteryData['soc'] = $property['value']; // Stato di carica (%)
                            break;
                        case 'voltage':
                            $batteryData['voltage'] = $property['value']; // Tensione (V)
                            break;
                        case 'current':
                            $batteryData['current'] = $property['value']; // Corrente (A)
                            break;
                        case 'power':
                            $batteryData['power'] = $property['value']; // Potenza (W)
                            break;
                        case 'consumedAh':
                            $batteryData['consumedAh'] = $property['value']; // Consumo (Ah)
                            break;
                        case 'timeToGo':
                            $batteryData['timeToGo'] = $property['value']; // Tempo rimanente (s)
                            break;
                        case 'alarm':
                            $batteryData['alarm'] = $property['value']; // Allarme (bool)
                            break;
                        case 'alarmReason':
                            $batteryData['alarmReason'] = $property['value']; // Motivo allarme
                            break;
                        case 'temperature':
                            $batteryData['temperature'] = $property['value']; // Temperatura (°C)
                            break;
                    }
                }
            }
            return $batteryData;
        } else {
            return ['error' => 'Dati non disponibili'];
        }
    }
    */
    $batteryData = ['soc' => 50,
            'voltage' => 12.5, 
            'current' => 5, 
            'power'=>12, 
            'consumedAh'=>10,
            'timeToGo'=>3600,
            'alarm'=>true,
            'alarmReason'=>'Tanta roba',
            'temperature'=>40];

            wp_send_json_success(['data' => $batteryData]);
    }



// Logout e blacklist token
add_action('wp_ajax_proxy_logout', function() {
    $proxy_token = $_POST['proxy_token'] ?? '';
    delete_transient("proxy_token_$proxy_token");
    setcookie("proxy_token", "", time() - 3600, "/", "", false, true);
    wp_send_json_success(['message' => 'Logout eseguito']);
});


