<?php

class PlayerImporter {

    /**
     * Imports player data from a CSV file into the database.
     *
     * @param string $filePath Path to the CSV file.
     * @param PDO $pdo PDO database connection object.
     * @return array Summary of the import process (imported, updated, errors).
     */
    public function importFromCSV(string $filePath, PDO $pdo): array {
        $summary = [
            'imported' => 0,
            'updated' => 0,
            'errors' => [],
        ];

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $summary['errors'][] = "File not found or not readable: {$filePath}";
            return $summary;
        }

        $handle = fopen($filePath, 'r');
        if ($handle === false) {
            $summary['errors'][] = "Could not open file: {$filePath}";
            return $summary;
        }

        // Read header row
        $header = fgetcsv($handle);
        if ($header === false) {
            $summary['errors'][] = "Could not read header row from CSV.";
            fclose($handle);
            return $summary;
        }

        // Expected columns - adjust if your CSV has different names
        $expectedColumns = ['name', 'fide_id', 'federation_code', 'rating_standard', 'rating_rapid', 'rating_blitz', 'title'];
        // Create a mapping from expected columns to their index in the CSV header
        $columnMap = array_flip($header);

        foreach ($expectedColumns as $col) {
            if (!isset($columnMap[$col])) {
                $summary['errors'][] = "Missing expected column '{$col}' in CSV header.";
            }
        }
        if (!empty($summary['errors'])) {
            fclose($handle);
            return $summary; // Stop if essential columns are missing
        }


        $rowCount = 0;
        while (($row = fgetcsv($handle)) !== false) {
            $rowCount++;
            $data = array_combine($header, $row); // Combine header with row data

            // Sanitize and prepare data
            $name = filter_var(trim($data['name'] ?? ''), FILTER_SANITIZE_STRING);
            $fideId = !empty($data['fide_id']) ? filter_var(trim($data['fide_id']), FILTER_VALIDATE_INT) : null;
            $federationCode = !empty($data['federation_code']) ? filter_var(trim($data['federation_code']), FILTER_SANITIZE_STRING) : null;
            $ratingStandard = !empty($data['rating_standard']) ? filter_var(trim($data['rating_standard']), FILTER_VALIDATE_INT) : null;
            $ratingRapid = !empty($data['rating_rapid']) ? filter_var(trim($data['rating_rapid']), FILTER_VALIDATE_INT) : null;
            $ratingBlitz = !empty($data['rating_blitz']) ? filter_var(trim($data['rating_blitz']), FILTER_VALIDATE_INT) : null;
            $title = !empty($data['title']) ? filter_var(trim($data['title']), FILTER_SANITIZE_STRING) : null;

            if (empty($name)) {
                $summary['errors'][] = "Row {$rowCount}: Name is required.";
                continue;
            }
            if ($fideId === false && !empty($data['fide_id'])) { // fide_id is optional, but if provided, must be int
                 $summary['errors'][] = "Row {$rowCount}: Invalid FIDE ID format for '{$data['fide_id']}'. Must be an integer.";
                 continue;
            }
             if ($ratingStandard === false && !empty($data['rating_standard'])) {
                $summary['errors'][] = "Row {$rowCount}: Invalid Standard Rating format for '{$data['rating_standard']}'. Must be an integer.";
                continue;
            }
            if ($ratingRapid === false && !empty($data['rating_rapid'])) {
                $summary['errors'][] = "Row {$rowCount}: Invalid Rapid Rating format for '{$data['rating_rapid']}'. Must be an integer.";
                continue;
            }
            if ($ratingBlitz === false && !empty($data['rating_blitz'])) {
                $summary['errors'][] = "Row {$rowCount}: Invalid Blitz Rating format for '{$data['rating_blitz']}'. Must be an integer.";
                continue;
            }


            try {
                $pdo->beginTransaction();

                $stmtCheck = null;
                if ($fideId !== null) {
                    $stmtCheck = $pdo->prepare("SELECT player_id FROM players WHERE fide_id = :fide_id");
                    $stmtCheck->bindParam(':fide_id', $fideId, PDO::PARAM_INT);
                    $stmtCheck->execute();
                }
                
                $existingPlayer = $stmtCheck ? $stmtCheck->fetch(PDO::FETCH_ASSOC) : false;

                if ($existingPlayer) {
                    // Update existing player
                    $stmtUpdate = $pdo->prepare(
                        "UPDATE players SET name = :name, federation_code = :federation_code, " .
                        "rating_standard = :rating_standard, rating_rapid = :rating_rapid, " .
                        "rating_blitz = :rating_blitz, title = :title, updated_at = CURRENT_TIMESTAMP " .
                        "WHERE player_id = :player_id"
                    );
                    $stmtUpdate->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmtUpdate->bindParam(':federation_code', $federationCode, PDO::PARAM_STR);
                    $stmtUpdate->bindParam(':rating_standard', $ratingStandard, PDO::PARAM_INT);
                    $stmtUpdate->bindParam(':rating_rapid', $ratingRapid, PDO::PARAM_INT);
                    $stmtUpdate->bindParam(':rating_blitz', $ratingBlitz, PDO::PARAM_INT);
                    $stmtUpdate->bindParam(':title', $title, PDO::PARAM_STR);
                    $stmtUpdate->bindParam(':player_id', $existingPlayer['player_id'], PDO::PARAM_INT);
                    
                    if ($stmtUpdate->execute()) {
                        $summary['updated']++;
                    } else {
                        $summary['errors'][] = "Row {$rowCount}: DB Update Error - " . implode(", ", $stmtUpdate->errorInfo());
                    }
                } else {
                    // Insert new player
                    $stmtInsert = $pdo->prepare(
                        "INSERT INTO players (name, fide_id, federation_code, rating_standard, rating_rapid, rating_blitz, title) " .
                        "VALUES (:name, :fide_id, :federation_code, :rating_standard, :rating_rapid, :rating_blitz, :title)"
                    );
                    $stmtInsert->bindParam(':name', $name, PDO::PARAM_STR);
                    $stmtInsert->bindParam(':fide_id', $fideId, PDO::PARAM_INT); // Can be null
                    $stmtInsert->bindParam(':federation_code', $federationCode, PDO::PARAM_STR);
                    $stmtInsert->bindParam(':rating_standard', $ratingStandard, PDO::PARAM_INT);
                    $stmtInsert->bindParam(':rating_rapid', $ratingRapid, PDO::PARAM_INT);
                    $stmtInsert->bindParam(':rating_blitz', $ratingBlitz, PDO::PARAM_INT);
                    $stmtInsert->bindParam(':title', $title, PDO::PARAM_STR);

                    if ($stmtInsert->execute()) {
                        $summary['imported']++;
                    } else {
                        $summary['errors'][] = "Row {$rowCount}: DB Insert Error - " . implode(", ", $stmtInsert->errorInfo());
                    }
                }
                $pdo->commit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $summary['errors'][] = "Row {$rowCount}: Database exception - " . $e->getMessage();
            }
        }

        fclose($handle);
        return $summary;
    }
}
?>
