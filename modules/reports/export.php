<?php
// Export endpoint for reports (CSV / XLSX)
session_start();

// Basic auth: ensure user logged in and allowed
if (!isset($_SESSION['user_id'])) {
    header('HTTP/1.1 302 Found');
    header('Location: ../../login.php');
    exit;
}

$allowedRoles = ['Admin','Clerk','Doctor','Nurse','Lab Technician','Pharmacist','Radiologist'];
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

require_once '../../config/database.php';

$database = new Database();
$db = $database->getConnection();

$type = $_GET['type'] ?? 'patient-visits';
$start = $_GET['start_date'] ?? '';
$end = $_GET['end_date'] ?? '';
$status = $_GET['status'] ?? '';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$format = $_GET['format'] ?? ($_GET['export'] ?? 'csv');

$params = [];
$rows = [];
$headers = [];

switch ($type) {
    case 'patient-visits':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND v.visit_date BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($status) { $where .= ' AND v.status = :status'; $params[':status']=$status; }
        if ($search) { $where .= ' AND (p.full_name LIKE :q OR p.medical_record_number LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT v.visit_id, v.visit_date, p.full_name, p.medical_record_number, v.visit_type, v.status FROM medical_visits v JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY v.visit_date DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Visit ID','Visit Date','Patient','MRN','Visit Type','Status'];
        break;

    case 'doctor-workload':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND v.visit_date BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($search) { $where .= ' AND (u.full_name LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT v.assigned_doctor_id AS user_id, u.full_name, COUNT(*) AS visits FROM medical_visits v JOIN users u ON v.assigned_doctor_id = u.user_id $where GROUP BY v.assigned_doctor_id ORDER BY visits DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Doctor ID','Doctor Name','Visit Count'];
        break;

    case 'nurse-assignment':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND v.visit_date BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($search) { $where .= ' AND (u.full_name LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT v.assigned_nurse_id AS user_id, u.full_name, COUNT(*) AS assigned FROM medical_visits v JOIN users u ON v.assigned_nurse_id = u.user_id $where GROUP BY v.assigned_nurse_id ORDER BY assigned DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Nurse ID','Nurse Name','Assigned Count'];
        break;

    case 'lab-requests':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND lr.request_date BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($status) { $where .= ' AND lr.status = :status'; $params[':status']=$status; }
        if ($search) { $where .= ' AND (p.full_name LIKE :q OR lr.test_type LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT lr.request_id, lr.request_date, p.full_name AS patient, lr.test_type, lr.status FROM lab_requests lr JOIN medical_visits v ON lr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY lr.request_date DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Request ID','Request Date','Patient','Test Type','Status'];
        break;

    case 'radiology-requests':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND rr.request_date BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($status) { $where .= ' AND rr.status = :status'; $params[':status']=$status; }
        if ($search) { $where .= ' AND (p.full_name LIKE :q OR rr.exam_type LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT rr.request_id, rr.request_date, p.full_name AS patient, rr.exam_type, rr.status FROM radiology_requests rr JOIN medical_visits v ON rr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY rr.request_date DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Request ID','Request Date','Patient','Exam Type','Status'];
        break;

    case 'pharmacy-dispensing':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND dr.dispense_date BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($search) { $where .= ' AND (p.full_name LIKE :q OR pr.medication_name LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT dr.dispense_id, dr.dispense_date, p.full_name AS patient, pr.medication_name, dr.pharmacist_id FROM dispensing_records dr LEFT JOIN prescriptions pr ON dr.prescription_id = pr.prescription_id LEFT JOIN medical_visits v ON pr.visit_id = v.visit_id LEFT JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY dr.dispense_date DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Dispense ID','Dispense Date','Patient','Medication','Pharmacist ID'];
        break;

    case 'referral':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND r.referral_date BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($status) { $where .= ' AND r.status = :status'; $params[':status']=$status; }
        if ($search) { $where .= ' AND (p.full_name LIKE :q OR r.referral_id LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT r.referral_id, r.referral_date, p.full_name AS patient, r.target_facility, r.priority, r.status FROM referrals r JOIN patients p ON r.patient_id = p.patient_id $where ORDER BY r.referral_date DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Referral ID','Referral Date','Patient','Target Facility','Priority','Status'];
        break;

    case 'discharge':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND d.discharged_at BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($search) { $where .= ' AND (p.full_name LIKE :q OR d.discharge_id LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT d.discharge_id, d.discharged_at, p.full_name AS patient, d.summary_of_care, d.instructions FROM discharges d JOIN patients p ON d.patient_id = p.patient_id $where ORDER BY d.discharged_at DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['Discharge ID','Discharged At','Patient','Summary','Instructions'];
        break;

    case 'staff-activity':
        $where = 'WHERE 1=1';
        if ($start && $end) { $where .= ' AND sl.changed_at BETWEEN :start AND :end'; $params[':start']=$start; $params[':end']=$end; }
        if ($search) { $where .= ' AND (u.full_name LIKE :q)'; $params[':q']="%$search%"; }
        $sql = "SELECT sl.user_id, u.full_name, COUNT(*) AS changes FROM status_logs sl JOIN users u ON sl.user_id = u.user_id $where GROUP BY sl.user_id ORDER BY changes DESC";
        $stmt = $db->prepare($sql);
        foreach ($params as $k=>$v) $stmt->bindValue($k,$v);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $headers = ['User ID','Full Name','Change Count'];
        break;

    default:
        $rows = [];
}

// Output
if ($format === 'xlsx') {
    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        http_response_code(500);
        echo 'PhpSpreadsheet is not installed. Run composer require phpoffice/phpspreadsheet';
        exit;
    }

    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // helper: numeric column to letters
    $colLetter = function($col) {
        $letters = '';
        while ($col > 0) {
            $mod = ($col - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $col = (int)(($col - $mod - 1) / 26);
        }
        return $letters;
    };

    $col = 1;
    foreach ($headers as $h) {
        $sheet->setCellValue($colLetter($col) . '1', $h);
        $col++;
    }
    $rowNum = 2;
    foreach ($rows as $r) {
        $col = 1;
        foreach ($r as $c) {
            $sheet->setCellValue($colLetter($col) . $rowNum, $c);
            $col++;
        }
        $rowNum++;
    }

    $filename = 'report_' . $type . '_' . date('Ymd_His') . '.xlsx';
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
} else {
    // CSV output
    $filename = 'report_' . $type . '_' . date('Ymd_His') . '.csv';
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $r) {
        fputcsv($out, array_values($r));
    }
    fclose($out);
    exit;
}

?>
