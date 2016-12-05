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

# Auth for PSN
$bungieType = getenv('BUNGIE_TYPE');
$bungieUsername = getenv('BUNGIE_USER');
$bungiePassword = getenv('BUNGIE_PASSWORD');
$auth = do_webauth($bungieType, $bungieUsername, $bungiePassword, COOKIE_PATH . '/psn.cookie');
if ($auth) {
    require_once dirname(__FILE__) . '/include/api.php';
    $ticket = get_trials_ticket();
} else {
    echo "Could not authenticate with bungie!";
}
