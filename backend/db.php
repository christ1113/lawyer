<?php
// backend/db.php
// Simple PDO connection helper. Reads DB config from environment variables.
// Usage:
//   require __DIR__ . '/db.php';
//   $pdo = getPDO();

function getPDO(): PDO {
    $db_name = getenv('DB_NAME') ?: 'lawyer_db';
    $db_user = getenv('DB_USER') ?: 'root';
    $db_pass = getenv('DB_PASS') ?: 'root';
    $db_host = getenv('DB_HOST') ?: 'lawyer-db-1';
    $cloud_sql_conn = getenv('CLOUD_SQL_CONNECTION_NAME'); // e.g. project:region:instance

    try {
        if ($cloud_sql_conn) {
            // When deployed to Cloud Run + Cloud SQL use the unix socket
            $dsn = "mysql:unix_socket=/cloudsql/{$cloud_sql_conn};dbname={$db_name};charset=utf8mb4";
        } else {
            $dsn = "mysql:host={$db_host};dbname={$db_name};charset=utf8mb4";
        }

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];

        return new PDO($dsn, $db_user, $db_pass, $options);
    } catch (PDOException $e) {
        // Log error server-side and show a generic message
        error_log('DB Connection error: ' . $e->getMessage());
        http_response_code(500);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Database connection failed']);
        exit;
    }
}
