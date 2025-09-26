<?php
class Car {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function show($id) {
        // Step 1: Prepare the SQL query to join the cars and serviceRecords tables
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

        // Step 2: Fetch all rows
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Step 3: If no rows are found, return an empty result or handle as needed
        if (empty($rows)) {
            return ["error" => "Car not found"];
        }

        // Step 4: Structure the data into the desired JSON format
        $car = [
            "id" => (int)$rows[0]['id'], // Cast to int if needed
            "brand" => $rows[0]['brand'],
            "carType" => $rows[0]['carType'],
            "serviceRecords" => []
        ];

        // Step 5: Loop through rows to build the serviceRecords array
        foreach ($rows as $row) {
            if ($row['service_id']) { // Only add if there’s a service record (LEFT JOIN might return NULL)
                $car['serviceRecords'][] = [
                    "id" => (string)$row['service_id'], // JSON shows id as string
                    "problem" => $row['problem'],
                    "solution" => $row['solution'],
                    "carId" => (int)$row['carId'], // Cast to int
                    "serviceAt" => $row['serviceAt']
                ];
            }
        }

        // Step 6: Return the structured data (it will be automatically encoded as JSON if used in an API context)
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