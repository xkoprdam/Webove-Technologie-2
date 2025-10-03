<?php
include 'cookie.php';
session_start();

// Check if the user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: index.php");
    exit;
}

require_once "../../config.php";
require_once 'vendor/autoload.php';
require_once 'utilities.php';

use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

$pdo = connectDatabase($hostname, $database, $username, $password);

$redirect_uri = "https://node65.webte.fei.stuba.sk/z1/oauth2callback.php";

// Check for dark mode cookie on page load
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] === 'true' : false;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    $email = trim($_POST['email']);
    if (empty($email)) {
        $errors['email'] = "Nevyplnený e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Neplatný formát e-mailu.";
    }

    $password = $_POST['password'];
    if (empty($password)) {
        $errors['password'] = "Nevyplnené heslo.";
    }

    $twofa = $_POST['2fa'];
    if (empty($twofa)) {
        $errors['2fa'] = "Nevyplnený 2FA kód.";
    } elseif (!preg_match('/^[0-9]{6}$/', $twofa)) {
        $errors['2fa'] = "2FA kód musí byť 6-miestne číslo.";
    }

    if (empty($errors)) {
        $sql = "SELECT id, fullname, email, password, 2fa_code, created_at FROM users WHERE email = :email";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);

        if ($stmt->execute()) {
            if ($stmt->rowCount() == 1) {
                $row = $stmt->fetch();
                $hashed_password = $row["password"];

                if (password_verify($password, $hashed_password)) {
                    $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
                    if ($tfa->verifyCode($row["2fa_code"], $twofa, 2)) {
                        $_SESSION["loggedin"] = true;
                        $_SESSION["login_type"] = 'email';
                        $_SESSION["fullname"] = trim($row['fullname']);
                        $_SESSION["email"] = $row['email'];
                        $_SESSION["created_at"] = $row['created_at'];

                        $sql = "INSERT INTO users_login (user_id, login_type, email, fullname, login_time) 
                                VALUES (:id, :login_type, :email, :fullname, NOW())";
                        $id = $row['id'];
                        $login_type = "web";
                        $email = $row['email'];
                        $fullname = $row['fullname'];

                        $stmt = $pdo->prepare($sql);
                        $stmt->bindParam(":id", $id, PDO::PARAM_INT);
                        $stmt->bindParam(":login_type", $login_type, PDO::PARAM_STR);
                        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
                        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);

                        if ($stmt->execute()) {
                            $reg_status = "Prihlasenie prebehlo uspesne.";
                        } else {
                            $reg_status = "Ups. Nieco sa pokazilo...";
                        }

                        header("location: restricted.php");
                        exit;
                    } else {
                        $errors['2fa'] = "Neplatný kód 2FA.";
                    }
                } else {
                    $errors['password'] = "Neplatné prihlasovacie údaje.";
                }
            } else {
                $errors['email'] = "Neplatné prihlasovacie údaje.";
            }
        } else {
            $errors['form'] = "Ups. Niečo sa pokazilo...";
        }
    }

    unset($stmt);
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="sk" class="<?php echo $darkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prihlásenie</title>
    <!-- Tailwind CSS with custom config for class-based dark mode -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', // Force class-based dark mode instead of media
        };
    </script>
    <style>
        .hidden { display: none; }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">

<!-- Navbar -->
<nav class="bg-gray-800 p-4 shadow-md dark:bg-gray-950">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-white text-xl font-bold">Zadanie 1 - Nobelove ceny</a>
        <div class="flex items-center space-x-4">
            <a href="register.php" class="text-gray-300 hover:text-white">Registrovať</a>
            <!-- Dark Mode Toggle -->
            <button id="darkModeToggle" class="p-2 rounded-full bg-gray-700 text-white hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <svg id="sunIcon" class="w-5 h-5 <?php echo $darkMode ? '' : 'hidden'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
                <svg id="moonIcon" class="w-5 h-5 <?php echo $darkMode ? 'hidden' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"></path>
                </svg>
            </button>
        </div>
    </div>
</nav>

<div class="max-w-7xl mx-auto mt-6 px-4">
    <div class="max-w-md mx-auto mt-8 p-6 bg-white dark:bg-gray-800 rounded-lg shadow-md">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Prihlásenie</h1>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-4">
                <?php foreach ($errors as $error): ?>
                    <p><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($reg_status)): ?>
            <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
                <?php echo htmlspecialchars($reg_status); ?>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post" id="loginForm">
            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-Mail:</label>
                <input type="email" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
                       name="email" id="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="email-feedback"></p>
            </div>

            <div class="mb-4">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Heslo:</label>
                <input type="password" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['password']) ? 'border-red-500' : ''; ?>"
                       name="password" id="password" required>
                <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="password-feedback"></p>
            </div>

            <div class="mb-6">
                <label for="2fa" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">2FA kód:</label>
                <input type="number" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['2fa']) ? 'border-red-500' : ''; ?>"
                       name="2fa" id="2fa" value="<?php echo htmlspecialchars($twofa ?? ''); ?>" required>
                <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="2fa-feedback"></p>
            </div>

            <button type="submit" class="w-full bg-indigo-600 dark:bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">Prihlásiť sa</button>

            <div class="mt-6 flex flex-col items-center space-y-4">
                <p class="text-center text-gray-600 dark:text-gray-400">alebo</p>
                <!-- Google Styled Button -->
                <a href="<?php echo filter_var($redirect_uri, FILTER_SANITIZE_URL); ?>" class="flex items-center justify-center w-full bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-md py-2 px-4 text-gray-700 dark:text-gray-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 shadow-sm">
                    <svg class="w-5 h-5 mr-2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                        <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                        <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.66-2.84z"/>
                        <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l2.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                    </svg>
                    Prihlásiť sa cez Google
                </a>
                <p class="text-center text-gray-600 dark:text-gray-400">
                    Nemáte ešte vytvorené konto?
                    <a href="register.php" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Zaregistrujte sa tu.</a>
                </p>
            </div>
        </form>
    </div>
</div>

<script>
    // Dark Mode Toggle Functionality
    const htmlElement = document.documentElement;
    const darkModeToggle = document.getElementById('darkModeToggle');
    const sunIcon = document.getElementById('sunIcon');
    const moonIcon = document.getElementById('moonIcon');

    // Set initial icon state based on PHP (cookie)
    const isDark = <?php echo json_encode($darkMode); ?>;
    if (isDark) {
        sunIcon.classList.remove('hidden');
        moonIcon.classList.add('hidden');
    } else {
        sunIcon.classList.add('hidden');
        moonIcon.classList.remove('hidden');
    }

    // Toggle dark mode and set cookie
    darkModeToggle.addEventListener('click', () => {
        htmlElement.classList.toggle('dark');
        const isDarkMode = htmlElement.classList.contains('dark');
        document.cookie = `darkMode=${isDarkMode}; path=/; max-age=${30 * 24 * 60 * 60}`;
        sunIcon.classList.toggle('hidden', !isDarkMode);
        moonIcon.classList.toggle('hidden', isDarkMode);
    });

    // Client-side form validation
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('loginForm');
        const twoFaRegex = /^[0-9]{6}$/;

        function validateField(input) {
            const field = input.id;
            const value = input.value.trim();
            const feedback = document.getElementById(`${field}-feedback`);

            let errorMessage = '';

            switch (field) {
                case 'email':
                    if (!value) {
                        errorMessage = 'Nevyplnený e-mail.';
                    } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                        errorMessage = 'Neplatný formát e-mailu.';
                    }
                    break;
                case 'password':
                    if (!value) {
                        errorMessage = 'Nevyplnené heslo.';
                    }
                    break;
                case '2fa':
                    if (!value) {
                        errorMessage = 'Nevyplnený 2FA kód.';
                    } else if (!twoFaRegex.test(value)) {
                        errorMessage = '2FA kód musí byť 6-miestne číslo.';
                    }
                    break;
            }

            if (errorMessage) {
                input.classList.add('border-red-500');
                input.classList.remove('border-gray-300', 'dark:border-gray-600');
                feedback.textContent = errorMessage;
                feedback.classList.remove('hidden');
            } else {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300', 'dark:border-gray-600');
                feedback.textContent = '';
                feedback.classList.add('hidden');
            }
        }

        const inputs = form.querySelectorAll('input');
        inputs.forEach(input => {
            input.addEventListener('input', () => validateField(input));
            input.addEventListener('blur', () => validateField(input));
        });

        form.addEventListener('submit', function (e) {
            inputs.forEach(input => validateField(input));
            const hasErrors = Array.from(inputs).some(input => input.classList.contains('border-red-500'));
            if (hasErrors) {
                e.preventDefault();
            }
        });
    });
</script>
</body>
</html>