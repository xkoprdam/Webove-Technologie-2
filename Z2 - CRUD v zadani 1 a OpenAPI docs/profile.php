<?php
session_start();
include 'cookie.php';

if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("Location: login.php");
    exit;
}

require_once '../../config.php';
require_once 'vendor/autoload.php';

use Google\Client;

$client = new Client();
$client->setAuthConfig('../../client_secret.json');

$db = connectDatabase($hostname, $database, $username, $password);

// Check for dark mode cookie on page load
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] === 'true' : false;

// Fetch user details and login history
$logins = [];
$userDetails = null;

if ($_SESSION['login_type'] === "gmail") {
    $stmt = $db->prepare("SELECT email, fullname FROM google_users WHERE email = :email");
    $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);
    $stmt->execute();
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userDetails) {
        $_SESSION['email'] = $userDetails['email'];
        $_SESSION['fullname'] = $userDetails['fullname'];
    }

    $stmt = $db->prepare("SELECT login_time FROM google_users_login WHERE email = :email ORDER BY login_time DESC");
    $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);
    $stmt->execute();
    $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif ($_SESSION['login_type'] === "email") {
    $stmt = $db->prepare("SELECT email, fullname FROM users WHERE email = :email");
    $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);
    $stmt->execute();
    $userDetails = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userDetails) {
        $_SESSION['email'] = $userDetails['email'];
        $_SESSION['fullname'] = $userDetails['fullname'];
    }

    $stmt = $db->prepare("SELECT login_time FROM users_login WHERE email = :email ORDER BY login_time DESC");
    $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);
    $stmt->execute();
    $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch the max length of the fullname column from the database
$sql = "SELECT CHARACTER_MAXIMUM_LENGTH 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_SCHEMA = :database 
          AND TABLE_NAME = 'users' 
          AND COLUMN_NAME = 'fullname'";
$stmt = $db->prepare($sql);
$stmt->bindParam(':database', $database, PDO::PARAM_STR);
$stmt->execute();
$column_info = $stmt->fetch(PDO::FETCH_ASSOC);
$fullname_max_length = $column_info ? (int)$column_info['CHARACTER_MAXIMUM_LENGTH'] : 100;

// Handle name change form submission
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_name'])) {
    $errors = [];
    $new_name = trim($_POST['new_name']);
    if (empty($new_name)) {
        $errors['new_name'] = "Meno nesmie byť prázdne.";
    } elseif (!preg_match("/^[a-zA-ZÀ-ž\s-]{1,$fullname_max_length}$/u", $new_name)) {
        $errors['firstname'] = "Meno môže obsahovať iba písmená, medzery a pomlčky (max $fullname_max_length znakov).";
    }

    if (empty($errors)) {
        if ($_SESSION['login_type'] === "gmail") {
            $stmt = $db->prepare("UPDATE google_users_login SET fullname = :name WHERE email = :email");
            $stmt->bindParam(':name', $new_name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);
        } elseif ($_SESSION['login_type'] === "email") {
            $stmt = $db->prepare("UPDATE users SET fullname = :name WHERE email = :email");
            $stmt->bindParam(':name', $new_name, PDO::PARAM_STR);
            $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);
        }

        if ($stmt->execute()) {
            $_SESSION['fullname'] = $new_name;
            $name_result = "Meno bolo úspešne zmenené.";
        } else {
            $name_result = "Chyba pri zmene mena: " . implode(", ", $stmt->errorInfo());
        }
    } else {
        $name_result = implode("\n", $errors);
    }
}

// Handle password change form submission (non-Google users only)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['new_password']) && $_SESSION['login_type'] === "email") {
    $errors = [];
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if (empty($current_password)) {
        $errors['current_password'] = "Súčasné heslo je povinné.";
    }
    if (empty($new_password)) {
        $errors['new_password'] = "Nové heslo je povinné.";
    } elseif (strlen($new_password) < 8) {
        $errors['new_password'] = "Nové heslo musí mať aspoň 8 znakov.";
    } elseif (!preg_match('/[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/', $new_password)) {
        $errors['new_password'] = "Nové heslo musí obsahovať písmená aj čísla.";
    }
    if (empty($confirm_password)) {
        $errors['confirm_password'] = "Potvrdenie hesla je povinné.";
    } elseif ($new_password !== $confirm_password) {
        $errors['confirm_password'] = "Nové heslá sa nezhodujú.";
    }

    if (empty($errors)) {
        $stmt = $db->prepare("SELECT password FROM users WHERE email = :email");
        $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password'])) {
            $errors['current_password'] = "Súčasné heslo je nesprávne.";
        } else {
            $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
            $stmt = $db->prepare("UPDATE users SET password = :password WHERE email = :email");
            $stmt->bindParam(':password', $hashed_password, PDO::PARAM_STR);
            $stmt->bindParam(':email', $_SESSION['email'], PDO::PARAM_STR);

            if ($stmt->execute()) {
                $password_result = "Heslo bolo úspešne zmenené.";
            } else {
                $password_result = "Chyba pri zmene hesla: " . implode(", ", $stmt->errorInfo());
            }
        }
    } else {
        $password_result = implode("\n", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="sk" class="<?php echo $darkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Zabezpečená stránka</title>
    <!-- Tailwind CSS with custom config for class-based dark mode -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', // Force class-based dark mode
        };
    </script>
    <style>
        .hidden { display: none; }
        .login-history {
            max-height: 200px;
            overflow-y: auto;
        }
        /* Light mode scrollbar */
        .login-history::-webkit-scrollbar {
            width: 8px;
        }
        .login-history::-webkit-scrollbar-track {
            background: #e5e7eb; /* gray-200 */
        }
        .login-history::-webkit-scrollbar-thumb {
            background: #6b7280; /* gray-500 */
            border-radius: 4px;
        }
        .login-history::-webkit-scrollbar-thumb:hover {
            background: #4b5563; /* gray-600 */
        }
        /* Dark mode scrollbar */
        .dark .login-history::-webkit-scrollbar-track {
            background: #1f2937; /* gray-800 */
        }
        .dark .login-history::-webkit-scrollbar-thumb {
            background: #6b7280; /* gray-500 */
        }
        .dark .login-history::-webkit-scrollbar-thumb:hover {
            background: #9ca3af; /* gray-400 */
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">
<!-- Navbar -->
<nav class="bg-gray-800 p-4 shadow-md dark:bg-gray-950">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-white text-xl font-bold">Zadanie 2 - API pre Nobelove ceny</a>
        <div class="flex items-center space-x-4">
            <a href="docs.php" class="text-gray-300 hover:text-white">Dokumentácia</a>
            <a href="profile.php" class="text-gray-300 hover:text-white font-semibold">Profil</a>
            <a href="logout.php" class="text-gray-300 hover:text-white">Odhlásiť</a>
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

<style>
    .relative:hover .hidden {
        display: block;
    }
</style>

<div class="max-w-7xl mx-auto mt-6 px-4">
    <div class="max-w-3xl mx-auto mt-8">
        <h3 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Vitaj, <?= htmlspecialchars($_SESSION['fullname']) ?></h3>

        <!-- Form to change name -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
            <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Zmeniť meno</h4>
            <form method="POST" id="nameForm">
                <div class="mb-4">
                    <label for="new_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                        Nové meno: <span class="text-red-500 dark:text-red-400">*</span>
                    </label>
                    <input type="text" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['new_name']) ? 'border-red-500' : ''; ?>"
                           id="new_name" name="new_name" value="<?= htmlspecialchars($new_name ?? $_SESSION['fullname']) ?>" required>
                    <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="new_name-feedback"><?= htmlspecialchars($errors['new_name'] ?? '') ?></p>
                    <?php if (isset($name_result)): ?>
                        <div class="mt-2 p-2 rounded <?= strpos($name_result, "Chyba") === false ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200' ?>">
                            <?= htmlspecialchars($name_result) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <button type="submit" class="w-full bg-indigo-600 dark:bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">Uložiť meno</button>
            </form>
        </div>

        <!-- Form to change password (non-Google users only) -->
        <?php if ($_SESSION['login_type'] === "email"): ?>
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6">
                <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Zmeniť heslo</h4>
                <form method="POST" id="passwordForm">
                    <div class="mb-4">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Súčasné heslo: <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input type="password" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['current_password']) ? 'border-red-500' : ''; ?>"
                               id="current_password" name="current_password" required>
                        <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="current_password-feedback"><?= htmlspecialchars($errors['current_password'] ?? '') ?></p>
                    </div>
                    <div class="mb-4">
                        <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Nové heslo: <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input type="password" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['new_password']) ? 'border-red-500' : ''; ?>"
                               id="new_password" name="new_password" required>
                        <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="new_password-feedback"><?= htmlspecialchars($errors['new_password'] ?? '') ?></p>
                    </div>
                    <div class="mb-6">
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                            Potvrdiť heslo: <span class="text-red-500 dark:text-red-400">*</span>
                        </label>
                        <input type="password" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 <?php echo isset($errors['confirm_password']) ? 'border-red-500' : ''; ?>"
                               id="confirm_password" name="confirm_password" required>
                        <p class="text-red-500 dark:text-red-400 text-sm mt-1 hidden" id="confirm_password-feedback"><?= htmlspecialchars($errors['confirm_password'] ?? '') ?></p>
                        <?php if (isset($password_result)): ?>
                            <div class="mt-2 p-2 rounded <?= $password_result === 'Heslo bolo úspešne zmenené.' ? 'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-200' : 'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-200' ?>">
                                <?= htmlspecialchars($password_result) ?>
                            </div>
                        <?php endif; ?>
                    </div>
                    <button type="submit" class="w-full bg-indigo-600 dark:bg-indigo-500 text-white py-2 px-4 rounded-md hover:bg-indigo-700 dark:hover:bg-indigo-600 focus:outline-none focus:ring-2 focus:ring-indigo-500">Uložiť heslo</button>
                </form>
            </div>
        <?php endif; ?>

        <!-- Login history -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6">
            <h4 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">História prihlásení</h4>
            <?php if (empty($logins)): ?>
                <p class="text-gray-600 dark:text-gray-400">Žiadne prihlásenia neboli zaznamenané.</p>
            <?php else: ?>
                <div class="login-history">
                    <ul class="divide-y divide-gray-200 dark:divide-gray-700">
                        <?php foreach ($logins as $login): ?>
                            <li class="py-2 text-gray-700 dark:text-gray-300"><?= htmlspecialchars($login['login_time']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
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

    // Client-side validation
    document.addEventListener('DOMContentLoaded', function () {
        const nameForm = document.getElementById('nameForm');
        const maxNameLength = <?= json_encode($fullname_max_length) ?>;
        const nameRegex = /^[a-zA-ZÀ-ž\s-]{1,}$/;
        const passwordForm = document.getElementById('passwordForm');
        const passwordRegex = /[A-Za-z].*[0-9]|[0-9].*[A-Za-z]/;

        function validateField(input) {
            const field = input.id;
            const value = input.value.trim();
            const feedback = document.getElementById(`${field}-feedback`);
            let errorMessage = '';

            switch (field) {
                case 'new_name':
                    if (!value) {
                        errorMessage = 'Meno nesmie byť prázdne.';
                    } else if (!nameRegex.test(value) || value.length > maxNameLength) {
                        errorMessage = `Meno môže obsahovať iba písmená, medzery a pomlčky (max ${maxNameLength} znakov).`;
                    }
                    break;
                case 'current_password':
                    if (!value) {
                        errorMessage = 'Súčasné heslo je povinné.';
                    }
                    break;
                case 'new_password':
                    if (!value) {
                        errorMessage = 'Nové heslo je povinné.';
                    } else if (value.length < 8) {
                        errorMessage = 'Nové heslo musí mať aspoň 8 znakov.';
                    } else if (!passwordRegex.test(value)) {
                        errorMessage = 'Nové heslo musí obsahovať písmená aj čísla.';
                    }
                    break;
                case 'confirm_password':
                    const newPassword = document.getElementById('new_password').value.trim();
                    if (!value) {
                        errorMessage = 'Potvrdenie hesla je povinné.';
                    } else if (value !== newPassword) {
                        errorMessage = 'Nové heslá sa nezhodujú.';
                    }
                    break;
            }

            if (errorMessage) {
                input.classList.add('border-red-500');
                input.classList.remove('border-gray-300', 'dark:border-gray-600');
                feedback.textContent = errorMessage;
                feedback.classList.remove('hidden');
                return false;
            } else {
                input.classList.remove('border-red-500');
                input.classList.add('border-gray-300', 'dark:border-gray-600');
                feedback.textContent = '';
                feedback.classList.add('hidden');
                return true;
            }
        }

        if (nameForm) {
            const newNameInput = document.getElementById('new_name');
            newNameInput.addEventListener('input', () => validateField(newNameInput));
            newNameInput.addEventListener('blur', () => validateField(newNameInput));

            nameForm.addEventListener('submit', function (e) {
                if (!validateField(newNameInput)) {
                    e.preventDefault();
                }
            });
        }

        if (passwordForm) {
            const inputs = passwordForm.querySelectorAll('input');
            inputs.forEach(input => {
                input.addEventListener('input', () => validateField(input));
                input.addEventListener('blur', () => validateField(input));
            });

            passwordForm.addEventListener('submit', function (e) {
                let hasErrors = false;
                inputs.forEach(input => {
                    if (!validateField(input)) hasErrors = true;
                });
                if (hasErrors) {
                    e.preventDefault();
                }
            });
        }
    });
</script>
</body>
</html>