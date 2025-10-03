<?php

require_once '../../config.php';

$db = connectDatabase($hostname, $database, $username, $password);

function processStatement($stmt) {
    if ($stmt->execute()) {
        return "Record inserted successfully.";
    } else {
        return "Error inserting record: " . implode(", ", $stmt->errorInfo());
    }
}

function insertLaureate($db, $name, $surname, $organisation, $sex, $birth_year, $death_year, $country) {

    if (!$organisation) {
        $fullname = $name . " " . $surname;

        // Check if the laureate already exists
        $stmt = $db->prepare("SELECT id FROM laureates WHERE fullname = :fullname AND birth_year = :birth_year");
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
        $stmt->execute();

        $laureate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($laureate) {
            return $laureate['id'];  // Return existing laureate ID
        }

        // If laureate does not exist, insert
        $stmt = $db->prepare("INSERT INTO laureates (fullname, sex, birth_year, death_year, country) VALUES (:fullname, :sex, :birth_year, :death_year, :country)");
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        // only for peace nobels
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_STR);
        $stmt->bindParam(':country', $country, PDO::PARAM_STR);
    } else {
        // Check if the organisation already exists
        $stmt = $db->prepare("SELECT id FROM laureates WHERE organisation = :organisation");
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->execute();

        $laureate = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($laureate) {
            return $laureate['id'];  // Return existing laureate ID
        }

        // If laureate does not exist, insert
        $stmt = $db->prepare("INSERT INTO laureates (organisation, sex, birth_year, death_year, country) VALUES (:organisation, :sex, :birth_year, :death_year, :country)");
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_STR);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_STR);
        $stmt->bindParam(':country', $country, PDO::PARAM_STR);
    }

    if ($stmt->execute()) {
        return $db->lastInsertId();  // Return new laureate ID
    } else {
        return false;  // Error handling
    }
}

function insertDetails($db, $language_sk, $language_en, $genre_sk, $genre_en) {
    // Check if the details already exist
    $stmt = $db->prepare("SELECT id FROM prize_details WHERE language_sk = :language_sk AND language_en = :language_en AND genre_sk = :genre_sk AND genre_en = :genre_en");
    $stmt->bindParam(':language_sk', $language_sk, PDO::PARAM_STR);
    $stmt->bindParam(':language_en', $language_en, PDO::PARAM_STR);
    $stmt->bindParam(':genre_sk', $genre_sk, PDO::PARAM_STR);
    $stmt->bindParam(':genre_en', $genre_en, PDO::PARAM_STR);
    $stmt->execute();

    $details = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($details) {
        return $details['id'];  // Return existing details ID
    }

    // If details do not exist, insert
    $stmt = $db->prepare("INSERT INTO prize_details (language_sk, language_en, genre_sk, genre_en) VALUES (:language_sk, :language_en, :genre_sk, :genre_en)");
    $stmt->bindParam(':language_sk', $language_sk, PDO::PARAM_STR);
    $stmt->bindParam(':language_en', $language_en, PDO::PARAM_STR);
    $stmt->bindParam(':genre_sk', $genre_sk, PDO::PARAM_STR);
    $stmt->bindParam(':genre_en', $genre_en, PDO::PARAM_STR);

    if ($stmt->execute()) {
        return $db->lastInsertId();  // Return new details ID
    } else {
        return false;  // Error handling
    }
}

function insertPrize($db, $year, $category, $contribution_sk, $contribution_en, $details_id = NULL) {
    // Check if the prize already exists with the same details
    $stmt = $db->prepare("SELECT id FROM prizes WHERE year = :year AND category = :category AND contribution_en = :contribution_en AND details_id = :details_id");
    $stmt->bindParam(':year', $year, PDO::PARAM_STR);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->bindParam(':contribution_en', $contribution_en, PDO::PARAM_STR);
    $stmt->bindParam(':details_id', $details_id, PDO::PARAM_INT);
    $stmt->execute();

    $prize = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($prize) {
        return $prize['id'];  // Return existing prize ID
    }

    // Insert new prize with linked details_id
    $stmt = $db->prepare("INSERT INTO prizes (year, category, contribution_sk, contribution_en, details_id) VALUES (:year, :category, :contribution_sk, :contribution_en, :details_id)");
    $stmt->bindParam(':year', $year, PDO::PARAM_STR);
    $stmt->bindParam(':category', $category, PDO::PARAM_STR);
    $stmt->bindParam(':contribution_sk', $contribution_sk, PDO::PARAM_STR);
    $stmt->bindParam(':contribution_en', $contribution_en, PDO::PARAM_STR);
    $stmt->bindParam(':details_id', $details_id, PDO::PARAM_INT);

    if ($stmt->execute()) {
        return $db->lastInsertId();  // Return new prize ID
    } else {
        return false;  // Error handling
    }
}

function bindPrizes($db, $laureate_id, $prize_id) {
    $stmt = $db->prepare("INSERT INTO laureate_prizes (laureate_id, prize_id) VALUES (:laureate_id, :prize_id)");

    $stmt->bindParam(':laureate_id', $laureate_id, PDO::PARAM_INT);
    $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);

    return processStatement($stmt);
}

function insertRow($db, $year, $category, $name, $surname, $sex, $birth_year, $death_year, $country, $contribution_sk, $contribution_en, $language_sk = NULL, $language_en = NULL, $genre_sk = NULL, $genre_en = NULL) {
    $db->beginTransaction();

    // Get or insert laureate
    $laureate_id = insertLaureate($db, $name, $surname, NULL,  $sex, $birth_year, $death_year, $country);

    if (!$laureate_id) {
        $db->rollBack();
        return "Error inserting laureate.";
    }

    // Get or insert prize details
    if ($language_sk && $language_en && $genre_sk && $genre_en) {
        $details_id = insertDetails($db, $language_sk, $language_en, $genre_sk, $genre_en);
        if ($details_id === false) {
            $db->rollBack();
            return "Error inserting prize details.";
        }
    }

    // Get or insert prize
    $prize_id = insertPrize($db, $year, $category, $contribution_sk, $contribution_en, NULL);
    if (!$prize_id) {
        $db->rollBack();
        return "Error inserting prize.";
    }

    // Check if the laureate is already linked to the prize
    $stmt = $db->prepare("SELECT COUNT(*) FROM laureate_prizes WHERE laureate_id = :laureate_id AND prize_id = :prize_id");
    $stmt->bindParam(':laureate_id', $laureate_id, PDO::PARAM_INT);
    $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);
    $stmt->execute();
    $count = $stmt->fetchColumn();

    if ($count == 0) {
        // If not linked, bind laureate to prize
        $status = bindPrizes($db, $laureate_id, $prize_id);
        if (strpos($status, "Error") !== false) {
            $db->rollBack();
            return $status;
        }
    }

    $db->commit();
    return "Row inserted successfully.";
}

// Simple CSV file parser
function parseCSV($db, $filename) {
    $handle = fopen($filename, "r");
    $data = array();

    fgetcsv($handle, 0, ";");

    while (($row = fgetcsv($handle, 0, ";")) !== FALSE) {
        $data[] = array_filter($row);  // push only non-empty values
        insertRow($db, $row[0], 'chémia', $row[1], $row[2], $row[3], $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], $row[10], $row[11], $row[12]);
    }
    fclose($handle);

    return $data;
}

// commented to prevent parsing
// $parsed_data = parseCSV($db, "data/nobel_CHE.csv");

?>