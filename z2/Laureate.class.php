<?php

require_once 'Prize.class.php';

use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="Laureate API", version="1.0.0")
 */
class Laureate {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @OA\Get(
     *     path="/laureates",
     *     summary="Get all laureates",
     *     tags={"Laureates"},
     *     @OA\Response(
     *         response=200,
     *         description="List of all laureates",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="id", type="integer"),
     *                 @OA\Property(property="fullname", type="string", nullable=true),
     *                 @OA\Property(property="organisation", type="string", nullable=true),
     *                 @OA\Property(property="sex", type="string", nullable=true),
     *                 @OA\Property(property="birth_year", type="integer", nullable=true),
     *                 @OA\Property(property="death_year", type="integer", nullable=true),
     *                 @OA\Property(property="country", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function index() {
        $stmt = $this->db->prepare("SELECT * FROM laureates");
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @OA\Get(
     *     path="/laureates/{id}",
     *     summary="Get a specific laureate by ID",
     *     tags={"Laureates"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Laureate ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laureate details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="id", type="integer"),
     *             @OA\Property(property="fullname", type="string", nullable=true),
     *             @OA\Property(property="organisation", type="string", nullable=true),
     *             @OA\Property(property="sex", type="string", nullable=true),
     *             @OA\Property(property="birth_year", type="integer", nullable=true),
     *             @OA\Property(property="death_year", type="integer", nullable=true),
     *             @OA\Property(property="country", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Laureate not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function show($id) {
        $stmt = $this->db->prepare("SELECT * FROM laureates WHERE id = :id");
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * @OA\Get(
     *     path="/laureates/getId",
     *     summary="Get laureate ID by fullname or organisation",
     *     tags={"Laureates"},
     *     @OA\Parameter(
     *         name="fullname",
     *         in="query",
     *         required=false,
     *         description="Full name of the laureate",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="organisation",
     *         in="query",
     *         required=false,
     *         description="Organisation name of the laureate",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laureate ID or null if not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function getId($fullname, $organisation) {
        $stmt = $this->db->prepare("SELECT id FROM laureates 
                                    WHERE fullname = :fullname OR organisation = :organisation LIMIT 1");
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "getId Error: " . $e->getMessage();
        }
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    }

    /**
     * @OA\Get(
     *     path="/prizes",
     *     summary="Get all laureates with their prize details",
     *     tags={"Prizes"},
     *     @OA\Parameter(
     *         name="category",
     *         in="query",
     *         required=false,
     *         description="Filter by prize category",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="year",
     *         in="query",
     *         required=false,
     *         description="Filter by prize year",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="country",
     *         in="query",
     *         required=false,
     *         description="Filter by laureate country",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of laureates with prize details",
     *         @OA\JsonContent(
     *             type="array",
     *             @OA\Items(
     *                 type="object",
     *                 @OA\Property(property="laureate_id", type="integer"),
     *                 @OA\Property(property="laureate", type="string"),
     *                 @OA\Property(property="fullname", type="string", nullable=true),
     *                 @OA\Property(property="organisation", type="string", nullable=true),
     *                 @OA\Property(property="country", type="string", nullable=true),
     *                 @OA\Property(property="year", type="integer"),
     *                 @OA\Property(property="category", type="string"),
     *                 @OA\Property(property="contribution_sk", type="string", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Server error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function indexPrizes($category = null, $year = null, $country = null) {
        $query = "SELECT 
                l.id AS laureate_id,
                COALESCE(NULLIF(l.fullname, ''), l.organisation) AS laureate,
                l.fullname,
                l.organisation,
                l.country,
                p.year,
                p.category,
                p.contribution_sk 
            FROM laureates l
            JOIN laureate_prizes lp ON l.id = lp.laureate_id
            JOIN prizes p ON lp.prize_id = p.id";

        $conditions = [];
        $params = [];

        if ($category) {
            $conditions[] = "p.category = :category";
            $params[':category'] = $category;
        }
        if ($year) {
            $conditions[] = "p.year = :year";
            $params[':year'] = $year;
        }
        if ($country) {
            $conditions[] = "l.country LIKE :country";
            $params[':country'] = "%$country%";
        }

        if (!empty($conditions)) {
            $query .= " WHERE " . implode(" AND ", $conditions);
        }

        $stmt = $this->db->prepare($query);

        try {
            $stmt->execute($params);
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @OA\Post(
     *     path="/laureate",
     *     summary="Create a new laureate",
     *     tags={"Laureates"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="sex", type="string", nullable=true),
     *             @OA\Property(property="birth_year", type="integer", nullable=true),
     *             @OA\Property(property="death_year", type="integer", nullable=true),
     *             @OA\Property(property="country", type="string", nullable=true),
     *             @OA\Property(property="fullname", type="string", nullable=true),
     *             @OA\Property(property="organisation", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Laureate created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="id", type="integer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function store($sex, $birth_year, $death_year, $country, $fullname = null, $organisation = null) {
        $stmt = $this->db->prepare("INSERT INTO laureates (fullname, organisation, sex, birth_year, death_year, country) VALUES (:fullname, :organisation, :sex, :birth_year, :death_year, :country)");

        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_INT);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_INT);
        $stmt->bindParam(':country', $country, PDO::PARAM_STR);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }

        return $this->db->lastInsertId();
    }

    /**
     * @OA\Put(
     *     path="/laureates/{id}",
     *     summary="Update an existing laureate",
     *     tags={"Laureates"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Laureate ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="fullname", type="string", nullable=true),
     *             @OA\Property(property="organisation", type="string", nullable=true),
     *             @OA\Property(property="sex", type="string", nullable=true),
     *             @OA\Property(property="birth_year", type="integer", nullable=true),
     *             @OA\Property(property="death_year", type="integer", nullable=true),
     *             @OA\Property(property="country", type="string", nullable=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Laureate updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function update($id, $fullname, $organisation, $sex, $birth_year, $death_year, $country) {
        if ($fullname == null && $organisation == null) {
            return "Error: Name or organisation name must be provided.";
        }

        $stmt = $this->db->prepare("UPDATE laureates SET fullname = :fullname, organisation = :organisation, sex = :sex, birth_year = :birth_year, death_year = :death_year, country = :country WHERE id = :id");

        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->bindParam(':fullname', $fullname, PDO::PARAM_STR);
        $stmt->bindParam(':organisation', $organisation, PDO::PARAM_STR);
        $stmt->bindParam(':sex', $sex, PDO::PARAM_STR);
        $stmt->bindParam(':birth_year', $birth_year, PDO::PARAM_INT);
        $stmt->bindParam(':death_year', $death_year, PDO::PARAM_INT);
        $stmt->bindParam(':country', $country, PDO::PARAM_STR);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
        return 0;
    }

    /**
     * @OA\Delete(
     *     path="/laureates/{id}",
     *     summary="Delete a laureate and associated prizes",
     *     tags={"Laureates"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Laureate ID",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Laureate deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Laureate not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string")
     *         )
     *     )
     * )
     */
    public function destroy($id) {
        try {
            $this->db->beginTransaction();

            $stmt = $this->db->prepare("SELECT COUNT(*) FROM laureates WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            if ($stmt->fetchColumn() == 0) {
                $this->db->rollBack();
                return "Error: Laureate with ID $id not found";
            }

            $stmt = $this->db->prepare("
            SELECT p.id AS prize_id
            FROM prizes p
            JOIN laureate_prizes lp ON p.id = lp.prize_id
            WHERE lp.laureate_id = :laureate_id
        ");
            $stmt->bindParam(':laureate_id', $id, PDO::PARAM_INT);
            $stmt->execute();
            $prizes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $prizeHandler = new Prize($this->db);
            foreach ($prizes as $prize) {
                $prize_id = $prize['prize_id'];

                $stmt = $this->db->prepare("
                SELECT COUNT(*) 
                FROM laureate_prizes 
                WHERE prize_id = :prize_id ");
                $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);
                $stmt->execute();
                $laureate_count = $stmt->fetchColumn();

                if ($laureate_count == 1) {
                    $stmt = $this->db->prepare("DELETE FROM laureate_prizes WHERE laureate_id = :id AND prize_id = :prize_id");
                    $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                    $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);
                    $stmt->execute();

                    $result = $prizeHandler->destroy($prize_id);

                    if ($result !== 0) {
                        $this->db->rollBack();
                        return "Error: Failed to delete prize with ID $prize_id - " . $result;
                    }
                }
            }

            $stmt = $this->db->prepare("DELETE FROM laureates WHERE id = :id");
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();

            $this->db->commit();
            return 0;

        } catch (PDOException $e) {
            $this->db->rollBack();
            return "Error: " . $e->getMessage();
        }
    }
}