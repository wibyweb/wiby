<?php
// SQLite database configuration for Wiby search engine
// For personal use

// Path to SQLite database file
define('DB_PATH', __DIR__ . '/../db/wiby.sqlite');

// Function to get database connection
function getDbConnection() {
    try {
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        return $pdo;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        return null;
    }
}

// Helper function to escape strings for SQLite
function db_escape_string($db, $string) {
    return str_replace("'", "''", $string);
}

// Helper function to execute query and return results
function db_query($db, $sql) {
    try {
        $stmt = $db->query($sql);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Query failed: ' . $e->getMessage());
        return false;
    }
}

// Helper function to execute prepared statement
function db_execute($db, $sql, $params = []) {
    try {
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    } catch (PDOException $e) {
        error_log('Execute failed: ' . $e->getMessage());
        return false;
    }
}

// Helper function to get last insert ID
function db_last_insert_id($db) {
    return $db->lastInsertId();
}

// Helper function to fetch all rows
function db_fetch_all($stmt) {
    return $stmt->fetchAll();
}

// Helper function to fetch single row
function db_fetch($stmt) {
    return $stmt->fetch();
}
?>
