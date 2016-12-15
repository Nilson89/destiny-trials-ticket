<?php

# Default settings
date_default_timezone_set('Europe/Berlin');
define(BUNGIE_URL, 'https://www.bungie.net');

# Set cookie path
if (!defined('COOKIE_PATH')) {
    define('COOKIE_PATH', dirname(__FILE__) . '/cookies');
}

# Load auth lib
require_once dirname(__FILE__) . "/include/auth.php";

# Load config
$config = require_once 'config.php';

# Auth for PSN
$bungieType = $config['BUNGIE_TYPE'];
$bungieUsername = $config['BUNGIE_USER'];
$bungiePassword = $config['BUNGIE_PASSWORD'];
$bungieAccountId = $config['BUNGIE_ACC_ID'];
$bungieApiToken = $config['BUNGIE_TOKEN'];
$auth = do_webauth($bungieType, $bungieUsername, $bungiePassword, COOKIE_PATH . '/psn.cookie');
if ($auth) {
    require_once dirname(__FILE__) . '/include/api.php';

    // Load ticket data
    $char = $_GET['char'];
    if (!isset($char)) {
        echo "Please add character id";
    } else {
        $ticket = get_trials_ticket($bungieType, $bungieAccountId, $char, $bungieApiToken);
        echo "<pre>";
        var_dump($ticket);
        echo "</pre>";
    }
} else {
    echo "Could not authenticate with bungie!";
}
