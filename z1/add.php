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
//// Include the insertRow function and dependencies
//function processStatement($stmt) {
//    if ($stmt->execute()) {
//        return "Record inserted successfully.";
//    } else {
//        return "Error inserting record: " . implode(", ", $stmt->errorInfo());
//    }
//}
//
//function insertLaureate($db, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country) {
//    if (!$organisation) {
//        $fullname = $name . " " . $surname;
//        $stmt = $db->prepare("SELECT id FROM laureates WHERE fullname = :fullname AND birth_year = :birth_year");
//        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
//        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
//        $stmt->execute();
//
//        $laureate = $stmt->fetch(PDO::FETCH_ASSOC);
//        if ($laureate) {
//            return $laureate['id'];
//        }
//
//        $stmt = $db->prepare("INSERT INTO laureates (fullname, sex, birth_year, death_year, country) VALUES (:fullname, :sex, :birth_year, :death_year, :country)");
//        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
//        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
//        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
//        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_STR);
//        $stmt->bindParam(':country', $country, PDO::PARAM_STR);
//    } else {
//        $stmt = $db->prepare("SELECT id FROM laureates WHERE organisation = :organisation");
//        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
//        $stmt->execute();
//
//        $laureate = $stmt->fetch(PDO::FETCH_ASSOC);
//        if ($laureate) {
//            return $laureate['id'];
//        }
//
//        $stmt = $db->prepare("INSERT INTO laureates (organisation, sex, birth_year, death_year, country) VALUES (:organisation, :sex, :birth_year, :death_year, :country)");
//        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
//        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
//        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
//        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_STR);
//        $stmt->bindParam(':country', $country, PDO::PARAM_STR);
//    }
//
//    if ($stmt->execute()) {
//        return $db->lastInsertId();
//    } else {
//        return false;
//    }
//}
//
//function insertDetails($db, $language_sk, $language_en, $genre_sk, $genre_en) {
//    $stmt = $db->prepare("SELECT id FROM prize_details WHERE language_sk = :language_sk AND language_en = :language_en AND genre_sk = :genre_sk AND genre_en = :genre_en");
//    $stmt->bindParam(':language_sk', $language_sk, PDO::PARAM_STR);
//    $stmt->bindParam(':language_en', $language_en, PDO::PARAM_STR);
//    $stmt->bindParam(':genre_sk', $genre_sk, PDO::PARAM_STR);
//    $stmt->bindParam(':genre_en', $genre_en, PDO::PARAM_STR);
//    $stmt->execute();
//
//    $details = $stmt->fetch(PDO::FETCH_ASSOC);
//    if ($details) {
//        return $details['id'];
//    }
//
//    $stmt = $db->prepare("INSERT INTO prize_details (language_sk, language_en, genre_sk, genre_en) VALUES (:language_sk, :language_en, :genre_sk, :genre_en)");
//    $stmt->bindParam(':language_sk', $language_sk, PDO::PARAM_STR);
//    $stmt->bindParam(':language_en', $language_en, PDO::PARAM_STR);
//    $stmt->bindParam(':genre_sk', $genre_sk, PDO::PARAM_STR);
//    $stmt->bindParam(':genre_en', $genre_en, PDO::PARAM_STR);
//
//    if ($stmt->execute()) {
//        return $db->lastInsertId();
//    } else {
//        return false;
//    }
//}
//
//function insertPrize($db, $year, $category, $contribution_sk, $contribution_en, $details_id = NULL) {
//    $stmt = $db->prepare("SELECT id FROM prizes WHERE year = :year AND category = :category AND contribution_en = :contribution_en AND details_id = :details_id");
//    $stmt->bindParam(':year', $year, PDO::PARAM_STR);
//    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
//    $stmt->bindParam(':contribution_en', $contribution_en, PDO::PARAM_STR);
//    $stmt->bindParam(':details_id', $details_id, PDO::PARAM_INT);
//    $stmt->execute();
//
//    $prize = $stmt->fetch(PDO::FETCH_ASSOC);
//    if ($prize) {
//        return $prize['id'];
//    }
//
//    $stmt = $db->prepare("INSERT INTO prizes (year, category, contribution_sk, contribution_en, details_id) VALUES (:year, :category, :contribution_sk, :contribution_en, :details_id)");
//    $stmt->bindParam(':year', $year, PDO::PARAM_STR);
//    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
//    $stmt->bindParam(':contribution_sk', $contribution_sk, PDO::PARAM_STR);
//    $stmt->bindParam(':contribution_en', $contribution_en, PDO::PARAM_STR);
//    $stmt->bindParam(':details_id', $details_id, PDO::PARAM_INT);
//
//    if ($stmt->execute()) {
//        return $db->lastInsertId();
//    } else {
//        return false;
//    }
//}
//
//function bindPrizes($db, $laureate_id, $prize_id) {
//    $stmt = $db->prepare("INSERT INTO laureate_prizes (laureate_id, prize_id) VALUES (:laureate_id, :prize_id)");
//    $stmt->bindParam(':laureate_id', $laureate_id, PDO::PARAM_INT);
//    $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);
//    return processStatement($stmt);
//}
//
//function insertRow($db, $year, $category, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country, $contribution_sk, $contribution_en, $language_sk = NULL, $language_en = NULL, $genre_sk = NULL, $genre_en = NULL) {
//    $db->beginTransaction();
//
//    $laureate_id = insertLaureate($db, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country);
//    if (!$laureate_id) {
//        $db->rollBack();
//        return "Error inserting laureate.";
//    }
//
//    $details_id = NULL;
//    if ($language_sk && $language_en && $genre_sk && $genre_en) {
//        $details_id = insertDetails($db, $language_sk, $language_en, $genre_sk, $genre_en);
//        if ($details_id === false) {
//            $db->rollBack();
//            return "Error inserting prize details.";
//        }
//    }
//
//    $prize_id = insertPrize($db, $year, $category, $contribution_sk, $contribution_en, $details_id);
//    if (!$prize_id) {
//        $db->rollBack();
//        return "Error inserting prize.";
//    }
//
//    $stmt = $db->prepare("SELECT COUNT(*) FROM laureate_prizes WHERE laureate_id = :laureate_id AND prize_id = :prize_id");
//    $stmt->bindParam(':laureate_id', $laureate_id, PDO::PARAM_INT);
//    $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);
//    $stmt->execute();
//    $count = $stmt->fetchColumn();
//
//    if ($count == 0) {
//        $status = bindPrizes($db, $laureate_id, $prize_id);
//        if (strpos($status, "Error") !== false) {
//            $db->rollBack();
//            return $status;
//        }
//    }
//
//    $db->commit();
//    return "Row inserted successfully.";
//}
//
//// Handle form submission
//if ($_SERVER["REQUEST_METHOD"] == "POST") {
//    $year = $_POST['year'];
//    $category = $_POST['category'];
//    $type = $_POST['type'];
//    $name = $_POST['name'] ?: NULL;
//    $surname = $_POST['surname'] ?: NULL;
//    $organisation = $_POST['organisation'] ?: NULL;
//    $sex = $_POST['sex'] ?: NULL;
//    $birth_year = $_POST['birth_year'];
//    $death_year = $_POST['death_year'] ?: NULL;
//    $country = $_POST['country'] ?: NULL;
//    $contribution_sk = $_POST['contribution_sk'];
//    $contribution_en = $_POST['contribution_en'];
//    $language_sk = $_POST['language_sk'] ?: NULL;
//    $language_en = $_POST['language_en'] ?: NULL;
//    $genre_sk = $_POST['genre_sk'] ?: NULL;
//    $genre_en = $_POST['genre_en'] ?: NULL;
//
//    // Validation
//    $errors = [];
//    if (!$year) {
//        $errors[] = "Rok je povinný.";
//    }
//    if (!$category) {
//        $errors[] = "Kategória je povinná.";
//    }
//    if (!$birth_year) {
//        $errors[] = "Rok narodenia je povinný.";
//    }
//    if (!$contribution_sk || !$contribution_en) {
//        $errors[] = "Príspevok (SK aj EN) je povinný.";
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
//    if ($category === 'literatúra' && (!$language_sk || !$language_en || !$genre_sk || !$genre_en)) {
//        $errors[] = "Všetky detaily ceny (jazyk a žáner, SK aj EN) sú povinné pre literatúru.";
//    }
//
//    if (empty($errors)) {
//        $result = insertRow($db, $year, $category, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country, $contribution_sk, $contribution_en, $language_sk, $language_en, $genre_sk, $genre_en);
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
<!--    <title>Pridať laureáta</title>-->
<!--    <!-- Bootstrap CSS -->-->
<!--    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">-->
<!--    <style>-->
<!--        .add-container {-->
<!--            max-width: 800px;-->
<!--            margin: 0 auto;-->
<!--            padding: 20px;-->
<!--        }-->
<!--        #person-fields, #organisation-fields, #literature-details {-->
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
<!--    <div class="add-container">-->
<!--        <h1 class="mb-3">Pridať laureáta</h1>-->
<!--        <h2 class="mb-4 text-muted">Vyplňte údaje o laureátovi a jeho cene</h2>-->
<!---->
<!--        --><?php //if (isset($result)): ?>
<!--            <div class="alert --><?php //= strpos($result, "Error") === false ? 'alert-success' : 'alert-danger' ?><!--" role="alert">-->
<!--                --><?php //= htmlspecialchars($result) ?>
<!--            </div>-->
<!--        --><?php //endif; ?>
<!---->
<!--        <form method="POST">-->
<!--            <div class="row mb-3">-->
<!--                <div class="col-md-4">-->
<!--                    <label for="year" class="form-label">Rok: <span class="text-danger">*</span></label>-->
<!--                    <input type="number" class="form-control" id="year" name="year" min="1901" max="2025" value="2025" required>-->
<!--                </div>-->
<!--                <div class="col-md-4">-->
<!--                    <label for="category" class="form-label">Kategória: <span class="text-danger">*</span></label>-->
<!--                    <select class="form-select" id="category" name="category" required>-->
<!--                        <option value="">Vyberte kategóriu</option>-->
<!--                        <option value="mier">Mier</option>-->
<!--                        <option value="literatúra">Literatúra</option>-->
<!--                        <option value="medicína">Medicína</option>-->
<!--                        <option value="fyzika">Fyzika</option>-->
<!--                        <option value="chémia">Chémia</option>-->
<!--                    </select>-->
<!--                </div>-->
<!--                <div class="col-md-4">-->
<!--                    <label for="type" class="form-label">Typ laureáta: <span class="text-danger">*</span></label>-->
<!--                    <select class="form-select" id="type" name="type" required>-->
<!--                        <option value="person">Osoba</option>-->
<!--                        <option value="organisation">Organizácia</option>-->
<!--                    </select>-->
<!--                </div>-->
<!--            </div>-->
<!---->
<!--            <div id="person-fields" class="row mb-3">-->
<!--                <div class="col-md-4">-->
<!--                    <label for="name" class="form-label">Meno: <span class="text-danger">*</span></label>-->
<!--                    <input type="text" class="form-control" id="name" name="name">-->
<!--                </div>-->
<!--                <div class="col-md-4">-->
<!--                    <label for="surname" class="form-label">Priezvisko: <span class="text-danger">*</span></label>-->
<!--                    <input type="text" class="form-control" id="surname" name="surname">-->
<!--                </div>-->
<!--                <div class="col-md-4">-->
<!--                    <label for="sex" class="form-label">Pohlavie: <span class="text-danger">*</span></label>-->
<!--                    <select class="form-select" id="sex" name="sex" required>-->
<!--                        <option value="">-</option>-->
<!--                        <option value="Male">Muž</option>-->
<!--                        <option value="Female">Žena</option>-->
<!--                    </select>-->
<!--                </div>-->
<!--            </div>-->
<!---->
<!--            <div id="organisation-fields" class="mb-3">-->
<!--                <label for="organisation" class="form-label">Organizácia: <span class="text-danger">*</span></label>-->
<!--                <input type="text" class="form-control" id="organisation" name="organisation">-->
<!--            </div>-->
<!---->
<!--            <div class="row mb-3">-->
<!--                <div class="col-md-6">-->
<!--                    <label for="birth_year" class="form-label">Rok narodenia: <span class="text-danger">*</span></label>-->
<!--                    <input type="number" class="form-control" id="birth_year" name="birth_year" min="1800" max="2025" required>-->
<!--                </div>-->
<!--                <div class="col-md-6">-->
<!--                    <label for="death_year" class="form-label">Rok úmrtia:</label>-->
<!--                    <input type="number" class="form-control" id="death_year" name="death_year" min="1800" max="2025">-->
<!--                </div>-->
<!--            </div>-->
<!---->
<!--            <div class="mb-3">-->
<!--                <label for="country" class="form-label">Krajina:</label>-->
<!--                <input type="text" class="form-control" id="country" name="country">-->
<!--            </div>-->
<!---->
<!--            <div class="mb-3">-->
<!--                <label for="contribution_sk" class="form-label">Príspevok (SK): <span class="text-danger">*</span></label>-->
<!--                <textarea class="form-control" id="contribution_sk" name="contribution_sk" rows="3" required></textarea>-->
<!--            </div>-->
<!--            <div class="mb-3">-->
<!--                <label for="contribution_en" class="form-label">Príspevok (EN): <span class="text-danger">*</span></label>-->
<!--                <textarea class="form-control" id="contribution_en" name="contribution_en" rows="3" required></textarea>-->
<!--            </div>-->
<!---->
<!--            <div id="literature-details">-->
<!--                <h4 class="mt-4">Detaily ceny (pre literatúru)</h4>-->
<!--                <div class="row mb-3">-->
<!--                    <div class="col-md-6">-->
<!--                        <label for="language_sk" class="form-label">Jazyk (SK): <span class="text-danger">*</span></label>-->
<!--                        <input type="text" class="form-control" id="language_sk" name="language_sk">-->
<!--                    </div>-->
<!--                    <div class="col-md-6">-->
<!--                        <label for="language_en" class="form-label">Jazyk (EN): <span class="text-danger">*</span></label>-->
<!--                        <input type="text" class="form-control" id="language_en" name="language_en">-->
<!--                    </div>-->
<!--                </div>-->
<!--                <div class="row mb-3">-->
<!--                    <div class="col-md-6">-->
<!--                        <label for="genre_sk" class="form-label">Žáner (SK): <span class="text-danger">*</span></label>-->
<!--                        <input type="text" class="form-control" id="genre_sk" name="genre_sk">-->
<!--                    </div>-->
<!--                    <div class="col-md-6">-->
<!--                        <label for="genre_en" class="form-label">Žáner (EN): <span class="text-danger">*</span></label>-->
<!--                        <input type="text" class="form-control" id="genre_en" name="genre_en">-->
<!--                    </div>-->
<!--                </div>-->
<!--            </div>-->
<!---->
<!--            <button type="submit" class="btn btn-primary w-100">Pridať laureáta</button>-->
<!--        </form>-->
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
<!--        const categorySelect = document.getElementById('category');-->
<!--        const personFields = document.getElementById('person-fields');-->
<!--        const organisationFields = document.getElementById('organisation-fields');-->
<!--        const literatureDetails = document.getElementById('literature-details');-->
<!---->
<!--        // Initial checks-->
<!--        toggleTypeFields();-->
<!--        toggleLiteratureDetails();-->
<!---->
<!--        // Event listeners-->
<!--        typeSelect.addEventListener('change', toggleTypeFields);-->
<!--        categorySelect.addEventListener('change', toggleLiteratureDetails);-->
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
<!--        function toggleLiteratureDetails() {-->
<!--            if (categorySelect.value === 'literatúra') {-->
<!--                literatureDetails.style.display = 'block';-->
<!--            } else {-->
<!--                literatureDetails.style.display = 'none';-->
<!--            }-->
<!--        }-->
<!--    });-->
<!--</script>-->
<!---->
<!--</body>-->
<!--</html>-->