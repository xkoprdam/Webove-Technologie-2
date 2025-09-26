<?php

session_start();

require_once 'vendor/autoload.php';
require_once '../../config.php';

use Google\Client;

$client = new Client();

// Required, call the setAuthConfig function to load authorization credentials from
// client_secret.json file. The file can be downloaded from Google Cloud Console.
$client->setAuthConfig('../../client_secret.json');
$redirect_uri = "https://node65.webte.fei.stuba.sk/z2/oauth2callback.php"; // Redirect URI for the OAuth2 callback. Must match the one in the Google Cloud Console.
$client->setRedirectUri($redirect_uri);

// Required, to set the scope value, call the addScope function.
// Scopes define the level of access that the application is requesting from Google.
$client->addScope(["email", "profile"]);
// Enable incremental authorization. Recommended as a best practice.
$client->setIncludeGrantedScopes(true);

// Recommended, offline access will give you both an access and refresh token so that
// your app can refresh the access token without user interaction.
$client->setAccessType("offline");

// Generate a URL for authorization as it doesn't contain code and error
if (!isset($_GET['code']) && !isset($_GET['error'])) {
    // Generate and set state value
    $state = bin2hex(random_bytes(16));
    $client->setState($state);
    $_SESSION['state'] = $state;

    // Generate a url that asks permissions.
    $auth_url = $client->createAuthUrl();
    header('Location: ' . filter_var($auth_url, FILTER_SANITIZE_URL));
}

// User authorized the request and authorization code is returned to exchange access and
// refresh tokens. If the state parameter is not set or does not match the state parameter in the
// authorization request, it is possible that the request has been created by a third party and the user
// will be redirected to a URL with an error message.
// If the authorization was successful, the response URI will contain an authorization code.
if (isset($_GET['code'])) {
    // Check the state value
    if (!isset($_GET['state']) || $_GET['state'] !== $_SESSION['state']) {
        die('State mismatch. Possible CSRF attack.');
    }

    // Get access and refresh tokens (if access_type is offline)
    $token = $client->fetchAccessTokenWithAuthCode($_GET['code']);

    /** Save access and refresh token to the session variables.
     * TODO: In a production app, you likely want to save the
     *              refresh token in a secure persistent storage instead. */
    $_SESSION['access_token'] = $token;
    $_SESSION['refresh_token'] = $client->getRefreshToken();
    $_SESSION['loggedin'] = true;  // User is logged in / authenticated - set custom session variable.

    // TODO: Implement a mechanism to save login information - user_id, login_type, email, fullname - to database.
    $pdo = connectDatabase($hostname, $database, $username, $password);

    $client->setAccessToken($_SESSION['access_token']);
    $oauth = new Google\Service\Oauth2($client);
    $account_info = $oauth->userinfo->get();

    // Get Google user details
    $id = $account_info->id;
    $email = $account_info->email;
    $fullname = $account_info->name;

    $_SESSION["fullname"] = $fullname;
    $_SESSION["email"] = $email;
    $_SESSION["login_type"] = 'gmail';


    // STEP 1: Check if user already exists in 'users' table
    $stmt = $pdo->prepare("SELECT google_id FROM google_users WHERE google_id = :id");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->execute();
    $userExists = $stmt->fetch();

    // STEP 2: If user does not exist, insert into 'users' table
    if (!$userExists) {
        $stmt = $pdo->prepare("INSERT INTO google_users (google_id, email, fullname, created_at) VALUES (:id, :email, :fullname, NOW())");
        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);
        $stmt->execute();
    }

    // STEP 3: Insert login record into 'users_login' table
    $stmt = $pdo->prepare("INSERT INTO google_users_login (google_id, email, fullname, login_time) 
                           VALUES (:id, :email, :fullname, NOW())");
    $stmt->bindParam(":id", $id, PDO::PARAM_INT);
    $stmt->bindParam(":email", $email, PDO::PARAM_STR);
    $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);

    if ($stmt->execute()) {
        $reg_status = "Prihlasenie prebehlo uspesne.";
    } else {
        $reg_status = "Ups. Nieco sa pokazilo...";
    }

    $redirect_uri = 'https://node65.webte.fei.stuba.sk/z2/index.php'; // Redirect to the restricted page or dashboard.
    header('Location: ' . filter_var($redirect_uri, FILTER_SANITIZE_URL));
}
// An error response e.g. error=access_denied
if (isset($_GET['error'])) {
    echo "Error: " . $_GET['error'];
}
