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
    <title>WEBTE2 Z2 - API</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
        };
    </script>
    <link rel="stylesheet" href="https://cdn.datatables.net/2.2.2/css/dataTables.tailwindcss.css">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
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
        .modal {
            transition: opacity 0.3s ease-in-out;
        }
        .modal-content {
            max-height: 80vh;
            overflow-y: auto;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300">

<!-- Navbar -->
<nav class="bg-gray-800 p-4 shadow-md dark:bg-gray-950">
    <div class="max-w-7xl mx-auto flex justify-between items-center">
        <a href="#" class="text-white text-xl font-bold">Zadanie 2 - API pre Nobelove ceny</a>
        <div class="flex items-center space-x-4">
            <a href="docs.php" class="text-gray-300 hover:text-white">Dokumentácia</a>
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

<!-- Page content -->
<div class="max-w-7xl mx-auto mt-6 px-4">
    <?php
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        echo '<h2 class="text-2xl font-semibold text-gray-800 dark:text-gray-200">Vitaj ' . htmlspecialchars($_SESSION['fullname']) . '</h2>';
    }
    ?>

    <div class="mt-6 flex justify-end">
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) { ?>
            <button id="addLaureateBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Pridať laureáta</button>
        <?php } ?>
    </div>

    <div class="mt-6 flex justify-end space-x-4">
        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) { ?>
<!--            <button id="addLaureateBtn" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700">Pridať laureáta</button>-->
            <form id="uploadLaureatesForm" enctype="multipart/form-data" class="flex items-center">
                <input type="file" id="jsonFile" name="jsonFile" accept=".json" class="p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100">
                <button type="submit" class="ml-2 px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">Nahrať JSON</button>
            </form>
        <?php } ?>
    </div>

<!--Filtre-->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
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
            <label for="countryFilter" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Krajina:</label>
            <input type="text" id="countryFilter" class="w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100" placeholder="Zadaj krajinu">
        </div>
        <div class="flex items-end">
            <button id="submitFilter" class="w-full p-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500">Hľadať</button>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
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

<!--Tabulka-->
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
            </tbody>
        </table>
    </div>
</div>

<!-- Edit Laureate Modal -->
<div id="editModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="modal-content bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Upraviť laureáta</h2>
        <form id="editLaureateForm">
            <input type="hidden" name="laureate_id" id="laureateId">
            <div class="mb-4">
                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Typ:</label>
                <select name="type" id="type" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
                    <option value="clovek">Človek</option>
                    <option value="organizacia">Organizácia</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meno:</label>
                <input type="text" name="name" id="name" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div id="genderDiv" class="mb-4 hidden">
                <label for="gender" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pohlavie:</label>
                <select name="gender" id="gender" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
                    <option value="muz">Muž</option>
                    <option value="zena">Žena</option>
                    <option value="ine">Iné</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="birth_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rok narodenia:</label>
                <input type="number" name="birth_year" id="birth_year" min="1800" max="2025" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div id="deathYearDiv" class="mb-4 hidden">
                <label for="death_year" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rok úmrtia:</label>
                <input type="number" name="death_year" id="death_year" min="1800" max="2025" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="country" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Krajina:</label>
                <input type="text" name="country" id="country" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" id="deleteLaureate" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">Vymazať</button>
                <button type="button" id="closeModal" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">Zrušiť</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Uložiť</button>
            </div>
        </form>
    </div>
</div>

<!-- Add Laureate Modal -->
<div id="addModal" class="modal fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
    <div class="modal-content bg-white dark:bg-gray-800 p-6 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-xl font-semibold text-gray-800 dark:text-gray-200 mb-4">Pridať laureáta</h2>
        <form id="addLaureateForm">
            <div class="mb-4">
                <label for="addType" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Typ:</label>
                <select name="type" id="addType" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
                    <option value="clovek">Človek</option>
                    <option value="organizacia">Organizácia</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="addName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Meno:</label>
                <input type="text" name="name" id="addName" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addGender" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pohlavie:</label>
                <select name="gender" id="addGender" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
                    <option value="muz">Muž</option>
                    <option value="zena">Žena</option>
                    <option value="ine">Iné</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="addBirthYear" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rok narodenia:</label>
                <input type="number" name="birth_year" id="addBirthYear" min="1800" max="2025" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addDeathYear" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rok úmrtia:</label>
                <input type="number" name="death_year" id="addDeathYear" min="1800" max="2025" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addCountry" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Krajina:</label>
                <input type="text" name="country" id="addCountry" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addCategory" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Kategória:</label>
                <select name="category" id="addCategory" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
                    <option value="mier">Mier</option>
                    <option value="literatúra">Literatúra</option>
                    <option value="medicína">Medicína</option>
                    <option value="fyzika">Fyzika</option>
                    <option value="chémia">Chémia</option>
                </select>
            </div>
            <div class="mb-4">
                <label for="addYear" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rok:</label>
                <input type="number" name="year" id="addYear" min="1901" max="2025" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addContributionSk" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Príspevok (SK):</label>
                <textarea name="contribution_sk" id="addContributionSk" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <div class="mb-4">
                <label for="addContributionEn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Príspevok (EN):</label>
                <textarea name="contribution_en" id="addContributionEn" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <div class="mb-4">
                <label for="addLanguageSk" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jazyk (SK):</label>
                <input type="text" name="language_sk" id="addLanguageSk" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addLanguageEn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jazyk (EN):</label>
                <input type="text" name="language_en" id="addLanguageEn" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addGenreSk" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Žáner (SK):</label>
                <input type="text" name="genre_sk" id="addGenreSk" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="mb-4">
                <label for="addGenreEn" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Žáner (EN):</label>
                <input type="text" name="genre_en" id="addGenreEn" class="mt-1 w-full p-2 border border-gray-300 dark:border-gray-600 rounded-md bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100 focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="flex justify-end space-x-2">
                <button type="button" id="closeAddModal" class="px-4 py-2 bg-gray-300 dark:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-md hover:bg-gray-400 dark:hover:bg-gray-500">Zrušiť</button>
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700">Pridať</button>
            </div>
        </form>
    </div>
</div>

<script>
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

    for (let i = 2023; i >= 1901; i--) {
        $('#year').append(`<option value="${i}">${i}</option>`);
    }

    $(document).ready(function() {
        // Function to load table data with filters
        function loadTable() {
            const category = $('#category').val();
            const year = $('#year').val();
            const country = $('#countryFilter').val();

            $.ajax({
                url: 'https://node65.webte.fei.stuba.sk/z2/api/v0/prizes',
                method: 'GET',
                data: {
                    category: category,
                    year: year,
                    country: country
                },
                dataType: 'json',
                success: function(data) {
                    if ($.fn.DataTable.isDataTable('#nobelTable')) {
                        $('#nobelTable').DataTable().destroy();
                    }

                    var table = $('#nobelTable').DataTable({
                        data: data,
                        columns: [
                            {
                                data: null,
                                render: function(data, type, row) {
                                    const laureateName = row.fullname && row.fullname.trim() !== '' ? row.fullname : row.organisation;
                                    const filterParams = [];
                                    if (category) filterParams.push(`category=${encodeURIComponent(category)}`);
                                    if (year) filterParams.push(`year=${encodeURIComponent(year)}`);
                                    if (country) filterParams.push(`country=${encodeURIComponent(country)}`);
                                    const queryString = filterParams.length ? '?' + filterParams.join('&') : '';
                                    return `<div class='p-3 flex items-center space-x-2'>
                                        <a href='detail.php?id=${row.laureate_id}${queryString}' class='text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300'>${laureateName}</a>
                                        <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) { ?>
                                            <button class='edit-laureate text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200' data-id='${row.laureate_id}'>
                                                <svg class='w-4 h-4' fill='none' stroke='currentColor' viewBox='0 0 24 24' xmlns='http://www.w3.org/2000/svg'>
                                                    <path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z'></path>
                                                </svg>
                                            </button>
                                        <?php } ?>
                                    </div>`;
                                }
                            },
                            { data: 'year', className: 'p-3 year-col' },
                            { data: 'category', className: 'p-3 category-col' },
                            { data: 'country', className: 'p-3' },
                            { data: 'contribution_sk', className: 'p-3' }
                        ],
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

                    $('#pageLength').on('change', function() {
                        var length = parseInt($(this).val());
                        table.page.len(length).draw();
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching data:', error);
                    $('#nobelTable tbody').html('<tr><td colspan="5" class="p-3 text-center">Chyba pri načítaní dát</td></tr>');
                }
            });
        }

        // Initial table load
        loadTable();

        // Reload table with filters on submit button click
        $('#submitFilter').on('click', function() {
            loadTable();
        });

        const editModal = $('#editModal');
        const closeModalBtn = $('#closeModal');
        const typeSelect = $('#type');
        const genderDiv = $('#genderDiv');
        const deathYearDiv = $('#deathYearDiv');
        const editFormFields = {
            name: $('#name'),
            type: typeSelect,
            gender: $('#gender'),
            birth_year: $('#birth_year'),
            death_year: $('#death_year'),
            country: $('#country')
        };

        function updateEditFormFields() {
            const isClovek = typeSelect.val() === 'clovek';
            genderDiv.toggleClass('hidden', !isClovek);
            deathYearDiv.toggleClass('hidden', !isClovek);
            validateEditField('gender');
            validateEditField('death_year');
        }

        function validateEditField(fieldName) {
            const field = editFormFields[fieldName];
            const value = field.val() ? field.val().trim() : '';
            const isClovek = typeSelect.val() === 'clovek';
            let errorMessage = '';

            field.removeClass('border-red-500');
            field.next('.error-message').remove();

            switch (fieldName) {
                case 'name':
                    if (!value) errorMessage = 'Meno je povinné.';
                    else if (!/^[a-zA-ZÀ-ž\s-]+$/.test(value)) errorMessage = 'Meno môže obsahovať iba písmená a pomlčky.';
                    break;
                case 'type':
                    if (!value) errorMessage = 'Typ je povinný.';
                    break;
                case 'gender':
                    if (isClovek && !value) errorMessage = 'Pohlavie je povinné pre človeka.';
                    break;
                case 'birth_year':
                    if (value) {
                        const birth = parseInt(value);
                        if (isNaN(birth) || birth < 1800 || birth > 2025) errorMessage = 'Rok narodenia musí byť medzi 1800 a 2025.';
                    }
                    break;
                case 'death_year':
                    if (isClovek && value) {
                        const death = parseInt(value);
                        const birth = parseInt(editFormFields.birth_year.val()) || 0;
                        if (isNaN(death) || death < 1800 || death > 2025) errorMessage = 'Rok úmrtia musí byť medzi 1800 a 2025.';
                        else if (birth && death < birth) errorMessage = 'Rok úmrtia nemôže byť pred rokom narodenia.';
                    }
                    break;
                case 'country':
                    if (!value) errorMessage = 'Krajina je povinná.';
                    break;
            }

            if (errorMessage) {
                field.addClass('border-red-500');
                field.after(`<p class="error-message text-red-500 text-sm mt-1">${errorMessage}</p>`);
                return false;
            }
            return true;
        }

        function validateEditForm() {
            return Object.keys(editFormFields).every(field => validateEditField(field));
        }

        Object.values(editFormFields).forEach(field => {
            field.on('input change', function() {
                validateEditField($(this).attr('id'));
            });
        });
        typeSelect.on('change', updateEditFormFields);

        $(document).on('click', '.edit-laureate', function() {
            const id = $(this).data('id');
            $.ajax({
                url: `https://node65.webte.fei.stuba.sk/z2/api/v0/laureates/${id}`,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const isClovek = !data.organisation || data.organisation === '';
                    const nameValue = isClovek ? (data.fullname || '') : (data.organisation || '');
                    const genderValue = data.sex === 'M' ? 'muz' : (data.sex === 'F' ? 'zena' : 'ine');

                    $('#laureateId').val(id);
                    editFormFields.name.val(nameValue);
                    editFormFields.type.val(isClovek ? 'clovek' : 'organizacia');
                    editFormFields.gender.val(genderValue);
                    editFormFields.birth_year.val(data.birth_year || '');
                    editFormFields.death_year.val(data.death_year || '');
                    editFormFields.country.val(data.country || '');

                    updateEditFormFields();
                    Object.keys(editFormFields).forEach(validateEditField);
                    editModal.removeClass('hidden');
                },
                error: function(xhr, status, error) {
                    alert('Chyba pri načítaní údajov laureáta: ' + error);
                }
            });
        });

        closeModalBtn.on('click', function() {
            editModal.addClass('hidden');
        });

        $(window).on('click', function(event) {
            if (event.target === editModal[0]) {
                editModal.addClass('hidden');
            }
        });

        $('#editLaureateForm').on('submit', function(e) {
            e.preventDefault();
            if (validateEditForm()) {
                const isClovek = editFormFields.type.val() === 'clovek';
                const nameValue = editFormFields.name.val().trim();
                const laureateId = $('#laureateId').val();

                const formData = {
                    fullname: isClovek ? nameValue : null,
                    organisation: isClovek ? null : nameValue,
                    sex: editFormFields.gender.val() === 'muz' ? 'M' : (editFormFields.gender.val() === 'zena' ? 'F' : 'I'),
                    birth_year: editFormFields.birth_year.val() ? parseInt(editFormFields.birth_year.val()) : null,
                    death_year: editFormFields.death_year.val() ? parseInt(editFormFields.death_year.val()) : null,
                    country: editFormFields.country.val().trim()
                };

                $.ajax({
                    url: `https://node65.webte.fei.stuba.sk/z2/api/v0/laureates/${laureateId}`,
                    method: 'PUT',
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    success: function(response) {
                        alert('Laureát bol úspešne aktualizovaný!');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Chyba pri aktualizácii: ' + error);
                    }
                });
            }
        });

        $('#deleteLaureate').on('click', function() {
            const laureateId = $('#laureateId').val();
            if (confirm('Naozaj chcete vymazať tohto laureáta?')) {
                $.ajax({
                    url: `https://node65.webte.fei.stuba.sk/z2/api/v0/laureates/${laureateId}`,
                    method: 'DELETE',
                    success: function(response) {
                        alert('Laureát bol úspešne vymazaný!');
                        editModal.addClass('hidden');
                        location.reload();
                    },
                    error: function(xhr, status, error) {
                        alert('Chyba pri vymazaní laureáta: ' + error);
                    }
                });
            }
        });

        // Add Modal Logic
        const addModal = $('#addModal');
        const addLaureateBtn = $('#addLaureateBtn');
        const closeAddModalBtn = $('#closeAddModal');
        const addFormFields = {
            name: $('#addName'),
            type: $('#addType'),
            gender: $('#addGender'),
            birth_year: $('#addBirthYear'),
            death_year: $('#addDeathYear'),
            country: $('#addCountry'),
            category: $('#addCategory'),
            year: $('#addYear'),
            contribution_sk: $('#addContributionSk'),
            contribution_en: $('#addContributionEn'),
            language_sk: $('#addLanguageSk'),
            language_en: $('#addLanguageEn'),
            genre_sk: $('#addGenreSk'),
            genre_en: $('#addGenreEn')
        };

        function validateAddField(fieldName) {
            const field = addFormFields[fieldName];
            const value = field.val() ? field.val().trim() : '';
            let errorMessage = '';

            field.removeClass('border-red-500');
            field.next('.error-message').remove();

            switch (fieldName) {
                case 'name':
                    if (!value) errorMessage = 'Meno je povinné.';
                    else if (!/^[a-zA-ZÀ-ž\s-]+$/.test(value)) errorMessage = 'Meno môže obsahovať iba písmená a pomlčky.';
                    break;
                case 'type':
                    if (!value) errorMessage = 'Typ je povinný.';
                    break;
                case 'country':
                    if (!value) errorMessage = 'Krajina je povinná.';
                    break;
                case 'category':
                    if (!value) errorMessage = 'Kategória je povinná.';
                    break;
                case 'year':
                    if (!value) errorMessage = 'Rok je povinný.';
                    else {
                        const year = parseInt(value);
                        if (isNaN(year) || year < 1901 || year > 2025) errorMessage = 'Rok musí byť medzi 1901 a 2025.';
                    }
                    break;
                case 'contribution_sk':
                    if (!value) errorMessage = 'Príspevok (SK) je povinný.';
                    break;
                case 'contribution_en':
                    if (!value) errorMessage = 'Príspevok (EN) je povinný.';
                    break;
                case 'birth_year':
                    if (value) {
                        const birth = parseInt(value);
                        if (isNaN(birth) || birth < 1800 || birth > 2025) errorMessage = 'Rok narodenia musí byť medzi 1800 a 2025.';
                    }
                    break;
                case 'death_year':
                    if (value) {
                        const death = parseInt(value);
                        const birth = parseInt(addFormFields.birth_year.val()) || 0;
                        if (isNaN(death) || death < 1800 || death > 2025) errorMessage = 'Rok úmrtia musí byť medzi 1800 a 2025.';
                        else if (birth && death < birth) errorMessage = 'Rok úmrtia nemôže byť pred rokom narodenia.';
                    }
                    break;
            }

            if (errorMessage) {
                field.addClass('border-red-500');
                field.after(`<p class="error-message text-red-500 text-sm mt-1">${errorMessage}</p>`);
                return false;
            }
            return true;
        }

        function validateAddForm() {
            const requiredFields = ['name', 'type', 'country', 'category', 'year', 'contribution_sk', 'contribution_en'];
            const optionalFields = ['gender', 'birth_year', 'death_year', 'language_sk', 'language_en', 'genre_sk', 'genre_en'];
            return requiredFields.every(field => validateAddField(field)) &&
                optionalFields.every(field => validateAddField(field));
        }

        Object.values(addFormFields).forEach(field => {
            field.on('input change', function() {
                validateAddField($(this).attr('id'));
            });
        });

        addLaureateBtn.on('click', function() {
            // Reset form fields
            Object.values(addFormFields).forEach(field => field.val(''));
            addModal.removeClass('hidden');
        });

        closeAddModalBtn.on('click', function() {
            addModal.addClass('hidden');
        });

        $(window).on('click', function(event) {
            if (event.target === addModal[0]) {
                addModal.addClass('hidden');
            }
        });

        $('#addLaureateForm').on('submit', function(e) {
            e.preventDefault();
            if (validateAddForm()) {
                const isClovek = addFormFields.type.val() === 'clovek';
                const nameValue = addFormFields.name.val().trim();

                const formData = {
                    fullname: isClovek ? nameValue : null,
                    organisation: isClovek ? null : nameValue,
                    sex: addFormFields.gender.val() === 'muz' ? 'M' : (addFormFields.gender.val() === 'zena' ? 'F' : (addFormFields.gender.val() === 'ine' ? 'I' : null)),
                    birth_year: addFormFields.birth_year.val() ? parseInt(addFormFields.birth_year.val()) : null,
                    death_year: addFormFields.death_year.val() ? parseInt(addFormFields.death_year.val()) : null,
                    country: addFormFields.country.val().trim(),
                    prize: {
                        category: addFormFields.category.val(),
                        year: parseInt(addFormFields.year.val()),
                        contribution_sk: addFormFields.contribution_sk.val().trim(),
                        contribution_en: addFormFields.contribution_en.val().trim(),
                        language_sk: addFormFields.language_sk.val() ? addFormFields.language_sk.val().trim() : null,
                        language_en: addFormFields.language_en.val() ? addFormFields.language_en.val().trim() : null,
                        genre_sk: addFormFields.genre_sk.val() ? addFormFields.genre_sk.val().trim() : null,
                        genre_en: addFormFields.genre_en.val() ? addFormFields.genre_en.val().trim() : null
                    }
                };

                $.ajax({
                    url: 'https://node65.webte.fei.stuba.sk/z2/api/v0/laureate',
                    method: 'POST',
                    data: JSON.stringify(formData),
                    contentType: 'application/json',
                    success: function(response) {
                        alert('Laureát bol úspešne pridaný!');
                        addModal.addClass('hidden');
                        loadTable();
                    },
                    error: function(xhr, status, error) {
                        alert('Chyba pri pridávaní laureáta: ' + error);
                    }
                });
            }
        });

        $('#uploadLaureatesForm').on('submit', function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('jsonFile');
            if (!fileInput.files.length) {
                alert('Prosím, vyberte JSON súbor.');
                return;
            }

            const formData = new FormData();
            formData.append('jsonFile', fileInput.files[0]);

            $.ajax({
                url: 'https://node65.webte.fei.stuba.sk/z2/api/v0/laureates',
                method: 'POST',
                data: formData,
                contentType: false, // Let browser set multipart/form-data
                processData: false, // Prevent jQuery from processing the data
                success: function(response) {
                    alert('Laureáti boli úspešne nahratí!');
                    loadTable(); // Reload table to reflect new data
                },
                error: function(xhr, status, error) {
                    alert('Chyba pri nahrávaní laureátov: ' + error);
                    console.log(xhr.responseText);
                }
            });
        });
    });
</script>


</body>
</html>