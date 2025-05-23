<?php
<?php
// Start session at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$pageTitle = "Chess Database App - Home";
$isLoggedIn = isset($_SESSION['user_id']);
$username = $isLoggedIn ? htmlspecialchars($_SESSION['username']) : '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="css/style.css"> <!-- Assuming you have a general style.css -->
    <style>
        body { font-family: sans-serif; margin: 0; background-color: #f4f4f4; color: #333; }
        .top-bar { background-color: #333; color: white; padding: 10px 20px; text-align: right; }
        .top-bar a { color: white; text-decoration: none; margin-left: 15px; }
        .top-bar a:hover { text-decoration: underline; }
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); display: inline-block; text-align:center; margin: 20px auto; display: block; max-width: 800px; }
        h1 { color: #333; text-align: center; }
        nav ul { list-style-type: none; padding: 0; text-align: center; }
        nav ul li { margin: 15px 0; }
        nav ul li a { text-decoration: none; color: #007bff; font-size: 1.3em; padding: 8px 15px; border: 1px solid #007bff; border-radius: 5px; transition: background-color 0.3s, color 0.3s; }
        nav ul li a:hover { background-color: #007bff; color: white; }
        .user-status { text-align: center; margin-bottom: 20px; font-size: 1.1em; }
    </style>
</head>
<body>
    <div class="top-bar">
        <?php if ($isLoggedIn): ?>
            <span>Welcome, <?php echo $username; ?>!</span>
            <a href="logout.php">Logout</a>
        <?php else: ?>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        <?php endif; ?>
    </div>

    <div class="container">
        <h1>Welcome to the Chess Database App!</h1>
        
        <?php if (isset($_GET['login_success']) && $_GET['login_success'] === 'true' && $isLoggedIn): ?>
            <div class="messages" style="margin-bottom: 20px;">
                 <div class="success" style="padding: 12px; background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 4px; text-align: left; font-size: 0.95em;">
                    Login successful!
                </div>
            </div>
        <?php endif; ?>

        <nav>
            <ul>
                <li><a href="select_opponents.php">Start New Game Simulation</a></li>
                <li><a href="import_players.php">Import Players</a></li>
                <li><a href="import_pgn.php">Import PGN File</a></li>
                <li><a href="game_view.php?game_id=1">View Sample Game 1</a></li>
                <li><a href="game_view.php?game_id=2">View Sample Game 2</a></li>
                <!-- More links can be added here -->
            </ul>
        </nav>
    </div>
</body>
</html>
?>
