<?php
/**
 * User Model
 * Handles all database operations related to user accounts.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Register a new user account.
 *
 * @param string $username
 * @param string $email
 * @param string $password   Plain-text password (will be hashed)
 * @param string $fullName
 * @return array{success: bool, message: string}
 */
function registerUser(
    string $username,
    string $email,
    string $password,
    string $fullName
): array {
    $conn = getDBConnection();

    // Check for duplicate username or email
    $stmt = $conn->prepare(
        'SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1'
    );
    $stmt->bind_param('ss', $username, $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Username or email already exists.'];
    }
    $stmt->close();

    // Hash password and insert record
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $conn->prepare(
        'INSERT INTO users (username, email, password, full_name) VALUES (?, ?, ?, ?)'
    );
    $stmt->bind_param('ssss', $username, $email, $hashedPassword, $fullName);

    if ($stmt->execute()) {
        $stmt->close();
        $conn->close();
        return ['success' => true, 'message' => 'Account created successfully.'];
    }

    $error = $stmt->error;
    $stmt->close();
    $conn->close();
    return ['success' => false, 'message' => 'Registration failed: ' . $error];
}

/**
 * Authenticate a user by username and password.
 * On success, populates $_SESSION with user data.
 *
 * @param string $username
 * @param string $password  Plain-text password
 * @return array{success: bool, message: string}
 */
function loginUser(string $username, string $password): array {
    $conn = getDBConnection();

    $stmt = $conn->prepare(
        'SELECT id, username, email, password, full_name, role FROM users WHERE username = ? LIMIT 1'
    );
    $stmt->bind_param('s', $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        $conn->close();
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    $user = $result->fetch_assoc();
    $stmt->close();
    $conn->close();

    if (!password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Invalid username or password.'];
    }

    // Regenerate session ID to prevent session fixation attacks
    session_regenerate_id(true);

    $_SESSION['user_id']        = $user['id'];
    $_SESSION['user_username']  = $user['username'];
    $_SESSION['user_email']     = $user['email'];
    $_SESSION['user_full_name'] = $user['full_name'];
    $_SESSION['user_role']      = $user['role'];

    return ['success' => true, 'message' => 'Login successful.'];
}

/**
 * Destroy the current session, effectively logging out the user.
 *
 * @return void
 */
function logoutUser(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Retrieve total number of registered users.
 *
 * @return int
 */
function getTotalUsers(): int {
    $conn   = getDBConnection();
    $result = $conn->query('SELECT COUNT(*) AS total FROM users');
    $row    = $result->fetch_assoc();
    $conn->close();
    return (int) $row['total'];
}
