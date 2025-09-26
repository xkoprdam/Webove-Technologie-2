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
	<title>Vitajte - Nobelove ceny</title>
	<script src="https://cdn.tailwindcss.com"></script>
	<script>
		tailwind.config = {
			darkMode: 'class',
		};
	</script>
	<style>
		.square-link {
			width: 300px;
			height: 300px;
			display: flex;
			justify-content: center;
			align-items: center;
			text-align: center;
			transition: transform 0.2s ease-in-out, background-color 0.3s;
		}
		.square-link:hover {
			transform: scale(1.05);
		}
	</style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors duration-300 min-h-screen flex flex-col">
<!-- Navbar -->
<nav class="bg-gray-800 p-4 shadow-md dark:bg-gray-950">
	<div class="max-w-7xl mx-auto flex justify-between items-center">
		<a href="z1/index.php" class="text-white text-xl font-bold">Koreňová stránka pre WEBTE2</a>
		<div class="flex items-center space-x-4">
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

<!-- Main Content -->
<div class="flex-grow flex items-center justify-center py-12">
	<div class="max-w-7xl mx-auto px-4">
		<div class="grid grid-cols-1 md:grid-cols-2 gap-8 max-w-3xl mx-auto">
			<!-- Square Link 1 -->
			<a href="z1/index.php" class="square-link bg-indigo-600 dark:bg-indigo-500 text-white rounded-lg shadow-lg hover:bg-indigo-700 dark:hover:bg-indigo-600">
				<span class="text-2xl font-semibold">Zadanie 1<br>Nobelove ceny</span>
			</a>
			<!-- Square Link 2 -->
			<a href="z2/index.php" class="square-link bg-green-600 dark:bg-green-500 text-white rounded-lg shadow-lg hover:bg-green-700 dark:hover:bg-green-600">
				<span class="text-2xl font-semibold">Zadanie 2<br>API</span>
			</a>
			<!-- Square Link 3 -->
			<a href="z3" class="square-link bg-blue-600 dark:bg-blue-500 text-white rounded-lg shadow-lg hover:bg-blue-700 dark:hover:bg-blue-600">
				<span class="text-2xl font-semibold">Zadanie 3</span>
			</a>
			<!-- Square Link 4 -->
			<a href="z4/index.php" class="square-link bg-purple-600 dark:bg-purple-500 text-white rounded-lg shadow-lg hover:bg-purple-700 dark:hover:bg-purple-600">
				<span class="text-2xl font-semibold">Zadanie 4</span>
			</a>
		</div>
	</div>
</div>

<!-- Footer (optional) -->
<footer class="bg-gray-800 dark:bg-gray-950 text-white py-4">
	<div class="max-w-7xl mx-auto text-center">
		<p>&copy; <?php echo date('Y'); ?> Matej Koprda</p>
	</div>
</footer>

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
</script>
</body>
</html>