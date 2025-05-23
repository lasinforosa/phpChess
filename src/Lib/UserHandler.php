<?php

class UserHandler {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Registers a new user.
     *
     * @param string $username
     * @param string $email
     * @param string $password
     * @param string $passwordConfirm
     * @return array ['success' => bool, 'message' => string]
     */
    public function registerUser(string $username, string $email, string $password, string $passwordConfirm): array {
        // Validation
        if (empty($username) || empty($email) || empty($password) || empty($passwordConfirm)) {
            return ['success' => false, 'message' => 'All fields are required.'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['success' => false, 'message' => 'Invalid email format.'];
        }

        if (strlen($password) < 8) { // Basic password strength: minimum length
            return ['success' => false, 'message' => 'Password must be at least 8 characters long.'];
        }

        if ($password !== $passwordConfirm) {
            return ['success' => false, 'message' => 'Passwords do not match.'];
        }

        try {
            // Check for existing username
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Username already exists.'];
            }

            // Check for existing email
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
            $stmt->bindParam(':email', $email, PDO::PARAM_STR);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Email address already registered.'];
            }

            // Password Hashing
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            if ($passwordHash === false) {
                // Log this error server-side as it's a system issue
                error_log("Password hashing failed for user: " . $username);
                return ['success' => false, 'message' => 'An internal error occurred during registration (hashing). Please try again.'];
            }

            // Database Insertion
            $stmtInsert = $this->pdo->prepare(
                "INSERT INTO users (username, email, password_hash, created_at, updated_at) " .
                "VALUES (:username, :email, :password_hash, NOW(), NOW())"
            );
            $stmtInsert->bindParam(':username', $username, PDO::PARAM_STR);
            $stmtInsert->bindParam(':email', $email, PDO::PARAM_STR);
            $stmtInsert->bindParam(':password_hash', $passwordHash, PDO::PARAM_STR);

            if ($stmtInsert->execute()) {
                return ['success' => true, 'message' => 'Registration successful!'];
            } else {
                // Log actual DB error server-side
                error_log("User registration DB error: " . implode(", ", $stmtInsert->errorInfo()));
                return ['success' => false, 'message' => 'Could not register user due to a database error. Please try again.'];
            }

        } catch (PDOException $e) {
            // Log actual DB error server-side
            error_log("PDOException during user registration: " . $e->getMessage());
            return ['success' => false, 'message' => 'A database error occurred during registration. Please try again.'];
        } catch (Exception $e) {
            // Log other unexpected errors
            error_log("General exception during user registration: " . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred. Please try again.'];
        }
    }

    /**
     * Logs in an existing user.
     *
     * @param string $usernameOrEmail
     * @param string $password
     * @return array ['success' => bool, 'message' => string|null, 'user_id' => int|null, 'username' => string|null]
     */
    public function loginUser(string $usernameOrEmail, string $password): array {
        if (empty($usernameOrEmail) || empty($password)) {
            return ['success' => false, 'message' => 'Username/email and password are required.'];
        }

        try {
            // Fetch User by username or email
            $stmt = $this->pdo->prepare("SELECT user_id, username, email, password_hash FROM users WHERE username = :identifier OR email = :identifier");
            $stmt->bindParam(':identifier', $usernameOrEmail, PDO::PARAM_STR);
            $stmt->execute();
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                return ['success' => false, 'message' => 'Invalid username/email or password.'];
            }

            // Verify Password
            if (password_verify($password, $user['password_hash'])) {
                // Session Management
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                
                return ['success' => true];
            } else {
                return ['success' => false, 'message' => 'Invalid username/email or password.'];
            }

        } catch (PDOException $e) {
            error_log("PDOException during user login: " . $e->getMessage());
            return ['success' => false, 'message' => 'A database error occurred during login. Please try again.'];
        } catch (Exception $e) {
            error_log("General exception during user login: " . $e->getMessage());
            return ['success' => false, 'message' => 'An unexpected error occurred during login. Please try again.'];
        }
    }
}
?>
