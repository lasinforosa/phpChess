<?php
// Configuration and Data Fetching
$pageTitle = "Select Opponents for New Game";
$config = require_once __DIR__ . '/../config/database.php';
$players = [];
$dbError = null;
$selectedWhitePlayerId = null;
$selectedBlackPlayerId = null;

try {
    $pdo = new PDO($config['dsn'], $config['username'], $config['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $pdo->query("SELECT player_id, name FROM players ORDER BY name ASC");
    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $dbError = "Database error: " . $e->getMessage();
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedWhitePlayerId = filter_input(INPUT_POST, 'white_player_id', FILTER_VALIDATE_INT);
    $selectedBlackPlayerId = filter_input(INPUT_POST, 'black_player_id', FILTER_VALIDATE_INT);

    if ($selectedWhitePlayerId && $selectedBlackPlayerId && $selectedWhitePlayerId === $selectedBlackPlayerId) {
        $formError = "White player and Black player cannot be the same.";
    } elseif (!$selectedWhitePlayerId || !$selectedBlackPlayerId) {
        $formError = "Please select both a White and a Black player.";
    }
    // Further processing would happen here, e.g., redirecting to new_game.php
    // For now, we just display the IDs if valid or show an error.

    if (!isset($formError) && $selectedWhitePlayerId && $selectedBlackPlayerId) {
        // Instead of just displaying IDs, redirect to game_view.php with a game_id
        // We'll use a cycling mechanism for game_id for demonstration
        session_start();
        $gameIdNum = ($_SESSION['game_id_num'] ?? 0) % 2 + 1; // Cycle between 1 and 2
        $_SESSION['game_id_num'] = $gameIdNum;
        $game_id = 'sample' . $gameIdNum;
        header("Location: game_view.php?game_id=" . urlencode($game_id) . "&white_player_id=" . urlencode($selectedWhitePlayerId) . "&black_player_id=" . urlencode($selectedBlackPlayerId));
        exit;
    }
}
// Ensure session is started if not already (e.g. if form not submitted yet, but we might use session for other things)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h1 { color: #333; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        select { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        button[type="submit"] { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button[type="submit"]:hover { background-color: #218838; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .info-message { background-color: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; padding: 10px; margin-bottom: 15px; border-radius: 4px; }
        .selection-display { margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

        <?php if ($dbError): ?>
            <div class="error-message"><?php echo htmlspecialchars($dbError); ?></div>
        <?php endif; ?>

        <?php if (isset($formError)): ?>
            <div class="error-message"><?php echo htmlspecialchars($formError); ?></div>
        <?php endif; ?>

        <?php if (!$dbError && empty($players)): ?>
            <div class="info-message">
                No players found in the database. Please <a href="import_players.php">import players</a> first.
            </div>
        <?php elseif (!$dbError && !empty($players)): ?>
            <form action="select_opponents.php" method="post">
                <!-- Add a hidden input or a select for game_id if we want user to choose -->
                <!-- For now, game_id is determined on submission -->
                <div class="form-group">
                    <label for="white_player_id">Select White Player:</label>
                    <select id="white_player_id" name="white_player_id" required>
                        <option value="">-- Select White --</option>
                        <?php foreach ($players as $player): ?>
                            <option value="<?php echo htmlspecialchars($player['player_id']); ?>" <?php echo ($selectedWhitePlayerId == $player['player_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($player['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="black_player_id">Select Black Player:</label>
                    <select id="black_player_id" name="black_player_id" required>
                        <option value="">-- Select Black --</option>
                        <?php foreach ($players as $player): ?>
                            <option value="<?php echo htmlspecialchars($player['player_id']); ?>" <?php echo ($selectedBlackPlayerId == $player['player_id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($player['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit">Start New Game</button>
            </form>

            <?php if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedWhitePlayerId && $selectedBlackPlayerId && !isset($formError)): ?>
                <!-- This part is less relevant now as we redirect, but can be kept for debugging if redirect fails -->
                <div class="selection-display">
                    <h3>Selected Players (before redirect):</h3>
                    <p>White Player ID: <?php echo htmlspecialchars($selectedWhitePlayerId); ?></p>
                    <p>Black Player ID: <?php echo htmlspecialchars($selectedBlackPlayerId); ?></p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
    </div>
</body>
</html>
