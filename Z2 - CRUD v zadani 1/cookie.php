<?php
$cookieAccepted = isset($_COOKIE['cookies_accepted']) && $_COOKIE['cookies_accepted'] == 'true';
?>

<?php if (!$cookieAccepted): ?>
    <div id="cookie-banner" class="fixed bottom-0 left-0 right-0 mx-3 mb-3 bg-gray-200 dark:bg-gray-700 text-gray-900 dark:text-white p-4 text-center rounded-lg shadow-lg z-[1050]">
        Pre funkčnosť tejto stránky sa využívajú cookies.
        <button id="accept-cookies" class="ml-2 bg-green-600 dark:bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-700 dark:hover:bg-green-600 focus:outline-none focus:ring-2 focus:ring-green-500 dark:focus:ring-green-400">Zatvoriť</button>
    </div>

    <script>
        document.getElementById("accept-cookies").addEventListener("click", function() {
            document.getElementById("cookie-banner").style.display = "none";
            document.cookie = "cookies_accepted=true; path=/; max-age=" + (60 * 60 * 24 * 365); // 1 year
        });
    </script>
<?php endif; ?>