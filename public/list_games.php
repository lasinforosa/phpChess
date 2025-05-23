<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php'; // For $dbConfig

$pageTitle = "Available Games";
$games = [];
$totalGames = 0;
$itemsPerPage = 10; // Or any other number you prefer
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$currentPage = max(1, $currentPage); // Ensure current page is at least 1
$offset = ($currentPage - 1) * $itemsPerPage;
$errorMessage = null;

try {
    $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // Count total games for pagination
    $totalStmt = $pdo->query("SELECT COUNT(*) FROM games");
    $totalGames = (int)$totalStmt->fetchColumn();

    // Fetch paginated games
    $stmt = $pdo->prepare("
        SELECT 
            g.game_id, 
            g.event_name, 
            g.site_name, 
            g.game_date, 
            g.result, 
            g.eco_code,
            wp.name AS white_player_name, 
            bp.name AS black_player_name
        FROM games g
        LEFT JOIN players wp ON g.white_player_id = wp.player_id
        LEFT JOIN players bp ON g.black_player_id = bp.player_id
        ORDER BY g.game_date DESC, g.game_id DESC
        LIMIT :limit OFFSET :offset
    ");
    $stmt->bindParam(':limit', $itemsPerPage, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $games = $stmt->fetchAll();

} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
    // In a real app, log this error to a file instead of/in addition to displaying
} catch (Exception $e) {
    $errorMessage = "An unexpected error occurred: " . $e->getMessage();
}

$totalPages = ceil($totalGames / $itemsPerPage);

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
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); margin: auto; }
        h1 { color: #333; text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f0f0f0; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        tr:hover { background-color: #f1f1f1; }
        a { color: #007bff; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .pagination { text-align: center; margin-top: 20px; }
        .pagination a, .pagination span { display: inline-block; padding: 8px 12px; margin: 0 2px; border: 1px solid #ddd; border-radius: 4px; }
        .pagination a:hover { background-color: #007bff; color: white; border-color: #007bff;}
        .pagination .current-page { background-color: #007bff; color: white; border-color: #007bff; }
        .no-games { text-align: center; padding: 20px; font-size: 1.1em; }
        .nav-links { margin-top: 20px; text-align: center; }
        .nav-links a { margin: 0 10px; }
        .error-message { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

        <?php if ($errorMessage): ?>
            <div class="error-message">
                <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>

        <?php if (!$errorMessage && empty($games) && $totalGames === 0): ?>
            <div class="no-games">
                No games found in the database.
            </div>
        <?php elseif (!$errorMessage && !empty($games)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Event</th>
                        <th>Site</th>
                        <th>Date</th>
                        <th>White Player</th>
                        <th>Black Player</th>
                        <th>Result</th>
                        <th>ECO</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($games as $game): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($game['event_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($game['site_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($game['game_date'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($game['white_player_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($game['black_player_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($game['result'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($game['eco_code'] ?? 'N/A'); ?></td>
                            <td><a href="game_view.php?game_id=<?php echo htmlspecialchars($game['game_id']); ?>">View Game</a></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($currentPage > 1): ?>
                        <a href="?page=<?php echo $currentPage - 1; ?>">Previous</a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <?php if ($i == $currentPage): ?>
                            <span class="current-page"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                        <a href="?page=<?php echo $currentPage + 1; ?>">Next</a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

        <?php elseif (!$errorMessage && $totalGames > 0 && empty($games)): // This case means page number is out of bounds for existing games ?>
            <div class="no-games">
                No games found on this page. Try a different page number.
            </div>
        <?php endif; ?>

        <div class="nav-links">
            <a href="index.php">Home Page</a> |
            <a href="import_pgn.php">Import New Game</a>
        </div>
    </div>
</body>
</html>
