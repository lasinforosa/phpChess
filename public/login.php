<?php
// Start session at the very top
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If already logged in, redirect to index.php
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

require_once __DIR__ . '/../src/Lib/UserHandler.php';
$dbConfigPath = __DIR__ . '/../config/database.php';

$pageTitle = "User Login";
$feedbackMessages = []; // To store success/error messages

if (isset($_GET['logged_out']) && $_GET['logged_out'] === 'true') {
    $feedbackMessages[] = ['type' => 'success', 'text' => 'You have been successfully logged out.'];
}
if (isset($_GET['registered']) && $_GET['registered'] === 'true') {
    $feedbackMessages[] = ['type' => 'success', 'text' => 'Registration successful! Please log in.'];
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!file_exists($dbConfigPath)) {
        $feedbackMessages[] = ['type' => 'error', 'text' => "FATAL ERROR: Database configuration file not found."];
    } else {
        $dbConfig = require $dbConfigPath;

        $usernameOrEmail = $_POST['username_or_email'] ?? '';
        $password = $_POST['password'] ?? '';

        try {
            $pdo = new PDO($dbConfig['dsn'], $dbConfig['username'], $dbConfig['password']);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $userHandler = new UserHandler($pdo);
            $result = $userHandler->loginUser($usernameOrEmail, $password);

            if ($result['success']) {
                // UserHandler already started session and set $_SESSION variables
                header("Location: index.php");
                exit;
            } else {
                $feedbackMessages[] = ['type' => 'error', 'text' => $result['message'] ?? 'Login failed. Please check your credentials.'];
            }

        } catch (PDOException $e) {
            error_log("PDOException in login.php: " . $e->getMessage());
            $feedbackMessages[] = ['type' => 'error', 'text' => "A database error occurred. Please try again later."];
        } catch (Exception $e) {
            error_log("General exception in login.php: " . $e->getMessage());
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
        .container { background-color: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); max-width: 400px; margin: 40px auto; }
        h1 { color: #333; text-align: center; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input[type="text"], input[type="password"] {
            width: calc(100% - 22px); /* Account for padding and border */
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box; /* Important */
        }
        input[type="submit"] { background-color: #28a745; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; display: block; width: 100%; }
        input[type="submit"]:hover { background-color: #218838; }
        .messages div { padding: 12px; margin-bottom: 15px; border-radius: 4px; text-align: left; font-size: 0.95em; }
        .messages .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .messages .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .extra-links { margin-top: 20px; text-align: center; }
        .extra-links a { text-decoration: none; color: #007bff; }
        .extra-links a:hover { text-decoration: underline; }
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

        <form action="login.php" method="post" novalidate>
            <div class="form-group">
                <label for="username_or_email">Username or Email:</label>
                <input type="text" id="username_or_email" name="username_or_email" value="<?php echo htmlspecialchars($_POST['username_or_email'] ?? ''); ?>" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" value="Login">
        </form>

        <div class="extra-links">
            <p>Don't have an account? <a href="register.php">Register here</a>.</p>
            <p><a href="index.php">Return to Home Page</a></p>
        </div>
    </div>
</body>
</html>
