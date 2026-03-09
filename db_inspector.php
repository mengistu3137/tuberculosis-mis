<?php
/**
 * STANDALONE DATABASE INSPECTOR
 * Returns table data in JSON format for easy copy-pasting.
 * Usage: db_inspector.php?table=TABLE_NAME
 */

header('Content-Type: application/json');

// 1. Database Credentials (Standalone configuration)
$host = "localhost";
$db_name = "mattu_tbmis";
$username = "root";
$password = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo json_encode(["error" => "Connection failed: " . $e->getMessage()]);
    exit;
}

// 2. Get table name from URL
$table = isset($_GET['table']) ? $_GET['table'] : '';

if (empty($table)) {
    echo json_encode([
        "error" => "No table specified",
        "usage" => "db_inspector.php?table=TABLENAME",
        "available_tables" => [
            "users",
            "patients",
            "medical_visits",
            "vital_signs",
            "diagnoses",
            "lab_requests",
            "lab_results",
            "prescriptions",
            "dispensing_records",
        
            "referrals"


        ]
    ]);
    exit;
}

// 3. Security: Whitelist of allowed tables to prevent SQL injection
$allowed_tables = [
    'users',
    'patients',
    'medical_visits',
    'vital_signs',
    'diagnoses',
    'treatment_plans',
    'lab_requests',
    'lab_results',
    'prescriptions',
    'dispensing_records',
    'discharges',
    "referrals"
];

if (!in_array($table, $allowed_tables)) {
    echo json_encode(["error" => "Table '$table' is not in the allowed list or does not exist."]);
    exit;
}

// 4. Retrieve Data
try {
    // We can use the variable safely here because it was checked against the whitelist above
    $stmt = $conn->prepare("SELECT * FROM $table");
    $stmt->execute();

    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 5. Output result as JSON
    echo json_encode([
        "table" => $table,
        "count" => count($results),
        "data" => $results
    ], JSON_PRETTY_PRINT); // Pretty print makes it easier for you to read

} catch (PDOException $e) {
    echo json_encode(["error" => "Query failed: " . $e->getMessage()]);
}