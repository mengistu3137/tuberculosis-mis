<?php
// Reports Module
// TB-focused reports with filtering, pagination, and CSV export

if (!isset($db)) {
    echo "<div class='p-6 bg-red-50 text-red-600 rounded-xl'>Database connection not available.</div>";
    return;
}

// RBAC: Only allow specified roles to access reports
$allowedRoles = ['Admin', 'Clerk', 'Doctor', 'Nurse', 'Lab Technician', 'Pharmacist', 'Radiologist'];
$userRole = $_SESSION['role'] ?? '';
if (!in_array($userRole, $allowedRoles)) {
    echo "<div class='p-6 bg-yellow-50 text-yellow-800 rounded-xl'>You do not have permission to view reports.</div>";
    return;
}

$reportTypes = [
    'patient-visits' => 'Patient Visit Report',
    'doctor-workload' => 'Doctor Workload Report',
    'nurse-assignment' => 'Nurse Assignment Report',
    'lab-requests' => 'Lab Requests Report',
    'radiology-requests' => 'Radiology Requests Report',
    'pharmacy-dispensing' => 'Pharmacy Dispensing Report',
    'referral' => 'Referral Report',
    'discharge' => 'Discharge Summary Report',
    'staff-activity' => 'Staff Activity Report'
];

$type = isset($_GET['type']) && array_key_exists($_GET['type'], $reportTypes) ? $_GET['type'] : 'patient-visits';

// Filters
$start = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// Pagination
$limit = 10;
$page = isset($_GET['p']) ? max(1, (int) $_GET['p']) : 1;
$offset = ($page - 1) * $limit;

$params = [];

function output_csv($filename, $headers, $rows)
{
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $out = fopen('php://output', 'w');
    fputcsv($out, $headers);
    foreach ($rows as $r)
        fputcsv($out, $r);
    fclose($out);
    exit;
}

function output_xlsx($filename, $headers, $rows)
{
    // Use PhpSpreadsheet if available
    if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
        echo "<div class='p-6 bg-yellow-50 text-yellow-800 rounded-xl'>XLSX export requires PhpSpreadsheet. Run:<br><code>composer require phpoffice/phpspreadsheet</code></div>";
        return;
    }


    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    // helper: convert 1-based column index to Excel column letters
    $colLetter = function ($col) {
        $letters = '';
        while ($col > 0) {
            $mod = ($col - 1) % 26;
            $letters = chr(65 + $mod) . $letters;
            $col = (int) (($col - $mod - 1) / 26);
        }
        return $letters;
    };

    // headers
    $col = 1;
    foreach ($headers as $h) {
        $cell = $colLetter($col) . '1';
        $sheet->setCellValue($cell, $h);
        $col++;
    }

    $rowNum = 2;
    foreach ($rows as $r) {
        $col = 1;
        foreach ($r as $c) {
            $cell = $colLetter($col) . $rowNum;
            $sheet->setCellValue($cell, $c);
            $col++;
        }
        $rowNum++;
    }

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;
}

$rows = [];
$total = 0;

switch ($type) {
    case 'patient-visits':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND v.visit_date BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($status) {
            $where .= ' AND v.status = :status';
            $params[':status'] = $status;
        }
        if ($search) {
            $where .= ' AND (p.full_name LIKE :q OR p.medical_record_number LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM medical_visits v JOIN patients p ON v.patient_id = p.patient_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT v.visit_id, v.visit_date, p.full_name, p.medical_record_number, v.visit_type, v.status FROM medical_visits v JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY v.visit_date DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Visit ID', 'Visit Date', 'Patient', 'MRN', 'Visit Type', 'Status'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['visit_id'], $r['visit_date'], $r['full_name'], $r['medical_record_number'], $r['visit_type'], $r['status']];
            if ($_GET['export'] === 'csv')
                output_csv('patient_visits.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('patient_visits.xlsx', $headers, $out);
        }
        break;

    case 'doctor-workload':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND v.visit_date BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($search) {
            $where .= ' AND (u.full_name LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(DISTINCT assigned_doctor_id) FROM medical_visits v JOIN users u ON v.assigned_doctor_id = u.user_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT v.assigned_doctor_id AS user_id, u.full_name, COUNT(*) AS visits FROM medical_visits v JOIN users u ON v.assigned_doctor_id = u.user_id $where GROUP BY v.assigned_doctor_id ORDER BY visits DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Doctor ID', 'Doctor Name', 'Visit Count'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['user_id'], $r['full_name'], $r['visits']];
            if ($_GET['export'] === 'csv')
                output_csv('doctor_workload.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('doctor_workload.xlsx', $headers, $out);
        }
        break;

    case 'nurse-assignment':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND v.visit_date BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($search) {
            $where .= ' AND (u.full_name LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(DISTINCT assigned_nurse_id) FROM medical_visits v JOIN users u ON v.assigned_nurse_id = u.user_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT v.assigned_nurse_id AS user_id, u.full_name, COUNT(*) AS assigned FROM medical_visits v JOIN users u ON v.assigned_nurse_id = u.user_id $where GROUP BY v.assigned_nurse_id ORDER BY assigned DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Nurse ID', 'Nurse Name', 'Assigned Count'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['user_id'], $r['full_name'], $r['assigned']];
            if ($_GET['export'] === 'csv')
                output_csv('nurse_assignment.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('nurse_assignment.xlsx', $headers, $out);
        }
        break;

    case 'lab-requests':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND lr.request_date BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($status) {
            $where .= ' AND lr.status = :status';
            $params[':status'] = $status;
        }
        if ($search) {
            $where .= ' AND (p.full_name LIKE :q OR lr.test_type LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM lab_requests lr JOIN medical_visits v ON lr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT lr.request_id, lr.request_date, p.full_name AS patient, lr.test_type, lr.status FROM lab_requests lr JOIN medical_visits v ON lr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY lr.request_date DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Request ID', 'Request Date', 'Patient', 'Test Type', 'Status'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['request_id'], $r['request_date'], $r['patient'], $r['test_type'], $r['status']];
            if ($_GET['export'] === 'csv')
                output_csv('lab_requests.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('lab_requests.xlsx', $headers, $out);
        }
        break;

    case 'radiology-requests':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND rr.request_date BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($status) {
            $where .= ' AND rr.status = :status';
            $params[':status'] = $status;
        }
        if ($search) {
            $where .= ' AND (p.full_name LIKE :q OR rr.exam_type LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM radiology_requests rr JOIN medical_visits v ON rr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT rr.request_id, rr.request_date, p.full_name AS patient, rr.exam_type, rr.status FROM radiology_requests rr JOIN medical_visits v ON rr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY rr.request_date DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Request ID', 'Request Date', 'Patient', 'Exam Type', 'Status'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['request_id'], $r['request_date'], $r['patient'], $r['exam_type'], $r['status']];
            if ($_GET['export'] === 'csv')
                output_csv('radiology_requests.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('radiology_requests.xlsx', $headers, $out);
        }
        break;

    case 'pharmacy-dispensing':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND dr.dispense_date BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($search) {
            $where .= ' AND (p.full_name LIKE :q OR pr.medication_name LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM dispensing_records dr LEFT JOIN prescriptions pr ON dr.prescription_id = pr.prescription_id LEFT JOIN medical_visits v ON pr.visit_id = v.visit_id LEFT JOIN patients p ON v.patient_id = p.patient_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT dr.dispense_id, dr.dispense_date, p.full_name AS patient, pr.medication_name, dr.pharmacist_id FROM dispensing_records dr LEFT JOIN prescriptions pr ON dr.prescription_id = pr.prescription_id LEFT JOIN medical_visits v ON pr.visit_id = v.visit_id LEFT JOIN patients p ON v.patient_id = p.patient_id $where ORDER BY dr.dispense_date DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Dispense ID', 'Dispense Date', 'Patient', 'Medication', 'Pharmacist ID'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['dispense_id'], $r['dispense_date'], $r['patient'], $r['medication_name'], $r['pharmacist_id']];
            if ($_GET['export'] === 'csv')
                output_csv('pharmacy_dispensing.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('pharmacy_dispensing.xlsx', $headers, $out);
        }
        break;

    case 'referral':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND r.referral_date BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($status) {
            $where .= ' AND r.status = :status';
            $params[':status'] = $status;
        }
        if ($search) {
            $where .= ' AND (p.full_name LIKE :q OR r.referral_id LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM referrals r JOIN patients p ON r.patient_id = p.patient_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT r.referral_id, r.referral_date, p.full_name AS patient, r.target_facility, r.priority, r.status FROM referrals r JOIN patients p ON r.patient_id = p.patient_id $where ORDER BY r.referral_date DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Referral ID', 'Referral Date', 'Patient', 'Target Facility', 'Priority', 'Status'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['referral_id'], $r['referral_date'], $r['patient'], $r['target_facility'], $r['priority'], $r['status']];
            if ($_GET['export'] === 'csv')
                output_csv('referral_report.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('referral_report.xlsx', $headers, $out);
        }
        break;

    case 'discharge':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND d.discharged_at BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($search) {
            $where .= ' AND (p.full_name LIKE :q OR d.discharge_id LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(*) FROM discharges d JOIN patients p ON d.patient_id = p.patient_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT d.discharge_id, d.discharged_at, p.full_name AS patient, d.summary_of_care, d.instructions FROM discharges d JOIN patients p ON d.patient_id = p.patient_id $where ORDER BY d.discharged_at DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['Discharge ID', 'Discharged At', 'Patient', 'Summary', 'Instructions'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['discharge_id'], $r['discharged_at'], $r['patient'], $r['summary_of_care'], $r['instructions']];
            if ($_GET['export'] === 'csv')
                output_csv('discharge_summary.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('discharge_summary.xlsx', $headers, $out);
        }
        break;

    case 'staff-activity':
        $where = 'WHERE 1=1';
        if ($start && $end) {
            $where .= ' AND sl.changed_at BETWEEN :start AND :end';
            $params[':start'] = $start;
            $params[':end'] = $end;
        }
        if ($search) {
            $where .= ' AND (u.full_name LIKE :q)';
            $params[':q'] = "%$search%";
        }

        $countStmt = $db->prepare("SELECT COUNT(DISTINCT sl.user_id) FROM status_logs sl JOIN users u ON sl.user_id = u.user_id $where");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $sql = "SELECT sl.user_id, u.full_name, COUNT(*) AS changes FROM status_logs sl JOIN users u ON sl.user_id = u.user_id $where GROUP BY sl.user_id ORDER BY changes DESC LIMIT :limit OFFSET :offset";
        $stmt = $db->prepare($sql);
        foreach ($params as $k => $v)
            $stmt->bindValue($k, $v);
        $stmt->bindValue(':limit', (int) $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int) $offset, PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (isset($_GET['export'])) {
            $headers = ['User ID', 'Full Name', 'Change Count'];
            $out = [];
            foreach ($rows as $r)
                $out[] = [$r['user_id'], $r['full_name'], $r['changes']];
            if ($_GET['export'] === 'csv')
                output_csv('staff_activity.csv', $headers, $out);
            if ($_GET['export'] === 'xlsx')
                output_xlsx('staff_activity.xlsx', $headers, $out);
        }
        break;

    default:
        $rows = [];
}

?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-xl font-bold text-gray-800"><?= htmlspecialchars($reportTypes[$type]) ?></h1>
        <div class="flex items-center gap-2">
            <a href="modules/reports/export.php?type=<?= urlencode($type) ?>&format=csv&<?= http_build_query(array_merge($_GET, ['format' => 'csv'])) ?>"
                class="px-3 py-2 bg-white border border-gray-200 rounded-xl text-sm font-semibold flex items-center gap-2 hover:bg-gray-50">
                <i data-lucide="download" class="w-4 h-4"></i> Export CSV
            </a>
            <a href="modules/reports/export.php?type=<?= urlencode($type) ?>&format=xlsx&<?= http_build_query(array_merge($_GET, ['format' => 'xlsx'])) ?>"
                class="px-3 py-2 bg-primary-600 text-white rounded-xl text-sm font-semibold flex items-center gap-2 hover:bg-primary-700">
                <i data-lucide="download" class="w-4 h-4"></i> Export XLSX
            </a>
        </div>
    </div>

    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-3 items-end">
        <input type="hidden" name="page" value="reports">
        <input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>">

        <div>
            <label class="text-xs font-semibold text-gray-500">Start Date</label>
            <input type="date" name="start_date" value="<?= htmlspecialchars($start) ?>"
                class="w-full px-3 py-2 rounded-xl border border-gray-200">
            </div>
        <div>
            <label class="text-xs font-semibold text-gray-500">End Date</label>
            <input type="date" name="end_date" value="<?= htmlspecialchars($end) ?>"
                class="w-full px-3 py-2 rounded-xl border border-gray-200">
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500">Status</label>
            <input type="text" name="status" value="<?= htmlspecialchars($status) ?>" placeholder="status"
                class="w-full px-3 py-2 rounded-xl border border-gray-200">
        </div>
        <div>
            <label class="text-xs font-semibold text-gray-500">Search</label>
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="Search"
                class="w-full px-3 py-2 rounded-xl border border-gray-200">
        </div>

        <div class="md:col-span-4 flex items-center gap-2">
     <?php foreach ($reportTypes as $key => $label): ?>
        <?php $active = $key === $type; ?>
        <a href="index.php?page=reports&type=<?= $key ?>"
            class="px-4 py-2 rounded-full text-sm font-medium border transition-shadow <?= $active ? 'bg-primary-600 text-white shadow-md' : 'bg-white text-gray-600 hover:shadow-sm' ?>">
            <?= $label ?></a>
    <?php endforeach; ?>
    <button type="submit" class="ml-auto px-4 py-2 bg-primary-600 text-white rounded-xl">Filter</button>
        </div>
    </form>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <table class="w-full text-left">
            <thead class="bg-gray-50 border-b">
                <tr>
                    <?php if (!empty($rows)):
                        foreach (array_keys($rows[0]) as $h): ?>
                            <th class="px-6 py-4 text-xs font-semibold text-gray-600 uppercase"><?= htmlspecialchars($h) ?></th>
                        <?php endforeach; else: ?>
                        <th class="px-6 py-4 text-sm text-gray-600">No results</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (!empty($rows)):
                    foreach ($rows as $r): ?>
                        <tr class="hover:bg-gray-50 transition-all">
                            <?php foreach ($r as $cell): ?>
                                <td class="px-6 py-4 text-sm text-gray-700"><?= htmlspecialchars((string) $cell) ?></td>
                        <?php endforeach; ?>
                                                                                    </tr>
                                                                            <?php endforeach; else: ?>
                                                                            <tr>
                                                                                <td class="p-12 text-center text-gray-400">No records found for selected filters.</td>
                                                                            </tr>
                                                                            <?php endif; ?>
                                                                            </tbody>
                                                                            </table>
                                                                            
                                                                            <?php $total_pages = $total > 0 ? ceil($total / $limit) : 1;
                                                                            if ($total_pages > 1): ?>
                                                                            <div class="px-6 py-4 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                                                                                <span class="text-sm text-gray-500">Page <?= $page ?> of <?= $total_pages ?></span>
                                                                                <div class="flex items-center gap-2">
                                                                                    <?php if ($page > 1): ?>
                                                                                    <a href="<?= htmlspecialchars('?page=reports&type=' . $type . '&p=' . ($page - 1) . '&' . http_build_query(array_merge($_GET, ['p' => $page - 1]))) ?>"
                                                                                        class="px-3 py-2 bg-white border rounded-xl">Prev</a>
                                                                                    <?php endif; ?>
                                                                                    <?php if ($page < $total_pages): ?>
                                                                                    <a href="<?= htmlspecialchars('?page=reports&type=' . $type . '&p=' . ($page + 1) . '&' . http_build_query(array_merge($_GET, ['p' => $page + 1]))) ?>"
                                                                                        class="px-3 py-2 bg-white border rounded-xl">Next</a>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                            <?php endif; ?>
    </div>
</div>

<script>lucide.createIcons();</script>