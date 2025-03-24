<?php
//include 'cookie.php';
//session_start();
//
//// Restrict access to logged-in users only
//if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
//    header("Location: login.php");
//    exit;
//}
//
//require_once '../../config.php';
//$db = connectDatabase($hostname, $database, $username, $password);
//
//// Fetch laureates for dropdown
//$stmt = $db->query("SELECT id, COALESCE(NULLIF(fullname, ''), organisation) AS name FROM laureates ORDER BY name");
//$laureates = $stmt->fetchAll(PDO::FETCH_ASSOC);
//
//// Function to fetch laureate details
//function getLaureateDetails($db, $id) {
//    $stmt = $db->prepare("SELECT fullname, organisation, sex, birth_year, death_year, country FROM laureates WHERE id = :id");
//    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
//    $stmt->execute();
//    return $stmt->fetch(PDO::FETCH_ASSOC);
//}
//
//// Function to update laureate
//function updateLaureate($db, $id, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country) {
//    if (!$organisation) {
//        $fullname = $name . " " . $surname;
//        $stmt = $db->prepare("UPDATE laureates SET fullname = :fullname, organisation = NULL, sex = :sex, birth_year = :birth_year, death_year = :death_year, country = :country WHERE id = :id");
//        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
//    } else {
//        $stmt = $db->prepare("UPDATE laureates SET fullname = NULL, organisation = :organisation, sex = :sex, birth_year = :birth_year, death_year = :death_year, country = :country WHERE id = :id");
//        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
//    }
//    $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
//    $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
//    $stmt->bindParam(':death_year', $death_year, PDO::PARAM_STR);
//    $stmt->bindParam(':country', $country, PDO::PARAM_STR);
//    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
//
//    if ($stmt->execute()) {
//        return "Laureate updated successfully.";
//    } else {
//        return "Error updating laureate: " . implode(", ", $stmt->errorInfo());
//    }
//}
//
//// Handle form actions
//$laureateData = null;
//if (isset($_GET['laureate_id']) && !empty($_GET['laureate_id'])) {
//    $laureateData = getLaureateDetails($db, $_GET['laureate_id']);
//}
//
//if ($_SERVER["REQUEST_METHOD"] == "POST") {
//    $id = $_POST['laureate_id'];
//    $type = $_POST['type'];
//    $name = $_POST['name'] ?: NULL;
//    $surname = $_POST['surname'] ?: NULL;
//    $organisation = $_POST['organisation'] ?: NULL;
//    $sex = $_POST['sex'] ?: NULL;
//    $birth_year = $_POST['birth_year'];
//    $death_year = $_POST['death_year'] ?: NULL;
//    $country = $_POST['country'] ?: NULL;
//
//    // Validation
//    $errors = [];
//    if (!$birth_year) {
//        $errors[] = "Rok narodenia je povinný.";
//    }
//    if ($type === 'person') {
//        if (!$name || !$surname) {
//            $errors[] = "Meno a priezvisko sú povinné pre osobu.";
//        }
//        if (!$sex) {
//            $errors[] = "Pohlavie je povinné pre osobu.";
//        }
//    }
//    if ($type === 'organisation' && !$organisation) {
//        $errors[] = "Organizácia je povinná pre organizáciu.";
//    }
//
//    if (empty($errors)) {
//        $result = updateLaureate($db, $id, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country);
//    } else {
//        $result = "Error: " . implode(" ", $errors);
//    }
//}
//?>
<!---->
<!--<!DOCTYPE html>-->
<!--<html lang="sk">-->
<!--<head>-->
<!--    <meta charset="UTF-8">-->
<!--    <meta name="viewport" content="width=device-width, initial-scale=1.0">-->
<!--    <title>Upraviť laureáta</title>-->
<!--    <!-- Bootstrap CSS -->-->
<!--    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">-->
<!--    <style>-->
<!--        .edit-container {-->
<!--            max-width: 800px;-->
<!--            margin: 0 auto;-->
<!--            padding: 20px;-->
<!--        }-->
<!--        #person-fields, #organisation-fields {-->
<!--            display: none;-->
<!--        }-->
<!--    </style>-->
<!--</head>-->
<!--<body>-->
<!--<!-- Navbar -->-->
<!--<nav class="navbar navbar-expand-lg navbar-dark bg-dark">-->
<!--    <div class="container-fluid">-->
<!--        <a class="navbar-brand" href="index.php">Zadanie 1 - Nobelove ceny</a>-->
<!--        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"-->
<!--                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">-->
<!--            <span class="navbar-toggler-icon"></span>-->
<!--        </button>-->
<!--        <div class="collapse navbar-collapse" id="navbarNav">-->
<!--            <ul class="navbar-nav ms-auto">-->
<!--                <li class="nav-item">-->
<!--                    <a class="nav-link" href="restricted.php">Profil</a>-->
<!--                </li>-->
<!--                <li class="nav-item">-->
<!--                    <a class="nav-link" href="logout.php">Odhlásiť sa</a>-->
<!--                </li>-->
<!--            </ul>-->
<!--        </div>-->
<!--    </div>-->
<!--</nav>-->
<!---->
<!--<div class="container mt-5">-->
<!--    <div class="edit-container">-->
<!--        <h1 class="mb-3">Upraviť laureáta</h1>-->
<!--        <h2 class="mb-4 text-muted">Vyberte a upravte údaje laureáta</h2>-->
<!---->
<!--        --><?php //if (isset($result)): ?>
<!--            <div class="alert --><?php //= strpos($result, "Error") === false ? 'alert-success' : 'alert-danger' ?><!--" role="alert">-->
<!--                --><?php //= htmlspecialchars($result) ?>
<!--            </div>-->
<!--        --><?php //endif; ?>
<!---->
<!--        <form method="GET" class="mb-4">-->
<!--            <div class="row">-->
<!--                <div class="col-md-8">-->
<!--                    <label for="laureate_search" class="form-label">Hľadať laureáta:</label>-->
<!--                    <input type="text" class="form-control" id="laureate_search" placeholder="Zadajte meno alebo organizáciu">-->
<!--                </div>-->
<!--                <div class="col-md-8 mt-2">-->
<!--                    <label for="laureate_id" class="form-label">Vyberte laureáta: <span class="text-danger">*</span></label>-->
<!--                    <select class="form-select" id="laureate_id" name="laureate_id" onchange="this.form.submit()" required>-->
<!--                        <option value="">Vyberte laureáta</option>-->
<!--                        --><?php //foreach ($laureates as $laureate): ?>
<!--                            <option value="--><?php //= $laureate['id'] ?><!--" --><?php //= isset($_GET['laureate_id']) && $_GET['laureate_id'] == $laureate['id'] ? 'selected' : '' ?><!-->-->
<!--                                --><?php //= htmlspecialchars($laureate['name']) ?>
<!--                            </option>-->
<!--                        --><?php //endforeach; ?>
<!--                    </select>-->
<!--                </div>-->
<!--            </div>-->
<!--        </form>-->
<!---->
<!--        --><?php //if ($laureateData): ?>
<!--            <form method="POST">-->
<!--                <input type="hidden" name="laureate_id" value="--><?php //= $laureateData['id'] ?><!--">-->
<!--                <div class="row mb-3">-->
<!--                    <div class="col-md-4">-->
<!--                        <label for="type" class="form-label">Typ laureáta: <span class="text-danger">*</span></label>-->
<!--                        <select class="form-select" id="type" name="type" required>-->
<!--                            <option value="person" --><?php //= $laureateData['fullname'] ? 'selected' : '' ?><!-->Osoba</option>-->
<!--                            <option value="organisation" --><?php //= $laureateData['organisation'] ? 'selected' : '' ?><!-->Organizácia</option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                </div>-->
<!---->
<!--                <div id="person-fields" class="row mb-3">-->
<!--                    <div class="col-md-4">-->
<!--                        <label for="name" class="form-label">Meno: <span class="text-danger">*</span></label>-->
<!--                        <input type="text" class="form-control" id="name" name="name" value="--><?php //= $laureateData['fullname'] ? explode(' ', $laureateData['fullname'])[0] : '' ?><!--">-->
<!--                    </div>-->
<!--                    <div class="col-md-4">-->
<!--                        <label for="surname" class="form-label">Priezvisko: <span class="text-danger">*</span></label>-->
<!--                        <input type="text" class="form-control" id="surname" name="surname" value="--><?php //= $laureateData['fullname'] && count(explode(' ', $laureateData['fullname'])) > 1 ? implode(' ', array_slice(explode(' ', $laureateData['fullname']), 1)) : '' ?><!--">-->
<!--                    </div>-->
<!--                    <div class="col-md-4">-->
<!--                        <label for="sex" class="form-label">Pohlavie: <span class="text-danger">*</span></label>-->
<!--                        <select class="form-select" id="sex" name="sex" required>-->
<!--                            <option value="">-</option>-->
<!--                            <option value="M" --><?php //= $laureateData['sex'] === 'M' ? 'selected' : '' ?><!-->Muž</option>-->
<!--                            <option value="F" --><?php //= $laureateData['sex'] === 'F' ? 'selected' : '' ?><!-->Žena</option>-->
<!--                        </select>-->
<!--                    </div>-->
<!--                </div>-->
<!---->
<!--                <div id="organisation-fields" class="mb-3">-->
<!--                    <label for="organisation" class="form-label">Organizácia: <span class="text-danger">*</span></label>-->
<!--                    <input type="text" class="form-control" id="organisation" name="organisation" value="--><?php //= $laureateData['organisation'] ?><!--">-->
<!--                </div>-->
<!---->
<!--                <div class="row mb-3">-->
<!--                    <div class="col-md-6">-->
<!--                        <label for="birth_year" class="form-label">Rok narodenia: <span class="text-danger">*</span></label>-->
<!--                        <input type="number" class="form-control" id="birth_year" name="birth_year" min="1800" max="2025" value="--><?php //= $laureateData['birth_year'] ?><!--" required>-->
<!--                    </div>-->
<!--                    <div class="col-md-6">-->
<!--                        <label for="death_year" class="form-label">Rok úmrtia:</label>-->
<!--                        <input type="number" class="form-control" id="death_year" name="death_year" min="1800" max="2025" value="--><?php //= $laureateData['death_year'] ?><!--">-->
<!--                    </div>-->
<!--                </div>-->
<!---->
<!--                <div class="mb-3">-->
<!--                    <label for="country" class="form-label">Krajina:</label>-->
<!--                    <input type="text" class="form-control" id="country" name="country" value="--><?php //= $laureateData['country'] ?><!--">-->
<!--                </div>-->
<!---->
<!--                <button type="submit" class="btn btn-primary w-100">Uložiť zmeny</button>-->
<!--            </form>-->
<!--        --><?php //endif; ?>
<!---->
<!--        <p class="mt-4">-->
<!--            <a href="index.php" class="btn btn-secondary">Späť na zoznam</a>-->
<!--        </p>-->
<!--    </div>-->
<!--</div>-->
<!---->
<!--<!-- Bootstrap JS -->-->
<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>-->
<!--<script>-->
<!--    document.addEventListener('DOMContentLoaded', function() {-->
<!--        const typeSelect = document.getElementById('type');-->
<!--        const personFields = document.getElementById('person-fields');-->
<!--        const organisationFields = document.getElementById('organisation-fields');-->
<!--        const searchInput = document.getElementById('laureate_search');-->
<!--        const laureateSelect = document.getElementById('laureate_id');-->
<!---->
<!--        // Initial check for type fields-->
<!--        toggleTypeFields();-->
<!---->
<!--        // Event listeners-->
<!--        typeSelect.addEventListener('change', toggleTypeFields);-->
<!--        searchInput.addEventListener('input', filterLaureates);-->
<!---->
<!--        function toggleTypeFields() {-->
<!--            if (typeSelect.value === 'person') {-->
<!--                personFields.style.display = 'flex'; // Use flex for row layout-->
<!--                organisationFields.style.display = 'none';-->
<!--            } else if (typeSelect.value === 'organisation') {-->
<!--                personFields.style.display = 'none';-->
<!--                organisationFields.style.display = 'block';-->
<!--            }-->
<!--        }-->
<!---->
<!--        function filterLaureates() {-->
<!--            const searchTerm = searchInput.value.toLowerCase();-->
<!--            const options = laureateSelect.getElementsByTagName('option');-->
<!---->
<!--            for (let i = 1; i < options.length; i++) { // Skip the first "Vyberte laureáta" option-->
<!--                const text = options[i].text.toLowerCase();-->
<!--                options[i].style.display = text.includes(searchTerm) ? '' : 'none';-->
<!--            }-->
<!--        }-->
<!--    });-->
<!--</script>-->
<!---->
<!--</body>-->
<!--</html>-->