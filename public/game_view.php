<?php
require_once __DIR__ . '/../src/Lib/PgnParser.php';
$dbConfigPath = __DIR__ . '/../config/database.php';

if (!file_exists($dbConfigPath)) {
    // It's critical to stop if the DB config is missing.
    // Using die() here for simplicity in this context.
    // In a larger app, you might throw an exception or use a more sophisticated error page.
    die("FATAL ERROR: Database configuration file not found. Please ensure 'config/database.php' exists.");
}
$dbConfig = require $dbConfigPath;

$pageTitle = "View Game"; // Default page title
$gameData = null;
$whitePlayerName = "N/A";
$blackPlayerName = "N/A";
$parsedHeaders = [];
$parsedMoves = [];
$errorMessage = null;
$mainGameTitle = "Game Details"; // Default main heading for the page

// 1. Get game_id from URL and validate
$game_id_from_url = filter_input(INPUT_GET, 'game_id', FILTER_VALIDATE_INT);

if ($game_id_from_url === false || $game_id_from_url === null) {
    $errorMessage = "Invalid or missing Game ID. Please select a game.";
} else {
    // 2. Establish Database Connection
    try {
        $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

        // 3. Fetch Game Data
        $stmt = $pdo->prepare("SELECT pgn_content, event_name, site_name, game_date, round_info, result, white_player_id, black_player_id, white_rating, black_rating, eco_code FROM games WHERE game_id = :game_id");
        $stmt->bindParam(':game_id', $game_id_from_url, PDO::PARAM_INT);
        $stmt->execute();
        $gameData = $stmt->fetch(); // PDO::FETCH_ASSOC is default

        if (!$gameData) {
            $errorMessage = "Game with ID " . htmlspecialchars($game_id_from_url) . " not found.";
        } else {
            // 4. Fetch Player Names
            if ($gameData['white_player_id']) {
                $stmtPlayer = $pdo->prepare("SELECT name FROM players WHERE player_id = :player_id");
                $stmtPlayer->execute([':player_id' => $gameData['white_player_id']]);
                $whitePlayer = $stmtPlayer->fetch();
                $whitePlayerName = $whitePlayer ? $whitePlayer['name'] : "Unknown Player";
            }
            if ($gameData['black_player_id']) {
                $stmtPlayer = $pdo->prepare("SELECT name FROM players WHERE player_id = :player_id");
                $stmtPlayer->execute([':player_id' => $gameData['black_player_id']]);
                $blackPlayer = $stmtPlayer->fetch();
                $blackPlayerName = $blackPlayer ? $blackPlayer['name'] : "Unknown Player";
            }

            // 5. Process PGN
            if (!empty($gameData['pgn_content'])) {
                $parser = new PgnParser($gameData['pgn_content']);
                $parsedHeaders = $parser->getHeaders(); // These are headers from PGN string itself
                $parsedMoves = $parser->getMoves();
            } else {
                // Allow viewing game info even if PGN is missing, but note it
                $parsedMoves = []; // Ensure it's an array
                // $errorMessage could be set here if PGN is critical, but for now, let's show what we have.
            }
            
            // Construct Page Title and Main Game Title
            // Prioritize DB fields for main title elements
            $event = $gameData['event_name'] ?? ($parsedHeaders['Event'] ?? 'Chess Game');
            $whiteDisplay = $whitePlayerName !== "N/A" ? $whitePlayerName : ($parsedHeaders['White'] ?? 'White');
            $blackDisplay = $blackPlayerName !== "N/A" ? $blackPlayerName : ($parsedHeaders['Black'] ?? 'Black');
            
            $pageTitle = $event . " - " . $whiteDisplay . " vs " . $blackDisplay;
            $mainGameTitle = $event;
            if (!empty($gameData['round_info'])) {
                 $mainGameTitle .= ", Round " . htmlspecialchars($gameData['round_info']);
            }
        }

    } catch (PDOException $e) {
        // Log error to a file in a real app: error_log($e->getMessage());
        $errorMessage = "Database error occurred. Please try again later.";
    } catch (Exception $e) {
        // Log error to a file: error_log($e->getMessage());
        $errorMessage = "An unexpected error occurred. Please try again later.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/chessboard-1.0.0.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <h1><?php echo htmlspecialchars($mainGameTitle); ?></h1>

    <?php if ($errorMessage): ?>
        <div class="error-message" style="padding: 10px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; margin-bottom: 20px; border-radius: 4px;">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <?php if ($gameData && !$errorMessage): ?>
        <div id="gameBoard" style="width: 400px; margin-bottom: 20px;"></div>

        <div class="pgn-data">
            <div class="pgn-headers">
                <h2>Game Information</h2>
                <dl>
                    <dt>Event</dt><dd><?php echo htmlspecialchars($gameData['event_name'] ?? ($parsedHeaders['Event'] ?? 'N/A')); ?></dd>
                    <dt>Site</dt><dd><?php echo htmlspecialchars($gameData['site_name'] ?? ($parsedHeaders['Site'] ?? 'N/A')); ?></dd>
                    <dt>Date</dt><dd><?php echo htmlspecialchars($gameData['game_date'] ?? ($parsedHeaders['Date'] ?? 'N/A')); ?></dd>
                    <dt>Round</dt><dd><?php echo htmlspecialchars($gameData['round_info'] ?? ($parsedHeaders['Round'] ?? 'N/A')); ?></dd>
                    <dt>Result</dt><dd><?php echo htmlspecialchars($gameData['result'] ?? ($parsedHeaders['Result'] ?? 'N/A')); ?></dd>
                    <dt>ECO</dt><dd><?php echo htmlspecialchars($gameData['eco_code'] ?? ($parsedHeaders['ECO'] ?? 'N/A')); ?></dd>
                    <dt>White</dt><dd><?php echo htmlspecialchars($whitePlayerName); ?> (<?php echo htmlspecialchars($gameData['white_rating'] ?? ($parsedHeaders['WhiteElo'] ?? 'Unrated')); ?>)</dd>
                    <dt>Black</dt><dd><?php echo htmlspecialchars($blackPlayerName); ?> (<?php echo htmlspecialchars($gameData['black_rating'] ?? ($parsedHeaders['BlackElo'] ?? 'Unrated')); ?>)</dd>
                    <?php
                        // Display other headers from PGN that are not standard DB fields
                        $standardDbHeaders = ['Event', 'Site', 'Date', 'Round', 'Result', 'ECO', 'White', 'Black', 'WhiteElo', 'BlackElo', 'PlyCount', 'FEN'];
                        foreach ($parsedHeaders as $key => $value) {
                            if (!in_array($key, $standardDbHeaders)) {
                                echo '<dt>' . htmlspecialchars($key) . '</dt><dd>' . htmlspecialchars($value) . '</dd>';
                            }
                        }
                    ?>
                </dl>
            </div>

            <div class="pgn-moves">
                <h2>PGN Moves</h2>
                <?php if (!empty($parsedMoves)): ?>
                    <ol>
                        <?php
                        $moveCount = 1;
                        $isWhiteMove = true;
                        foreach ($parsedMoves as $move):
                            if ($isWhiteMove): ?>
                                <li><span class="move-number"><?php echo $moveCount; ?>.</span> <?php echo htmlspecialchars($move); ?>
                            <?php else: ?>
                                <?php echo htmlspecialchars($move); ?></li>
                                <?php $moveCount++;
                            endif;
                            $isWhiteMove = !$isWhiteMove;
                        endforeach;
                        // Ensure the last li is closed if the game ends on White's move
                        if (!$isWhiteMove && !empty($parsedMoves)) { echo '</li>'; }
                        ?>
                    </ol>
                <?php elseif (empty($gameData['pgn_content']) && $gameData): ?>
                     <p>PGN content is missing for this game record.</p>
                <?php else: ?>
                    <p>No moves found or PGN content missing.</p>
                <?php endif; ?>
            </div>
        </div>

        <script src="js/jquery-3.7.1.min.js"></script>
        <script src="js/chessboard-1.0.0.min.js"></script>
        <script>
            $(document).ready(function() {
                if (typeof Chessboard === 'function') {
                    var board = Chessboard('gameBoard', 'start');
                } else {
                    console.error("Chessboard function not found. Ensure chessboard-1.0.0.min.js is loaded correctly.");
                    $('#gameBoard').html('<p style="color: red;">Error: Chessboard library could not be loaded. Please check the console.</p>');
                }
            });
        </script>
    <?php elseif (!$errorMessage): ?>
         <p>Game data could not be loaded. Please try selecting another game or ensure the game ID is correct.</p>
    <?php endif; ?>
</body>
</html>
