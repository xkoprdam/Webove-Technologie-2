<?php
class Car {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function show($id) {
        $query = "
        SELECT 
            cars.id,
            cars.brand,
            cars.carType,
            serviceRecords.id AS service_id, -- Still need this alias to avoid column name conflict
            serviceRecords.problem,
            serviceRecords.solution,
            serviceRecords.carId,
            serviceRecords.serviceAt
        FROM cars
        LEFT JOIN serviceRecords ON cars.id = serviceRecords.carId
        WHERE cars.id = :id
    ";

        $stmt = $this->db->prepare($query);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            return ["error" => "Car not found"];
        }

        $car = [
            "id" => (int)$rows[0]['id'],
            "brand" => $rows[0]['brand'],
            "carType" => $rows[0]['carType'],
            "serviceRecords" => []
        ];


        foreach ($rows as $row) {
            if ($row['service_id']) { 
                $car['serviceRecords'][] = [
                    "id" => (string)$row['service_id'],
                    "problem" => $row['problem'],
                    "solution" => $row['solution'],
                    "carId" => (int)$row['carId'],
                    "serviceAt" => $row['serviceAt']
                ];
            }
        }

        return $car;
    }
    
    public function store($brand, $carType, $serviceRecords) {
        $stmt = $this->db->prepare("INSERT INTO car (brand, carType) 
                                    VALUES (:brand, :carType)");

        $stmt->bindParam(':brand', $brand);
        $stmt->bindParam(':car_type', $carType);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        $carId = $this->db->lastInsertId();

        foreach ($serviceRecords as $serviceRecord) {
            $this->storeRecord( $serviceRecord['problem'],
                                $serviceRecord['solution'],
                                $carId,
                                $serviceRecord['serviceAt']);
        }

        return $carId;
    }

    public function storeRecord($problem, $solution, $carId, $serviceAt) {
        $stmt = $this->db->prepare("INSERT INTO service_records (problem, solution, carId, serviceAt)
                                    VALUES (:problem, :solution, :carId, :serviceAt)");

            $stmt->bindParam(':problem', $problem, PDO::PARAM_STR);
            $stmt->bindParam(':solution', $solution, PDO::PARAM_STR);
            $stmt->bindParam(':carId', $carId, PDO::PARAM_INT);
            $stmt->bindParam(':serviceAt', $serviceAt, PDO::PARAM_STR);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }

    public function destroy($carId) {

        $stmt = $this->db->prepare("DELETE FROM cars WHERE id = :id");
        $stmt->bindParam(':id', $carId, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        $stmt = $this->db->prepare("DELETE FROM service_records WHERE carId = :id");
        $stmt->bindParam(':id', $carId, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
        return true;
    }
}
?>