<?php
include 'cookie.php';
session_start();

// Check if the user is already logged in
if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
    header("location: restricted.php");
    exit;
}

require_once '../../config.php';
require_once 'vendor/autoload.php';
require_once 'utilities.php';

use RobThree\Auth\Providers\Qr\EndroidQrCodeProvider;
use RobThree\Auth\TwoFactorAuth;

$pdo = connectDatabase($hostname, $database, $username, $password);

// Check for dark mode cookie on page load
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] === 'true' : false;

// Fetch the max length of the fullname column from the database
$sql = "SELECT CHARACTER_MAXIMUM_LENGTH 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = :database 
          AND TABLE_NAME = 'users' 
          AND COLUMN_NAME = 'fullname'";
$stmt = $pdo->prepare($sql);
$stmt->bindParam(':database', $database, PDO::PARAM_STR);
$stmt->execute();
$column_info = $stmt->fetch(PDO::FETCH_ASSOC);
$fullname_max_length = $column_info ? (int)$column_info['CHARACTER_MAXIMUM_LENGTH'] : 100;
$max_name_length = (int)($fullname_max_length / 2);

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $errors = [];

    // Validate email
    $email = trim($_POST['email']);
    if (isEmpty($email) === true) {
        $errors['email'] = "Nevyplnený e-mail.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = "Neplatný formát e-mailu.";
    } elseif (userExist($pdo, $email)) {
        $errors['email'] = "Používateľ s týmto e-mailom už existuje.";
    }

    // Validate name and surname with dynamic max length
    $firstname = trim($_POST['firstname']);
    if (isEmpty($firstname) === true) {
        $errors['firstname'] = "Nevyplnené meno.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ž\s-]{1,$max_name_length}$/u", $firstname)) {
        $errors['firstname'] = "Meno môže obsahovať iba písmená, medzery a pomlčky (max $max_name_length znakov).";
    }

    $lastname = trim($_POST['lastname']);
    if (isEmpty($lastname) === true) {
        $errors['lastname'] = "Nevyplnené priezvisko.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ž\s-]{1,$max_name_length}$/u", $lastname)) {
        $errors['lastname'] = "Priezvisko môže obsahovať iba písmená, medzery a pomlčky (max $max_name_length znakov).";
    }

    // Validate password
    $password = $_POST['password'];
    if (isEmpty($password) === true) {
        $errors['password'] = "Nevyplnené heslo.";
    } elseif (strlen($password) < 8) {
        $errors['password'] = "Heslo musí mať aspoň 8 znakov.";
    } elseif (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $password)) {
        $errors['password'] = "Heslo musí obsahovať písmená aj čísla.";
    }

    if (empty($errors)) {
        $sql = "INSERT INTO users (fullname, email, password, 2fa_code) VALUES (:fullname, :email, :password, :2fa_code)";
        $fullname = $firstname . ' ' . $lastname;
        $pw_hash = password_hash($password, PASSWORD_ARGON2ID);

        $tfa = new TwoFactorAuth(new EndroidQrCodeProvider());
        $user_secret = $tfa->createSecret();
        $qr_code = $tfa->getQRCodeImageAsDataUri('Nobel Prizes', $user_secret);

        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(":fullname", $fullname, PDO::PARAM_STR);
        $stmt->bindParam(":email", $email, PDO::PARAM_STR);
        $stmt->bindParam(":password", $pw_hash, PDO::PARAM_STR);
        $stmt->bindParam(":2fa_code", $user_secret, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $reg_success = true;
            $reg_status = "Registrácia prebehla úspešne.";
        } else {
            $reg_status = "Ups. Niečo sa pokazilo: " . implode(", ", $stmt->errorInfo());
        }

        unset($stmt);
    }
    unset($pdo);
}
?>

<!DOCTYPE html>
<html lang="sk" class="<?php echo $darkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrácia</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
    </script>
    <style>
        .hidden { display: none; }
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 0.5rem;
            max-width: 90%;
            width: 450px; /* Slightly wider to fit inline elements */
            position: relative;
            text-align: center;
        }
        .dark .modal-content {
            background: #1f2937;
        }
        .secret-container {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: nowrap;
        }
        .secret-container code {
            padding: 0.5rem;
            background: #f3f4f6;
            border-radius: 0.25rem;
            font-size: 0.875rem;
        }
        .dark .secret-container code {
            background: #374151;
        }
        .secret-container button {
            padding: 0.5rem;
            flex-shrink: 0;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">
<!-- Navbar -->
<nav class="bg-gray-800 p-4 shadow-md dark:bg-gray-950">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-white text-xl font-bold">Zadanie 1 - Nobelove ceny</a>
        <div class="flex items-center space-x-4">
            <a href="login.php" class="text-gray-300 hover:text-white">Prihlásiť</a>
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
        <h2 class="text-2xl font-semibold text-gray-600 dark:text-gray-400 mb-6">Vytvorenie nového používateľského konta</h2>

        <?php if (isset($reg_status) && !$reg_success): ?>
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
                <?= htmlspecialchars($reg_status) ?>
            </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="bg-red-100 dark:bg-red-900 border border-red-400 dark:border-red-700 text-red-700 dark:text-red-200 px-4 py-3 rounded mb-6">
                <?php foreach ($errors as $error): ?>
                    <p><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="<?= htmlspecialchars($_SERVER['PHP_SELF']) ?>" method="post" id="registrationForm">
            <div class="mb-4">
                <label for="firstname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Meno:</label>
                <input type="text" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['firstname']) ? 'border-red-500' : ''; ?>"
                       name="firstname" id="firstname" placeholder="napr. John"
                       value="<?= htmlspecialchars($firstname ?? '') ?>" required maxlength="<?= $max_name_length ?>">
                <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="firstname-feedback"></p>
            </div>

            <div class="mb-4">
                <label for="lastname" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priezvisko:</label>
                <input type="text" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['lastname']) ? 'border-red-500' : ''; ?>"
                       name="lastname" id="lastname" placeholder="napr. Doe"
                       value="<?= htmlspecialchars($lastname ?? '') ?>" required maxlength="<?= $max_name_length ?>">
                <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="lastname-feedback"></p>
            </div>

            <div class="mb-4">
                <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">E-mail:</label>
                <input type="email" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['email']) ? 'border-red-500' : ''; ?>"
                       name="email" id="email" placeholder="napr. johndoe@example.com"
                       value="<?= htmlspecialchars($email ?? '') ?>" required>
                <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="email-feedback"></p>
            </div>

            <div class="mb-6">
                <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Heslo:</label>
                <input type="password" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['password']) ? 'border-red-500' : ''; ?>"
                       name="password" id="password" required>
                <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="password-feedback"></p>
            </div>

            <button type="submit" class="w-full bg-indigo-600 dark:bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">Vytvoriť konto</button>
        </form>

        <p class="mt-3 text-center text-gray-600 dark:text-gray-400">
            Už máte vytvorené konto? <a href="login.php" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300">Prihláste sa tu.</a>
        </p>
    </div>
</div>

<!-- Modal for QR Code and Secret -->
<?php if (isset($reg_success) && $reg_success): ?>
    <div id="twoFAModal" class="modal">
        <div class="modal-content text-gray-900 dark:text-gray-100">
            <h3 class="text-lg font-semibold mb-4">Nastavenie 2FA</h3>
            <p class="mb-2">Zadajte tento kód do 2FA aplikácie:</p>
            <div class="secret-container">
                <code id="secretCode"><?= htmlspecialchars($user_secret) ?></code>
                <button id="copySecret" class="bg-indigo-600 text-white rounded hover:bg-indigo-700" title="Kopírovať">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                    </svg>
                </button>
            </div>
            <p class="mb-2">Alebo naskenujte tento QR kód:</p>
            <img src="<?= $qr_code ?>" alt="QR kód pre 2FA" class="mx-auto mb-4" style="max-width: 200px;">
            <a href="login.php" class="block text-center bg-indigo-600 dark:bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600">Pokračovať na prihlásenie</a>
        </div>
    </div>
<?php endif; ?>

<script>
    // Dark Mode Toggle Functionality
    const htmlElement = document.documentElement;
    const darkModeToggle = document.getElementById('darkModeToggle');
    const sunIcon = document.getElementById('sunIcon');
    const moonIcon = document.getElementById('moonIcon');

    const isDark = <?php echo json_encode($darkMode); ?>;
    if (isDark) {
        sunIcon.classList.remove('hidden');
        moonIcon.classList.add('hidden');
    } else {
        sunIcon.classList.add('hidden');
        moonIcon.classList.remove('hidden');
    }

    darkModeToggle.addEventListener('click', () => {
        htmlElement.classList.toggle('dark');
        const isDarkMode = htmlElement.classList.contains('dark');
        document.cookie = `darkMode=${isDarkMode}; path=/; max-age=${30 * 24 * 60 * 60}`;
        sunIcon.classList.toggle('hidden', !isDarkMode);
        moonIcon.classList.toggle('hidden', isDarkMode);
    });

    // Client-side form validation and modal functionality
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('registrationForm');
        const maxNameLength = <?php echo json_encode($max_name_length); ?>;
        const nameRegex = /^[a-zA-ZÀ-ž\s-]{1,}$/;
        const passwordRegex = /[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/;

        function validateField(input) {
            const field = input.id;
            const value = input.value.trim();
            const feedback = document.getElementById(`${field}-feedback`);

            let errorMessage = '';

            switch (field) {
                case 'firstname':
                case 'lastname':
                    if (!value) {
                        errorMessage = `Nevyplnené ${field === 'firstname' ? 'meno' : 'priezvisko'}.`;
                    } else if (!nameRegex.test(value) || value.length > maxNameLength) {
                        errorMessage = `${field === 'firstname' ? 'Meno' : 'Priezvisko'} môže obsahovať iba písmená, medzery a pomlčky (max ${maxNameLength} znakov).`;
                    }
                    break;
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
                    } else if (value.length < 8) {
                        errorMessage = 'Heslo musí mať aspoň 8 znakov.';
                    } else if (!passwordRegex.test(value)) {
                        errorMessage = 'Heslo musí obsahovať písmená aj čísla.';
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

        // Copy Button Functionality
        const copyButton = document.getElementById('copySecret');

        if (copyButton) {
            copyButton.addEventListener('click', function() {
                const secret = document.getElementById('secretCode').textContent;
                navigator.clipboard.writeText(secret).then(() => {
                    copyButton.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>';
                    setTimeout(() => {
                        copyButton.innerHTML = '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path></svg>';
                    }, 2000);
                });
            });
        }
    });
</script>
</body>
</html>