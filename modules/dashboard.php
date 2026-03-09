<?php
/**
 * DYNAMIC ROLE-BASED DASHBOARD
 * Single dashboard that adapts based on logged-in user role
 * Shows role-specific stats, notifications, and recent activities
 */

$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// --- GLOBAL STATS (Used across roles) ---
$totalPatients = $db->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$todayVisits = $db->query("SELECT COUNT(*) FROM medical_visits WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn();
$pendingLabs = $db->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'pending'")->fetchColumn();
$pendingMeds = $db->query("SELECT COUNT(*) FROM prescriptions WHERE is_dispensed = 0")->fetchColumn();

// --- ROLE-SPECIFIC CONFIGURATION ---
$roleConfig = [];

switch ($userRole) {

    case 'Admin':
        // --- NEW MODIFICATION: COUNT USERS BY ROLE ---
        $countDoctors = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Doctor'")->fetchColumn();
        $countNurses = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Nurse'")->fetchColumn();
        $countPharmacists = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Pharmacist'")->fetchColumn();
        $countRadiologists = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Radiologist'")->fetchColumn();
        $countClerks = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Clerk'")->fetchColumn();
        $countAdmins = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Admin'")->fetchColumn();
        $countLabTechs = $db->query("SELECT COUNT(*) FROM users WHERE role = 'Lab Technician'")->fetchColumn();
        $countTotalStaff = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();


        $roleConfig = [
            'welcome' => 'System Control',
            'icon' => 'shield-check',
            'quickActions' => [
                ['label' => 'Manage Users', 'icon' => 'user-cog', 'page' => 'admin', 'color' => 'blue'],
                ['label' => 'System Reports', 'icon' => 'file-bar-chart', 'page' => 'reports', 'color' => 'indigo'],

            ],
            'stats' => [
                ['label' => 'Medical Doctors', 'value' => $countDoctors, 'trend' => 'Active Staff', 'color' => 'blue', 'icon' => 'stethoscope'],
                ['label' => 'Nursing Staff', 'value' => $countNurses, 'trend' => 'Clinical Support', 'color' => 'violet', 'icon' => 'heart-pulse'],
                ['label' => 'Pharmacists', 'value' => $countPharmacists, 'trend' => 'Medical Dispensary', 'color' => 'emerald', 'icon' => 'pill'],
                ['label' => 'Radiologists', 'value' => $countRadiologists, 'trend' => 'Imaging Dept', 'color' => 'fuchsia', 'icon' => 'scan'],
                ['label' => 'Clerks', 'value' => $countClerks, 'trend' => 'Front Desk', 'color' => 'indigo', 'icon' => 'clipboard-list'],
                ['label' => 'Lab Techs', 'value' => $countLabTechs, 'trend' => 'Laboratory', 'color' => 'orange', 'icon' => 'flask-conical'],
                ['label' => 'Administrators', 'value' => $countAdmins, 'trend' => 'System Admins', 'color' => 'slate', 'icon' => 'shield-check'],
                ['label' => 'Total Staff', 'value' => $countTotalStaff, 'trend' => 'System Staff', 'color' => 'gray', 'icon' => 'users']

            ],
            'notifications' => [
                'system_alerts' => "SELECT 'system' as source, user_id as id, 'Security' as from_name, full_name as patient_name, user_id as patient_id, user_id as medical_record_number, CONCAT('New user registered: ', email) as message, created_at, 'blue' as color, 'user-plus' as icon FROM users ORDER BY created_at DESC LIMIT 5"
            ],
            'recentActivity' => [
                // Admin sees newest staff members
                'query' => "SELECT full_name, user_id as medical_record_number, role as details, status, 'blue' as color, user_id as patient_id, email as contact_details, '' as address, 0 as age, 'N/A' as gender FROM users ORDER BY created_at DESC LIMIT 5",
                'columns' => ['Staff Name', 'Staff ID', 'System Role', 'Status']
            ]
        ];
        break;

    case 'Clerk':
        $roleConfig = [
            'welcome' => 'Front Desk',
            'icon' => 'clipboard-list',
            'quickActions' => [
                ['label' => 'New Patient', 'icon' => 'user-plus', 'page' => 'registration', 'color' => 'blue'],
                ['label' => 'Patient Registry', 'icon' => 'users', 'page' => 'records', 'color' => 'indigo'],
                ['label' => 'Today\'s Visits', 'icon' => 'calendar', 'page' => 'visit', 'color' => 'emerald']
            ],
            'stats' => [
                ['label' => 'Total Patients', 'value' => $totalPatients, 'trend' => 'In Registry', 'color' => 'blue', 'icon' => 'users'],
                ['label' => 'New Today', 'value' => $db->query("SELECT COUNT(*) FROM patients WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Registrations', 'color' => 'emerald', 'icon' => 'user-plus'],
                ['label' => 'Check-ins Today', 'value' => $todayVisits, 'trend' => 'Visits', 'color' => 'indigo', 'icon' => 'log-in'],
                ['label' => 'Active Patients', 'value' => $db->query("SELECT COUNT(*) FROM medical_visits WHERE status = 'active'")->fetchColumn(), 'trend' => 'In Hospital', 'color' => 'amber', 'icon' => 'activity']
            ],
            'notifications' => [
                'doctor_requests' => "SELECT 'doctor' as source, d.diagnosis_id as id, CONCAT('Dr. ', u.full_name) as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, 'Follow-up appointment needed' as message, d.diagnosis_date as created_at, 'blue' as color, 'stethoscope' as icon FROM diagnoses d JOIN users u ON d.doctor_id = u.user_id JOIN medical_visits v ON d.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id WHERE d.diagnosis_date = CURRENT_DATE",
                'discharge_requests' => "SELECT 'discharge' as source, d.discharge_id as id, CONCAT('Dr. ', u.full_name) as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, 'Ready for discharge' as message, d.discharged_at as created_at, 'purple' as color, 'log-out' as icon FROM discharges d JOIN users u ON d.discharged_by = u.user_id JOIN patients p ON d.patient_id = p.patient_id"
            ],
            'recentActivity' => [
                'query' => "SELECT *, full_name as name, medical_record_number as id, address as details, 'Registered' as status, 'emerald' as color FROM patients ORDER BY created_at DESC LIMIT 5",
                'columns' => ['Name', 'MRN', 'Contact', 'Time']
            ]
        ];
        break;
   

    case 'Nurse':
        $roleConfig = [
            'welcome' => 'Triage',
            'icon' => 'heart-pulse',
            'quickActions' => [
                ['label' => 'Triage Queue', 'icon' => 'gauge', 'page' => 'triage', 'color' => 'violet'],
                ['label' => 'Record Vitals', 'icon' => 'activity', 'page' => 'vitals', 'color' => 'blue'],
                ['label' => 'Emergency', 'icon' => 'alert-triangle', 'page' => 'emergency', 'color' => 'red']
            ],
            'stats' => [
                ['label' => 'Waiting Triage', 'value' => $db->query("SELECT COUNT(*) FROM medical_visits WHERE status = 'waiting_triage' AND DATE(created_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'In Queue', 'color' => 'violet', 'icon' => 'gauge'],
                ['label' => 'Seen Today', 'value' => $db->query("SELECT COUNT(*) FROM vital_signs WHERE DATE(recorded_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Completed', 'color' => 'emerald', 'icon' => 'check-circle'],
                ['label' => 'Emergency Cases', 'value' => $db->query("SELECT COUNT(*) FROM medical_visits WHERE visit_type = 'Emergency' AND DATE(created_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Critical', 'color' => 'red', 'icon' => 'alert-triangle'],
                ['label' => 'Total Patients', 'value' => $totalPatients, 'trend' => 'Registry', 'color' => 'blue', 'icon' => 'users']
            ],
            'notifications' => [
                'doctor_calls' => "SELECT 'doctor' as source, d.diagnosis_id as id, CONCAT('Dr. ', u.full_name) as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, 'Requesting vitals review' as message, d.created_at, 'blue' as color, 'stethoscope' as icon FROM diagnoses d JOIN users u ON d.doctor_id = u.user_id JOIN medical_visits v ON d.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id WHERE d.needs_vitals_review = 1 AND d.vitals_reviewed = 0",
                'emergency_alerts' => "SELECT 'emergency' as source, v.visit_id as id, 'ER Triage' as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, 'Emergency patient arrived' as message, v.created_at, 'red' as color, 'alert-triangle' as icon FROM medical_visits v JOIN patients p ON v.patient_id = p.patient_id WHERE v.visit_type = 'Emergency' AND v.triage_completed = 0 AND DATE(v.created_at) = CURRENT_DATE"
            ],
            'recentActivity' => [
                'query' => "SELECT p.*, v.visit_type as details, v.status, CASE v.visit_type WHEN 'Emergency' THEN 'red' ELSE 'violet' END as color FROM patients p JOIN medical_visits v ON p.patient_id = v.patient_id WHERE v.status = 'waiting_triage' ORDER BY v.created_at ASC LIMIT 5",
                'columns' => ['Patient', 'MRN', 'Type', 'Status']
            ]
        ];
        break;

    case 'Doctor':
        $myTodayPatients = $db->prepare("SELECT COUNT(DISTINCT visit_id) FROM diagnoses WHERE doctor_id = ? AND DATE(diagnosis_date) = CURRENT_DATE");
        $myTodayPatients->execute([$userId]);
        
        $roleConfig = [
            'welcome' => 'Consultation',
            'icon' => 'stethoscope',
            'quickActions' => [
                ['label' => 'My Queue', 'icon' => 'users', 'page' => 'consultation', 'color' => 'blue'],
                ['label' => 'Lab Results', 'icon' => 'flask-conical', 'page' => 'lab-results', 'color' => 'orange'],
                ['label' => 'Prescribe', 'icon' => 'pill', 'page' => 'prescribe', 'color' => 'emerald']
            ],
            'stats' => [
                ['label' => 'My Patients', 'value' => $myTodayPatients->fetchColumn(), 'trend' => 'Today', 'color' => 'blue', 'icon' => 'stethoscope'],
                ['label' => 'Waiting', 'value' => $db->query("SELECT COUNT(*) FROM medical_visits WHERE status = 'waiting_doctor' AND DATE(created_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'In Queue', 'color' => 'amber', 'icon' => 'clock'],
                ['label' => 'Pending Labs', 'value' => $pendingLabs, 'trend' => 'Awaiting', 'color' => 'orange', 'icon' => 'flask-conical'],
                ['label' => 'Discharges', 'value' => $db->query("SELECT COUNT(*) FROM discharges WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Today', 'color' => 'purple', 'icon' => 'log-out']
            ],
            'notifications' => [
                'lab_results' => "SELECT 'lab' as source, lr.request_id as id, 'Lab' as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, CONCAT('Results ready: ', lr.test_type) as message, lr.updated_at as created_at, 'orange' as color, 'flask-conical' as icon FROM lab_requests lr JOIN medical_visits v ON lr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id WHERE lr.status = 'completed' AND lr.results_viewed = 0",
                'critical_cases' => "SELECT 'critical' as source, v.visit_id as id, 'ER' as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, 'Critical patient needs attention' as message, v.created_at, 'red' as color, 'alert-triangle' as icon FROM medical_visits v JOIN patients p ON v.patient_id = p.patient_id WHERE v.visit_type = 'Emergency' AND v.doctor_assigned IS NULL",
                'consult_requests' => "SELECT 'consult' as source, v.visit_id as id, 'Nurse' as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, 'Vitals recorded, ready for consultation' as message, v.created_at as created_at, 'blue' as color, 'heart-pulse' as icon FROM medical_visits v JOIN patients p ON v.patient_id = p.patient_id WHERE v.status = 'active' "
            ],
            'recentActivity' => [
                'query' => "SELECT p.*, d.diagnosis_details as details, d.created_at, 'Completed' as status, 'blue' as color 
                FROM diagnoses d 
                JOIN medical_visits v ON d.visit_id = v.visit_id 
                JOIN patients p ON v.patient_id = p.patient_id 
                WHERE d.doctor_id = ? 
                ORDER BY d.created_at DESC 
                LIMIT 5",
                'params' => [$userId],
                'columns' => ['Patient', 'MRN', 'Diagnosis', 'Time']
            ]
        ];
        break;

    case 'Lab Technician':
        $roleConfig = [
            'welcome' => 'Laboratory',
            'icon' => 'flask-conical',
            'quickActions' => [
                ['label' => 'Pending Tests', 'icon' => 'clock', 'page' => 'laboratory', 'color' => 'orange'],
                ['label' => 'Enter Results', 'icon' => 'edit', 'page' => 'lab-results', 'color' => 'blue'],
                ['label' => 'Emergency', 'icon' => 'alert-triangle', 'page' => 'emergency-lab', 'color' => 'red']
            ],
            'stats' => [
                ['label' => 'Pending', 'value' => $pendingLabs, 'trend' => 'To Process', 'color' => 'orange', 'icon' => 'clock'],
                ['label' => 'Completed Today', 'value' => $db->query("SELECT COUNT(*) FROM lab_results WHERE DATE(performed_date) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Done', 'color' => 'emerald', 'icon' => 'check-circle'],
                ['label' => 'Emergency', 'value' => $db->query("SELECT COUNT(*) FROM lab_requests r JOIN medical_visits v ON r.visit_id = v.visit_id WHERE v.visit_type='Emergency' AND r.status='pending'")->fetchColumn(), 'trend' => 'STAT', 'color' => 'red', 'icon' => 'zap'],
                ['label' => 'Total Today', 'value' => $db->query("SELECT COUNT(*) FROM lab_requests WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Requests', 'color' => 'blue', 'icon' => 'flask-conical']
            ],
            'notifications' => [
                'stat_requests' => "SELECT 'stat' as source, lr.request_id as id, 'ER' as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, CONCAT('STAT: ', lr.test_type) as message, lr.created_at, 'red' as color, 'zap' as icon FROM lab_requests lr JOIN medical_visits v ON lr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id WHERE lr.priority = 'STAT' AND lr.status = 'pending'",
                'new_requests' => "SELECT 'new' as source, lr.request_id as id, CONCAT('Dr. ', u.full_name) as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, CONCAT('New: ', lr.test_type) as message, lr.created_at, 'blue' as color, 'flask-conical' as icon FROM lab_requests lr JOIN medical_visits v ON lr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id JOIN users u ON lr.doctor_id = u.user_id WHERE lr.status = 'pending' AND lr.priority != 'STAT'"
            ],
            'recentActivity' => [
                'query' => "SELECT p.*, lr.test_type as details, lr.status, CASE lr.status WHEN 'pending' THEN 'orange' WHEN 'processing' THEN 'blue' ELSE 'emerald' END as color FROM lab_requests lr JOIN medical_visits v ON lr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id WHERE lr.status IN ('pending', 'processing') ORDER BY lr.created_at ASC LIMIT 5",
                'columns' => ['Patient', 'MRN', 'Test', 'Status']
            ]
        ];
        break;

    case 'Radiologist':
        $roleConfig = [
            'welcome' => 'Radiology',
            'icon' => 'scan',
            'quickActions' => [
                ['label' => 'Imaging Queue', 'icon' => 'clock', 'page' => 'radiology', 'color' => 'fuchsia'],
                ['label' => 'Upload Images', 'icon' => 'upload', 'page' => 'upload-images', 'color' => 'blue'],
                ['label' => 'Reports', 'icon' => 'file-text', 'page' => 'radiology-reports', 'color' => 'emerald']
            ],
            'stats' => [
                ['label' => 'Pending Scans', 'value' => $db->query("SELECT COUNT(*) FROM radiology_requests WHERE status = 'pending'")->fetchColumn(), 'trend' => 'In Queue', 'color' => 'fuchsia', 'icon' => 'clock'],
                ['label' => 'Completed', 'value' => $db->query("SELECT COUNT(*) FROM radiology_results WHERE DATE(performed_date) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Today', 'color' => 'emerald', 'icon' => 'check-circle'],
                ['label' => 'Emergency', 'value' => $db->query("SELECT COUNT(*) FROM radiology_requests r JOIN medical_visits v ON r.visit_id = v.visit_id WHERE v.visit_type='Emergency' AND r.status='pending'")->fetchColumn(), 'trend' => 'STAT', 'color' => 'red', 'icon' => 'zap'],
                ['label' => 'Total', 'value' => $db->query("SELECT COUNT(*) FROM radiology_requests WHERE DATE(created_at) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Requests', 'color' => 'blue', 'icon' => 'scan']
            ],
            'notifications' => [
                'stat_imaging' => "SELECT 'stat' as source, rr.request_id as id, 'ER' as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, CONCAT('STAT: ', rr.exam_type) as message, rr.created_at, 'red' as color, 'zap' as icon FROM radiology_requests rr JOIN medical_visits v ON rr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id WHERE rr.priority = 'STAT' AND rr.status = 'pending'",
                'new_orders' => "SELECT 'new' as source, rr.request_id as id, CONCAT('Dr. ', u.full_name) as from_name, p.full_name as patient_name, p.patient_id, p.medical_record_number, CONCAT('New: ', rr.exam_type) as message, rr.created_at, 'fuchsia' as color, 'scan' as icon FROM radiology_requests rr JOIN medical_visits v ON rr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id JOIN users u ON rr.doctor_id = u.user_id WHERE rr.status = 'pending'"
            ],
            'recentActivity' => [
                'query' => "SELECT p.*, rr.exam_type as details, rr.status, 'fuchsia' as color FROM radiology_requests rr JOIN medical_visits v ON rr.visit_id = v.visit_id JOIN patients p ON v.patient_id = p.patient_id WHERE rr.status = 'pending' ORDER BY rr.created_at ASC LIMIT 5",
                'columns' => ['Patient', 'MRN', 'Exam', 'Status']
            ]
        ];
        break;

    case 'Pharmacist':
        $roleConfig = [
            'welcome' => 'Pharmacy',
            'icon' => 'pill',
            'quickActions' => [
                ['label' => 'Pending Rx', 'icon' => 'clock', 'page' => 'pharmacy', 'color' => 'emerald']
               
            ],
            'stats' => [
                ['label' => 'Pending', 'value' => $pendingMeds, 'trend' => 'To Dispense', 'color' => 'emerald', 'icon' => 'clock'],
                ['label' => 'Dispensed', 'value' => $db->query("SELECT COUNT(*) FROM dispensing_records WHERE DATE(dispense_date) = CURRENT_DATE")->fetchColumn(), 'trend' => 'Today', 'color' => 'blue', 'icon' => 'package-check'],
                ['label' => 'Emergency', 'value' => $db->query("SELECT COUNT(*) FROM prescriptions p JOIN medical_visits v ON p.visit_id = v.visit_id WHERE v.visit_type='Emergency' AND p.is_dispensed=0")->fetchColumn(), 'trend' => 'STAT', 'color' => 'red', 'icon' => 'zap'],
                ['label' => 'New Orders', 'value' => $db->query("SELECT COUNT(*) FROM prescriptions WHERE created_at > NOW() - INTERVAL 1 HOUR")->fetchColumn(), 'trend' => 'Last Hour', 'color' => 'purple', 'icon' => 'bell']
            ],
            'notifications' => [
                'stat_prescriptions' => "SELECT 'stat' as source, p.prescription_id as id, 'ER' as from_name, pat.full_name as patient_name, pat.patient_id, pat.medical_record_number, CONCAT('STAT: ', p.medication_name) as message, p.created_at, 'red' as color, 'zap' as icon FROM prescriptions p JOIN medical_visits v ON p.visit_id = v.visit_id JOIN patients pat ON v.patient_id = pat.patient_id WHERE v.visit_type='Emergency' AND p.is_dispensed=0",
                'new_prescriptions' => "SELECT 'new' as source, p.prescription_id as id, CONCAT('Dr. ', u.full_name) as from_name, pat.full_name as patient_name, pat.patient_id, pat.medical_record_number, CONCAT('New: ', p.medication_name) as message, p.created_at, 'emerald' as color, 'pill' as icon FROM prescriptions p JOIN medical_visits v ON p.visit_id = v.visit_id JOIN patients pat ON v.patient_id = pat.patient_id JOIN users u ON p.prescribed_by = u.user_id WHERE p.is_dispensed=0 AND p.priority != 'STAT'"
            ],
            'recentActivity' => [
                'query' => "SELECT pat.*, p.medication_name as details, 'Pending' as status, 'amber' as color FROM prescriptions p JOIN medical_visits v ON p.visit_id = v.visit_id JOIN patients pat ON v.patient_id = pat.patient_id WHERE p.is_dispensed=0 ORDER BY p.created_at ASC LIMIT 5",
                'columns' => ['Patient', 'MRN', 'Medication', 'Status']
            ]
        ];
        break;

    default:
        // Fallback for other roles
        $roleConfig = [
            'welcome' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'quickActions' => [],
            'stats' => [
                ['label' => 'Total Patients', 'value' => $totalPatients, 'trend' => 'Registry', 'color' => 'blue', 'icon' => 'users'],
                ['label' => 'Visits Today', 'value' => $todayVisits, 'trend' => 'Check-ins', 'color' => 'indigo', 'icon' => 'calendar'],
                ['label' => 'Pending Labs', 'value' => $pendingLabs, 'trend' => 'Lab', 'color' => 'orange', 'icon' => 'flask-conical'],
                ['label' => 'Pending Rx', 'value' => $pendingMeds, 'trend' => 'Pharmacy', 'color' => 'emerald', 'icon' => 'pill']
            ],
            'notifications' => [],
            'recentActivity' => [
                'query' => "SELECT *, full_name as name, medical_record_number as id, address as details, 'Active' as status, 'blue' as color FROM patients ORDER BY created_at DESC LIMIT 5",
                'columns' => ['Name', 'MRN', 'Address', 'Status']
            ]
        ];
}

// Fetch notifications for this role
$allNotifications = [];
if (!empty($roleConfig['notifications'])) {
    foreach ($roleConfig['notifications'] as $key => $query) {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $allNotifications = array_merge($allNotifications, $results);
    }
}

// Sort notifications by date (newest first)
usort($allNotifications, function($a, $b) {
    return strtotime($b['created_at']) - strtotime($a['created_at']);
});
$recentNotifications = array_slice($allNotifications, 0, 10);

// Fetch recent activity
$recentActivity = [];
if (isset($roleConfig['recentActivity']['query'])) {
    $stmt = $db->prepare($roleConfig['recentActivity']['query']);
    if (!empty($roleConfig['recentActivity']['params'])) {
        $stmt->execute($roleConfig['recentActivity']['params']);
    } else {
        $stmt->execute();
    }
    $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>


<div class="space-y-8">
    <!-- 1. WELCOME HEADER -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-black text-gray-800 uppercase tracking-tight italic">
                <?php echo $roleConfig['welcome']; ?> 
            </h1>
            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest mt-1">
                <i data-lucide="<?php echo $roleConfig['icon']; ?>" class="w-3 h-3 inline mr-1"></i>
                Welcome back, <?php echo explode(' ', $_SESSION['full_name'])[0]; ?> • <?php echo $userRole; ?>
            </p>
        </div>
        <div class="text-right hidden sm:block">
            <p class="text-xs font-bold text-gray-400 uppercase"><?php echo date('l, F j, Y'); ?></p>
            <p class="text-[10px] text-gray-300 font-medium italic mt-1">
                <span class="inline-block w-2 h-2 bg-emerald-500 rounded-full animate-pulse mr-1"></span>
                Online
            </p>
        </div>
    </div>

    <!-- 2. QUICK ACTIONS (if any for this role) -->
    <?php if (!empty($roleConfig['quickActions'])): ?>
    <div class="bg-gradient-to-r from-blue-600 to-indigo-600 rounded-[2rem] p-6 text-white shadow-xl">
        <div class="flex flex-col md:flex-row items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-sm">
                    <i data-lucide="zap" class="w-6 h-6"></i>
                </div>
                <div>
                    <h3 class="font-black text-lg">Quick Actions</h3>
                    <p class="text-white/80 text-[10px] font-bold uppercase tracking-wider">Common tasks</p>
                </div>
            </div>
            <div class="flex flex-wrap gap-3">
                <?php foreach ($roleConfig['quickActions'] as $action): ?>
                    <a href="index.php?page=<?php echo $action['page']; ?>" 
                        class="px-5 py-2.5 bg-white text-<?php echo $action['color']; ?>-600 rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-50 transition-all flex items-center gap-2 shadow-lg">
                        <i data-lucide="<?php echo $action['icon']; ?>" class="w-4 h-4"></i>
                        <?php echo $action['label']; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 3. ROLE-SPECIFIC STATS GRID -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
        <?php foreach ($roleConfig['stats'] as $s): ?>
            <div class="bg-white p-6 rounded-[2rem] shadow-sm border border-gray-100 border-b-4 border-<?php echo $s['color']; ?>-500 hover:shadow-xl transition-all duration-300 group">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest"><?php echo $s['label']; ?></p>
                        <h3 class="text-3xl font-black text-gray-800 mt-2"><?php echo $s['value']; ?></h3>
                    </div>
                    <div class="w-10 h-10 bg-<?php echo $s['color']; ?>-50 text-<?php echo $s['color']; ?>-600 rounded-xl flex items-center justify-center group-hover:bg-<?php echo $s['color']; ?>-600 group-hover:text-white transition-colors">
                        <i data-lucide="<?php echo $s['icon']; ?>" class="w-5 h-5"></i>
                    </div>
                </div>
                <div class="mt-4 flex items-center gap-1">
                    <span class="text-[9px] font-black text-<?php echo $s['color']; ?>-600 uppercase italic px-2 py-1 bg-<?php echo $s['color']; ?>-50 rounded-lg">
                        <?php echo $s['trend']; ?>
                    </span>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- 4. NOTIFICATIONS SECTION (if any for this role) -->
    <?php if (!empty($recentNotifications)): ?>
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex justify-between items-center bg-gradient-to-r from-blue-50 to-indigo-50">
            <div>
                <h4 class="font-black text-gray-800 uppercase text-sm tracking-widest flex items-center gap-2">
                    <i data-lucide="bell" class="w-5 h-5 text-blue-600"></i>
                    Notifications & Alerts
                </h4>
                <p class="text-[9px] text-gray-500 font-bold uppercase tracking-wider mt-1">
                    Action items requiring attention
                </p>
            </div>
            <span class="px-3 py-1 bg-blue-600 text-white text-[9px] font-black rounded-full">
                <?php echo count($recentNotifications); ?> New
            </span>
        </div>
        
        <div class="divide-y divide-gray-50 max-h-[500px] overflow-y-auto custom-scrollbar">
            <?php foreach ($recentNotifications as $note): ?>
                <div class="p-6 hover:bg-gray-50/50 transition-all group">
                    <div class="flex items-start gap-4">
                        <div class="w-10 h-10 bg-<?php echo $note['color']; ?>-50 text-<?php echo $note['color']; ?>-600 rounded-xl flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                            <i data-lucide="<?php echo $note['icon']; ?>" class="w-5 h-5"></i>
                        </div>
                        
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs font-black text-gray-800"><?php echo $note['patient_name']; ?></span>
                                <span class="text-[8px] font-black text-gray-400 uppercase tracking-wider"><?php echo $note['medical_record_number']; ?></span>
                            </div>
                            
                            <p class="text-sm font-bold text-gray-700 mb-1"><?php echo $note['message']; ?></p>
                            
                            <div class="flex items-center gap-3 text-[9px]">
                                <span class="text-gray-400 font-black uppercase tracking-wider">From: <?php echo $note['from_name']; ?></span>
                                <span class="text-gray-300">•</span>
                                <span class="text-gray-400 font-black"><?php echo date('M d, H:i', strtotime($note['created_at'])); ?></span>
                            </div>
                        </div>
                        
                        <button onclick="handleNotification('<?php echo $note['source']; ?>', '<?php echo $note['id']; ?>', '<?php echo $note['patient_id']; ?>')" 
                            class="px-4 py-2 bg-<?php echo $note['color']; ?>-600 text-white rounded-xl font-black text-[8px] uppercase tracking-widest hover:bg-<?php echo $note['color']; ?>-700 transition-all shadow-sm hover:shadow-md active:scale-95 shrink-0">
                            Action
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 5. RECENT ACTIVITY -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-8 border-b border-gray-50 flex justify-between items-center">
            <h4 class="font-black text-gray-700 uppercase text-xs tracking-widest">Recent Activity</h4>
            <a href="index.php?page=records" class="text-blue-600 text-[10px] font-black uppercase tracking-widest hover:underline flex items-center gap-1">
                View All <i data-lucide="arrow-right" class="w-3 h-3"></i>
            </a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead class="bg-gray-50/50 text-gray-400 text-[9px] font-black uppercase tracking-[0.2em]">
                    <tr>
                        <?php foreach ($roleConfig['recentActivity']['columns'] as $col): ?>
                            <th class="px-8 py-5"><?php echo $col; ?></th>
                        <?php endforeach; ?>
                        <th class="px-8 py-5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php foreach ($recentActivity as $row): ?>
                        <tr class="hover:bg-blue-50/20 transition-all group">
                            <td class="px-8 py-5 font-black text-gray-800 text-sm"><?php echo $row['full_name']; ?></td>
                            <td class="px-8 py-5 text-[10px] font-bold text-blue-600 uppercase"><?php echo $row['medical_record_number']; ?></td>
                            <td class="px-8 py-5 text-xs font-bold text-gray-500"><?php echo $row['details'] ?? $row['address'] ?? $row['test_type'] ?? $row['medication_name'] ?? '-'; ?></td>
                            <td class="px-8 py-5">
                                <span class="px-3 py-1 bg-<?php echo $row['color']; ?>-50 text-<?php echo $row['color']; ?>-600 text-[9px] font-black uppercase rounded-lg border border-<?php echo $row['color']; ?>-100">
                                    <?php echo $row['status'] ?? 'Active'; ?>
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button onclick="viewPatientDetail(<?php echo htmlspecialchars(json_encode($row)); ?>)"
                                    class="p-2 bg-gray-50 text-gray-400 group-hover:bg-blue-600 group-hover:text-white rounded-xl transition-all">
                                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- PATIENT DETAIL MODAL (reused for all roles) -->
<div id="pDetailModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-md transition-all duration-300">
    <div id="pDetailCard" class="bg-white rounded-[3rem] w-full max-w-lg p-10 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300">
        <div class="flex justify-between items-start mb-8">
            <div id="mInitials" class="w-16 h-16 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-2xl shadow-lg italic">--</div>
            <button onclick="closePModal()" class="text-gray-300 hover:text-red-500 transition-colors"><i data-lucide="x-circle" class="w-8 h-8"></i></button>
        </div>
        <div class="space-y-8 mb-10">
            <div>
                <h2 id="mName" class="text-2xl font-black text-gray-800 uppercase italic leading-tight">--</h2>
                <div class="flex gap-2 mt-2">
                    <span id="mMRN" class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-black uppercase rounded-md border border-blue-100">--</span>
                    <span id="mAgeSex" class="px-2 py-0.5 bg-gray-50 text-gray-500 text-[10px] font-black uppercase rounded-md border border-gray-100">--</span>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="bg-gray-50 p-6 rounded-[2rem] border border-gray-100">
                    <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-3 italic">Address</p>
                    <p id="mAddress" class="text-sm font-bold text-gray-700 italic">--</p>
                </div>
                <div class="bg-blue-50/50 p-6 rounded-[2rem] border border-blue-100">
                    <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-3 italic">Contact</p>
                    <p id="mPhone" class="text-sm font-black text-blue-700 tracking-widest">--</p>
                </div>
            </div>
        </div>

   <?php $isDoctor = ($_SESSION['role'] === 'Doctor'); ?>

<a id="mLink"
   <?php if ($isDoctor): ?>
       href="index.php?page=consultation"
   <?php endif; ?>
   class="w-full py-4 rounded-[2rem] font-black text-[10px] uppercase tracking-widest shadow-lg flex items-center justify-center gap-2 transition-all
   <?php echo $isDoctor 
        ? 'bg-blue-600 text-white hover:bg-blue-700 active:scale-95' 
        : 'bg-gray-200 text-gray-400 cursor-not-allowed'; ?>">

    <i data-lucide="folder-open" class="w-4 h-4"></i>
    Open Medical Chart
</a>
        <input type="hidden" id="mPatientId">
    </div>
</div>

<script>
    lucide.createIcons();

    function viewPatientDetail(data) {
        document.getElementById('mInitials').innerText = data.full_name.substring(0, 1).toUpperCase();
        document.getElementById('mName').innerText = data.full_name;
        document.getElementById('mMRN').innerText = data.medical_record_number;
        document.getElementById('mAgeSex').innerText = `${data.age}Y • ${data.gender}`;
        document.getElementById('mAddress').innerText = data.address || 'Not Recorded';
        document.getElementById('mPhone').innerText = data.contact_details || 'Not Recorded';
        document.getElementById('mPatientId').value = data.patient_id;
        document.getElementById('mLink').href = `index.php?page=consultation&id=${data.patient_id}`;

        document.getElementById('pDetailModal').classList.remove('hidden');
        requestAnimationFrame(() => document.getElementById('pDetailCard').classList.remove('scale-95'));
    }

    function closePModal() {
        document.getElementById('pDetailCard').classList.add('scale-95');
        setTimeout(() => document.getElementById('pDetailModal').classList.add('hidden'), 200);
    }

    function handleNotification(source, referenceId, patientId) {
        // Route to appropriate page based on notification source and user role
        const role = '<?php echo $userRole; ?>';
        let page = '';
        
        switch(role) {
            case 'Clerk':
                if (source === 'doctor') page = 'schedule-followup';
                else if (source === 'lab') page = 'notify-patient';
                else if (source === 'pharmacy') page = 'notify-patient';
                else if (source === 'discharge') page = 'discharge';
                else if (source === 'appointment') page = 'checkin';
                break;
            case 'Nurse':
                if (source === 'doctor') page = 'vitals-review';
                else if (source === 'emergency') page = 'triage';
                break;
            case 'Doctor':
                if (source === 'lab') page = 'consultation';
                else if (source === 'critical') page = 'emergency';
                else if (source === 'consult') page = 'consultation';
                break;
            case 'Lab Technician':
                if (source === 'stat') page = 'laboratory';
                else page = 'laboratory';
                break;
            case 'Radiologist':
                if (source === 'stat') page = 'radiology';
                else page = 'radiology';
                break;
            case 'Pharmacist':
                if (source === 'stat') page = 'pharmacy';
                else page = 'pharmacy';
                break;
        }
        
        if (page) {
            window.location.href = `index.php?page=${page}&id=${patientId}&ref=${referenceId}`;
        } else {
            window.location.href = `index.php?page=records&id=${patientId}`;
        }
    }

    // Close modal when clicking outside
    document.getElementById('pDetailModal')?.addEventListener('click', function(e) {
        if (e.target === this) closePModal();
    });
</script>