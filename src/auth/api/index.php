<?php
/**
 * Authentication Handler for Login Form
 *
 * This PHP script handles user authentication via POST requests from the Fetch API.
 * It validates credentials against a MySQL database using PDO,
 * creates sessions, and returns JSON responses.
 */

// --- Session Management ---
// Start a PHP session using session_start()
// This must be called before any output is sent to the browser
// Sessions allow us to store user data across multiple pages
session_start();

// --- Set Response Headers ---
// Set the Content-Type header to 'application/json'
// This tells the browser that we're sending JSON data back
header('Content-Type: application/json');

// Include the shared database connection file
require_once '../../common/db.php';

// --- Check Request Method ---
// Verify that the request method is POST
// If the request is not POST, return an error response and exit
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
    exit;
}

// --- Get POST Data ---
// Retrieve the raw POST data from the Fetch API request body
$rawData = file_get_contents('php://input');

// Decode the JSON data into a PHP associative array
$data = json_decode($rawData, true);

// Extract the email and password from the decoded data
// Check if both 'email' and 'password' keys exist
if (!isset($data['email']) || !isset($data['password'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Email and password are required'
    ]);
    exit;
}

// Store the email and password in variables
// Trim any whitespace from the email
$email = trim($data['email']);
$password = $data['password'];

// --- Server-Side Validation ---
// Validate the email format on the server side
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format'
    ]);
    exit;
}

// Validate the password length, minimum 8 characters
if (strlen($password) < 8) {
    echo json_encode([
        'success' => false,
        'message' => 'Password must be at least 8 characters'
    ]);
    exit;
}

// --- Database Connection ---
// Get the database connection using the provided getDBConnection() function
// Wrap database operations in a try-catch block to handle PDO exceptions
try {
    $pdo = getDBConnection();

    // --- Prepare SQL Query ---
    // Write a SQL SELECT query to find the user by email
    // Select id, name, email, password, and is_admin
    // Use a named placeholder to prevent SQL injection attacks
    $sql = "SELECT id, name, email, password, is_admin
            FROM users
            WHERE email = :email";

    // --- Prepare the Statement ---
    // Prepare the SQL statement using the PDO prepare method
    $stmt = $pdo->prepare($sql);

    // --- Execute the Query ---
    // Execute the prepared statement with the email parameter
    $stmt->execute([
        ':email' => $email
    ]);

    // --- Fetch User Data ---
    // Fetch the user record from the database as an associative array
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // --- Verify User Exists and Password Matches ---
    // Check if a user was found and verify the submitted password
    // password_verify compares the submitted password with the hashed database password
    if ($user && password_verify($password, $user['password'])) {

        // --- Handle Successful Authentication ---
        // Store safe user information in session variables
        // Do not store the password in the session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['is_admin'] = $user['is_admin'];
        $_SESSION['logged_in'] = true;

        // Prepare a success response array
        // Include safe user details only
        echo json_encode([
            'success' => true,
            'message' => 'Login successful',
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'is_admin' => $user['is_admin']
            ]
        ]);

        exit;
    }

    // --- Handle Failed Authentication ---
    // If user does not exist or password verification fails,
    // return a general error message for security
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email or password'
    ]);

    exit;

} catch (PDOException $e) {

    // Catch PDO exceptions
    // Log the error for debugging
    error_log($e->getMessage());

    // Return a generic error message to the client
    // Do not expose database details to the user
    echo json_encode([
        'success' => false,
        'message' => 'Server error'
    ]);

    exit;
}

?>
