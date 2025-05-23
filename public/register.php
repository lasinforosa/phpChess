<?php
session_start(); // For potential future use, like flash messages

require_once __DIR__ . '/../src/Lib/UserHandler.php';
$dbConfigPath = __DIR__ . '/../config/database.php';

$pageTitle = "User Registration";
$feedbackMessages = []; // To store success/error messages from UserHandler

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!file_exists($dbConfigPath)) {
        $feedbackMessages[] = ['type' => 'error', 'text' => "FATAL ERROR: Database configuration file not found."];
    } else {
        $dbConfig = require $dbConfigPath;

        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        try {
            $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $userHandler = new UserHandler($pdo);
            $result = $userHandler->registerUser($username, $email, $password, $passwordConfirm);

            if ($result['success']) {
                $feedbackMessages[] = ['type' => 'success', 'text' => $result['message'] . " You can now try logging in (once login is implemented)."];
                // Clear form fields on success
                $_POST = []; 
            } else {
                $feedbackMessages[] = ['type' => 'error', 'text' => $result['message']];
            }

        } catch (PDOException $e) {
            // Log actual DB error server-side
            error_log("PDOException in register.php: " . $e->getMessage());
            $feedbackMessages[] = ['type' => 'error', 'text' => "A database error occurred. Please try again later."];
        } catch (Exception $e) {
            // Log other unexpected errors
            error_log("General exception in register.php: " . $e->getMessage());
            $feedbackMessages[] = ['type' => 'error', 'text' => "An unexpected error occurred. Please try again."];
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
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 500px; margin: 40px auto; }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="email"], input[type="password"] {
            width: calc(100% - 22px); /* Account for padding and border */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Important */
        }
        input[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; display: block; width: 100%; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .messages div { padding: 12px; margin-bottom: 15px; border-radius: 4px; text-align: left; font-size: 0.95em; }
        .messages .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .messages .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        nav { margin-top: 25px; text-align: center; }
        nav a { text-decoration: none; color: #007bff; margin: 0 10px; }
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

        <form action="register.php" method="post" novalidate>
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="password_confirm">Confirm Password:</label>
                <input type="password" id="password_confirm" name="password_confirm" required>
            </div>
            <input type="submit" value="Register">
        </form>

        <nav>
            <p>
                <a href="index.php">Home</a>
                <!-- Add a link to login.php once it's created -->
                <!-- | <a href="login.php">Login</a> -->
            </p>
        </nav>
    </div>
</body>
</html>
