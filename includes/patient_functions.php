<?php
session_start();
require_once('../config/db.php');

if (isset($_POST['register_patient'])) {
    // 1. Sanitize Inputs
    $full_name = mysqli_real_escape_string($conn, $_POST['full_name']);
    $age = mysqli_real_escape_string($conn, $_POST['age']);
    $gender = mysqli_real_escape_string($conn, $_POST['gender']);
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $contact = mysqli_real_escape_string($conn, $_POST['contact']);

    // 2. Generate a Unique MRN (Format: RU/YEAR/RANDOM)
    $mrn = "RU/" . date('y') . "/" . rand(1000, 9999);
    $reg_date = date('Y-m-d');

    // 3. Prepare SQL (As per Table 15 in PDF)
    $query = "INSERT INTO patients (medical_record_number, full_name, age, gender, address, contact_details, registered_date) 
              VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssissss", $mrn, $full_name, $age, $gender, $address, $contact, $reg_date);

    if ($stmt->execute()) {
        // Success! Redirect to the patient's new file
        header("Location: ../index.php?page=consultation&id=" . $mrn . "&status=success");
    } else {
        // Error
        header("Location: ../index.php?page=registration&status=error");
    }
    exit();
}
?>