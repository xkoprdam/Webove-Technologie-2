<?php
include 'cookie.php';

session_start();
require_once 'vendor/autoload.php';

use Google\Client;

$client = new Client();
$client->setAuthConfig('../../client_secret.json'); // Adjust path if needed

// Check if Google access token exists
if (isset($_SESSION['access_token'])) {
    $client->setAccessToken($_SESSION['access_token']);

    // Revoke the Google token
    $client->revokeToken();
}

// Clear all session variables
session_unset();
session_destroy();

// Clear browser cookies (important for some browsers)
setcookie(session_name(), '', time() - 42000, '/');

header("location: index.php");
exit;