<?php

class PgnParser {
    private string $pgnString;
    private array $headers = [];
    private array $moves = [];
    private bool $parsed = false;

    /**
     * Constructor that takes the full PGN string.
     *
     * @param string $pgnString The PGN string.
     */
    public function __construct(string $pgnString) {
        $this->pgnString = trim($pgnString);
    }

    /**
     * Parses the PGN string to extract headers and movetext if not already parsed.
     */
    private function parse(): void {
        if ($this->parsed) {
            return;
        }

        $lines = explode("\n", str_replace("\r\n", "\n", $this->pgnString));
        $movetextLines = [];
        $inHeaders = true;

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            if ($inHeaders && strpos($line, '[') === 0 && strpos($line, ']') === strlen($line) - 1) {
                if (preg_match('/\[\s*([A-Za-z0-9_]+)\s*"([^"]+)"\s*\]/', $line, $match)) {
                    $this->headers[$match[1]] = $match[2];
                }
            } else {
                $inHeaders = false;
                // If line doesn't start with '[', it's part of the movetext or a comment above it
                if (strpos($line, '[') !== 0) {
                    $movetextLines[] = $line;
                }
            }
        }
        
        $rawMovetext = implode(" ", $movetextLines);
        $this->parseMovetext($rawMovetext);

        $this->parsed = true;
    }

    /**
     * Parses the PGN header tags.
     *
     * @return array Associative array of PGN headers.
     */
    public function getHeaders(): array {
        if (!$this->parsed) {
            $this->parse();
        }
        return $this->headers;
    }

    /**
     * Parses the PGN movetext.
     * This is a simplified parser and does not handle comments, variations, or NAGs yet.
     *
     * @param string $rawMovetext The raw movetext string.
     */
    private function parseMovetext(string $rawMovetext): void {
        // Remove comments { ... }
        $movetext = preg_replace('/\{[^}]*\}/', '', $rawMovetext);
        // Remove comments ; to end of line
        $movetext = preg_replace('/;.*$/m', '', $movetext);
        // Remove variations ( ... ) - simple approach
        $movetext = preg_replace('/\([^)]*\)/', '', $movetext);
        // Remove Numeric Annotation Glyphs (NAGs) like $1, $10
        $movetext = preg_replace('/\$\d+/', '', $movetext);

        // Remove game result markers like 1-0, 0-1, 1/2-1/2, * from the end of the movetext
        $results = ['1-0', '0-1', '1/2-1/2', '*'];
        foreach ($results as $result) {
            if (str_ends_with($movetext, $result)) {
                $movetext = trim(substr($movetext, 0, -strlen($result)));
                break;
            }
        }
        
        // Split into potential moves and move numbers
        $tokens = preg_split('/\s+/', trim($movetext));
        
        $processedMoves = [];
        foreach ($tokens as $token) {
            $token = trim($token);
            if (empty($token)) {
                continue;
            }
            // Filter out move numbers (e.g., "1.", "20...") and game termination markers
            if (!preg_match('/^\d+\.(\.\.)?$/', $token) && !in_array($token, $results)) {
                $processedMoves[] = $token;
            }
        }
        $this->moves = $processedMoves;
    }

    /**
     * Returns an array of moves in SAN (Standard Algebraic Notation).
     *
     * @return array Array of SAN moves.
     */
    public function getMoves(): array {
        if (!$this->parsed) {
            $this->parse();
        }
        return $this->moves;
    }
}
?>
