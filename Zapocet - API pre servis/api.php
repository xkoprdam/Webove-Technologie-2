<?php

require_once('config.php');
require_once('Car.class.php');

$pdo = connectDatabase($hostname, $database, $username, $password);
$car = new Car($pdo);

header("Content-Type: application/json");

// https://node65.webte.fei.stuba.sk/z2/api/v0/cars
// POST, GET, PUT, DELETE - CRUD: Create, Read, Update, Delete

$method = $_SERVER['REQUEST_METHOD'];
$route = explode('/', $_GET['route']);

switch ($method) {
    case 'GET':
        if ($route[0] == 'cars' && count($route) == 2 && is_numeric($route[1])) {
            $carId = $route[1];
            $data = $car->show($carId);
            if ($data) {
                http_response_code(200);
                echo json_encode([
                    'message' => "Car details",
                    'data' => $data
                ]);
                break;
            }
            http_response_code(404);
            echo json_encode([
                'message' => "Car not found"
            ]);
            break;
        }
    case 'POST':
        if ($route[0] == 'cars' && count($route) == 1) {
            $data = json_decode(file_get_contents('php://input'), true);

            $carId = $car->store($data['brand'], $data['carType'], $data['serviceRecords']);

            $newCar = $car->show($carId);
            http_response_code(201);
            echo json_encode([
                'message' => "Car created successfully",
                'data' => $newCar
            ]);
            break;
        }
        elseif ($route[0] == 'cars' && is_numeric($route[1]) && $route[2] == 'service' && count($route) == 3) {

            $carId = $route[1];

            $carData = $car->show($carId);
            if (!$carData) {
                http_response_code(404);
                echo json_encode([
                    'message' => 'Car not found'
                ]);
                break;
            }

            $data = json_decode(file_get_contents('php://input'), true);

            $record_id = $car->storeRecord($data['problem'], $data['solution'], $carId, $data['createdAt']);

            $record = $car->showRecord($record_id);

            http_response_code(204);
            echo json_encode([
                'message' => "Service record created successfully",
                'data' => $record
            ]);
            break;
        }
    case 'DELETE':
        if ($route[0] == 'cars' && count($route) == 2 && is_numeric($route[1])) {
            $carId = $route[1];
            $exist = $car->show($carId);

            if (!$exist) {
                http_response_code(404);
                echo json_encode([
                    'message' => "Car not found",
                ]);
                break;
            }

            $car->destroy($carId);
            http_response_code(200);
            echo json_encode([
                'message' => "Car details"
            ]);
            break;
        }


    default:
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        break;
}