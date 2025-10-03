<?php
include 'cookie.php';
session_start();

// Check for dark mode cookie on page load
$darkMode = isset($_COOKIE['darkMode']) ? $_COOKIE['darkMode'] === 'true' : false;
?>

<!DOCTYPE html>
<html lang="sk" class="<?php echo $darkMode ? 'dark' : 'light'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nobelove ceny</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class', // Force class-based dark mode instead of media
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
        .hidden { display: none; }
        .dataTables_filter { display: none; }
        #nobelTable td, #nobelTable th {
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
        <a href="#" class="text-white text-xl font-bold">Zadanie 1 - Nobelove ceny</a>
        <div class="flex items-center space-x-4">
            <?php
            if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                echo '<a href="restricted.php" class="text-gray-300 hover:text-white">Profil</a>';
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

<div class="max-w-7xl mx-auto mt-6 px-4">
    <?php
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        echo '<h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Vitaj ' . htmlspecialchars($_SESSION['fullname']) . '</h2>';
    }
    ?>
    <div class="mt-6"></div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div>
            <label for="category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Kategória:</label>
            <select id="category" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                <option value="">Všetky</option>
                <option value="mier">Mier</option>
                <option value="literatúra">Literatúra</option>
                <option value="medicína">Medicína</option>
                <option value="fyzika">Fyzika</option>
                <option value="chémia">Chémia</option>
            </select>
        </div>
        <div>
            <label for="year" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Rok:</label>
            <select id="year" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                <option value="">Všetky</option>
            </select>
        </div>
        <div>
            <label for="pageLength" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Záznamy na stránke:</label>
            <select id="pageLength" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="-1">Všetky</option>
            </select>
        </div>
    </div>

    <div class="overflow-x-auto bg-white dark:bg-gray-800 rounded-lg shadow">
        <table id="nobelTable" class="w-full">
            <thead class="bg-gray-800 text-white dark:bg-gray-950">
            <tr>
                <th class="p-3 text-left">Laureát</th>
                <th class="p-3 text-left year-col">Rok</th>
                <th class="p-3 text-left category-col">Kategória</th>
                <th class="p-3 text-left">Krajina</th>
                <th class="p-3 text-left">Príspevok</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
            <?php
            require_once '../../config.php';
            $db = connectDatabase($hostname, $database, $username, $password);

            $stmt = $db->query("SELECT 
                                        COALESCE(NULLIF(fullname, ''), organisation) AS laureate, laureate_id,
                                        year, category, country, contribution_sk 
                                    FROM laureates 
                                    JOIN laureate_prizes ON laureates.id = laureate_prizes.laureate_id
                                    JOIN prizes ON laureate_prizes.prize_id = prizes.id");

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                echo "<tr class='hover:bg-gray-50 dark:hover:bg-gray-700'>";
                echo "<td class='p-3'><a href='detail.php?id={$row['laureate_id']}' class='text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300'>{$row['laureate']}</a></td>";
                echo "<td class='p-3 year-col'>{$row['year']}</td>";
                echo "<td class='p-3 category-col'>{$row['category']}</td>";
                echo "<td class='p-3'>{$row['country']}</td>";
                echo "<td class='p-3'>{$row['contribution_sk']}</td>";
                echo "</tr>";
            }
            ?>
            </tbody>
        </table>
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

        // Set cookie (expires in 30 days)
        document.cookie = `darkMode=${isDarkMode}; path=/; max-age=${30 * 24 * 60 * 60}`;

        // Toggle icons
        sunIcon.classList.toggle('hidden', !isDarkMode);
        moonIcon.classList.toggle('hidden', isDarkMode);
    });

    // Populate year dropdown (1901 - 2023)
    for (let i = 2023; i >= 1901; i--) {
        $('#year').append(`<option value="${i}">${i}</option>`);
    }

    $(document).ready(function() {
        var table = $('#nobelTable').DataTable({
            pageLength: 10,
            responsive: true,
            lengthChange: false,
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

        function updateTable() {
            let categoryVal = $('#category').val();
            let yearVal = $('#year').val();
            let categorySelected = categoryVal !== "";
            let yearSelected = yearVal !== "";

            table.column(2).visible(!categorySelected);
            table.column(1).visible(!yearSelected);

            table.column(2).search(categorySelected ? categoryVal : '');
            table.column(1).search(yearSelected ? yearVal : '');
            table.draw();
        }

        $('#category, #year').on('change', updateTable);

        $('#pageLength').on('change', function() {
            var length = parseInt($(this).val());
            table.page.len(length).draw();
        });
    });
</script>
</body>
</html>