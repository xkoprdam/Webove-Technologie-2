<?php
include 'cookie.php';
session_start();

require_once '../../config.php';
$db = connectDatabase($hostname, $database, $username, $password);

// Check for dark mode cookie on page load
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] === 'true' : false;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "Neplatné ID laureáta.";
    exit;
}

$laureate_id = $_GET['id'];

$stmt = $db->prepare("SELECT 
                        COALESCE(NULLIF(fullname, ''), organisation) AS laureate,
                        sex, birth_year, death_year, country 
                      FROM laureates 
                      WHERE id = ?");
$stmt->execute([$laureate_id]);
$laureate = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$laureate) {
    echo "Laureát neexistuje.";
    exit;
}

// Split the laureate name into words
$laureate_name_parts = explode(' ', $laureate['laureate']);

// Determine gender text
$gender = $laureate['sex'] === 'M' ? 'muž' : ($laureate['sex'] === 'F' ? 'žena' : htmlspecialchars($laureate['sex']));

// Replace commas with line breaks for country
$country_with_breaks = str_replace(',', '<br>', htmlspecialchars($laureate['country']));

// Get all prizes for the laureate
$prizes_stmt = $db->prepare("SELECT 
                                year, category, contribution_sk, contribution_en,
                                d.language_sk, d.language_en, d.genre_sk, d.genre_en
                            FROM prizes
                            JOIN laureate_prizes ON prizes.id = laureate_prizes.prize_id
                            LEFT JOIN prize_details d ON prizes.details_id = d.id
                            WHERE laureate_prizes.laureate_id = ?");
$prizes_stmt->execute([$laureate_id]);
$prizes = $prizes_stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if the laureate has won a prize in Literature
$hasLiteraturePrize = false;
foreach ($prizes as $prize) {
    if ($prize['category'] === 'literatúra') {
        $hasLiteraturePrize = true;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="sk" class="<?php echo $darkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail laureáta - <?= htmlspecialchars($laureate['laureate']) ?></title>
    <!-- Tailwind CSS with custom config for class-based dark mode -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', // Force class-based dark mode
        };
    </script>
    <!-- DataTables Tailwind CSS Technical Preview -->
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.tailwindcss.css">
    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.js"></script>
    <script src="https://cdn.datatables.net/2.2.2/js/dataTables.tailwindcss.js"></script>
    <style>
        .dataTables_filter { display: none; }
        #prizesTable td, #prizesTable th {
            white-space: normal !important;
            word-wrap: break-word;
        }
        div.dt-paging {
            display: flex;
            justify-content: center;
            padding: 1rem 0;
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
            <?php
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                echo '<a href="restricted.php" class="text-gray-300 hover:text-white">Profil</a>';
                echo '<a href="logout.php" class="text-gray-300 hover:text-white">Odhlásiť sa</a>';
            } else {
                echo '<a href="register.php" class="text-gray-300 hover:text-white">Registrovať</a>';
                echo '<a href="login.php" class="text-gray-300 hover:text-white">Prihlásiť sa</a>';
            }
            ?>
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
    <h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200 mb-6">Detail laureáta:</h2>

    <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 mb-6 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- First Column: Name -->
        <div class="flex flex-col justify-center">
            <?php foreach ($laureate_name_parts as $part): ?>
                <span class="text-5xl font-bold text-gray-800 dark:text-gray-200 leading-tight"><?php echo htmlspecialchars($part); ?></span>
            <?php endforeach; ?>
        </div>
        <!-- Second Column: Details -->
        <div class="text-gray-700 dark:text-gray-300">
            <p><strong>Pohlavie:</strong> <?php echo $gender; ?></p>
            <p><strong>Rok narodenia:</strong> <?php echo htmlspecialchars($laureate['birth_year']); ?></p>
            <p><strong>Rok úmrtia:</strong> <?php echo htmlspecialchars($laureate['death_year']) ?: 'žije'; ?></p>
            <p><strong>Krajina:</strong> <?php echo $country_with_breaks; ?></p>
        </div>
    </div>

    <h3 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Ceny</h3>
    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table id="prizesTable" class="w-full">
            <thead class="bg-gray-800 text-white dark:bg-gray-950">
            <tr>
                <th class="p-3 text-left">Rok</th>
                <th class="p-3 text-left">Kategória</th>
                <th class="p-3 text-left">Príspevok SK</th>
                <th class="p-3 text-left">Príspevok EN</th>
                <?php if ($hasLiteraturePrize): ?>
                    <th class="p-3 text-left">Jazyk SK</th>
                    <th class="p-3 text-left">Jazyk EN</th>
                    <th class="p-3 text-left">Žáner SK</th>
                    <th class="p-3 text-left">Žáner EN</th>
                <?php endif; ?>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php foreach ($prizes as $prize): ?>
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                    <td class="p-3"><?= htmlspecialchars($prize['year']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($prize['category']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($prize['contribution_sk']) ?></td>
                    <td class="p-3"><?= htmlspecialchars($prize['contribution_en']) ?></td>
                    <?php if ($hasLiteraturePrize): ?>
                        <td class="p-3"><?= htmlspecialchars($prize['language_sk']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($prize['language_en']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($prize['genre_sk']) ?></td>
                        <td class="p-3"><?= htmlspecialchars($prize['genre_en']) ?></td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <p class="mt-6">
        <a href="index.php" class="inline-block bg-gray-500 dark:bg-gray-600 text-white py-2 px-4 rounded-md hover:bg-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500">Späť na zoznam</a>
    </p>
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

    // DataTables Initialization
    $(document).ready(function() {
        $('#prizesTable').DataTable({
            responsive: true,
            lengthChange: false,
            pageLength: 10,
            layout: {
                topStart: null,
                topEnd: null,
                bottomStart: null,
                bottomEnd: null,
                bottom: 'paging'
            },
            pagingType: 'simple_numbers',
            language: {
                paginate: {
                    previous: 'Predošlá',
                    next: 'Ďalšia'
                }
            },
            autoWidth: false,
            columnDefs: [
                { targets: '_all', width: 'auto' }
            ]
        });
    });
</script>
</body>
</html>