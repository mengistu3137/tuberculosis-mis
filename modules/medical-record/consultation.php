<?php
// 1. Get Context
$id = $_GET['id'] ?? "";
$patient = $patientObj->getById($id);
$role = $_SESSION['role'];

if (!$patient) {
    echo "<div class='p-10 text-center font-semibold text-gray-400 uppercase tracking-wider'>Patient Record Not Found</div>";
    exit;
}

// 2. Identify the Active Visit (MOVE THIS UP)
$vQuery = $db->prepare("SELECT * FROM medical_visits WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
$vQuery->execute([$id]);
$activeVisit = $vQuery->fetch(PDO::FETCH_ASSOC);

$visit_id = $activeVisit['visit_id'] ?? "NO_ACTIVE_VISIT";
$hasActiveVisit = ($visit_id !== "NO_ACTIVE_VISIT");

// 3. Check if this is view-only mode (for discharged patients) - NOW AFTER visit_id is defined
$viewOnly = isset($_GET['viewonly']) && $_GET['viewonly'] == 1;

// If view-only mode, disable all form submissions
if ($viewOnly) {
    // Override any POST requests
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        header("Location: index.php?page=consultation&id=$id&vid=$visit_id&viewonly=1&error=readonly");
        exit;
    }
}

if (isset($_POST['order_radiology_btn'])) {
    error_log("Radiology POST data: " . print_r($_POST, true));
}



// --- HANDLE UPDATE/DELETE ACTIONS ---
$msg = "";
$msgType = "teal";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['request_nurse'])) {
        $assignedNurse = $assignObj->autoAssignNurse($visit_id);

        if ($assignedNurse) {
            $nurseLabel = $assignedNurse['full_name'] ?? 'Assigned Nurse';
            $nurseId = $assignedNurse['user_id'] ?? '';
            $nurseEmail = $assignedNurse['email'] ?? '';

            $nurseSummary = $nurseLabel;
            if (!empty($nurseId)) {
                $nurseSummary .= " (" . $nurseId . ")";
            }
            if (!empty($nurseEmail)) {
                $nurseSummary .= " • " . $nurseEmail;
            }

            $msg = "Nursing support assigned -> " . $nurseSummary . ".";
            $msgType = "indigo";
        } else {
            $msg = "No nurses available at the moment.";
            $msgType = "orange";
        }
    }
    // Diagnosis Actions
    if (isset($_POST['save_diagnosis'])) {
        if ($clinicalObj->recordDiagnosis($_POST)) {
            $msg = "Clinical findings signed by Dr. " . $_SESSION['full_name'];
            $msgType = "emerald";
        }
    }
    if (isset($_POST['update_diagnosis'])) {
        $did = $_POST['diagnosis_id'];
        $details = $_POST['diagnosis_details'];
        if ($clinicalObj->updateDiagnosis($did, $details, $_SESSION['user_id'])) {
            $msg = "Diagnosis updated successfully.";
            $msgType = "teal";
        } else {
            $msg = "Failed to update diagnosis.";
            $msgType = "red";
        }
    }
    if (isset($_POST['delete_diagnosis_confirm'])) {
        $did = $_POST['diagnosis_id'];
        if ($clinicalObj->deleteDiagnosis($did)) {
            $msg = "Diagnosis record deleted.";
            $msgType = "red";
        } else {
            $msg = "Failed to delete diagnosis.";
            $msgType = "red";
        }
    }

    // Treatment Actions
    if (isset($_POST['save_treatment'])) {
        if ($clinicalObj->saveTreatmentPlan($_POST)) {
            $msg = "Treatment Protocol committed.";
            $msgType = "emerald";
        }
    }
    if (isset($_POST['update_treatment'])) {
        $tid = $_POST['treatment_id'];
        $desc = $_POST['description'];
        $start = $_POST['start_date'];
        $end = $_POST['end_date'];
        if ($clinicalObj->updateTreatmentPlan($tid, $desc, $start, $end)) {
            $msg = "Treatment plan updated successfully.";
            $msgType = "teal";
        } else {
            $msg = "Failed to update treatment.";
            $msgType = "red";
        }
    }
    if (isset($_POST['delete_treatment_confirm'])) {
        $tid = $_POST['treatment_id'];
        if ($clinicalObj->deleteTreatmentPlan($tid)) {
            $msg = "Treatment plan deleted.";
            $msgType = "red";
        } else {
            $msg = "Failed to delete treatment.";
            $msgType = "red";
        }
    }

    // Other standard actions...
    if (isset($_POST['save_triage'])) {
        if ($clinicalObj->recordVitals($_POST)) {
            $msg = "Vital signs recorded.";
            $msgType = "emerald";
        }
    }
    if (isset($_POST['order_lab_btn'])) {
        // 1. Create the request and capture the ID
        $reqId = $labObj->requestTest(
            $visit_id,
            $_POST['test_type'],
            $_POST['tb_test_category'] ?? null,
            $_POST['specimen_type'] ?? null
        );

        if ($reqId) {
            // 2. TRIGGER AUTO ASSIGNMENT
            $assignObj->autoAssignLabTech($reqId);

            $msg = "Lab request transmitted and assigned to technician.";
            $msgType = "teal";
        }
    }
    if (isset($_POST['order_radiology_btn'])) {
        // 1. Create the request and capture the ID
        $reqId = $clinicalObj->requestRadiology(
            $visit_id,
            $_POST['exam_type'],
            $_POST['body_part'] ?? null,
            $_POST['clinical_history'] ?? null,
            $_POST['priority'] ?? 'normal'
        );

        if ($reqId) {
            // 2. TRIGGER AUTO ASSIGNMENT
            $assignObj->autoAssignRadiologist($reqId);

            $msg = "Radiology imaging request sent and assigned to radiologist.";
            $msgType = "emerald";
        } else {
            $msg = "Failed to send radiology request.";
            $msgType = "red";
        }
    }
    if (isset($_POST['admit_inpatient'])) {
        // We use the visitObj to update the database table 'medical_visits'
        $stmt = $db->prepare("UPDATE medical_visits SET visit_type = 'Inpatient' WHERE visit_id = ?");
        if ($stmt->execute([$visit_id])) {
            $msg = "Patient status upgraded to Inpatient Admission successfully.";
            $msgType = "indigo";
            // Update the local variable so the UI reflects the change immediately without refresh
            $activeVisit['visit_type'] = 'Inpatient';
        } else {
            $msg = "Failed to update admission status.";
            $msgType = "red";
        }
    }
}

// 4. FETCH DATA FOR UI
$clinicalData = $clinicalObj->getVisitSummary($visit_id);
$vitals = $clinicalData['vitals'];
$diagnosis = $clinicalData['diagnosis'];
$treatment = $clinicalData['treatment'];

// Fetch History and Results
$history = $clinicalObj->getPatientHistory($id);
$labResults = $clinicalObj->getVisitLabResults($visit_id);

// Fetch Treatment History (New)
$treatmentHistory = $clinicalObj->getPatientTreatmentHistory($id);

$exitPath = (in_array($role, ['Doctor', 'Nurse'])) ? 'visit' : 'records';
$actions = [
    ["id" => "prescribe", "label" => "Prescription", "icon" => "pill"],
    ["id" => "referral", "label" => "Referral", "icon" => "split"],
    ["id" => "discharge", "label" => "Discharge", "icon" => "log-out"],
   

    
];
?>

 <div class="space-y-6">

    <!-- 1. PATIENT CONTEXT BAR -->
    <div class="sticky -top-10 z-20 bg-white/90 backdrop-blur-md border border-teal-100 p-5 rounded-2xl shadow-sm flex flex-col gap-4">
        <!-- Notification -->
        <?php if ($msg): ?>
            <div id="notification-alert"
                class="w-full p-4 bg-<?php echo $msgType; ?>-50 text-<?php echo $msgType; ?>-700 rounded-2xl border border-<?php echo $msgType; ?>-100 font-bold text-xs flex items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2 shadow-sm">
                <div class="flex items-center gap-3">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span><?php echo $msg; ?></span>
                    </div>
                    <button onclick="closeNotification()" class="p-1 hover:bg-<?php echo $msgType; ?>-100 rounded-lg transition-colors">
                        <i data-lucide="x" class="w-4 h-4 text-<?php echo $msgType; ?>-400"></i>
                    </button>
                </div>
        <?php endif; ?>

        <!-- PATIENT INFO -->
        <div class="flex flex-col md:flex-row items-center justify-between w-full">
           <div class="flex items-center gap-5 p-2">
    <div class="w-14 h-14 bg-teal-600 text-white rounded-full flex items-center justify-center font-bold text-xl shadow-md shrink-0">
        <?= strtoupper(substr($patient['full_name'], 0, 1)); ?>
            </div>
        
            <div class="flex flex-col gap-1">
                <div class="flex items-center gap-3">
                    <h2 class="text-xl font-bold text-slate-800 leading-tight"><?= $patient['full_name']; ?></h2>
                    <span
                        class="px-2 py-0.5 bg-slate-100 text-slate-600 text-[11px] font-medium rounded-md border border-slate-200">
                        <?= $patient['medical_record_number']; ?>
                    </span>
                </div>
        
                <div class="flex items-center gap-4">
                    <p class="text-[11px] font-semibold first-letter:uppercase tracking-wider text-slate-400">
                        Status: <span
                            class="text-indigo-500  first-letter:uppercase"><?= $activeVisit ? $activeVisit['visit_type'] : "Inactive"; ?></span>
                    </p>
        
               <?php if (!$viewOnly && $hasActiveVisit && $role == 'Doctor' && $activeVisit['visit_type'] != 'Inpatient'): ?>
    <form method="POST" id="admitForm" class="inline">
        <button type="button"
            onclick="openActionModal('admitForm', 'Confirm Admission', 'This will upgrade the patient to Inpatient status for ward management.', 'home')"
            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[10px] font-semibold uppercase tracking-wide transition-all bg-white text-teal-600 border border-transparent hover:border-teal-200 hover:shadow-sm shadow-sm group">
            <i data-lucide="double-bed" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
            <span class="hidden lg:block">Admit Inpatient</span>
        </button>
        <input type="hidden" name="admit_inpatient" value="1">
    </form>
<?php endif; ?>
                </div>
            </div>
        </div>
            
            <div class="flex items-center gap-3 w-full md:w-auto mt-4 md:mt-0">
                <div class="flex items-center gap-2 bg-gray-50 p-1.5 rounded-2xl border border-gray-100 flex-wrap">
                   
                    
                    <?php foreach ($actions as $act):
                        // Disable actions if view-only mode OR no active visit OR role restrictions
                        $disabled = ($viewOnly || !$hasActiveVisit || (($act['id'] == 'prescribe' || $act['id'] == 'discharge') && $role != 'Doctor' && $role != 'Admin'));

                        if ($viewOnly && $act['id'] != 'discharge'):
                            // In view-only mode, only show disabled icons
                            ?>
                            <span
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[10px] font-semibold uppercase tracking-wide opacity-30 grayscale bg-gray-100 text-gray-400 border border-transparent cursor-not-allowed"
                                title="Read-only mode - patient discharged">
                                <i data-lucide="<?php echo $act['icon']; ?>" class="w-4 h-4"></i>
                                <span class="hidden lg:block"><?php echo $act['label']; ?></span>
                                    </span>
                            <?php elseif (!$viewOnly): ?>
                                    <a href=" <?php echo $disabled ? '#' : "index.php?page={$act['id']}&id=$id&vid=$visit_id"; ?>"
                                            class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[9px] font-semibold
                            uppercase tracking-widest transition-all
                            <?php echo $disabled ? 'opacity-20 cursor-not-allowed grayscale bg-gray-100 text-gray-400' : "bg-white text-teal-600 hover:border-teal-200 hover:shadow-sm shadow-sm"; ?>
                            border border-transparent"
                                    <?php echo $disabled ? 'onclick="return false;"' : ''; ?>>
                                        <i data-lucide="<?php echo $act['icon']; ?>" class="w-4 h-4"></i>
                                    <span class="hidden lg:block"><?php echo $act['label']; ?>
                                </span>
                                </a>
                            <?php endif; ?>
                     <?php endforeach; ?>

                        <!-- Request Nurse Button - Separate for Doctors only -->
                        <?php if (!$viewOnly && $hasActiveVisit && $role == 'Doctor'): ?>
                                <form method="POST" class="inline">
                            <button type="submit" name="request_nurse"
                                class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-[10px] font-semibold uppercase tracking-wide bg-indigo-50 text-indigo-600 border border-indigo-100 hover:bg-indigo-600 hover:text-white transition-all shadow-sm">
                                <i data-lucide="user-plus" class="w-4 h-4"></i>
                                <span class="hidden lg:block">Assign Nurse</span>
                                        </button>
                                </form>
                         <?php endif; ?>

                            <!-- Exit button -->
                            <a href="index.php?page=<?php echo $exitPath; ?>"
                                class="px-4 py-2 text-red-500 hover:bg-red-50 rounded-xl transition-all">
                                    <i data-lucide="x-circle" class="w-5 h-5"></i>
                                </a>
            </div>
        </div>
    </div>

    <!-- View-only indicator (moved outside the flex container but inside the main div) -->
    <?php if ($viewOnly): ?>
        <div
            class="mt-2 bg-amber-50 border border-amber-200 rounded-xl p-3 text-amber-700 text-xs font-semibold uppercase tracking-wide flex items-center gap-2">
            <i data-lucide="eye" class="w-4 h-4"></i>
            <span>View-Only Mode - Patient Discharged. No changes can be made.</span>
        </div>
    <?php endif; ?>
</div>

    <?php if (!$hasActiveVisit): ?>
            <div class="p-12 bg-teal-50 border-2 border-dashed border-teal-200 rounded-2xl text-center">
                <h3 class="text-teal-700 font-semibold uppercase tracking-widest mb-2">Check-in Required</h3>
                <p class="text-xs text-teal-500 font-medium max-w-sm mx-auto mb-6">No active visit found.</p>
                <a href="index.php?page=records" class="inline-block px-8 py-3 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase shadow-lg hover:bg-teal-700">Go to Registry</a>
            </div>
    <?php else: ?>
            <div class="grid grid-cols-1 xl:grid-cols-4 gap-8">
            
               
                 <div class="flex flex-col space-y-8">
                     <!-- COLUMN 1: MEDICAL HISTORY (Diagnosis) -->
                    <div class="space-y-6">
                    <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm h-fit">
                        <h3 class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide mb-6 italic border-b border-teal-50 pb-2">Diagnosis Archive</h3>
                        <div class="space-y-6 max-h-[600px] overflow-y-auto pr-2 custom-scrollbar">
                            <?php if (empty($history)): ?>
                                    <p class="text-[9px] text-gray-300 font-bold uppercase text-center italic py-10">No history on file</p>
                            <?php else: ?>
                                    <?php foreach ($history as $h):
                                        $createdTime = strtotime($h['created_at'] ?? $h['diagnosis_date']);
                                        $diffMinutes = round((time() - $createdTime) / 60);
                                        $canEdit = ($diffMinutes <= 30 && $role == 'Doctor');
                                        ?>
                                            <div class="relative pl-5 border-l-2 border-teal-100 group hover:border-teal-300 transition-colors">
                                                <div class="absolute -left-[5px] top-0 w-2 h-2 bg-teal-500 rounded-full border-2 border-white shadow-sm"></div>
                                                <div class="flex justify-between items-start">
                                                    <p class="text-[9px] font-semibold text-teal-400 uppercase leading-none">
                                                        <?php echo date("M d, Y", strtotime($h['diagnosis_date'])); ?>
                                                    </p>
                                                    <?php if ($canEdit): ?>
                                                            <div class="relative">
                                                                <button onclick="toggleMenu('dx-<?php echo $h['diagnosis_id']; ?>')" class="text-gray-400 hover:text-teal-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                                                                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                                                </button>
                                                                <div id="menu-dx-<?php echo $h['diagnosis_id']; ?>" class="hidden absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                                                                    <button onclick="openEditDiagnosisModal('<?php echo $h['diagnosis_id']; ?>', '<?php echo htmlspecialchars($h['diagnosis_details'], ENT_QUOTES); ?>')" class="w-full text-left px-4 py-2 text-[10px] font-bold text-gray-700 hover:bg-teal-50 hover:text-teal-600 flex items-center gap-2">
                                                                        <i data-lucide="edit-2" class="w-3 h-3"></i> Edit
                                                                    </button>
                                                                    <form method="POST" onsubmit="return confirm('Delete this diagnosis?');" class="w-full">
                                                                        <input type="hidden" name="diagnosis_id" value="<?php echo $h['diagnosis_id']; ?>">
                                                                        <input type="hidden" name="delete_diagnosis_confirm" value="1">
                                                                        <button type="submit" class="w-full text-left px-4 py-2 text-[10px] font-bold text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                                            <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                                                        </button>
                                                                    </form>
                                                                </div>
                                                            </div>
                                                    <?php endif; ?>
                                                </div>
                                                <p class="text-[11px] font-semibold text-gray-700 mt-2 leading-relaxed"><?php echo $h['diagnosis_details']; ?></p>
                                            </div>
                                    <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                 <!-- Lab  ORDERS -->
                    <div class="grid grid-cols-1  gap-8 <?php echo ($role != 'Doctor' && $role != 'Admin') ? 'opacity-40 pointer-events-none' : ''; ?>">
                        <div class="bg-teal-50/50 rounded-2xl p-8 border border-teal-100 shadow-sm flex flex-col justify-between">
                            <h3 class="text-sm font-bold text-gray-800 mb-6 flex items-center gap-2"><i data-lucide="flask-conical" class="w-4 h-4 text-teal-600"></i> TB Lab Order</h3>
                            <form method="POST" class="space-y-4">
                                <input type="text" name="test_type" required placeholder="E.g. GeneXpert MTB/RIF, Sputum AFB, TB Culture" class="w-full px-6 py-3 bg-white border-none rounded-xl font-bold text-xs shadow-sm">
                                <select name="tb_test_category" class="w-full px-6 py-3 bg-white border-none rounded-xl font-bold text-xs shadow-sm">
                                    <option value="">TB Test Category</option>
                                    <option value="Bacteriological">Bacteriological</option>
                                    <option value="Molecular">Molecular</option>
                                    <option value="Drug Susceptibility">Drug Susceptibility</option>
                                </select>
                                <select name="specimen_type" class="w-full px-6 py-3 bg-white border-none rounded-xl font-bold text-xs shadow-sm">
                                    <option value="">Specimen Type</option>
                                    <option value="Sputum">Sputum</option>
                                    <option value="Gastric Aspirate">Gastric Aspirate</option>
                                    <option value="Pleural Fluid">Pleural Fluid</option>
                                    <option value="Blood">Blood</option>
                                </select>
                                <button name="order_lab_btn" class="w-full py-3 bg-teal-600 text-white rounded-xl font-semibold text-[9px] uppercase tracking-widest shadow-lg hover:bg-teal-700">Transmit</button>
                            </form>
                        </div>
                       
                    </div>

                   <!-- RADIOLOGY ORDERS -->
<div class="grid grid-cols-1 gap-8 <?php echo ($role != 'Doctor' && $role != 'Admin') ? 'opacity-40 pointer-events-none' : ''; ?>">

<div class="bg-teal-50/60 rounded-2xl p-8 border border-teal-100 shadow-sm flex flex-col justify-between">

<h3 class="text-sm font-bold text-gray-800 mb-6 flex items-center gap-2">
<i data-lucide="scan" class="w-4 h-4 text-teal-600"></i>
Radiology Order
</h3>

<form method="POST" class="space-y-4">

<input type="text"
name="exam_type"
required
placeholder="Exam Type (e.g. Chest X-Ray, CT Head)"
class="w-full px-6 py-3 bg-white border-none rounded-xl font-bold text-xs shadow-sm focus:ring-2 focus:ring-teal-500">

<input type="text"
name="body_part"
placeholder="Body Part (e.g. Chest, Brain)"
class="w-full px-6 py-3 bg-white border-none rounded-xl font-bold text-xs shadow-sm focus:ring-2 focus:ring-teal-500">

<textarea
name="clinical_history"
rows="2"
placeholder="Clinical History / Indication"
class="w-full px-6 py-3 bg-white border-none rounded-xl font-bold text-xs shadow-sm focus:ring-2 focus:ring-teal-500"></textarea>

<select
name="priority"
class="w-full px-6 py-3 bg-white border-none rounded-xl font-bold text-xs shadow-sm focus:ring-2 focus:ring-teal-500">

<option value="normal">Normal Priority</option>
<option value="STAT">STAT (Emergency)</option>

</select>

<button
name="order_radiology_btn"
class="w-full py-3 bg-teal-600 text-white rounded-xl font-semibold text-[9px] uppercase tracking-widest shadow-lg hover:bg-teal-700 transition-all active:scale-95">

Transmit Order

</button>

</form>
</div>
</div>

                 </div>
                

              
              <!-- COLUMN 2-3: MAIN CLINICAL AREA -->
<div class="xl:col-span-2 space-y-8">
   <!-- LAB RESULTS BANNER - Shows both completed and pending results -->
<?php
    // Fetch both completed lab results and pending lab requests
    $labResults = $clinicalObj->getVisitLabResults($visit_id); // Completed results
    $pendingLabRequests = $clinicalObj->getPendingLabRequests($visit_id); // Pending requests

    // Merge or display both
    if (!empty($labResults) || !empty($pendingLabRequests)):
        ?>
    <!-- LAB RESULTS BANNER -->
    <div
        class="bg-gradient-to-r from-teal-600 to-teal-500 rounded-2xl p-8 text-white shadow-xl shadow-teal-200 relative overflow-hidden animate-in zoom-in duration-500">
    
        <!-- Header -->
        <div class="flex items-center justify-between mb-6 relative z-10">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold uppercase italic tracking-tighter">Laboratory Findings</h3>

<?php
            $totalItems = count($labResults) + count($pendingLabRequests);
            if ($totalItems > 1):
                ?>
    <span class="bg-white/20 text-white text-[8px] font-medium px-2 py-1 rounded-full uppercase tracking-wider">
        <?php echo $totalItems; ?> tests
    </span>
<?php endif; ?>

</div>

<div class="flex items-center gap-2">
<?php if (!empty($pendingLabRequests)): ?>
            <span class="bg-amber-400/20 text-amber-100 text-[8px] font-medium px-2 py-1 rounded-full flex items-center gap-1">
                <span class="w-1.5 h-1.5 bg-amber-400 rounded-full animate-pulse"></span>
    <?php echo count($pendingLabRequests); ?> pending
</span>
<?php endif; ?>
</div>
</div>

<!-- Content: Completed results and pending requests -->
<div class="relative z-10 -mx-2 px-2">
    <div class="flex gap-4 overflow-x-auto pb-4 scrollbar-thin scrollbar-thumb-transparent scrollbar-track-transparent hover:scrollbar-thumb-white/30" style="-webkit-overflow-scrolling: touch;">
        <?php if (!empty($labResults)): ?>
            <?php foreach ($labResults as $lr): ?>
                <div class="flex-none w-[320px] snap-start bg-white/10 p-4 rounded-2xl border border-white/10">
                    <div class="flex items-center justify-between mb-2">
                        <span
                            class="text-xs font-semibold uppercase tracking-wide text-teal-200"><?php echo $lr['test_type']; ?></span>
                        <?php if (!empty($lr['performed_date'])): ?>
                            <span class="text-[8px] font-medium bg-emerald-400 text-white px-2 py-0.5 rounded-full">Done</span>
                        <?php endif; ?>
            </div>
                    <p class="text-xs font-bold text-white/90 mb-2"><?php echo $lr['result_details']; ?></p>
                    <p class="text-[8px] text-teal-200 mt-3 font-semibold uppercase">Technician: <?php echo $lr['tech_name'] ?? 'N/A'; ?>
                    </p>
                    <?php if (!empty($lr['performed_date'])): ?>
                        <p class="text-[8px] text-teal-200 mt-2">Performed: <?php echo date('M d, Y', strtotime($lr['performed_date'])); ?></p>
                    <?php endif; ?>
                                </div>
                <?php endforeach; ?>
        <?php endif; ?>

        <?php if (!empty($pendingLabRequests)): ?>
            <?php foreach ($pendingLabRequests as $pr): ?>
                <div class="flex-none w-[320px] snap-start bg-white/6 p-4 rounded-2xl border border-white/6">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-xs font-semibold uppercase tracking-wide text-teal-200"><?php echo $pr['test_type']; ?></span>
                <span class="text-[8px] font-medium bg-amber-400 text-white px-2 py-0.5 rounded-full">Pending</span>
            </div>
                    <p class="text-xs text-white/80 mb-2">Requested: <?php echo date('M d, Y', strtotime($pr['created_at'] ?? $pr['request_date'] ?? 'now')); ?></p>
                    <p class="text-[8px] text-teal-200 mt-3 font-semibold uppercase">Requested By:
                        <?php echo $pr['doctor_name'] ?? 'N/A'; ?></p>
                    </div>
                    <?php endforeach; ?>
        <?php endif; ?>
    </div>
    </div>

<!-- Fade indicators -->
<div class="absolute top-0 left-0 w-8 h-full bg-gradient-to-r from-teal-600 to-transparent pointer-events-none z-20"></div>
<div class="absolute top-0 right-0 w-8 h-full bg-gradient-to-l from-teal-600 to-transparent pointer-events-none z-20"></div>

</div>
<?php endif; ?>
    <!-- RADIOLOGY RESULTS BANNER - Add this after lab results -->
<?php
    $radiologyResults = $clinicalObj->getRadiologyResults($visit_id);
    if (!empty($radiologyResults)):
        ?>
    <!-- RADIOLOGY RESULTS BANNER -->
    <div
        class="bg-gradient-to-r from-teal-600 to-teal-500 rounded-2xl p-8 text-white shadow-xl shadow-teal-200 relative overflow-hidden animate-in zoom-in duration-500 mt-4">
    
        <div class="flex items-center justify-between mb-6 relative z-10">
            <div class="flex items-center gap-3">
                <h3 class="text-lg font-bold uppercase italic tracking-tighter">Radiology Findings</h3>

<?php if (count($radiologyResults) > 1): ?>
    <span class="bg-white/20 text-white text-[8px] font-medium px-2 py-1 rounded-full uppercase tracking-wider">
        <?php echo count($radiologyResults); ?> studies
    </span>
<?php endif; ?>
    
            </div>
        </div>
    
        <!-- Horizontal Scroll -->
        <div class="relative z-10 -mx-2 px-2">
            <div class="flex overflow-x-auto gap-4 pb-4 scrollbar-thin scrollbar-thumb-transparent scrollbar-track-transparent hover:scrollbar-thumb-white/30"
                style="scroll-snap-type:x mandatory; -webkit-overflow-scrolling:touch;">
    
                <?php foreach ($radiologyResults as $rad): ?>
                    <div class="flex-none w-[350px] snap-start">
                    
                        <div class="bg-white/10 p-5 rounded-2xl border border-white/10 backdrop-blur-sm h-full">
    
                            <div class="flex justify-between items-center mb-2">
                                <span class="text-xs font-semibold uppercase tracking-wide text-teal-200">
                                    <?php echo $rad['exam_type']; ?>
                                    <?php if ($rad['body_part']): ?>
                                        (<?php echo $rad['body_part']; ?>)
                                    <?php endif; ?>
                                    </span>
    
                                <?php if ($rad['priority'] == 'STAT'): ?>
                                                    <span class="text-[8px] font-medium bg-red-500 text-white px-2 py-0.5 rounded-full">
                                                        STAT
                                                    </span>
                                    <?php endif; ?>
                                    </div>
    
                            <?php if ($rad['findings']): ?>
                    <p class="text-xs font-bold text-white/90 mb-2">
                        Findings: <?php echo $rad['findings']; ?>
    </p>
    
                                <p class="text-xs font-bold text-white/90 mb-2">
                                    Impression: <?php echo $rad['impression']; ?>
    </p>
    
                                <?php if ($rad['image_path']): ?>
                                    <a href="<?php echo $rad['image_path']; ?>" target="_blank"
                                        class="inline-flex items-center gap-1 text-[8px] font-medium text-teal-200 hover:text-white transition-colors">
                                        <i data-lucide="image" class="w-3 h-3"></i> View Image
                                    </a>
                                <?php endif; ?>
                    <p class="text-[8px] text-teal-200 mt-3 font-semibold uppercase">
                        Radiologist: <?php echo $rad['radiologist_name'] ?? 'Pending'; ?>
    </p>
    
                            <?php else: ?>
                    <p class="text-sm font-bold text-white/50 italic">
                        Results pending...
                    </p>
    
    <?php endif; ?>
    
                            <?php if ($rad['performed_date']): ?>
                    <p class="text-[8px] text-teal-200 mt-2">
                        Performed: <?php echo date('M d, Y', strtotime($rad['performed_date'])); ?>
    </p>
    
                            <?php endif; ?>
    
                        </div>
                    </div>
                <?php endforeach; ?>
    
            </div>
        </div>
    
        <!-- Fade indicators -->
        <div
            class="absolute top-0 left-0 w-8 h-full bg-gradient-to-r from-teal-600 to-transparent pointer-events-none z-20">
        </div>
        <div
            class="absolute top-0 right-0 w-8 h-full bg-gradient-to-l from-teal-600 to-transparent pointer-events-none z-20">
        </div>
    
    </div>
 <?php endif; ?>

                  
 <!-- PHYSICIAN PROGRESS NOTES -->
    <div class="flex-1 bg-white rounded-2xl p-6 sm:p-10 border border-gray-100 shadow-sm justify-center <?php echo ($role != 'Doctor' && $role != 'Admin') ? 'opacity-40 pointer-events-none' : ''; ?>">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-1.5 h-6 bg-teal-600 rounded-full shadow-lg"></div>
        <h3 class="text-xl font-bold text-gray-800">TB Clinical Assessment</h3>
    </div>
    <form id="diagnosisForm" method="POST" class="space-y-8">
        <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">
        <div class="space-y-3">
            <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">Assessment &
                Findings</label>
            <!-- REMOVED PHP VALUE to keep empty -->
          <textarea name="diagnosis_details" rows="8" 
   <?php echo $viewOnly ? 'disabled' : 'required'; ?>
            class="w-full bg-gray-50 border-none rounded-xl px-6 sm:px-8 py-6 focus:ring-2 focus:ring-teal-500 font-medium text-gray-700 shadow-inner <?php echo $viewOnly ? 'opacity-60 cursor-not-allowed' : ''; ?>"
            placeholder="<?php echo $viewOnly ? 'View-only mode - patient discharged' : 'Analyze symptoms...'; ?>"><?php echo $viewOnly ? ($diagnosis['diagnosis_details'] ?? '') : ''; ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
            <select name="tb_classification" <?php echo $viewOnly ? 'disabled' : ''; ?> class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
                <option value="">TB Classification</option>
                <option value="Pulmonary TB">Pulmonary TB</option>
                <option value="Extrapulmonary TB">Extrapulmonary TB</option>
                <option value="Presumptive TB">Presumptive TB</option>
            </select>
            <select name="diagnosis_method" <?php echo $viewOnly ? 'disabled' : ''; ?> class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
                <option value="">Diagnosis Method</option>
                <option value="Clinical">Clinical</option>
                <option value="GeneXpert">GeneXpert</option>
                <option value="AFB Smear">AFB Smear</option>
                <option value="Culture">Culture</option>
                <option value="Radiology">Radiology</option>
            </select>
            <select name="tb_treatment_status" <?php echo $viewOnly ? 'disabled' : ''; ?> class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
                <option value="">TB Treatment Status</option>
                <option value="Not Started">Not Started</option>
                <option value="On Treatment">On Treatment</option>
                <option value="Completed">Completed</option>
                <option value="Lost to Follow-up">Lost to Follow-up</option>
            </select>
            <select name="mdr_tb_status" <?php echo $viewOnly ? 'disabled' : ''; ?> class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
                <option value="No">MDR-TB: No</option>
                <option value="Yes">MDR-TB: Yes</option>
            </select>
        </div>

        <div class="flex justify-center pt-4 border-t border-gray-50 items-center gap-6 text-center">
        <?php if ($viewOnly): ?>
            <button type="button" disabled
                class="w-full sm:w-auto px-8 sm:px-14 py-4 bg-gray-400 text-white rounded-2xl font-semibold text-[10px] uppercase tracking-widest cursor-not-allowed opacity-50">
                Read Only - Discharged
            </button>
        <?php else: ?>
            <button type="submit" name="save_diagnosis"
                class="w-full sm:w-auto px-8 sm:px-14 py-4 bg-teal-600 text-white rounded-2xl font-semibold text-[10px] uppercase tracking-wide hover:bg-teal-700 transition-all shadow-md active:scale-95">
                Sign Record
            </button>
        <?php endif; ?>
        </div>
    </form>
</div>

<!-- TREATMENT PROTOCOL -->
<div
    class="flex-1 bg-white rounded-2xl p-6 sm:p-10 border border-gray-100 shadow-sm <?php echo (!$diagnosis) ? 'opacity-30 grayscale' : ''; ?>">
    <div class="flex items-center gap-3 mb-8">
        <div class="w-1.5 h-6 bg-emerald-500 rounded-full shadow-lg"></div>
        <h3 class="text-xl font-bold text-gray-800">TB Treatment Plan</h3>
    </div>
    <?php if ($diagnosis): ?>
        <form id="treatmentForm" method="POST" class="space-y-6">
            <!-- CRITICAL FIX: Explicitly include the ID of the diagnosis we are treating -->
            <input type="hidden" name="diagnosis_id" value="<?php echo $diagnosis['diagnosis_id']; ?>">
            <!-- Also pass visit_id just in case -->
            <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">

            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-emerald-600 uppercase tracking-widest ml-4">Strategy
                    Description</label>
               <textarea name="description" rows="3" 
    <?php echo $viewOnly ? 'disabled' : 'required'; ?>
                class="w-full bg-gray-50 border-none rounded-xl px-6 sm:px-8 py-6 focus:ring-2 focus:ring-emerald-500 font-medium text-gray-700 shadow-inner <?php echo $viewOnly ? 'opacity-60 cursor-not-allowed' : ''; ?>"
                placeholder="<?php echo $viewOnly ? 'View-only mode - patient discharged' : 'Enter treatment plan...'; ?>"><?php echo $viewOnly ? ($treatment['description'] ?? '') : ''; ?></textarea>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                <select name="tb_phase" <?php echo $viewOnly ? 'disabled' : ''; ?> class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
                    <option value="">TB Treatment Phase</option>
                    <option value="Intensive Phase">Intensive Phase</option>
                    <option value="Continuation Phase">Continuation Phase</option>
                    <option value="MDR-TB Phase">MDR-TB Phase</option>
                </select>
                <input type="text" name="drug_regimen" <?php echo $viewOnly ? 'disabled' : ''; ?>
                    placeholder="Drug regimen (e.g. 2RHZE/4RH)"
                    class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
                <select name="adherence_status" <?php echo $viewOnly ? 'disabled' : ''; ?> class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
                    <option value="">Adherence Status</option>
                    <option value="Good">Good</option>
                    <option value="Fair">Fair</option>
                    <option value="Poor">Poor</option>
                </select>
                <input type="date" name="next_follow_up_date" <?php echo $viewOnly ? 'disabled' : ''; ?>
                    class="px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
            </div>
            <textarea name="follow_up_notes" rows="2" <?php echo $viewOnly ? 'disabled' : ''; ?>
                placeholder="TB follow-up note"
                class="w-full bg-gray-50 border-none rounded-xl px-4 sm:px-6 py-4 font-medium text-sm"></textarea>
            <div class="grid grid-cols-2 gap-4 sm:gap-6">
                <input type="date" name="start_date" required
                    class="treatment-date-input px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full"
                    value="<?php echo date('Y-m-d'); ?>">
                <input type="date" name="end_date"
                    class="treatment-date-input px-4 sm:px-6 py-4 bg-gray-50 border-none rounded-xl font-bold text-sm w-full">
            </div>
            <div class="flex justify-center pt-4 border-t border-gray-50 items-center gap-6 text-center">
    <?php if ($viewOnly): ?>
        <button type="button" disabled
            class="w-full sm:w-auto px-8 sm:px-14 py-4 bg-gray-400 text-white rounded-2xl font-semibold text-[10px] uppercase tracking-widest cursor-not-allowed opacity-50">
            Read Only - Discharged
        </button>
    <?php else: ?>
        <button type="submit" name="save_treatment"
            class="w-full sm:w-auto px-8 sm:px-14 py-4 bg-emerald-600 text-white rounded-2xl font-semibold text-[10px] uppercase tracking-widest hover:bg-emerald-700 transition-all shadow-xl active:scale-95">
            Plan Treatment
        </button>
    <?php endif; ?>
            </div>
        </form>
    <?php else: ?>
        <p class="text-center py-6 text-xs font-semibold uppercase text-gray-300 tracking-wider italic">Pending Clinical
            Diagnosis</p>
    <?php endif; ?>
</div>
               
                   
                </div>

                <!-- COLUMN 4: VITALS & TREATMENT HISTORY -->
                <div class="space-y-6">
                    <!-- CURRENT VITALS -->
                     
                    <div class="bg-teal-600 rounded-2xl p-8 text-white shadow-xl shadow-teal-200 relative overflow-hidden group">
                        <i data-lucide="activity" class="absolute -right-10 -bottom-10 w-48 h-48 text-white/5 rotate-12 transition-all group-hover:rotate-45 duration-700"></i>
<!-- REPLACE FROM HERE -->
<div class="flex justify-between items-start mb-10 relative z-10">
    <div>
        <h4 class="text-xs font-semibold uppercase tracking-wide text-teal-200">Session Vitals</h4>
        <p class="text-[9px] text-teal-300/60 font-bold uppercase">Current Visit</p>
    </div>

    <?php if ($_SESSION['role'] == 'Nurse' || $_SESSION['role'] == 'Doctor'): ?>
        <a href="index.php?page=record-vital&id=<?php echo $id; ?>&vid=<?php echo $visit_id; ?>"
            class="w-10 h-10 bg-white/20 hover:bg-white text-white hover:text-teal-600 rounded-2xl transition-all flex items-center justify-center shadow-lg backdrop-blur-md group/btn"
            title="Record New Vital Signs">
            <i data-lucide="plus" class="w-5 h-5 group-hover/btn:scale-110 transition-transform"></i>
        </a>
    <?php endif; ?>
</div>
<!-- TO HERE (Grid starts below) -->
                        <div class="grid grid-cols-1 gap-y-10 relative z-10 font-semibold">
                            <div><p class="text-[9px] text-teal-200 uppercase mb-1">Temperature</p><p class="text-2xl"><?php echo $vitals['temperature'] ?? '--'; ?>°C</p></div>
                            <div><p class="text-[9px] text-teal-200 uppercase mb-1">Blood Pressure</p><p class="text-2xl"><?php echo $vitals['blood_pressure'] ?? '--/--'; ?></p></div>
                            <div><p class="text-[9px] text-teal-200 uppercase mb-1">Heart Rate</p><p class="text-2xl"><?php echo $vitals['pulse'] ?? '--'; ?> bpm</p></div>
                        </div>
                    </div>

                    <!-- NEW: TREATMENT HISTORY -->
                <div class="bg-white rounded-2xl p-8 border border-gray-100 shadow-sm h-fit">
                    <h3 class="text-[10px] font-semibold text-emerald-600 uppercase tracking-widest mb-6 italic border-b border-emerald-50 pb-2">Treatment Archive</h3>
                    <div class="space-y-6 max-h-[400px] overflow-y-auto pr-2 custom-scrollbar">
                        <?php if (empty($treatmentHistory)): ?>
                                <p class="text-[9px] text-gray-300 font-bold uppercase text-center italic py-10">No treatments on file</p>
                        <?php else: ?>
                                <?php foreach ($treatmentHistory as $t):
                                    $createdTime = strtotime($t['created_at'] ?? $t['start_date']);
                                    $diffMinutes = round((time() - $createdTime) / 60);
                                    $canEdit = ($diffMinutes <= 30 && $role == 'Doctor');
                                    
                                    // Get corresponding diagnosis details from the array (assuming your SQL join fetches it)
                                    // If your SQL doesn't fetch it, we would need to look it up. 
                                    // For now, assuming the array has 'diagnosis_details' if joined properly.
                                    // If not, this button will just show "Details not available".
                                    $relatedDiagnosis = $t['diagnosis_details'] ?? "Diagnosis details not linked.";
                                    ?>
                                    <div class="relative pl-5 border-l-2 border-emerald-100 group hover:border-emerald-300 transition-colors">
                                        <div class="absolute -left-[5px] top-0 w-2 h-2 bg-emerald-500 rounded-full border-2 border-white shadow-sm"></div>
                                        <div class="flex justify-between items-start">
                                            <p class="text-[9px] font-semibold text-emerald-500 uppercase leading-none">
                                                <?php echo date("M d, Y", strtotime($t['start_date'])); ?>
                                            </p>
                                            <div class="flex gap-2">
                                                <button onclick="openDiagnosisViewModal('<?php echo htmlspecialchars($t['diagnosis_details'] ?? 'No diagnosis details available', ENT_QUOTES); ?>', '<?php echo date("M d, Y", strtotime($t['diagnosis_date'] ?? 'now')); ?>')" class="text-teal-400 hover:text-teal-600
                                                    transition-colors p-1 rounded-full hover:bg-teal-50" title="View Associated Diagnosis">
                                                    <i data-lucide="eye" class="w-4 h-4"></i>
                                                </button>

                                                <?php if ($canEdit): ?>
                                                        <div class="relative">
                                                            <button onclick="toggleMenu('tr-<?php echo $t['plan_id']; ?>')" class="text-gray-400 hover:text-emerald-600 transition-colors p-1 rounded-full hover:bg-gray-100">
                                                                <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                                            </button>
                                                            <div id="menu-tr-<?php echo $t['plan_id']; ?>" class="hidden absolute right-0 mt-2 w-32 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">
                                                                <button onclick="openEditTreatmentModal('<?php echo $t['plan_id']; ?>', '<?php echo htmlspecialchars($t['description'], ENT_QUOTES); ?>', '<?php echo $t['start_date']; ?>', '<?php echo $t['end_date']; ?>')" class="w-full text-left px-4 py-2 text-[10px] font-bold text-gray-700 hover:bg-emerald-50 hover:text-emerald-600 flex items-center gap-2">
                                                                    <i data-lucide="edit-2" class="w-3 h-3"></i> Edit
                                                                </button>
                                                                <form method="POST" onsubmit="return confirm('Delete this treatment?');" class="w-full">
                                                                    <input type="hidden" name="treatment_id" value="<?php echo $t['plan_id']; ?>">
                                                                    <input type="hidden" name="delete_treatment_confirm" value="1">
                                                                    <button type="submit" class="w-full text-left px-4 py-2 text-[10px] font-bold text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                                        <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                                                    </button>
                                                                </form>
                                                            </div>
                                                        </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <p class="text-[11px] font-semibold text-gray-700 mt-2 leading-relaxed"><?php echo $t['description']; ?></p>
                                        <?php if ($t['end_date']): ?>
                                                <p class="text-[8px] text-gray-400 mt-1 uppercase font-bold">Until: <?php echo $t['end_date']; ?></p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                </div>
            </div>
    <?php endif; ?>
<!-- PHYSICIAN ASSESSMENT & TREATMENT PLAN - SIDE BY SIDE ON LARGER SCREENS -->

</div>
<!-- MODAL - DIAGNOSIS VIEW MODAL -->
<div id="diagnosisViewModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeDiagnosisViewModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-white rounded-xl shadow-xl p-8 animate-in zoom-in-95 duration-200">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800">Associated Diagnosis</h3>
            <button onclick="closeDiagnosisViewModal()" class="text-gray-400 hover:text-red-500 transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        <div class="space-y-6">
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-2">Diagnosis Date</label>
                <p id="view_diagnosis_date" class="text-sm font-bold text-gray-700 bg-gray-50 rounded-2xl px-6 py-4 border border-gray-200"></p>
            </div>
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-2">Clinical Findings</label>
                <p id="view_diagnosis_details" class="text-sm font-medium text-gray-700 bg-gray-50 rounded-2xl px-6 py-4 border border-gray-200 leading-relaxed min-h-[120px]"></p>
            </div>
            <div class="flex justify-end pt-2">
                <button onclick="closeDiagnosisViewModal()" class="px-8 py-3 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase hover:bg-teal-700 shadow-lg">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- EDIT DIAGNOSIS MODAL -->
<div id="editModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeEditDiagnosisModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-white rounded-xl shadow-xl p-8 animate-in zoom-in-95 duration-200">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800">Edit Diagnosis</h3>
            <button onclick="closeEditDiagnosisModal()" class="text-gray-400 hover:text-red-500 transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="diagnosis_id" id="edit_diagnosis_id">
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-2">Updated Findings</label>
                <textarea name="diagnosis_details" id="edit_diagnosis_details" rows="6" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-medium text-gray-700"></textarea>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeEditDiagnosisModal()" class="px-6 py-3 rounded-xl font-semibold text-[10px] uppercase text-gray-500 hover:bg-gray-100">Cancel</button>
                <button type="submit" name="update_diagnosis" class="px-8 py-3 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase hover:bg-teal-700 shadow-lg">Update Record</button>
            </div>
        </form>
    </div>
</div>

<!-- EDIT TREATMENT MODAL -->
<div id="editTreatmentModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeEditTreatmentModal()"></div>
    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-lg bg-white rounded-xl shadow-xl p-8 animate-in zoom-in-95 duration-200">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-bold text-gray-800">Edit Treatment</h3>
            <button onclick="closeEditTreatmentModal()" class="text-gray-400 hover:text-red-500 transition-colors"><i data-lucide="x" class="w-6 h-6"></i></button>
        </div>
        <form method="POST" class="space-y-6">
            <input type="hidden" name="treatment_id" id="edit_treatment_id">
            <div class="space-y-2">
                <label class="text-[10px] font-semibold text-emerald-600 uppercase tracking-widest ml-2">Strategy Description</label>
                <textarea name="description" id="edit_treatment_description" rows="4" required class="w-full bg-gray-50 border border-gray-200 rounded-2xl px-6 py-4 focus:ring-2 focus:ring-emerald-500 font-medium text-gray-700"></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase ml-2">Start Date</label>
                    <input type="date" name="start_date" id="edit_start_date" required class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                </div>
                <div>
                    <label class="text-[10px] font-bold text-gray-500 uppercase ml-2">End Date</label>
                    <input type="date" name="end_date" id="edit_end_date" class="w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm">
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-2">
                <button type="button" onclick="closeEditTreatmentModal()" class="px-6 py-3 rounded-xl font-semibold text-[10px] uppercase text-gray-500 hover:bg-gray-100">Cancel</button>
                <button type="submit" name="update_treatment" class="px-8 py-3 bg-emerald-600 text-white rounded-xl font-semibold text-[10px] uppercase hover:bg-emerald-700 shadow-lg">Update Plan</button>
            </div>
        </form>
    </div>
</div>

<div id="actionModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-[2px] transition-all duration-300">
    <div id="actionCard" class="bg-white rounded-2xl w-full max-w-[360px] p-8 shadow-xl border border-gray-100 transform scale-95 transition-all duration-300">
        
        <!-- Icon Section: Blue themed for positive action -->
        <div class="w-16 h-16 bg-teal-50 text-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-sm">
            <i id="actionIcon" data-lucide="home" class="w-8 h-8"></i>
        </div>

        <!-- Text Section -->
        <div class="text-center mb-8">
            <h3 id="actionTitle" class="text-xl font-bold text-gray-800 tracking-tight mb-2">Confirm Action</h3>
            <p id="actionDesc" class="text-gray-500 text-xs font-medium leading-relaxed italic">
                Are you sure you want to proceed with this clinical update?
            </p>
        </div>

        <!-- Action Buttons -->
        <div class="flex gap-3">
            <button onclick="closeActionModal()"
                class="flex-1 py-3 bg-gray-50 text-gray-400 rounded-xl font-semibold uppercase text-[10px] tracking-widest hover:bg-gray-100 hover:text-gray-600 transition-all">
                Cancel
            </button>
            <button id="actionConfirmBtn"
                class="flex-1 py-3 bg-teal-600 text-white rounded-xl font-semibold uppercase text-[10px] tracking-widest text-center shadow-md hover:bg-teal-700 hover:shadow-lg hover:scale-[1.02] transition-all">
                 Confirm
            </button>
        </div>
    </div>
</div>

<script>
     let activeForm = null;

    /**
     * @param {string} formId - The ID of the form to submit
     * @param {string} title - Modal Title
     * @param {string} desc - Modal Description
     * @param {string} icon - Lucide icon name
     */
    function openActionModal(formId, title, desc, icon = 'home') {
        activeForm = document.getElementById(formId);
        
        document.getElementById('actionTitle').innerText = title;
        document.getElementById('actionDesc').innerText = desc;
        
        // Update icon
        const iconElement = document.getElementById('actionIcon');
        iconElement.setAttribute('data-lucide', icon);
        lucide.createIcons();

        const modal = document.getElementById('actionModal');
        const card = document.getElementById('actionCard');

        modal.classList.remove('hidden');
        requestAnimationFrame(() => card.classList.remove('scale-95'));
    }

    function closeActionModal() {
        const modal = document.getElementById('actionModal');
        const card = document.getElementById('actionCard');
        card.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
        activeForm = null;
    }

   

    function closeNotification() {
        const alert = document.getElementById('notification-alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            alert.style.marginBottom = '-60px';
            setTimeout(() => { alert.remove(); }, 500);
        }
    }
    setTimeout(closeNotification, 5000);
    if (typeof lucide !== 'undefined') { lucide.createIcons(); }

    // --- FORM RESET LOGIC ---
    // This script runs on page load. If there is a success message, it clears the relevant forms.
    document.addEventListener("DOMContentLoaded", function() {
        const alertBox = document.getElementById('notification-alert');
        if (alertBox) {
            const alertText = alertBox.innerText.toLowerCase();
            
            // Define patterns that indicate a successful save/update
            const successPatterns = ['signed', 'committed', 'updated', 'deleted', 'transmitted'];

            // Check if alert text contains any success pattern
            const isSuccess = successPatterns.some(pattern => alertText.includes(pattern));

            if (isSuccess) {
                // 1. Reset Diagnosis Form (Already empty in HTML, just in case)
                const dxForm = document.getElementById('diagnosisForm');
                if (dxForm) dxForm.reset();

                // 2. Reset Treatment Form (Keep Start Date as today, clear others)
                const trForm = document.getElementById('treatmentForm');
                if (trForm) {
                    const desc = trForm.querySelector('textarea[name="description"]');
                    const start = trForm.querySelector('input[name="start_date"]');
                    const end = trForm.querySelector('input[name="end_date"]');
                    
                    if(desc) desc.value = '';
                    if(start) start.value = new Date().toISOString().split('T')[0];
                    if(end) end.value = '';
                }

                // 3. Reset Triage Form
                const vitalsInputs = document.querySelectorAll('.triage-input');
                vitalsInputs.forEach(input => input.value = '');
            }
        }
    });

    // Toggle Dropdown Menus
    function toggleMenu(id) {
        document.querySelectorAll('[id^="menu-"]').forEach(el => {
            if(el.id !== 'menu-' + id) el.classList.add('hidden');
        });
        const menu = document.getElementById('menu-' + id);
        menu.classList.toggle('hidden');
    }
    window.addEventListener('click', function(e) {
        if (!e.target.closest('button')) {
            document.querySelectorAll('[id^="menu-"]').forEach(el => el.classList.add('hidden'));
        }
    });

    // Diagnosis Modal
    const dxModal = document.getElementById('editModal');
    const dxIdInput = document.getElementById('edit_diagnosis_id');
    const dxDetailsInput = document.getElementById('edit_diagnosis_details');

    function openEditDiagnosisModal(id, details) {
        dxIdInput.value = id;
        dxDetailsInput.value = details;
        dxModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    function closeEditDiagnosisModal() { dxModal.classList.add('hidden'); }

    // Treatment Modal
    const trModal = document.getElementById('editTreatmentModal');
    const trIdInput = document.getElementById('edit_treatment_id');
    const trDescInput = document.getElementById('edit_treatment_description');
    const trStartInput = document.getElementById('edit_start_date');
    const trEndInput = document.getElementById('edit_end_date');

    function openEditTreatmentModal(id, desc, start, end) {
        trIdInput.value = id;
        trDescInput.value = desc;
        trStartInput.value = start;
        trEndInput.value = end;
        trModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }
    function closeEditTreatmentModal() { trModal.classList.add('hidden'); }
    // Diagnosis View Modal
const dxViewModal = document.getElementById('diagnosisViewModal');
const dxViewDate = document.getElementById('view_diagnosis_date');
const dxViewDetails = document.getElementById('view_diagnosis_details');

function openDiagnosisViewModal(details, date) {
    dxViewDetails.textContent = details || 'No diagnosis details available';
    dxViewDate.textContent = date || 'Date not available';
    dxViewModal.classList.remove('hidden');
    if (typeof lucide !== 'undefined') lucide.createIcons();
}

function closeDiagnosisViewModal() { 
    dxViewModal.classList.add('hidden'); 
}

 // Execution Logic
    document.getElementById('actionConfirmBtn').addEventListener('click', function() {
        if(activeForm) activeForm.submit();
    });
</script>