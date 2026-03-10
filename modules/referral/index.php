<?php
// Get patient and visit context
$id = $_GET['id'] ?? "";
$visit_id = $_GET['vid'] ?? "";
$patient = $patientObj->getById($id);

if (!$patient) {
    echo "<div class='p-10 text-center font-semibold text-gray-400 uppercase tracking-wider'>Patient Record Not Found</div>";
    exit;
}

// Get active visit if visit_id not provided
if (empty($visit_id)) {
    $vQuery = $db->prepare("SELECT * FROM medical_visits WHERE patient_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
    $vQuery->execute([$id]);
    $activeVisit = $vQuery->fetch(PDO::FETCH_ASSOC);
    $visit_id = $activeVisit['visit_id'] ?? null;
}

// Fetch patient's diagnoses history
$patientHistory = $clinicalObj->getPatientHistory($id);
// Fetch patient's treatment history
$treatmentHistory = $clinicalObj->getPatientTreatmentHistory($id);

// Get doctor's information - MOVED TO TOP
$doctorQuery = $db->prepare("SELECT email FROM users WHERE user_id = ?");
$doctorQuery->execute([$_SESSION['user_id']]);
$doctor = $doctorQuery->fetch(PDO::FETCH_ASSOC);

// Check if phone exists in session or use empty value
$doctorPhone = $_SESSION['phone'] ?? '';

$msg = "";
$msgType = "teal";

// Handle referral submission
// Handle referral submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_referral'])) {

    // -------------------------
    // REQUIRED FIELD VALIDATION
    // -------------------------
    $errors = [];

    if (empty($visit_id)) {
        $errors[] = "No active visit found for this patient.";
    }

    if (empty($_POST['target_department'])) {
        $errors[] = "Target department is required.";
    }

    if (empty(trim($_POST['reason']))) {
        $errors[] = "Reason for referral is required.";
    }

    if (!empty($errors)) {
        $msg = implode("<br>", $errors);
        $msgType = "red";
    } else {

        // -------------------------
        // Collect Diagnoses
        // -------------------------
        $diagnoses = [];
        if (!empty($_POST['diagnosis_items'])) {
            foreach ($_POST['diagnosis_items'] as $diagnosis) {
                if (!empty(trim($diagnosis))) {
                    $diagnoses[] = trim($diagnosis);
                }
            }
        }

        // -------------------------
        // Collect Treatments
        // -------------------------
        $treatments = [];
        if (!empty($_POST['treatment_items'])) {
            foreach ($_POST['treatment_items'] as $treatment) {
                if (!empty(trim($treatment))) {
                    $treatments[] = trim($treatment);
                }
            }
        }

        // -------------------------
        // Build Data (DB SAFE)
        // -------------------------
        $data = [

            // REQUIRED
            'patient_id' => $id,
            'visit_id' => $visit_id,
            'source_doctor_id' => $_SESSION['user_id'], // ✅ ADDED
            'target_department' => $_POST['target_department'],
            'reason' => trim($_POST['reason']),

            // Optional but useful
            'date_of_birth' => $_POST['date_of_birth'] ?? null,

            // Referring
            'referring_facility' => "Mattu Karl Specialized Hospital",
            'referring_focal_point' => $_SESSION['full_name'],
            'referring_phone' => !empty($_POST['referring_phone']) ? $_POST['referring_phone'] : null,

            // Target
            'target_facility' => !empty($_POST['target_facility']) ? $_POST['target_facility'] : null,
            'target_focal_point' => !empty($_POST['target_focal_point']) ? $_POST['target_focal_point'] : null,
            'target_phone' => !empty($_POST['target_phone']) ? $_POST['target_phone'] : null,

            // Enums (use defaults if empty)
            'priority' => $_POST['priority'] ?? 'Routine',
            'referral_type' => $_POST['referral_type'] ?? 'Outpatient',

            // Logistics
            'transportation_needs' => $_POST['transportation_needs'] ?? null,
            'follow_up_requirements' => $_POST['follow_up_requirements'] ?? null,

            // JSON Data
            'diagnosis_items' => $diagnoses,
            'other_diagnoses' => $_POST['other_diagnoses'] ?? null,

            'treatments_initiated' => $_POST['treatments_initiated'] ?? null,
            'treatment_items' => $treatments,
            'medication_chart_attached' => isset($_POST['medication_chart_attached']) ? true : false,

            // Functional Status
            'mobility_status' => $_POST['mobility_status'] ?? null,
            'mobility_precautions' => $_POST['mobility_precautions'] ?? null,
            'self_care_status' => $_POST['self_care_status'] ?? null,
            'cognitive_impairment' => isset($_POST['cognitive_impairment']) ? true : false,
            'assistive_devices_provided' => $_POST['assistive_devices_provided'] ?? null,
            'assistive_devices_required' => $_POST['assistive_devices_required'] ?? null,

            // Compiled Info
            'compiled_by' => $_SESSION['full_name'] ?? null,
            'compiled_position' => $_SESSION['role'] ?? null,
            'signature' => $_POST['signature'] ?? $_SESSION['full_name']
        ];

        // -------------------------
        // Create Referral
        // -------------------------
        

        $result = $clinicalObj->createDetailedReferral($data, $_SESSION['user_id']);

        if ($result) {
            $msg = "Referral sent successfully to " . ($_POST['target_facility'] ?? "target facility");
            $msgType = "emerald";

            // Store the referral ID in session for persistence
            $_SESSION['last_referral_id'] = $result;

            // Also store in a local variable for immediate use
            $new_referral_id = $result;
            $showPrint = true;
        } else {
            $msg = "Error sending referral.";
            $msgType = "red";
        }
    }
}
if (isset($new_referral_id)) {
    $referral_id = $new_referral_id;
}
// Otherwise check if there's one in the session from a previous submission
elseif (isset($_SESSION['last_referral_id'])) {
    $referral_id = $_SESSION['last_referral_id'];
}
// Or get it from URL parameter if provided
elseif (isset($_GET['ref_id'])) {
    $referral_id = $_GET['ref_id'];
}
// Default to empty
else {
    $referral_id = "";
}
// Fetch existing referrals for this patient
$referrals = $clinicalObj->getPatientReferrals($id);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TB Referral Form - Mettu Karl Referral Hospital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {"50":"#f0fdfa","100":"#ccfbf1","200":"#99f6e4","300":"#5eead4","400":"#2dd4bf","500":"#14b8a6","600":"#0d9488","700":"#0f766e","800":"#115e59","900":"#134e4a","950":"#042f2e"}
                    }
                }
            }
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            font-family: 'Sora', sans-serif;
            background-color: #f8fafc;
        }

        @media print {
            .no-print {
                display: none !important;
            }

            .print-only {
                display: block !important;
            }

            .print-card {
                box-shadow: none !important;
                border: 1px solid #e5e7eb !important;
            }
        }
        
        .dynamic-item {
            transition: all 0.3s ease;
        }
        
        .remove-item {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        
        .remove-item:hover {
            color: #ef4444;
            transform: scale(1.1);
        }
    </style>
</head>

<body class="p-8">
    <div class="max-w-5xl mx-auto space-y-6">
        <!-- Notification -->
        <?php if ($msg): ?>
                <div id="notification-alert"
                    class="no-print p-4 bg-<?php echo $msgType; ?>-50 text-<?php echo $msgType; ?>-700 rounded-2xl border border-<?php echo $msgType; ?>-100 font-bold text-xs flex items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2 shadow-sm">
                    <div class="flex items-center gap-3">
                        <i data-lucide="info" class="w-4 h-4"></i>
                        <span><?php echo $msg; ?></span>
                    </div>
                </div>
        <?php endif; ?>

        <!-- Print Controls -->
        <div class="no-print flex items-center justify-between">
            <div>
                <h1 class="text-2xl font-bold text-gray-800">Patient Referral Form</h1>
                <p class="text-sm text-gray-500">WHO-compliant referral documentation</p>
            </div>
           <!-- Update the print button section -->
<div class="flex gap-3">
    <?php if (isset($showPrint) && $showPrint && !empty($referral_id)): ?>
                <a href="modules/referral/print.php?id=<?php echo urlencode($referral_id); ?>" target="_blank"
                    class="px-6 py-3 bg-teal-600 text-white rounded-xl font-semibold text-xs uppercase tracking-widest hover:bg-teal-700 transition-all flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i> View Referral PDF
                </a>
            <?php endif; ?>
        
            <!-- Also add a button to view recent referrals if they exist -->
            <?php if (!isset($showPrint) && !empty($referrals) && count($referrals) > 0): ?>
                <a href="modules/referral/print.php?id=<?php echo urlencode($referrals[0]['referral_id']); ?>" target="_blank"
                    class="px-6 py-3 bg-green-600 text-white rounded-xl font-semibold text-xs uppercase tracking-widest hover:bg-green-700 transition-all flex items-center gap-2">
                    <i data-lucide="file-text" class="w-4 h-4"></i> View Latest Referral
                </a>
            <?php endif; ?>
        
            <a href="index.php?page=consultation&id=<?php echo urlencode($id); ?>"
                class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl font-semibold text-xs uppercase tracking-widest hover:bg-gray-200 transition-all">
                Back to Consultation
            </a>
        </div>
        </div>

        <!-- Main Form -->
         <!-- Recent Referrals List -->
<?php if (!empty($referrals)): ?>
            <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 mt-6">
                <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Recent Referrals</h2>
                <div class="space-y-3">
                    <?php foreach ($referrals as $ref): ?>
                        <div class="flex items-center justify-between p-4 bg-gray-50 rounded-xl">
                            <div>
                                <p class="font-bold text-gray-800">To:
                                    <?php echo htmlspecialchars($ref['target_facility'] ?? 'N/A'); ?>
                                </p>
                                <p class="text-xs text-gray-500">Date:
                                    <?php echo date('Y-m-d', strtotime($ref['created_at'])); ?>
                                </p>
                                <p class="text-xs text-gray-500">Status:
                                    <?php echo $ref['status'] ?? 'Pending'; ?>
                                </p>
                            </div>
                            <a href="modules/referral/print.php?id=<?php echo urlencode($ref['referral_id']); ?>" target="_blank"
                                class="px-4 py-2 bg-teal-100 text-teal-600 rounded-xl font-bold text-xs hover:bg-teal-200 transition-all flex items-center gap-2">
                                <i data-lucide="file-text" class="w-4 h-4"></i> View PDF
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        <div class="bg-white rounded-2xl shadow-lg border border-gray-200 p-8 print-card">
            <form method="POST" class="space-y-8" id="referralForm">
                <!-- Patient Information Header -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Patient Information</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <p class="text-xs font-bold text-gray-500">Full Name</p>
                            <p class="font-bold text-gray-800 text-lg"><?php echo $patient['full_name']; ?></p>
                        </div>
                        <div>
                            <p class="text-xs font-bold text-gray-500">Medical Record Number</p>
                            <p class="font-semibold text-teal-600"><?php echo $patient['medical_record_number']; ?></p>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Date of Birth</label>
                            <?php
                            $estimatedDob = date('Y-m-d', strtotime("-" . $patient['age'] . " years"));
                            ?>
                            <input type="date" name="date_of_birth" value="<?php echo $estimatedDob; ?>" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl focus:ring-2 focus:ring-teal-500">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Age / Gender</label>
                            <input type="text" value="<?php echo $patient['age']; ?> years / <?php echo $patient['gender']; ?>" readonly
                                class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl text-gray-600">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Contact</label>
                            <input type="text" value="<?php echo $patient['contact_details']; ?>" readonly
                                class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl text-gray-600">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Address</label>
                            <input type="text" value="<?php echo $patient['address']; ?>" readonly
                                class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl text-gray-600">
                        </div>
                    </div>
                </div>

               
              <!-- Referring Facility -->

<div class="border-b border-gray-200 pb-6">
    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Referring From</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="md:col-span-2">
            <label class="text-xs font-bold text-gray-500 block mb-1">Facility Name</label>
            <input type="text" name="referring_facility" value="Mattu Karl Specialized Hospital" readonly
                class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl text-gray-600">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 block mb-1">Focal Point</label>
            <input type="text" name="referring_focal_point" value="<?php echo $_SESSION['full_name']; ?>" readonly
                class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl">
        </div>
        <div>
            <label class="text-xs font-bold text-gray-500 block mb-1">Phone <span class="text-gray-400 font-normal">(optional)</span></label>
            <input type="text" name="referring_phone" placeholder="+251-XXX-XXXXXX" 
                value="<?php echo $doctorPhone; ?>"
                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
        </div>
    </div>
</div>

                <!-- Target Facility -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Referral To</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-gray-500 block mb-1">Facility Name</label>
                            <input type="text" name="target_facility" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Focal Point</label>
                            <input type="text" name="target_focal_point" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Phone</label>
                            <input type="text" name="target_phone" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                        </div>
                    </div>
                </div>

                <!-- Referral Details -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Referral Details</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Target Department</label>
                            <select name="target_department" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                                <option value="">Select Department</option>
                                <option value="Internal Medicine">Internal Medicine</option>
                                <option value="Surgery">Surgery</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Gynecology">Gynecology</option>
                                <option value="Emergency">Emergency</option>
                                <option value="ICU">ICU</option>
                                <option value="Radiology">Radiology</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Orthopedics">Orthopedics</option>
                                <option value="ENT">ENT</option>
                                <option value="Ophthalmology">Ophthalmology</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Priority</label>
                            <select name="priority" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                                <option value="Routine">Routine</option>
                                <option value="Urgent">Urgent</option>
                                <option value="Emergency">Emergency</option>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Referral Type</label>
                            <select name="referral_type" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                                <option value="Outpatient">Outpatient</option>
                                <option value="Inpatient">Inpatient</option>
                                <option value="Community">Community</option>
                            </select>
                        </div>
                        <div class="md:col-span-3">
                            <label class="text-xs font-bold text-gray-500 block mb-1">Reason for Referral</label>
                            <textarea name="reason" rows="3" required
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Diagnoses - Dynamic -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Diagnoses</h2>
                    <div class="space-y-4">
                        <div id="diagnoses-container">
                            <label class="text-xs font-bold text-gray-500 block mb-2">Diagnosis List</label>
                            <?php
                            // Pre-fill with existing diagnoses
                            $diagnosisCount = count($patientHistory);
                            if ($diagnosisCount > 0):
                                foreach ($patientHistory as $index => $dx):
                                    if ($index < 10): // Limit to 10 items
                                        ?>
                                            <div class="flex gap-2 mb-2 dynamic-item">
                                                <input type="text" name="diagnosis_items[]" 
                                                    value="<?php echo htmlspecialchars($dx['diagnosis_details']); ?>"
                                                    class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                                                    placeholder="Diagnosis <?php echo $index + 1; ?>">
                                                <button type="button" class="remove-item px-3 text-gray-400 hover:text-red-500" onclick="this.parentElement.remove()">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                    <?php
                                    endif;
                                endforeach;
                            else:
                                ?>
                                <div class="flex gap-2 mb-2 dynamic-item">
                                    <input type="text" name="diagnosis_items[]" 
                                        class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                                        placeholder="Diagnosis 1">
                                    <button type="button" class="remove-item px-3 text-gray-400 hover:text-red-500" onclick="this.parentElement.remove()">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" onclick="addDiagnosisField()" 
                            class="text-xs font-bold text-teal-600 hover:text-teal-800 flex items-center gap-1">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Another Diagnosis
                        </button>
                        
                        <div class="mt-4">
                            <label class="text-xs font-bold text-gray-500 block mb-1">Other Diagnoses</label>
                            <textarea name="other_diagnoses" rows="2"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Treatments - Dynamic -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Treatments</h2>
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Treatments Initiated</label>
                            <textarea name="treatments_initiated" rows="2"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"></textarea>
                        </div>
                        
                        <div id="treatments-container">
                            <label class="text-xs font-bold text-gray-500 block mb-2">Ongoing Treatments</label>
                            <?php
                            // Pre-fill with existing treatments
                            $treatmentCount = count($treatmentHistory);
                            if ($treatmentCount > 0):
                                foreach ($treatmentHistory as $index => $tx):
                                    if ($index < 10):
                                        ?>
                                            <div class="flex gap-2 mb-2 dynamic-item">
                                                <input type="text" name="treatment_items[]" 
                                                    value="<?php echo htmlspecialchars($tx['description']); ?>"
                                                    class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                                                    placeholder="Treatment <?php echo $index + 1; ?>">
                                                <button type="button" class="remove-item px-3 text-gray-400 hover:text-red-500" onclick="this.parentElement.remove()">
                                                    <i data-lucide="x" class="w-4 h-4"></i>
                                                </button>
                                            </div>
                                    <?php
                                    endif;
                                endforeach;
                            else:
                                ?>
                                <div class="flex gap-2 mb-2 dynamic-item">
                                    <input type="text" name="treatment_items[]" 
                                        class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                                        placeholder="Treatment 1">
                                    <button type="button" class="remove-item px-3 text-gray-400 hover:text-red-500" onclick="this.parentElement.remove()">
                                        <i data-lucide="x" class="w-4 h-4"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <button type="button" onclick="addTreatmentField()" 
                            class="text-xs font-bold text-teal-600 hover:text-teal-800 flex items-center gap-1">
                            <i data-lucide="plus-circle" class="w-4 h-4"></i> Add Another Treatment
                        </button>
                        
                        <div class="flex items-center gap-2 mt-4">
                            <input type="checkbox" name="medication_chart_attached" id="med_chart" value="1" class="w-4 h-4">
                            <label for="med_chart" class="text-xs font-bold text-gray-600">Attach copy of medication chart at discharge</label>
                        </div>
                    </div>
                </div>

                <!-- Transportation & Follow-up -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Logistics</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Transportation Needs</label>
                            <textarea name="transportation_needs" rows="2"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                                placeholder="Transfer requirements, special considerations, frequency"></textarea>
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Follow-up Requirements</label>
                            <textarea name="follow_up_requirements" rows="2"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                                placeholder="Date of surgical review, removal of cast, external fixator, etc."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Functional Status -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Functional Status</h2>
                    <div class="space-y-6">
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-2">Mobility</label>
                            <select name="mobility_status"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl mb-2">
                                <option value="">Select Status</option>
                                <option value="Bed bound">Bed bound</option>
                                <option value="Wheelchair">Wheelchair</option>
                                <option value="Crutches">Crutches</option>
                                <option value="Walking frame">Walking frame</option>
                                <option value="Requires assistance">Requires assistance</option>
                                <option value="Independent">Independent</option>
                            </select>
                            <input type="text" name="mobility_precautions"
                                placeholder="Precautions (weight bearing restrictions, spinal precautions, etc.)"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                        </div>

                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-2">Self-care</label>
                            <select name="self_care_status"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                                <option value="">Select Status</option>
                                <option value="Carer dependent">Carer dependent</option>
                                <option value="Requires commode">Requires commode</option>
                                <option value="Requires modified latrine/washroom">Requires modified latrine/washroom</option>
                                <option value="Independent">Independent</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2">
                            <input type="checkbox" name="cognitive_impairment" id="cognitive" value="1" class="w-4 h-4">
                            <label for="cognitive" class="text-xs font-bold text-gray-600">Cognitive Impairment</label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-xs font-bold text-gray-500 block mb-1">Assistive Device(s) Provided</label>
                                <input type="text" name="assistive_devices_provided"
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                            </div>
                            <div>
                                <label class="text-xs font-bold text-gray-500 block mb-1">Assistive Device(s) Required</label>
                                <input type="text" name="assistive_devices_required"
                                    class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Compiled By -->
                <div class="border-b border-gray-200 pb-6">
                    <h2 class="text-lg font-semibold text-teal-600 uppercase tracking-wider mb-4">Compiled By</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Name</label>
                            <input type="text" value="<?php echo $_SESSION['full_name']; ?>" readonly
                                class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl">
                        </div>
                        <div>
                            <label class="text-xs font-bold text-gray-500 block mb-1">Position</label>
                            <input type="text" value="<?php echo $_SESSION['role']; ?>" readonly
                                class="w-full px-4 py-2 bg-gray-100 border border-gray-200 rounded-xl">
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-xs font-bold text-gray-500 block mb-1">Signature</label>
                            <input type="text" name="signature" value="<?php echo $_SESSION['full_name']; ?>"
                                class="w-full px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl">
                        </div>
                    </div>
                    <p class="text-xs text-gray-400 mt-4 italic">
                        NOTE: This form must accompany the patient's medical file. A copy should be retained by the referring team.
                    </p>
                </div>

                <!-- Submit Button -->
                <div class="flex justify-end gap-4 pt-4">
                    <button type="submit" name="submit_referral"
                        class="px-10 py-4 bg-teal-600 text-white rounded-xl font-semibold text-xs uppercase tracking-widest hover:bg-teal-700 transition-all flex items-center gap-2">
                        <i data-lucide="send" class="w-4 h-4"></i> Generate Referral
                    </button>
                </div>
            </form>
        </div>

    </div>
<script>
    lucide.createIcons();

    // Auto-hide notification
    setTimeout(function () {
        const alert = document.getElementById('notification-alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);

    function addDiagnosisField() {
        const container = document.getElementById('diagnoses-container');
        const newField = document.createElement('div');
        newField.className = 'flex gap-2 mb-2 dynamic-item';

        newField.innerHTML = `
            <input type="text" name="diagnosis_items[]" 
                class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                placeholder="Diagnosis">
            <button type="button" class="remove-item px-3 text-gray-400 hover:text-red-500" onclick="this.parentElement.remove()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;

        container.appendChild(newField);
        lucide.createIcons();
    }

    function addTreatmentField() {
        const container = document.getElementById('treatments-container');
        const newField = document.createElement('div');
        newField.className = 'flex gap-2 mb-2 dynamic-item';

        newField.innerHTML = `
            <input type="text" name="treatment_items[]" 
                class="flex-1 px-4 py-2 bg-gray-50 border border-gray-200 rounded-xl"
                placeholder="Treatment">
            <button type="button" class="remove-item px-3 text-gray-400 hover:text-red-500" onclick="this.parentElement.remove()">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        `;

        container.appendChild(newField);
        lucide.createIcons();
    }
</script>
</body>

</html>