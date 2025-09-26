<?php
include 'cookie.php';
session_start();
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nobelova cena</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Handsontable CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@handsontable/horizon-theme/dist/horizon.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.css">
    <style>
        #nobelGrid {
            height: 500px;
            width: 100%;
        }
    </style>
</head>
<body>
<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container-fluid">
        <a class="navbar-brand" href="#">Zadanie 1 - Nobelove ceny</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <?php
                    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                        echo '<a class="nav-link" href="restricted.php">Profil</a>';
                    } else {
                        echo '<a class="nav-link" href="register.php">Registrovať</a>';
                    }
                    ?>
                </li>
                <li class="nav-item">
                    <?php
                    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
                        echo '<a class="nav-link" href="logout.php">Odhlásiť sa</a>';
                    } else {
                        echo '<a class="nav-link" href="login.php">Prihlásiť sa</a>';
                    }
                    ?>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container mt-4">
    <?php
    if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
        echo '<h2>Vitaj ' . htmlspecialchars($_SESSION['fullname']) . ' </h2>';
    }
    ?>
    <br>

    <div class="row mb-4">
        <div class="col-md-4 mb-3">
            <label for="category" class="form-label">Kategória:</label>
            <select id="category" class="form-select">
                <option value="">Všetky</option>
                <option value="mier">Mier</option>
                <option value="literatúra">Literatúra</option>
                <option value="medicína">Medicína</option>
                <option value="fyzika">Fyzika</option>
                <option value="chémia">Chémia</option>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="year" class="form-label">Rok:</label>
            <select id="year" class="form-select">
                <option value="">Všetky</option>
                <?php
                for ($i = 2023; $i >= 1900; $i--) {
                    echo "<option value=\"$i\">$i</option>";
                }
                ?>
            </select>
        </div>
        <div class="col-md-4 mb-3">
            <label for="pageLength" class="form-label">Záznamy na stránku:</label>
            <select id="pageLength" class="form-select">
                <option value="10">10</option>
                <option value="25">25</option>
                <option value="50">50</option>
                <option value="-1">Všetky</option>
            </select>
        </div>
    </div>

    <!-- Handsontable Container -->
    <div id="nobelGrid" class="horizon-theme"></div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- Handsontable JS -->
<script src="https://cdn.jsdelivr.net/npm/handsontable/dist/handsontable.full.min.js"></script>

<script>
    // Fetch initial data from PHP
    const initialData = <?php
        require_once '../../config.php';
        $db = connectDatabase($hostname, $database, $username, $password);
        $stmt = $db->query("SELECT 
                                COALESCE(NULLIF(fullname, ''), organisation) AS laureate, laureate_id,
                                year, category, country, contribution_sk 
                            FROM laureates 
                            JOIN laureate_prizes ON laureates.id = laureate_prizes.laureate_id
                            JOIN prizes ON laureate_prizes.prize_id = prizes.id");
        $rows = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $rows[] = [
                htmlspecialchars($row['laureate']),
                htmlspecialchars($row['year']),
                htmlspecialchars($row['category']),
                htmlspecialchars($row['country']),
                htmlspecialchars($row['contribution_sk']),
                htmlspecialchars($row['laureate_id'])
            ];
        }
        echo json_encode($rows);
        ?>;

    // Initialize Handsontable
    let hot;
    document.addEventListener('DOMContentLoaded', () => {
        const container = document.getElementById('nobelGrid');

        hot = new Handsontable(container, {
            data: initialData,
            colHeaders: ['Laureát', 'Rok', 'Kategória', 'Krajina', 'Príspevok'],
            className: 'htHorizon',
            columns: [
                {
                    renderer: (instance, td, row, col, prop, value, cellProperties) => {
                        const laureateId = instance.getDataAtRowProp(row, 5);
                        td.innerHTML = `<a href="detail.php?id=${laureateId}">${value}</a>`;
                        return td;
                    }
                },
                {},
                {},
                {},
                {},
                { hidden: true } // Hide laureate_id column
            ],
            stretchH: 'all',
            height: '500px',
            licenseKey: 'non-commercial-and-evaluation',
            filters: true,
            dropdownMenu: true,
            columnSorting: true,
            manualColumnResize: true,
            pageSize: 10
        });

        applyFilters();
    });

    // Apply filters based on dropdowns
    function applyFilters() {
        const categoryVal = document.getElementById('category').value;
        const yearVal = document.getElementById('year').value;

        hot.getPlugin('filters').clearConditions();

        if (categoryVal) {
            hot.getPlugin('filters').addCondition(2, 'eq', [categoryVal]);
        }
        if (yearVal) {
            hot.getPlugin('filters').addCondition(1, 'eq', [yearVal]);
        }

        hot.getPlugin('filters').filter();
        hot.render();
    }

    // Event listeners for filter dropdowns and page size
    document.getElementById('category').addEventListener('change', applyFilters);
    document.getElementById('year').addEventListener('change', applyFilters);
    document.getElementById('pageLength').addEventListener('change', (e) => {
        const pageSize = parseInt(e.target.value);
        const newData = pageSize === -1 ? initialData : initialData.slice(0, pageSize);
        hot.loadData(newData);
        applyFilters();
    });
</script>
</body>
</html>