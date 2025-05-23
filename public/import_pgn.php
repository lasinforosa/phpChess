<?php
session_start(); // Optional: For flash messages or other session-based feedback

require_once __DIR__ . '/../src/Lib/PgnParser.php';
$dbConfigPath = __DIR__ . '/../config/database.php';

$pageTitle = "Import PGN File";
$feedbackMessages = []; // To store success/error messages

function convertPgnDateToSql(string $pgnDate): ?string {
    if (strpos($pgnDate, '????') !== false || strpos($pgnDate, '??.??.??') !== false || empty(trim($pgnDate, '.?'))) {
        return null;
    }
    // Replace dots with hyphens
    $sqlDate = str_replace('.', '-', $pgnDate);
    // Validate if it's a plausible date format after replacement (YYYY-MM-DD)
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $sqlDate)) {
        // Further validation like checkdate() could be used if necessary
        $d = DateTime::createFromFormat('Y-m-d', $sqlDate);
        if ($d && $d->format('Y-m-d') === $sqlDate) {
            return $sqlDate;
        }
    }
    return null; // Return null if format is not as expected
}


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["pgn_file"])) {
    if (!file_exists($dbConfigPath)) {
        $feedbackMessages[] = ['type' => 'error', 'text' => "FATAL ERROR: Database configuration file not found."];
    } else {
        $dbConfig = require $dbConfigPath;

        // File Validation
        $fileError = $_FILES["pgn_file"]["error"];
        if ($fileError !== UPLOAD_ERR_OK) {
            $feedbackMessages[] = ['type' => 'error', 'text' => "File upload error code: " . $fileError];
        } else {
            $fileName = $_FILES["pgn_file"]["name"];
            $fileTmpPath = $_FILES["pgn_file"]["tmp_name"];
            $fileSize = $_FILES["pgn_file"]["size"];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileExtension !== 'pgn') {
                $feedbackMessages[] = ['type' => 'error', 'text' => "Invalid file type. Only .pgn files are allowed."];
            } elseif ($fileSize > 2000000) { // Max 2MB
                $feedbackMessages[] = ['type' => 'error', 'text' => "File is too large. Maximum size is 2MB."];
            } else {
                // File Processing
                $pgnFileContent = file_get_contents($fileTmpPath);
                if ($pgnFileContent === false) {
                    $feedbackMessages[] = ['type' => 'error', 'text' => "Could not read PGN file content."];
                } else {
                    try {
                        $parser = new PgnParser($pgnFileContent);
                        $headers = $parser->getHeaders();
                        // $moves = $parser->getMoves(); // Not directly used for insertion based on schema, but could be validated

                        // Database Interaction
                        $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
                        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                        // Fetch Player IDs
                        $whitePlayerId = null;
                        $blackPlayerId = null;

                        if (!empty($headers['White'])) {
                            $stmtPlayer = $pdo->prepare("SELECT player_id FROM players WHERE name = :name");
                            $stmtPlayer->execute([':name' => $headers['White']]);
                            $player = $stmtPlayer->fetch(PDO::FETCH_ASSOC);
                            if ($player) {
                                $whitePlayerId = $player['player_id'];
                            }
                        }
                        if (!empty($headers['Black'])) {
                            $stmtPlayer = $pdo->prepare("SELECT player_id FROM players WHERE name = :name");
                            $stmtPlayer->execute([':name' => $headers['Black']]);
                            $player = $stmtPlayer->fetch(PDO::FETCH_ASSOC);
                            if ($player) {
                                $blackPlayerId = $player['player_id'];
                            }
                        }
                        
                        // Prepare Game Data
                        $gameData = [
                            'white_player_id' => $whitePlayerId,
                            'black_player_id' => $blackPlayerId,
                            'white_rating' => $headers['WhiteElo'] ?? null,
                            'black_rating' => $headers['BlackElo'] ?? null,
                            'result' => $headers['Result'] ?? '*',
                            'pgn_content' => $pgnFileContent,
                            'eco_code' => $headers['ECO'] ?? null,
                            'event_name' => $headers['Event'] ?? null,
                            'site_name' => $headers['Site'] ?? null,
                            'game_date' => isset($headers['Date']) ? convertPgnDateToSql($headers['Date']) : null,
                            'round_info' => $headers['Round'] ?? null,
                            'user_id' => null, // Or some system user ID
                        ];

                        // Insert Game
                        $sql = "INSERT INTO games (white_player_id, black_player_id, white_rating, black_rating, result, pgn_content, eco_code, event_name, site_name, game_date, round_info, user_id, created_at, updated_at) 
                                VALUES (:white_player_id, :black_player_id, :white_rating, :black_rating, :result, :pgn_content, :eco_code, :event_name, :site_name, :game_date, :round_info, :user_id, NOW(), NOW())";
                        $stmtInsertGame = $pdo->prepare($sql);
                        $stmtInsertGame->execute($gameData);
                        $lastInsertId = $pdo->lastInsertId();

                        $feedbackMessages[] = ['type' => 'success', 'text' => "Game imported successfully! New Game ID: " . $lastInsertId];

                    } catch (PDOException $e) {
                        $feedbackMessages[] = ['type' => 'error', 'text' => "Database error: " . $e->getMessage()];
                    } catch (Exception $e) {
                        $feedbackMessages[] = ['type' => 'error', 'text' => "An error occurred: " . $e->getMessage()];
                    }
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css"> <!-- Assuming general styles -->
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 600px; margin: auto; }
        h1 { color: #333; text-align: center; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="file"] { border: 1px solid #ddd; padding: 8px; border-radius: 4px; width: calc(100% - 18px); }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; display: block; width: 100%; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .messages div { padding: 10px; margin-bottom: 10px; border-radius: 4px; text-align: center; }
        .messages .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .messages .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        nav { margin-top: 20px; text-align: center; }
        nav a { text-decoration: none; color: #007bff; }
        nav a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

        <?php if (!empty($feedbackMessages)): ?>
            <div class="messages">
                <?php foreach ($feedbackMessages as $msg): ?>
                    <div class="<?php echo htmlspecialchars($msg['type']); ?>">
                        <?php echo htmlspecialchars($msg['text']); ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form action="import_pgn.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="pgn_file">Select PGN File (single game):</label>
                <input type="file" id="pgn_file" name="pgn_file" accept=".pgn" required>
            </div>
            <input type="submit" value="Import PGN">
        </form>

        <nav>
            <p><a href="index.php">Return to Home Page</a></p>
        </nav>
    </div>
</body>
</html>
