<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chess Game View</title>
    <link rel="stylesheet" href="css/chessboard-1.0.0.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<?php
require_once __DIR__ . '/../src/Lib/PgnParser.php';

$game_id = $_GET['game_id'] ?? 'default'; // Default game_id
$whitePlayerId = $_GET['white_player_id'] ?? 'N/A'; // Get from URL
$blackPlayerId = $_GET['black_player_id'] ?? 'N/A'; // Get from URL

$pgnString = '';
$gameTitle = "Selected Game";

// Sample PGN 1 (Kasparov vs. Karpov)
$pgnSample1 = <<<PGN
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
PGN;

// Sample PGN 2 (Short vs. Timman, a different game)
$pgnSample2 = <<<PGN
[Event "Tilburg Fontys"]
[Site "Tilburg NED"]
[Date "1991.10.18"]
[Round "13"]
[White "Short, Nigel D"]
[Black "Timman, Jan H"]
[Result "1-0"]
[ECO "C88"]
[WhiteElo "2660"]
[BlackElo "2630"]
[PlyCount "81"]

1.e4 e5 2.Nf3 Nc6 3.Bb5 a6 4.Ba4 Nf6 5.O-O Be7 6.Re1 b5 7.Bb3 O-O
8.h3 Bb7 9.d3 d6 10.a3 Nb8 11.Nbd2 Nbd7 12.Nf1 Re8 13.Ng3 Bf8
14.Ng5 d5 15.exd5 Nc5 16.Ba2 Nxd5 17.N5e4 Ne6 18.Qg4 Kh8 19.Ng5
Qd7 20.Nxe6 Rxe6 21.Qf3 Rae8 22.Ne4 Rg6 23.Ng3 Bc5 24.Be3 Bxe3
25.fxe3 Rf6 26.Qh5 g6 27.Qh4 Qd8 28.Rf1 Rxf1+ 29.Rxf1 f5 30.Qxd8
Rxd8 31.Bxd5 Bxd5 32.Ne2 c5 33.Nc3 Bc6 34.b4 cxb4 35.axb4 Kg7
36.Ra1 Bb7 37.Kf2 Rc8 38.Ra3 Kf6 39.g3 Ke6 40.Ke2 Kd6 41.Kd2 1-0
PGN;

// Default PGN
$pgnDefault = <<<PGN
[Event "Default Game"]
[Site "?"]
[Date "????.??.??"]
[Round "?"]
[White "PlayerW"]
[Black "PlayerB"]
[Result "*"]

1. e4 e5 *
PGN;

if ($game_id === 'sample1') {
    $pgnString = $pgnSample1;
    $gameTitle = "Kasparov vs. Karpov (1985)";
} elseif ($game_id === 'sample2') {
    $pgnString = $pgnSample2;
    $gameTitle = "Short vs. Timman (1991)";
} else {
    $pgnString = $pgnDefault;
    $gameTitle = "Default Game";
    if ($game_id !== 'default') {
        $gameTitle = "Unknown Game ID: " . htmlspecialchars($game_id) . " - Showing Default";
    }
}

$parser = new PgnParser($pgnString);
$headers = $parser->getHeaders();
$moves = $parser->getMoves();
// Update page title based on game, if available
if (!empty($headers['White']) && !empty($headers['Black'])) {
    $pageTitle = htmlspecialchars($headers['White']) . " vs " . htmlspecialchars($headers['Black']);
    if (!empty($headers['Date'])) {
        $pageTitle .= " (" . htmlspecialchars($headers['Date']) . ")";
    }
} else {
    $pageTitle = $gameTitle;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="css/chessboard-1.0.0.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

    <h1><?php echo $gameTitle; ?></h1>
    <p>Displaying game for White Player ID: <?php echo htmlspecialchars($whitePlayerId); ?>, Black Player ID: <?php echo htmlspecialchars($blackPlayerId); ?></p>
?>

    <div id="gameBoard" style="width: 400px"></div>


    <div class="pgn-data">
        <div class="pgn-headers">
            <h2>PGN Headers</h2>
            <?php if (!empty($headers)): ?>
                <dl>
                    <?php foreach ($headers as $key => $value): ?>
                        <dt><?php echo htmlspecialchars($key); ?></dt>
                        <dd><?php echo htmlspecialchars($value); ?></dd>
                    <?php endforeach; ?>
                </dl>
            <?php else: ?>
                <p>No headers found.</p>
            <?php endif; ?>
        </div>

        <div class="pgn-moves">
            <h2>PGN Moves</h2>
            <?php if (!empty($moves)): ?>
                <ol>
                    <?php
                    $moveCount = 1;
                    $isWhiteMove = true;
                    foreach ($moves as $move):
                        if ($isWhiteMove): ?>
                            <li><span class="move-number"><?php echo $moveCount; ?>.</span> <?php echo htmlspecialchars($move); ?>
                        <?php else: ?>
                            <?php echo htmlspecialchars($move); ?></li>
                            <?php $moveCount++;
                        endif;
                        $isWhiteMove = !$isWhiteMove;
                    endforeach;
                    // Ensure the last li is closed if the game ends on White's move
                    if (!$isWhiteMove) { echo '</li>'; }
                    ?>
                </ol>
            <?php else: ?>
                <p>No moves found.</p>
            <?php endif; ?>
        </div>
    </div>

    <script src="js/jquery-3.7.1.min.js"></script>
    <script src="js/chessboard-1.0.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Ensure the Chessboard function is available
            if (typeof Chessboard === 'function') {
                var board = Chessboard('gameBoard', 'start'); // 'start' displays the initial chess position
            } else {
                console.error("Chessboard function not found. Ensure chessboard-1.0.0.min.js is loaded correctly.");
                // Optionally, display a message to the user in the gameBoard div
                $('#gameBoard').html('<p style="color: red;">Error: Chessboard library could not be loaded. Please check the console.</p>');
            }
        });
    </script>

</body>
</html>
