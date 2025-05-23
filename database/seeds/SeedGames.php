<?php

// Ensure we are running from CLI
if (php_sapi_name() !== 'cli') {
    die("This script can only be run from the command line.\n");
}

echo "Starting game seeding process...\n";

// 1. Include Dependencies
$configPath = __DIR__ . '/../../config/database.php';
// PgnParser is not strictly needed if PGN data is pre-formatted for insertion.
// $pgnParserPath = __DIR__ . '/../../src/Lib/PgnParser.php';

if (!file_exists($configPath)) {
    die("Error: Database configuration file not found at {$configPath}\n");
}
// if (!file_exists($pgnParserPath)) {
//     die("Error: PgnParser class file not found at {$pgnParserPath}\n");
// }

$dbConfig = require $configPath;
// require_once $pgnParserPath;

// 2. Establish Database Connection
$dsn = $dbConfig['dsn'];
$username = $dbConfig['username'];
$password = $dbConfig['password'];
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    echo "Database connection established successfully.\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// 3. Define Sample Player Data (Optional, for robustness)
try {
    $stmtCheckPlayer = $pdo->prepare("SELECT player_id FROM players WHERE player_id = :player_id");
    
    // Player 1
    $stmtCheckPlayer->execute([':player_id' => 1]);
    if (!$stmtCheckPlayer->fetch()) {
        $stmtInsertPlayer = $pdo->prepare("INSERT INTO players (player_id, name, fide_id, created_at, updated_at) VALUES (:player_id, :name, :fide_id, NOW(), NOW()) ON DUPLICATE KEY UPDATE name=name"); // Use ON DUPLICATE KEY UPDATE to be safe
        $stmtInsertPlayer->execute([
            ':player_id' => 1,
            ':name' => 'Player One (Sample)',
            ':fide_id' => 1000001,
        ]);
        echo "Sample Player 1 (ID: 1) inserted or already exists.\n";
    } else {
        echo "Sample Player 1 (ID: 1) already exists.\n";
    }

    // Player 2
    $stmtCheckPlayer->execute([':player_id' => 2]);
    if (!$stmtCheckPlayer->fetch()) {
        $stmtInsertPlayer->execute([
            ':player_id' => 2,
            ':name' => 'Player Two (Sample)',
            ':fide_id' => 1000002,
        ]);
        echo "Sample Player 2 (ID: 2) inserted or already exists.\n";
    } else {
        echo "Sample Player 2 (ID: 2) already exists.\n";
    }

} catch (PDOException $e) {
    echo "Error during sample player check/insertion: " . $e->getMessage() . "\n";
    // Decide if you want to die here or continue if players might exist or are not critical for some reason
}


// 4. Define Sample Game Data
$gamesData = [
    [
        'white_player_id' => 1,
        'black_player_id' => 2,
        'white_rating' => 2700,
        'black_rating' => 2720,
        'result' => '0-1',
        'pgn_content' => <<<PGN
[Event "FIDE World Championship Match"]
[Site "Moscow RUS"]
[Date "1985.09.03"]
[Round "1"]
[White "Kasparov, Garry"]
[Black "Karpov, Anatoly"]
[Result "0-1"]
[ECO "E21"]
[WhiteElo "2700"]
[BlackElo "2720"]
[PlyCount "80"]

1. d4 Nf6 2. c4 e6 3. Nc3 Bb4 4. Nf3 O-O 5. Bg5 c5 6. e3 cxd4 7. exd4
h6 8. Bh4 Qa5 9. Qc2 Ne4 10. Rc1 d5 11. cxd5 exd5 12. a3 Bxc3+ 13. bxc3
Bf5 14. Qb2 Re8 15. Be2 Nd7 16. O-O Rac8 17. Qb4 Qc7 18. c4 a5 19. Qb2
dxc4 20. Bxc4 Qf4 21. Bg3 Nxg3 22. hxg3 Qd6 23. Qb3 Be6 24. d5 Bg4
25. Nd4 Nc5 26. Qb1 Bd7 27. Rfd1 Re4 28. Qb2 Rce8 29. Bf1 Na4 30. Qb3
Nc5 31. Qc3 b6 32. Rb1 Na4 33. Qd2 Qc5 34. Rbc1 Qxa3 35. Rc7 Qd6 36. Rxd7
Qxd7 37. Bb5 Qd6 38. Bxe8 Rxe8 39. Nf5 Qe5 40. g4 Nc3 0-1
PGN,
        'eco_code' => 'E21',
        'event_name' => 'FIDE World Championship Match',
        'site_name' => 'Moscow RUS',
        'game_date' => '1985-09-03',
        'round_info' => '1',
        'user_id' => null, // Or a specific user ID if a sample user is created
    ],
    [
        'white_player_id' => 2,
        'black_player_id' => 1,
        'white_rating' => 2685,
        'black_rating' => 2620,
        'result' => '1-0',
        'pgn_content' => <<<PGN
[Event "Candidates semi-final"]
[Site "Linares ESP"]
[Date "1992.04.06"]
[Round "1"]
[White "Short, Nigel D"]
[Black "Timman, Jan H"]
[Result "1-0"]
[ECO "C88"]
[WhiteElo "2685"]
[BlackElo "2620"]
[PlyCount "81"]

1. e4 e5 2. Nf3 Nc6 3. Bb5 a6 4. Ba4 Nf6 5. O-O Be7 6. Re1 b5 7. Bb3 O-O
8. h3 Bb7 9. d3 d6 10. a3 Nb8 11. Nbd2 Nbd7 12. Nf1 Re8 13. Ng3 Bf8
14. Ng5 d5 15. exd5 Nc5 16. Ba2 Nxd5 17. N5e4 Ne6 18. Nf5 Nf6 19. Qf3
Nxe4 20. dxe4 Qf6 21. Qg3 Kh8 22. Bxe6 Rxe6 23. Bg5 Qg6 24. Rad1 Bc6
25. Rd3 Rae8 26. Red1 f6 27. Be3 Qxg3 28. Nxg3 Rd6 29. Bc5 Rxd3 30. Rxd3
Bxc5 31. Rc3 Bxf2+ 32. Kxf2 Re6 33. Rd3 g6 34. Rd8+ Kg7 35. Rc8 Re7
36. Ke3 Kf7 37. Ne2 Bb7 38. Rb8 c5 39. Nc3 Rd7 40. Na2 Ke7 41. Rh8 1-0
PGN,
        'eco_code' => 'C88',
        'event_name' => 'Candidates semi-final',
        'site_name' => 'Linares ESP',
        'game_date' => '1992-04-06',
        'round_info' => '1',
        'user_id' => null,
    ],
];

// 5. Insert Games into Database
$pdo->beginTransaction();
try {
    $stmtInsertGame = $pdo->prepare(
        "INSERT INTO games (white_player_id, black_player_id, white_rating, black_rating, result, pgn_content, eco_code, event_name, site_name, game_date, round_info, user_id, created_at, updated_at) " .
        "VALUES (:white_player_id, :black_player_id, :white_rating, :black_rating, :result, :pgn_content, :eco_code, :event_name, :site_name, :game_date, :round_info, :user_id, NOW(), NOW())"
    );

    foreach ($gamesData as $game) {
        // Basic check: Ensure referenced players exist before attempting to insert game
        // More robust checks can be added if player IDs are not hardcoded (e.g. fetched based on name)
        $stmtCheckPlayer->execute([':player_id' => $game['white_player_id']]);
        if (!$stmtCheckPlayer->fetch()) {
            echo "Skipping game '{$game['event_name']}' because white_player_id {$game['white_player_id']} does not exist.\n";
            continue;
        }
        $stmtCheckPlayer->execute([':player_id' => $game['black_player_id']]);
        if (!$stmtCheckPlayer->fetch()) {
            echo "Skipping game '{$game['event_name']}' because black_player_id {$game['black_player_id']} does not exist.\n";
            continue;
        }

        $stmtInsertGame->execute([
            ':white_player_id' => $game['white_player_id'],
            ':black_player_id' => $game['black_player_id'],
            ':white_rating' => $game['white_rating'],
            ':black_rating' => $game['black_rating'],
            ':result' => $game['result'],
            ':pgn_content' => $game['pgn_content'],
            ':eco_code' => $game['eco_code'],
            ':event_name' => $game['event_name'],
            ':site_name' => $game['site_name'],
            ':game_date' => $game['game_date'],
            ':round_info' => $game['round_info'],
            ':user_id' => $game['user_id'],
        ]);
        echo "Sample game '{$game['event_name']}' inserted successfully.\n";
    }

    $pdo->commit();
    echo "All sample games inserted successfully.\n";

} catch (PDOException $e) {
    $pdo->rollBack();
    echo "Error during game insertion: " . $e->getMessage() . "\n";
    die("Game seeding failed. Transaction rolled back.\n");
}

echo "Game seeding process completed.\n";

?>
