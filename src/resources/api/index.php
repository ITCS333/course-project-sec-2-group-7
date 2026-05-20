<?php
/**
 * Course Resources API
 * 
 * This is a RESTful API that handles all CRUD operations for course resources 
 * and their associated comments/discussions.
 * It uses PDO to interact with a MySQL database.
 * 
 * Database Table Structures (for reference):
 * 
 * Table: resources
 * Columns:
 *   - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - title (VARCHAR(255), NOT NULL)
 *   - description (TEXT, nullable)
 *   - link (VARCHAR(500), NOT NULL)
 *   - created_at (TIMESTAMP)
 * 
 * Table: comments_resource
 * Columns:
 *   - id (INT UNSIGNED, PRIMARY KEY, AUTO_INCREMENT)
 *   - resource_id (INT UNSIGNED, FOREIGN KEY references resources.id, CASCADE DELETE)
 *   - author (VARCHAR(100), NOT NULL)
 *   - text (TEXT, NOT NULL)
 *   - created_at (TIMESTAMP)
 * 
 * HTTP Methods Supported:
 *   - GET:    Retrieve resource(s) or comment(s)
 *   - POST:   Create a new resource or comment
 *   - PUT:    Update an existing resource
 *   - DELETE: Delete a resource (associated comments in comments_resource are
 *             removed automatically by the ON DELETE CASCADE constraint)
 * 
 * Response Format: JSON
 * All responses follow the structure:
 *   { "success": true,  "data": ...    }  (on success)
 *   { "success": false, "message": ... }  (on error)
 * 
 * API Endpoints:
 * 
 *   Resources:
 *     GET    /resources/api/index.php                         - Get all resources
 *     GET    /resources/api/index.php?id={id}                 - Get single resource by ID
 *     POST   /resources/api/index.php                         - Create new resource
 *     PUT    /resources/api/index.php                         - Update resource
 *     DELETE /resources/api/index.php?id={id}                 - Delete resource
 * 
 *   Comments:
 *     GET    /resources/api/index.php?resource_id={id}&action=comments
 *                                                             - Get all comments for a resource
 *     POST   /resources/api/index.php?action=comment          - Create a new comment
 *     DELETE /resources/api/index.php?comment_id={id}&action=delete_comment
 *                                                             - Delete a single comment
 * 
 * Query Parameters for GET all resources:
 *   - search: Optional. Filter resources by title or description using LIKE.
 *   - sort:   Optional. Sort field — allowed values: title, created_at (default: created_at).
 *   - order:  Optional. Sort direction — allowed values: asc, desc (default: desc).
 */

// ============================================================================
// HEADERS AND INITIALIZATION
// ============================================================================

// TODO: Set headers for JSON response and CORS
// Set Content-Type to application/json
header('Content-Type: application/json');
// Allow cross-origin requests (CORS) if needed
header('Access-Control-Allow-Origin: *');
// Allow specific HTTP methods (GET, POST, PUT, DELETE, OPTIONS)
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
// Allow specific headers (Content-Type, Authorization)
header('Access-Control-Allow-Headers: Content-Type, Authorization');


// TODO: Handle preflight OPTIONS request
// If the request method is OPTIONS, return 200 status and exit
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}


// TODO: Include the database connection file
require_once '../../common/db.php';


// TODO: Get the PDO database connection
$db = getDBConnection();


// TODO: Get the HTTP request method
$method = $_SERVER['REQUEST_METHOD'];


// TODO: Get the request body for POST and PUT requests
$rawData = file_get_contents('php://input');
$data    = json_decode($rawData, true);


// TODO: Parse query parameters from $_GET
// Get 'action', 'id', 'resource_id', 'comment_id'
$action     = isset($_GET['action'])      ? $_GET['action']      : '';
$id         = isset($_GET['id'])          ? $_GET['id']          : null;
$resourceId = isset($_GET['resource_id']) ? $_GET['resource_id'] : null;
$commentId  = isset($_GET['comment_id'])  ? $_GET['comment_id']  : null;


// ============================================================================
// RESOURCE FUNCTIONS
// ============================================================================

/**
 * Function: Get all resources
 * Method: GET (no id or action parameter)
 * 
 * Query Parameters:
 *   - search: Optional search term to filter by title or description
 *   - sort:   Optional field to sort by — allowed values: title, created_at
 *   - order:  Optional sort direction — allowed values: asc, desc (default: desc)
 * 
 * Response:
 *   { "success": true, "data": [ ...resource objects ] }
 */
function getAllResources($db) {
    // TODO: Initialize the base SQL query
    $sql = 'SELECT id, title, description, link, created_at FROM resources';

    // TODO: Check if search parameter exists in $_GET
    $search = null;
    if (!empty($_GET['search'])) {
        // If yes, add WHERE clause using LIKE to search title and description
        $search = $_GET['search'];
        $sql .= ' WHERE title LIKE :search OR description LIKE :search';
    }

    // TODO: Validate the sort parameter
    // Allowed values: title, created_at
    $allowedSort = ['title', 'created_at'];
    $sort = isset($_GET['sort']) && in_array($_GET['sort'], $allowedSort)
        ? $_GET['sort']
        : 'created_at';

    // TODO: Validate the order parameter
    // Allowed values: asc, desc
    $allowedOrder = ['asc', 'desc'];
    $order = isset($_GET['order']) && in_array(strtolower($_GET['order']), $allowedOrder)
        ? strtolower($_GET['order'])
        : 'desc';

    // TODO: Add ORDER BY clause to the query
    $sql .= " ORDER BY {$sort} {$order}";

    // TODO: Prepare the statement using PDO
    $stmt = $db->prepare($sql);

    // TODO: If a search parameter was used, bind it with % wildcards
    if ($search !== null) {
        $stmt->bindValue(':search', '%' . $search . '%');
    }

    // TODO: Execute the query
    $stmt->execute();

    // TODO: Fetch all results as an associative array
    $resources = $stmt->fetchAll();

    // TODO: Return JSON response using sendResponse()
    sendResponse(['success' => true, 'data' => $resources]);
}


/**
 * Function: Get a single resource by ID
 * Method: GET with ?id={id}
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID (from $_GET['id'])
 * 
 * Response (success):
 *   { "success": true, "data": { id, title, description, link, created_at } }
 * Response (not found):
 *   HTTP 404 — { "success": false, "message": "Resource not found." }
 */
function getResourceById($db, $resourceId) {
    // TODO: Validate that $resourceId is provided and is numeric
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID.'], 400);
        return;
    }

    // TODO: Prepare SQL query
    $stmt = $db->prepare(
        'SELECT id, title, description, link, created_at FROM resources WHERE id = ?'
    );

    // TODO: Bind $resourceId and execute
    $stmt->execute([$resourceId]);

    // TODO: Fetch the result as an associative array
    $resource = $stmt->fetch();

    // TODO: If found, return success response with resource data
    if ($resource) {
        sendResponse(['success' => true, 'data' => $resource]);
    } else {
        // If not found, return error response with HTTP 404
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
    }
}


/**
 * Function: Create a new resource
 * Method: POST (no action parameter)
 * 
 * Required JSON Body:
 *   - title:       Resource title (required)
 *   - description: Resource description (optional, defaults to empty string)
 *   - link:        URL to the resource (required, must be a valid URL)
 * 
 * Response (success):
 *   HTTP 201 — { "success": true, "message": "...", "id": <new resource id> }
 * Response (validation error):
 *   HTTP 400 — { "success": false, "message": "..." }
 */
function createResource($db, $data) {
    // TODO: Validate required fields — title and link must not be empty
    $validation = validateRequiredFields($data, ['title', 'link']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $validation['missing'])
        ], 400);
        return;
    }

    // TODO: Sanitize input — trim whitespace from all fields
    $title       = sanitizeInput($data['title']);
    $description = isset($data['description']) ? sanitizeInput($data['description']) : '';
    $link        = sanitizeInput($data['link']);

    // TODO: Validate the link using filter_var with FILTER_VALIDATE_URL
    if (!validateUrl($link)) {
        sendResponse(['success' => false, 'message' => 'Invalid URL provided.'], 400);
        return;
    }

    // TODO: Prepare INSERT query
    $stmt = $db->prepare(
        'INSERT INTO resources (title, description, link) VALUES (?, ?, ?)'
    );

    // TODO: Bind title, description, and link; then execute
    $stmt->execute([$title, $description, $link]);

    // TODO: If rowCount() > 0, return success response with HTTP 201
    if ($stmt->rowCount() > 0) {
        sendResponse([
            'success' => true,
            'message' => 'Resource created successfully.',
            'id'      => $db->lastInsertId()
        ], 201);
    } else {
        // If failed, return error response with HTTP 500
        sendResponse(['success' => false, 'message' => 'Failed to create resource.'], 500);
    }
}


/**
 * Function: Update an existing resource
 * Method: PUT
 * 
 * Required JSON Body:
 *   - id:          The resource's database ID (required)
 *   - title:       Updated title (optional)
 *   - description: Updated description (optional)
 *   - link:        Updated URL (optional, must be a valid URL if provided)
 * 
 * Response (success):
 *   HTTP 200 — { "success": true, "message": "Resource updated successfully." }
 * Response (not found):
 *   HTTP 404 — { "success": false, "message": "Resource not found." }
 * Response (validation error):
 *   HTTP 400 — { "success": false, "message": "..." }
 */
function updateResource($db, $data) {
    // TODO: Validate that id is provided in $data
    if (empty($data['id'])) {
        sendResponse(['success' => false, 'message' => 'Resource ID is required.'], 400);
        return;
    }

    $resourceId = $data['id'];

    // TODO: Check if the resource exists — SELECT by id
    $stmt = $db->prepare('SELECT id FROM resources WHERE id = ?');
    $stmt->execute([$resourceId]);
    if (!$stmt->fetch()) {
        // If not found, return error response with HTTP 404
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
        return;
    }

    // TODO: Build UPDATE query dynamically for only the fields provided
    $fields = [];
    $values = [];

    if (isset($data['title'])) {
        $fields[] = 'title = ?';
        $values[] = sanitizeInput($data['title']);
    }
    if (isset($data['description'])) {
        $fields[] = 'description = ?';
        $values[] = sanitizeInput($data['description']);
    }
    if (isset($data['link'])) {
        // TODO: If link is being updated, validate it with FILTER_VALIDATE_URL
        if (!validateUrl($data['link'])) {
            sendResponse(['success' => false, 'message' => 'Invalid URL provided.'], 400);
            return;
        }
        $fields[] = 'link = ?';
        $values[] = sanitizeInput($data['link']);
    }

    // If no fields to update, return error response with HTTP 400
    if (empty($fields)) {
        sendResponse(['success' => false, 'message' => 'No fields to update.'], 400);
        return;
    }

    // TODO: Build the final SQL
    $sql      = 'UPDATE resources SET ' . implode(', ', $fields) . ' WHERE id = ?';
    $values[] = $resourceId;

    // TODO: Prepare, bind all update values then bind id, and execute
    $stmt = $db->prepare($sql);
    $stmt->execute($values);

    // TODO: Return success response with HTTP 200
    sendResponse(['success' => true, 'message' => 'Resource updated successfully.']);
}


/**
 * Function: Delete a resource
 * Method: DELETE with ?id={id}
 * 
 * Parameters:
 *   - $resourceId: The resource's database ID (from $_GET['id'])
 * 
 * Response (success):
 *   HTTP 200 — { "success": true, "message": "Resource deleted successfully." }
 * Response (not found):
 *   HTTP 404 — { "success": false, "message": "Resource not found." }
 * 
 * Note: All associated comments in comments_resource are deleted automatically
 *       by the ON DELETE CASCADE foreign key constraint — no manual deletion
 *       of comments is needed.
 */
function deleteResource($db, $resourceId) {
    // TODO: Validate that $resourceId is provided and is numeric
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID.'], 400);
        return;
    }

    // TODO: Check if the resource exists — SELECT by id
    $stmt = $db->prepare('SELECT id FROM resources WHERE id = ?');
    $stmt->execute([$resourceId]);
    if (!$stmt->fetch()) {
        // If not found, return error response with HTTP 404
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
        return;
    }

    // TODO: Prepare DELETE query
    $stmt = $db->prepare('DELETE FROM resources WHERE id = ?');

    // TODO: Bind $resourceId and execute
    $stmt->execute([$resourceId]);

    // TODO: If rowCount() > 0, return success response with HTTP 200
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Resource deleted successfully.']);
    } else {
        // If failed, return error response with HTTP 500
        sendResponse(['success' => false, 'message' => 'Failed to delete resource.'], 500);
    }
}


// ============================================================================
// COMMENT FUNCTIONS
// ============================================================================

/**
 * Function: Get all comments for a specific resource
 * Method: GET with ?resource_id={id}&action=comments
 * 
 * Query Parameters:
 *   - resource_id: The resource's database ID (required)
 * 
 * Response:
 *   { "success": true, "data": [ ...comment objects ] }
 *   Returns an empty data array if no comments exist (not an error).
 *
 * Each comment object: { id, resource_id, author, text, created_at }
 */
function getCommentsByResourceId($db, $resourceId) {
    // TODO: Validate that $resourceId is provided and is numeric
    if (!$resourceId || !is_numeric($resourceId)) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID.'], 400);
        return;
    }

    // TODO: Prepare SQL query
    $stmt = $db->prepare(
        'SELECT id, resource_id, author, text, created_at
         FROM comments_resource
         WHERE resource_id = ?
         ORDER BY created_at ASC'
    );

    // TODO: Bind $resourceId and execute
    $stmt->execute([$resourceId]);

    // TODO: Fetch all results as an associative array
    $comments = $stmt->fetchAll();

    // TODO: Return success response — always return an array
    sendResponse(['success' => true, 'data' => $comments]);
}


/**
 * Function: Create a new comment
 * Method: POST with ?action=comment
 * 
 * Required JSON Body:
 *   - resource_id: The resource's database ID (required, must be numeric)
 *   - author:      Name of the comment author (required)
 *   - text:        Comment text content (required)
 * 
 * Response (success):
 *   HTTP 201 — { "success": true, "message": "...", "id": <new comment id> }
 * Response (resource not found):
 *   HTTP 404 — { "success": false, "message": "Resource not found." }
 * Response (validation error):
 *   HTTP 400 — { "success": false, "message": "..." }
 */
function createComment($db, $data) {
    // TODO: Validate required fields — resource_id, author, and text
    $validation = validateRequiredFields($data, ['resource_id', 'author', 'text']);
    if (!$validation['valid']) {
        sendResponse([
            'success' => false,
            'message' => 'Missing required fields: ' . implode(', ', $validation['missing'])
        ], 400);
        return;
    }

    // TODO: Validate that resource_id is numeric
    if (!is_numeric($data['resource_id'])) {
        sendResponse(['success' => false, 'message' => 'Invalid resource ID.'], 400);
        return;
    }

    // TODO: Check that the resource exists in the resources table
    $stmt = $db->prepare('SELECT id FROM resources WHERE id = ?');
    $stmt->execute([$data['resource_id']]);
    if (!$stmt->fetch()) {
        // If not found, return error response with HTTP 404
        sendResponse(['success' => false, 'message' => 'Resource not found.'], 404);
        return;
    }

    // TODO: Sanitize author and text — trim whitespace
    $author = sanitizeInput($data['author']);
    $text   = sanitizeInput($data['text']);

    // TODO: Prepare INSERT query
    $stmt = $db->prepare(
        'INSERT INTO comments_resource (resource_id, author, text) VALUES (?, ?, ?)'
    );

    // TODO: Bind resource_id, author, and text; then execute
    $stmt->execute([$data['resource_id'], $author, $text]);

    // TODO: If rowCount() > 0, return success response with HTTP 201
    if ($stmt->rowCount() > 0) {
        $newId = $db->lastInsertId();
        sendResponse([
            'success' => true,
            'message' => 'Comment created successfully.',
            'id'      => $newId,
            'data'    => [
                'id'          => $newId,
                'resource_id' => $data['resource_id'],
                'author'      => $author,
                'text'        => $text,
                'created_at'  => date('Y-m-d H:i:s')
            ]
        ], 201);
    } else {
        // If failed, return error response with HTTP 500
        sendResponse(['success' => false, 'message' => 'Failed to create comment.'], 500);
    }
}


/**
 * Function: Delete a comment
 * Method: DELETE with ?comment_id={id}&action=delete_comment
 * 
 * Query Parameters:
 *   - comment_id: The comment's database ID (required)
 * 
 * Response (success):
 *   HTTP 200 — { "success": true, "message": "Comment deleted successfully." }
 * Response (not found):
 *   HTTP 404 — { "success": false, "message": "Comment not found." }
 */
function deleteComment($db, $commentId) {
    // TODO: Validate that $commentId is provided and is numeric
    if (!$commentId || !is_numeric($commentId)) {
        sendResponse(['success' => false, 'message' => 'Invalid comment ID.'], 400);
        return;
    }

    // TODO: Check if the comment exists in comments_resource — SELECT by id
    $stmt = $db->prepare('SELECT id FROM comments_resource WHERE id = ?');
    $stmt->execute([$commentId]);
    if (!$stmt->fetch()) {
        // If not found, return error response with HTTP 404
        sendResponse(['success' => false, 'message' => 'Comment not found.'], 404);
        return;
    }

    // TODO: Prepare DELETE query
    $stmt = $db->prepare('DELETE FROM comments_resource WHERE id = ?');

    // TODO: Bind $commentId and execute
    $stmt->execute([$commentId]);

    // TODO: If rowCount() > 0, return success response with HTTP 200
    if ($stmt->rowCount() > 0) {
        sendResponse(['success' => true, 'message' => 'Comment deleted successfully.']);
    } else {
        // If failed, return error response with HTTP 500
        sendResponse(['success' => false, 'message' => 'Failed to delete comment.'], 500);
    }
}


// ============================================================================
// MAIN REQUEST ROUTER
// ============================================================================

try {
    // TODO: Route the request based on $method and $action

    if ($method === 'GET') {

        // If action === 'comments', return all comments for a resource
        if ($action === 'comments') {
            getCommentsByResourceId($db, $resourceId);

        // If 'id' is present in $_GET, return a single resource
        } elseif ($id !== null) {
            getResourceById($db, $id);

        // Otherwise, return all resources (supports ?search=, ?sort=, ?order=)
        } else {
            getAllResources($db);
        }

    } elseif ($method === 'POST') {

        // If action === 'comment', create a new comment
        if ($action === 'comment') {
            createComment($db, $data);

        // Otherwise, create a new resource
        } else {
            createResource($db, $data);
        }

    } elseif ($method === 'PUT') {

        // Update an existing resource
        updateResource($db, $data);

    } elseif ($method === 'DELETE') {

        // If action === 'delete_comment', delete a single comment
        if ($action === 'delete_comment') {
            deleteComment($db, $commentId);

        // Otherwise, delete a resource
        } else {
            deleteResource($db, $id);
        }

    } else {
        // Return HTTP 405 Method Not Allowed for unsupported methods
        sendResponse(['success' => false, 'message' => 'Method not allowed.'], 405);
    }

} catch (PDOException $e) {
    // TODO: Log the error with error_log()
    error_log($e->getMessage());
    // Return a generic HTTP 500 error — do NOT expose $e->getMessage() to the client
    sendResponse(['success' => false, 'message' => 'Database error occurred.'], 500);

} catch (Exception $e) {
    // TODO: Log the error with error_log()
    error_log($e->getMessage());
    // Return HTTP 500 error response using sendResponse()
    sendResponse(['success' => false, 'message' => 'An unexpected error occurred.'], 500);
}


// ============================================================================
// HELPER FUNCTIONS
// ============================================================================

/**
 * Helper: Send a JSON response and stop execution.
 * 
 * @param array $data        Response payload. Must include a 'success' key.
 * @param int   $statusCode  HTTP status code (default: 200).
 */
function sendResponse($data, $statusCode = 200) {
    // TODO: Set the HTTP status code using http_response_code()
    http_response_code($statusCode);

    // TODO: Ensure $data is an array; if not, wrap it
    if (!is_array($data)) {
        $data = ['data' => $data];
    }

    // TODO: Echo json_encode($data) and call exit
    echo json_encode($data);
    exit;
}


/**
 * Helper: Validate a URL string.
 * 
 * @param  string $url
 * @return bool  True if the URL passes FILTER_VALIDATE_URL, false otherwise.
 */
function validateUrl($url) {
    // TODO: Use filter_var($url, FILTER_VALIDATE_URL)
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}


/**
 * Helper: Sanitize a single input string.
 * 
 * @param  string $data
 * @return string  Trimmed, tag-stripped, and HTML-encoded string.
 */
function sanitizeInput($data) {
    // TODO: trim() → strip_tags() → htmlspecialchars(ENT_QUOTES, 'UTF-8')
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}


/**
 * Helper: Check that all required fields exist and are non-empty in $data.
 * 
 * @param  array $data            Associative array of input data.
 * @param  array $requiredFields  List of field names that must be present.
 * @return array  ['valid' => bool, 'missing' => string[]]
 */
function validateRequiredFields($data, $requiredFields) {
    // TODO: Loop through $requiredFields
    $missing = [];
    foreach ($requiredFields as $field) {
        // Collect any that are absent or empty in $data into a $missing array
        if (!isset($data[$field]) || $data[$field] === '') {
            $missing[] = $field;
        }
    }
    // Return ['valid' => (count($missing) === 0), 'missing' => $missing]
    return ['valid' => count($missing) === 0, 'missing' => $missing];
}

?>