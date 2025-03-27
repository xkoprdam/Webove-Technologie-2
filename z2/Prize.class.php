<?php

use OpenApi\Annotations as OA;

/**
 * @OA\Info(title="Prize API", version="1.0.0")
 */
class Prize {

    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    /**
     * @OA\Post(
     *     path="/prizes",
     *     summary="Create a new prize and associate it with a laureate",
     *     tags={"Prizes"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="laureate_id", type="integer", description="ID of the laureate"),
     *             @OA\Property(property="year", type="string", description="Year of the prize"),
     *             @OA\Property(property="category", type="string", description="Category of the prize"),
     *             @OA\Property(property="contribution_sk", type="string", description="Contribution in Slovak"),
     *             @OA\Property(property="contribution_en", type="string", description="Contribution in English"),
     *             @OA\Property(property="language_sk", type="string", nullable=true, description="Language in Slovak"),
     *             @OA\Property(property="language_en", type="string", nullable=true, description="Language in English"),
     *             @OA\Property(property="genre_sk", type="string", nullable=true, description="Genre in Slovak"),
     *             @OA\Property(property="genre_en", type="string", nullable=true, description="Genre in English")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Prize created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="prize_id", type="integer", description="ID of the created prize")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request or database error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     )
     * )
     */
    public function store($laureate_id, $year, $category, $contribution_sk, $contribution_en,
                          $language_sk = null, $language_en = null, $genre_sk = null, $genre_en = null) {

        $details_id = null;

        // Add details to DB if there are any
        if ($language_sk != null && $language_en != null && $genre_sk != null && $genre_en != null) {
            $stmt = $this->db->prepare("INSERT INTO prize_details (language_sk, language_en, genre_sk, genre_en) 
                                        VALUES (:language_sk, :language_en, :genre_sk, :genre_en)");

            $stmt->bindParam(':language_sk', $language_sk, PDO::PARAM_STR);
            $stmt->bindParam(':language_en', $language_en, PDO::PARAM_STR);
            $stmt->bindParam(':genre_sk', $genre_sk, PDO::PARAM_STR);
            $stmt->bindParam(':genre_en', $genre_en, PDO::PARAM_STR);

            try {
                $stmt->execute();
            } catch (PDOException $e) {
                return "Error in details: " . $e->getMessage();
            }

            $details_id = $this->db->lastInsertId();
        }

        // Add the prize to DB
        $stmt = $this->db->prepare("INSERT INTO prizes (year, category, contribution_sk, contribution_en, details_id) 
                                    VALUES (:year, :category, :contribution_sk, :contribution_en, :details_id)");

        $stmt->bindParam(':year', $year, PDO::PARAM_STR);
        $stmt->bindParam(':category', $category, PDO::PARAM_STR);
        $stmt->bindParam(':contribution_sk', $contribution_sk, PDO::PARAM_STR);
        $stmt->bindParam(':contribution_en', $contribution_en, PDO::PARAM_STR);
        $stmt->bindParam(':details_id', $details_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error in prizes: " . $e->getMessage();
        }

        $prize_id = $this->db->lastInsertId();

        // Add an association with laureate to laureates_prizes table
        $stmt = $this->db->prepare("INSERT INTO laureate_prizes (laureate_id, prize_id) 
                                    VALUES (:laureate_id, :prize_id)");

        $stmt->bindParam(':laureate_id', $laureate_id, PDO::PARAM_INT);
        $stmt->bindParam(':prize_id', $prize_id, PDO::PARAM_INT);

        try {
            $stmt->execute();
        } catch (PDOException $e) {
            return "Error laureate_prizes: " . $e->getMessage();
        }

        return $prize_id;
    }

    /**
     * @OA\Delete(
     *     path="/prizes/{prize_id}",
     *     summary="Delete a prize and its associated details",
     *     tags={"Prizes"},
     *     @OA\Parameter(
     *         name="prize_id",
     *         in="path",
     *         required=true,
     *         description="ID of the prize to delete",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Prize deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Prize deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Prize not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request or database error",
     *         @OA\JsonContent(
     *             @OA\Property(property="error", type="string", description="Error message")
     *         )
     *     )
     * )
     */
    public function destroy($prize_id) {
        try {
            // Check if the details exist
            $stmt = $this->db->prepare("SELECT details_id FROM prizes WHERE id = :id");
            $stmt->bindParam(':id', $prize_id, PDO::PARAM_INT);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$result) {
                return "Error: Prize with ID $prize_id not found";
            }

            $details_id = $result['details_id'];

            // Delete the prizes
            $stmt = $this->db->prepare("DELETE FROM prizes WHERE id = :id");
            $stmt->bindParam(':id', $prize_id, PDO::PARAM_INT);
            $stmt->execute();

            // Check if there are any details for this prize
            if ($details_id !== null) {
                // Delete the prize_details record
                $stmt = $this->db->prepare("DELETE FROM prize_details WHERE id = :details_id");
                $stmt->bindParam(':details_id', $details_id, PDO::PARAM_INT);
                $stmt->execute();
            }

            return 0;

        } catch (PDOException $e) {
            return "Error: " . $e->getMessage();
        }
    }
}