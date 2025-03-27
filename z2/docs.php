<?php
include 'cookie.php'; // Ensure this file exists and handles cookie logic
session_start();

// Check for dark mode cookie on page load
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] === 'true' : false;
?>

<!DOCTYPE html>
<html lang="sk" class="<?php echo $darkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="SwaggerUI - API Documentation">
    <title>WEBTE2 Z2 - API Dokumentácia</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
    </script>
    <!-- Swagger UI CSS -->
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui.css" />
    <!-- Custom Styles -->
    <style>
        .hidden { display: none; }
        .modal {
            transition: opacity 0.3s ease-in-out;
        }
        .modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }
        /* Ensure Swagger UI adapts to dark mode */
        .swagger-ui .topbar {
            background-color: #1f2937; /* gray-800 */
        }
        .dark .swagger-ui .topbar {
            background-color: #0f172a; /* gray-950 */
        }
        .swagger-ui .scheme-container {
            background: #f3f4f6; /* gray-100 */
        }
        .dark .swagger-ui .scheme-container {
            background: #111827; /* gray-900 */
        }
        .swagger-ui {
            color: #111827; /* gray-900 */
        }
        .dark .swagger-ui {
            color: #d1d5db; /* gray-300 */
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">

<!-- Navbar -->
<nav class="bg-gray-800 p-4 shadow-md dark:bg-gray-950">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="index.php" class="text-white text-xl font-bold">Zadanie 2 - API pre Nobelove ceny</a>
        <div class="flex items-center space-x-4">
            <a href="docs.php" class="text-gray-300 hover:text-white font-semibold">Dokumentácia</a>
            <?php
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                echo '<a href="profile.php" class="text-gray-300 hover:text-white">Profil</a>';
                echo '<a href="logout.php" class="text-gray-300 hover:text-white">Odhlásiť sa</a>';
            } else {
                echo '<a href="register.php" class="text-gray-300 hover:text-white">Registrovať</a>';
                echo '<a href="login.php" class="text-gray-300 hover:text-white">Prihlásiť sa</a>';
            }
            ?>
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

<!-- Page Content -->
<div class="max-w-7xl mx-auto mt-6 px-4">
    <!-- Laureate API -->
    <div class="mb-8">
        <div id="swagger-ui-1" class="bg-white dark:bg-gray-800 rounded-lg shadow"></div>
    </div>

    <!-- Prize API -->
    <div>
        <div id="swagger-ui-2" class="bg-white dark:bg-gray-800 rounded-lg shadow"></div>
    </div>
</div>

<!-- Swagger UI Script -->
<script src="https://unpkg.com/swagger-ui-dist@5.11.0/swagger-ui-bundle.js" crossorigin></script>
<script>
    window.onload = () => {
        // Laureate API
        window.ui1 = SwaggerUIBundle({
            url: './laureate.yaml',
            dom_id: '#swagger-ui-1',
            onComplete: () => {
                console.log('Laureate API loaded successfully');
            },
            onFailure: (error) => {
                console.error('Failed to load Laureate API:', error);
            }
        });

        // Prize API
        window.ui2 = SwaggerUIBundle({
            url: './prize.yaml',
            dom_id: '#swagger-ui-2',
            onComplete: () => {
                console.log('Prize API loaded successfully');
            },
            onFailure: (error) => {
                console.error('Failed to load Prize API:', error);
            }
        });

        // Dark Mode Toggle Logic
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
    };
</script>

</body>
</html>