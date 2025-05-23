<?php
// Basic entry point for the application
// More routing and bootstrapping will be added here later.

$pageTitle = "Chess Database App - Home";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css"> <!-- Assuming you have a general style.css -->
    <style>
        body { font-family: sans-serif; margin: 20px; background-color: #f4f4f4; color: #333; text-align: center; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: inline-block; }
        h1 { color: #333; }
        nav ul { list-style-type: none; padding: 0; }
        nav ul li { margin: 10px 0; }
        nav ul li a { text-decoration: none; color: #007bff; font-size: 1.2em; }
        nav ul li a:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Welcome to the Chess Database App!</h1>
        <nav>
            <ul>
                <li><a href="select_opponents.php">Start New Game Simulation</a></li>
                <li><a href="import_players.php">Import Players</a></li>
                <li><a href="game_view.php">View Default Game</a></li> <!-- Link to view a game directly, perhaps with a default ID -->
            </ul>
        </nav>
    </div>
</body>
</html>
?>
