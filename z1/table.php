<?php
include 'cookie.php';
session_start(); // Start session to track login status
?>

<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nobelova cena</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- AG Grid CSS -->
    <link rel="stylesheet" href="https://unpkg.com/ag-grid-community@31.0.2/styles/ag-grid.css">
    <link rel="stylesheet" href="https://unpkg.com/ag-grid-community@31.0.2/styles/ag-theme-quartz.css">
    <style>
        #nobelGrid {
            height: 500px;
            width: 100%;
        }
        #nobelGrid.ag-theme-quartz {
            --ag-accent-color: #34A131;
            --ag-background-color: #ffffff;
            --ag-border-color: transparent;
            --ag-font-family: Arial;
            --ag-foreground-color: rgb(46, 55, 66);
            --ag-header-background-color: #F9FAFB;
            --ag-header-font-size: 14px;
            --ag-header-font-weight: 600;
            --ag-header-foreground-color: #919191;
            --ag-odd-row-background-color: #F9FAFB;
            --ag-row-border-color: transparent;
            --ag-panel-border-color: transparent;
            --ag-grid-size: 8px;
            --ag-border-radius: 0px;
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

    <!-- AG Grid Container -->
    <div id="nobelGrid" class="ag-theme-quartz"></div>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AG Grid JS -->
<script src="https://unpkg.com/ag-grid-community@31.0.2/dist/ag-grid-community.min.noStyle.js"></script>

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
                'laureate' => htmlspecialchars($row['laureate']),
                'laureate_id' => htmlspecialchars($row['laureate_id']),
                'year' => htmlspecialchars($row['year']),
                'category' => htmlspecialchars($row['category']),
                'country' => htmlspecialchars($row['country']),
                'contribution_sk' => htmlspecialchars($row['contribution_sk'])
            ];
        }
        echo json_encode($rows);
        ?>;

    // Column Definitions for AG Grid
    const columnDefs = [
        {
            field: 'laureate',
            headerName: 'Laureát',
            sortable: true,
            filter: true,
            cellRenderer: params => `<a href="detail.php?id=${params.data.laureate_id}">${params.value}</a>`
        },
        { field: 'year', headerName: 'Rok', sortable: true, filter: true },
        { field: 'category', headerName: 'Kategória', sortable: true, filter: true },
        { field: 'country', headerName: 'Krajina', sortable: true, filter: true },
        { field: 'contribution_sk', headerName: 'Príspevok', sortable: true, filter: true }
    ];

    // Grid Options
    const gridOptions = {
        columnDefs: columnDefs,
        rowData: initialData,
        pagination: true,
        paginationPageSize: 10,
        paginationPageSizeSelector: [10, 25, 50, -1],
        defaultColDef: {
            resizable: true,
            filter: true
        },
        onGridReady: (params) => {
            // No need to assign gridApi here; it's returned by createGrid
            applyFilters();
        }
    };

    // Initialize AG Grid with createGrid
    let gridApi;
    document.addEventListener('DOMContentLoaded', () => {
        const gridDiv = document.querySelector('#nobelGrid');
        gridApi = agGrid.createGrid(gridDiv, gridOptions); // Use createGrid instead of new Grid
    });

    // Apply filters based on dropdowns
    function applyFilters() {
        const categoryVal = document.getElementById('category').value;
        const yearVal = document.getElementById('year').value;

        gridApi.setFilterModel({
            category: categoryVal ? { filterType: 'text', type: 'equals', filter: categoryVal } : null,
            year: yearVal ? { filterType: 'text', type: 'equals', filter: yearVal } : null
        });
    }

    // Event listeners for filter dropdowns and page size
    document.getElementById('category').addEventListener('change', applyFilters);
    document.getElementById('year').addEventListener('change', applyFilters);
    document.getElementById('pageLength').addEventListener('change', (e) => {
        const pageSize = parseInt(e.target.value);
        gridApi.paginationSetPageSize(pageSize === -1 ? initialData.length : pageSize);
    });
</script>
</body>
</html>