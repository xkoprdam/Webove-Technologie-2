<?php

require_once('../../config.php');
require_once('Laureate.class.php');

$pdo = connectDatabase($hostname, $database, $username, $password);
$laureate = new Laureate($pdo);
$prize = new Prize($pdo);

header("Content-Type: application/json");

// https://node65.webte.fei.stuba.sk/z2/api/v0/laureates/1/prizes/1/
// POST, GET, PUT, DELETE - CRUD: Create, Read, Update, Delete

$method = $_SERVER['REQUEST_METHOD'];
$route = explode('/', $_GET['route']);

switch ($method) {
    case 'GET':
        if ($route[0] == 'laureates' && count($route) == 1) {
            http_response_code(200);
            echo json_encode($laureate->index());  // Get all laureates
            break;
        }
        elseif ($route[0] == 'laureates' && count($route) == 2 && is_numeric($route[1])) {
            $id = $route[1];
            $data = $laureate->show($id);
            if ($data) {
                http_response_code(200);
                echo json_encode($data);
                break;
            }
        }
        elseif ($route[0] == 'prizes' && count($route) == 1) {

            // Get filter parameters from the query string
            $category = isset($_GET['category']) ? $_GET['category'] : null;
            $year = isset($_GET['year']) ? $_GET['year'] : null;
            $country = isset($_GET['country']) ? $_GET['country'] : null;

            // Pass filters to indexPrizes and get filtered data
            $data = $laureate->indexPrizes($category, $year, $country);

            http_response_code(200);
            echo json_encode($data);
            break;
        }

        http_response_code(404);
        echo json_encode(['message' => 'Not found']);
        break;
    case 'POST':
        if ($route[0] == 'laureate') {
            $data = json_decode(file_get_contents('php://input'), true);

            // Normalize empty or unset values to null
            foreach ($data as $key => $value) {
                if (!isset($data[$key]) || $data[$key] == '') {
                    $data[$key] = null;
                }
            }

            // Check if a laureate with the same fullname or organisation already exists
            $existingLaureateId = $laureate->getId($data['fullname'], $data['organisation']);

            if ($existingLaureateId && is_numeric($existingLaureateId)) {
                // Use the existing laureate's ID
                $laureateId = $existingLaureateId;
            } else {
                // Create a new laureate if no match is found
                $laureateId = $laureate->store(
                    $data['sex'],
                    $data['birth_year'],
                    $data['death_year'],
                    $data['country'],
                    $data['fullname'],
                    $data['organisation']
                );

                if (!is_numeric($laureateId)) {
                    http_response_code(400);
                    echo json_encode(['message' => "laureateId Bad request", 'data' => $laureateId]);
                    break;
                }
            }

            // Store the prize using the laureate ID (new or existing)
            $prizeData = $data['prize'];
            $prizeId = $prize->store(
                $laureateId,
                $prizeData['year'],
                $prizeData['category'],
                $prizeData['contribution_sk'],
                $prizeData['contribution_en'],
                $prizeData['language_sk'],
                $prizeData['language_en'],
                $prizeData['genre_sk'],
                $prizeData['genre_en']
            );

            if (!is_numeric($prizeId)) {
                http_response_code(400);
                echo json_encode(['message' => "prizeId Bad request", 'data' => $prizeId]);
                break;
            }

            $new_laureate = $laureate->show($laureateId);
            http_response_code(201);
            echo json_encode([
                'message' => "Created successfully",
                'data' => $new_laureate
            ]);
            break;
        }
        
        // Multiple laureates route: /laureates (JSON file upload)
        else if ($route[0] == 'laureates') {
            if (!isset($_FILES['jsonFile']) || $_FILES['jsonFile']['error'] !== UPLOAD_ERR_OK) {
                http_response_code(400);
                echo json_encode(['message' => "No valid JSON file uploaded"]);
                break;
            }

            $filePath = $_FILES['jsonFile']['tmp_name'];
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);

            if ($data === null || !is_array($data)) {
                http_response_code(400);
                echo json_encode(['message' => "Invalid JSON format or not an array"]);
                break;
            }

            $results = [];
            $errors = [];
            $allSuccess = true;

            foreach ($data as $index => $laureateData) {
                // Normalize empty or unset values to null
                foreach ($laureateData as $key => $value) {
                    if (!isset($laureateData[$key]) || $laureateData[$key] == '') {
                        $laureateData[$key] = null;
                    }
                }

                // Check if a laureate with the same fullname or organisation already exists
                $existingLaureateId = $laureate->getId($laureateData['fullname'], $laureateData['organisation']);

                if ($existingLaureateId && is_numeric($existingLaureateId)) {
                    $laureateId = $existingLaureateId;
                } else {
                    $laureateId = $laureate->store(
                        $laureateData['sex'],
                        $laureateData['birth_year'],
                        $laureateData['death_year'],
                        $laureateData['country'],
                        $laureateData['fullname'],
                        $laureateData['organisation']
                    );
                    if (!is_numeric($laureateId)) {
                        $allSuccess = false;
                        $errors[] = [
                            'index' => $index,
                            'message' => "Failed to create laureate",
                            'data' => $laureateId
                        ];
                        continue;
                    }
                }

                // Store the prize
                $prizeData = $laureateData['prize'];
                $prizeId = $prize->store(
                    $laureateId,
                    $prizeData['year'],
                    $prizeData['category'],
                    $prizeData['contribution_sk'],
                    $prizeData['contribution_en'],
                    $prizeData['language_sk'] ?? null,
                    $prizeData['language_en'] ?? null,
                    $prizeData['genre_sk'] ?? null,
                    $prizeData['genre_en'] ?? null
                );

                if (!is_numeric($prizeId)) {
                    $allSuccess = false;
                    $errors[] = [
                        'index' => $index,
                        'message' => "Failed to create prize",
                        'data' => $prizeId
                    ];
                    continue;
                }

                $new_laureate = $laureate->show($laureateId);
                $results[] = [
                    'index' => $index,
                    'laureate_id' => $laureateId,
                    'prize_id' => $prizeId,
                    'data' => $new_laureate
                ];
            }

            if ($allSuccess) {
                http_response_code(201);
                echo json_encode([
                    'message' => "All laureates and prizes created successfully",
                    'data' => $results
                ]);
            } else {
                http_response_code(207);
                echo json_encode([
                    'message' => "Some operations failed",
                    'success' => $results,
                    'errors' => $errors
                ]);
            }
            break;
        }
        http_response_code(400);
        echo json_encode(['message' => 'Bad requestix']);
        break;
    case 'PUT':
        if ($route[0] == 'laureates' && count($route) == 2 && is_numeric($route[1])) {
            $currentID = $route[1];
            $currentData = $laureate->show($currentID);
            if (!$currentData) {
                http_response_code(404);
                echo json_encode(['message' => 'Not found']);
                break;
            }

            $updatedData = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                echo json_encode(['message' => 'Invalid JSON']);
                break;
            }

            $currentData = $updatedData;

            $status = $laureate->update(
                $currentID,
                $currentData['fullname'],
                $currentData['organisation'],
                $currentData['sex'],
                $currentData['birth_year'],
                $currentData['death_year'],
                $currentData['country']
            );

            if ($status != 0) {
                http_response_code(400);
                echo json_encode(['message' => "Bad request", 'data' => $status]);
                break;
            }

            http_response_code(200);
            echo json_encode([
                'message' => "Updated successfully",
                'data' => $currentData
            ]);
            break;
        }
        http_response_code(404);
        echo json_encode(['message' => 'Not found']);
        break;
    case 'DELETE':
        if ($route[0] == 'laureates' && count($route) == 2 && is_numeric($route[1])) {
            $id = $route[1];
            $exist = $laureate->show($id);
            if (!$exist) {
                http_response_code(404);
                echo json_encode(['message' => 'Not found']);
                break;
            }

            $status = $laureate->destroy($id);

            if ($status != 0) {
                http_response_code(400);
                echo json_encode(['message' => "Bad request", 'data' => $status]);
                break;
            }

            http_response_code(201);
            echo json_encode(['message' => "Deleted successfully"]);
            break;

        }
        http_response_code(404);
        echo json_encode(['message' => 'Not found']);
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}