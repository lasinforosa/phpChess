<?php

// Ensure Composer's autoloader is loaded if you're using Composer
// require_once __DIR__ . '/../vendor/autoload.php'; 

require_once __DIR__ . '/../src/Lib/PlayerImporter.php';
$config = require_once __DIR__ . '/../config/database.php';

$pageTitle = "Import Players from CSV";
$uploadDir = __DIR__ . '/../database/uploads/';
$uploadPath = $uploadDir . '.gitkeep'; // For ensuring directory is tracked

// Ensure upload directory exists and has a .gitkeep file
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0775, true)) {
        die("Failed to create upload directory: {$uploadDir}");
    }
}
if (!file_exists($uploadPath)) {
    if (!touch($uploadPath)) {
        // Log error or handle, but don't necessarily die
        error_log("Failed to create .gitkeep file in {$uploadDir}");
    }
}


$importSummary = null;
$errorMessages = [];
$successMessages = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['player_csv']) && $_FILES['player_csv']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['player_csv']['tmp_name'];
        $fileName = basename($_FILES['player_csv']['name']);
        $fileSize = $_FILES['player_csv']['size'];
        $fileType = $_FILES['player_csv']['type'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        $allowedExtensions = ['csv'];
        $allowedMimeTypes = ['text/csv', 'application/csv', 'text/plain', 'application/vnd.ms-excel']; // Some browsers send different MIME types

        if (!in_array($fileExtension, $allowedExtensions)) {
            $errorMessages[] = "Invalid file extension. Only CSV files are allowed.";
        } elseif (!in_array(mime_content_type($fileTmpPath), $allowedMimeTypes)) {
            // Check MIME type of the actual file content as an additional security measure
             $errorMessages[] = "Invalid file type. Please upload a valid CSV file. MIME: " . mime_content_type($fileTmpPath);
        } elseif ($fileSize > (5 * 1024 * 1024)) { // Max 5MB
            $errorMessages[] = "File is too large. Maximum size is 5MB.";
        } else {
            $destinationPath = $uploadDir . uniqid('player_import_', true) . '.' . $fileExtension;

            if (move_uploaded_file($fileTmpPath, $destinationPath)) {
                try {
                    // Establish PDO connection
                    $pdo = new PDO($config['dsn'], $config['username'], $config['password']);
                    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

                    $importer = new PlayerImporter();
                    $importSummary = $importer->importFromCSV($destinationPath, $pdo);

                    if (!empty($importSummary['imported']) || !empty($importSummary['updated'])) {
                        $successMessages[] = "Import processed successfully.";
                    }
                    if (!empty($importSummary['errors'])) {
                        $errorMessages = array_merge($errorMessages, $importSummary['errors']);
                         $errorMessages[] = "Some records could not be processed. See details above/below.";
                    }


                } catch (PDOException $e) {
                    $errorMessages[] = "Database connection error: " . $e->getMessage();
                } catch (Exception $e) {
                    $errorMessages[] = "An unexpected error occurred: " . $e->getMessage();
                } finally {
                    if (file_exists($destinationPath)) {
                        unlink($destinationPath); // Delete the temporary file
                    }
                }
            } else {
                $errorMessages[] = "Failed to move uploaded file to destination.";
            }
        }
    } elseif (isset($_FILES['player_csv']) && $_FILES['player_csv']['error'] !== UPLOAD_ERR_NO_FILE) {
        // Handle other upload errors
        switch ($_FILES['player_csv']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessages[] = "File is too large.";
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessages[] = "File was only partially uploaded.";
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessages[] = "Missing a temporary folder for uploads.";
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessages[] = "Failed to write file to disk.";
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessages[] = "A PHP extension stopped the file upload.";
                break;
            default:
                $errorMessages[] = "An unknown error occurred during file upload.";
        }
    } else {
        $errorMessages[] = "No file was uploaded. Please choose a CSV file.";
    }
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
        input[type="file"] { border: 1px solid #ddd; padding: 8px; border-radius: 4px; width: calc(100% - 18px); }
        button[type="submit"] { background-color: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button[type="submit"]:hover { background-color: #0056b3; }
        .messages div { padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        .messages .error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .messages .success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .summary { margin-top: 20px; padding: 15px; background-color: #e9ecef; border-radius: 4px; }
        .summary h3 { margin-top: 0; }
        .summary ul { padding-left: 20px; }
        .summary li { margin-bottom: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($pageTitle); ?></h1>

        <div class="messages">
            <?php foreach ($errorMessages as $msg): ?>
                <div class="error"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
            <?php foreach ($successMessages as $msg): ?>
                <div class="success"><?php echo htmlspecialchars($msg); ?></div>
            <?php endforeach; ?>
        </div>

        <form action="import_players.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
                <label for="player_csv">Player CSV File:</label>
                <input type="file" id="player_csv" name="player_csv" accept=".csv,text/csv">
            </div>
            <button type="submit">Import Players</button>
        </form>

        <?php if ($importSummary !== null): ?>
            <div class="summary">
                <h3>Import Summary</h3>
                <p>Successfully Imported: <?php echo (int)($importSummary['imported'] ?? 0); ?></p>
                <p>Successfully Updated: <?php echo (int)($importSummary['updated'] ?? 0); ?></p>
                <?php if (!empty($importSummary['errors'])): ?>
                    <p>Errors encountered: <?php echo count($importSummary['errors']); ?></p>
                    <ul>
                        <?php foreach ($importSummary['errors'] as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
