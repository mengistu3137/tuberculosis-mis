<?php
/**
 * MASTER TB REGISTRY (Registration Desk)
 * Responsibilities:
 * 1. Patient Registration
 * 2. Patient Check-in (Encounter Initiation)
 * 3. Patient Edit/Delete (within 30 minutes)
 */

// Handle Patient Update
$update_msg = "";
$update_msgType = "blue";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_patient_btn'])) {
    if ($patientObj->updatePatient($_POST)) {
        $reg_msg = "Patient record updated successfully.";
        $reg_msgType = "emerald";

        // Auto-close modal after success
        echo "<script>
            setTimeout(function() {
                closeEditPatientModal();
            }, 2000);
        </script>";
    } else {
        $reg_msg = "Failed to update patient record.";
        $reg_msgType = "red";
    }
}

// Handle Patient Delete
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_patient_confirm'])) {
    if ($patientObj->deletePatient($_POST['patient_id'])) {
        $reg_msg = "Patient record deleted successfully.";
        $reg_msgType = "emerald";
    } else {
        $reg_msg = "Cannot delete patient with existing visit history.";
        $reg_msgType = "red";
    }
}

// Handle Patient Registration
$reg_msg = $reg_msg ?? "";
$reg_msgType = $reg_msgType ?? "blue";
$form_data = [
    'full_name' => '',
    'age' => '',
    'gender' => '',
    'contact_details' => '',
    'address' => ''
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_btn'])) {
    // 1. Capture and Sanitize
    $full_name = trim($_POST['full_name']);
    $age_raw = $_POST['age'];
    $age = filter_var($age_raw, FILTER_VALIDATE_INT);
    $gender = $_POST['gender'];
    $phone = trim($_POST['contact_details']);
    $address = trim($_POST['address']);

    // Store submitted data to repopulate form
    $form_data = [
        'full_name' => htmlspecialchars($full_name, ENT_QUOTES, 'UTF-8'),
        'age' => htmlspecialchars($age_raw, ENT_QUOTES, 'UTF-8'),
        'gender' => htmlspecialchars($gender, ENT_QUOTES, 'UTF-8'),
        'contact_details' => htmlspecialchars($phone, ENT_QUOTES, 'UTF-8'),
        'address' => htmlspecialchars($address, ENT_QUOTES, 'UTF-8')
    ];

    // 2. STRICT VALIDATION ENGINE
    $errors = [];

    if (empty($full_name)) {
        $errors[] = "Full name is required.";
    }
    if ($age_raw === "") {
        $errors[] = "Age is required.";
    }
    if (empty($gender)) {
        $errors[] = "Gender is required.";
    }
    if (empty($phone)) {
        $errors[] = "Phone number is required.";
    }
    if (empty($address)) {
        $errors[] = "Address is required.";
    }

    if (!empty($errors)) {
        $reg_msg = implode(" ", $errors);
        $reg_msgType = "orange";
    }
    // Phone Validation: Starts with 09 or 07, exactly 10 digits
    elseif (!preg_match('/^(09|07)\d{8}$/', $phone)) {
        $reg_msg = "Validation Error: Phone must be 10 digits starting with 09 or 07.";
        $reg_msgType = "red";
    }
    // Age Validation: Logical Human Boundary (0 to 120)
    elseif ($age === false || $age < 0 || $age > 120) {
        $reg_msg = "Illogical Data: Age '$age_raw' is invalid. Must be between 0 and 120.";
        $reg_msgType = "red";
    } else {
        // 3. EXECUTE REGISTRATION
        $result = $patientObj->register($_POST);

        if ($result === "duplicate") {
            $reg_msg = "Registry Conflict: Patient with these details already exists.";
            $reg_msgType = "orange";
        } elseif ($result) {
            $reg_msg = "Enrollment Successful. Medical Record Number: $result";
            $reg_msgType = "emerald";

            // Clear form data on success
            $form_data = [
                'full_name' => '',
                'age' => '',
                'gender' => '',
                'contact_details' => '',
                'address' => ''
            ];

            // Auto-close modal after 2 seconds on success
            echo "<script>
                setTimeout(function() {
                    closeRegistrationModal();
                }, 2000);
            </script>";
        } else {
            $reg_msg = "System Error: Failed to commit record to infrastructure.";
            $reg_msgType = "red";
        }
    }
}

$limit = 8;
$current_page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
$offset = ($current_page - 1) * $limit;
$searchTerm = $_GET['q'] ?? "";

// Fetch Master Registry
$total_records = $patientObj->countSearch($searchTerm);
$total_pages = ceil($total_records / $limit);
$stmt = $patientObj->searchPaginated($searchTerm, $limit, $offset);

// Handle POST Check-in (Encounter Initiation)
$msg = "";
$msgType = "blue";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['perform_checkin'])) {
    $visit_id = $visitObj->create($_POST['patient_id'], $_POST['visit_type'], $_POST['clinical_notes']);

    if ($visit_id) {
        // ONLY assign the Doctor at this stage
        $assignObj->autoAssignDoctor($visit_id);

        $msg = "TB intake successful. Client assigned to TB clinician.";
        $msgType = "emerald";

        echo "<script>
            setTimeout(function() {
                window.location.href='index.php?page=visit&status=success&vid=$visit_id';
            }, 1000);
        </script>";
        exit;
    }
}
?>

<div class="space-y-6">

    <!-- Header with Notifications -->
    <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-black text-gray-800 tracking-tight uppercase text-emerald-700">TB Patient Registry</h1>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                <?php echo $total_records; ?> Total Records
            </p>

            <!-- Combined Notifications Area -->
            <?php if ($msg): ?>
                <div
                    class="mt-2 flex items-center gap-2 text-<?php echo $msgType; ?>-600 bg-<?php echo $msgType; ?>-50 px-3 py-1.5 rounded-xl w-fit border border-<?php echo $msgType; ?>-100 shadow-sm animate-in fade-in zoom-in">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest"><?php echo $msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- Registration messages -->
            <?php if ($reg_msg): ?>
                <div
                    class="mt-2 flex items-center gap-2 text-<?php echo $reg_msgType; ?>-600 bg-<?php echo $reg_msgType; ?>-50 px-3 py-1.5 rounded-xl w-fit border border-<?php echo $reg_msgType; ?>-100 shadow-sm animate-in fade-in zoom-in">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span class="text-[10px] font-black uppercase tracking-widest"><?php echo $reg_msg; ?></span>
                </div>
            <?php endif; ?>
        </div>

        <div class="flex items-center gap-3">
            <button onclick="openRegistrationModal()"
                class="px-5 py-3 bg-emerald-700 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-emerald-100 hover:bg-emerald-800 transition-all flex items-center gap-2">
                <i data-lucide="user-plus" class="w-4 h-4"></i> Register TB Patient
            </button>
            <a href="index.php?page=visit"
                class="group relative px-4 py-2 bg-blue-600 text-white rounded-2xl font-black text-[11px] first-letter:uppercase tracking-wider hover:bg-blue-700 transition-all duration-300 flex items-center gap-3 shadow-xl shadow-blue-200 hover:shadow-blue-300 hover:scale-105 active:scale-95 overflow-hidden">
                <span class="absolute inset-0 bg-white/10 group-hover:animate-pulse rounded-2xl"></span>
                <span class="relative flex items-center justify-center">
                    <i data-lucide="list"
                        class="w-4 h-4 text-white/90 group-hover:scale-110 transition-transform duration-300"></i>
                    <span class="absolute -top-1 -right-1 w-2 h-2">
                        <span class="absolute inset-0 rounded-full bg-emerald-400 animate-ping"></span>
                        <span class="absolute inset-0 rounded-full bg-emerald-400"></span>
                    </span>
                </span>
                <span class="relative flex items-center gap-2">
                    <span class="relative">
                        TB CASE ENCOUNTERS
                        <span
                            class="absolute -bottom-1 left-0 w-0 group-hover:w-full h-0.5 bg-white/60 transition-all duration-300"></span>
                    </span>
                </span>
                <span
                    class="relative -mr-2 ml-2 px-2 py-1 bg-white/20 rounded-lg text-[9px] font-black backdrop-blur-sm border border-white/20">
                    <?php echo $visitObj->countAll() ?? 0; ?>
                </span>
            </a>
        </div>
    </div>

    <!-- TB Patient Registry Table -->
    <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden transition-all">
        <table class="w-full text-left">
            <thead
                class="bg-gray-50/50 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-100">
                <tr>
                    <th class="px-8 py-6">Patient Identity</th>
                    <th class="px-8 py-6 text-center">Bio Stats</th>
                    <th class="px-8 py-6">Contact Info</th>
                    <th class="px-8 py-6 text-right">Clerk Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php while ($p = $stmt->fetch(PDO::FETCH_ASSOC)):
                    $initials = strtoupper(substr($p['full_name'], 0, 1));

                    // Calculate time difference for edit/delete permission (30 minutes)
                    $createdTime = strtotime($p['created_at'] ?? date('Y-m-d H:i:s'));
                    $diffMinutes = round((time() - $createdTime) / 60);
                    $canEdit = ($diffMinutes <= 30 && $_SESSION['role'] == 'Clerk');
                    ?>
                    <tr class="hover:bg-blue-50/20 transition-colors group">
                        <td class="px-8 py-6">
                            <div class="flex items-center gap-4">
                                <div
                                    class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black shadow-md">
                                    <?php echo $initials; ?>
                                </div>
                                <div>
                                    <p class="font-black text-gray-800 text-sm">
                                        <?php echo $p['full_name']; ?>
                                    </p>
                                    <p class="text-[9px] font-black text-blue-400 uppercase tracking-tighter mt-1">
                                        <?php echo $p['medical_record_number']; ?>
                                    </p>
                                    <?php if ($canEdit): ?>
                                        <span class="text-[7px] text-emerald-500 font-black uppercase mt-1 block">
                                            <i data-lucide="clock" class="w-2 h-2 inline"></i> <?php echo $diffMinutes; ?> min
                                            old
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td class="px-8 py-6 text-center">
                            <span
                                class="px-3 py-1 bg-white border border-gray-100 rounded-lg text-[10px] font-black text-gray-500 uppercase">
                                <?php echo $p['age']; ?>Y • <?php echo $p['gender']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-6 text-xs font-bold text-gray-500 italic">
                            <?php echo $p['contact_details']; ?>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <!-- CHECK-IN BUTTON -->
                                <button
                                    onclick="openCheckinModal('<?php echo $p['patient_id']; ?>', '<?php echo addslashes($p['full_name']); ?>')"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-700 transition-all shadow-md">
                                    <i data-lucide="log-in" class="w-3 h-3"></i> Check-in
                                </button>

                                <!-- KEBAB MENU (Only visible within 30 minutes) -->
                                <?php if ($canEdit): ?>
                                    <div class="relative">
                                        <button onclick="togglePatientMenu('patient-<?php echo $p['patient_id']; ?>')"
                                            class="p-2.5 bg-gray-50 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all">
                                            <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                        </button>

                                        <div id="menu-patient-<?php echo $p['patient_id']; ?>"
                                            class="hidden absolute right-0 mt-2 w-36 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">

                                            <!-- Edit Button -->
                                            <button onclick="openEditPatientModal('<?php echo $p['patient_id']; ?>', 
                                            '<?php echo addslashes($p['full_name']); ?>',
                                            '<?php echo $p['age']; ?>',
                                            '<?php echo $p['gender']; ?>',
                                            '<?php echo $p['contact_details']; ?>',
                                            '<?php echo addslashes($p['address']); ?>')"
                                                class="w-full text-left px-4 py-3 text-[10px] font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2">
                                                <i data-lucide="edit-2" class="w-3 h-3"></i> Edit
                                            </button>

                                            <!-- Delete Button -->
                                            <form method="POST"
                                                onsubmit="return confirm('Are you sure you want to delete this patient record? This action cannot be undone.');">
                                                <input type="hidden" name="patient_id" value="<?php echo $p['patient_id']; ?>">
                                                <input type="hidden" name="delete_patient_confirm" value="1">
                                                <button type="submit"
                                                    class="w-full text-left px-4 py-3 text-[10px] font-bold text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                    <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <!-- PAGINATION -->
        <?php if ($total_pages > 1): ?>
            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest italic">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                </span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="index.php?page=registration&p=<?php echo $i; ?>&q=<?php echo $searchTerm; ?>"
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-black text-[10px] transition-all <?php echo ($i == $current_page) ? 'bg-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white text-gray-400 border border-gray-100 hover:border-blue-200'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- EDIT PATIENT MODAL -->
    <div id="editPatientModal" class="fixed inset-0 z-[100] hidden">
        <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeEditPatientModal()">
        </div>
        <div
            class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-2xl bg-white rounded-[3rem] shadow-2xl p-8 animate-in zoom-in-95 duration-200">

            <!-- Modal Header -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-100">
                        <i data-lucide="user-edit" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight">Edit Patient</h2>
                        <p class="text-[9px] text-gray-400 font-bold tracking-[0.2em] mt-1">Update Patient Information
                        </p>
                    </div>
                </div>
                <button onclick="closeEditPatientModal()"
                    class="text-gray-400 hover:text-red-500 transition-colors p-2 hover:bg-gray-100 rounded-xl">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Edit Form -->
            <form method="POST" class="space-y-6">
                <input type="hidden" name="patient_id" id="edit_patient_id">
                <input type="hidden" name="update_patient" value="1">

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                    <!-- Name - Full Width -->
                    <div class="md:col-span-2 space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Full
                            Patient
                            Name</label>
                        <input type="text" name="full_name" id="edit_full_name" required
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                    </div>

                    <!-- Age -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Age
                            (0-120)</label>
                        <input type="number" name="age" id="edit_age" required min="0" max="120"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                    </div>

                    <!-- Gender -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Biological
                            Sex</label>
                        <select name="gender" id="edit_gender" required
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-xs uppercase tracking-widest cursor-pointer shadow-inner">
                            <option value="">Select</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                        </select>
                    </div>

                    <!-- Phone -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Contact
                            Phone</label>
                        <input type="text" name="contact_details" id="edit_contact" required maxlength="10"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                    </div>

                    <!-- Address -->
                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Residential
                            Address</label>
                        <input type="text" name="address" id="edit_address" required
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" onclick="closeEditPatientModal()"
                        class="px-6 py-3 text-gray-400 rounded-xl font-bold text-[9px] uppercase tracking-widest hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" name="update_patient_btn"
                        class="px-8 py-3 bg-blue-600 text-white rounded-xl font-black text-[9px] uppercase tracking-[0.2em] shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95">
                        Update Patient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- CHECK-IN MODAL -->
    <div id="checkinModal"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md transition-all duration-300">
        <div id="checkinCard"
            class="bg-white rounded-[3rem] w-full max-w-md p-8 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300">

            <!-- Modal Header -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black shadow-lg shadow-blue-100">
                        <i data-lucide="log-in" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-black text-gray-800 uppercase tracking-tight">Patient Check-in</h2>
                        <p class="text-[8px] text-gray-400 font-bold tracking-widest mt-1">Initiate New Encounter</p>
                    </div>
                </div>
                <button onclick="closeCheckinModal()"
                    class="text-gray-400 hover:text-red-500 transition-colors p-2 hover:bg-gray-100 rounded-xl">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <h3 id="checkinPatientName" class="text-xl font-black text-gray-800 mb-6 pb-4 border-b border-gray-100">--
            </h3>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="patient_id" id="checkinID">
                <input type="hidden" name="perform_checkin" value="1">

                <div class="space-y-2">
                    <label class="text-[9px] font-black text-blue-600 uppercase tracking-widest ml-2">Visit
                        Classification</label>
                    <select name="visit_type" required
                        class="w-full px-5 py-4 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-black text-xs uppercase tracking-widest shadow-inner cursor-pointer">
                        <option value="Outpatient">🏥 Outpatient Visit</option>
                        <option value="Emergency">🚨 Emergency Triage</option>
                    </select>
                    <p class="text-[8px] text-gray-400 mt-1 ml-2">Note: Inpatient admissions require Doctor
                        authorization</p>
                </div>

                <div class="space-y-2">
                    <label class="text-[9px] font-black text-blue-600 uppercase tracking-widest ml-2">Reason for
                        Visit</label>
                    <textarea name="clinical_notes" rows="3" required
                        class="w-full bg-gray-50 border-none rounded-xl px-5 py-4 text-sm font-medium shadow-inner focus:ring-2 focus:ring-blue-500"
                        placeholder="E.g. Routine checkup, fever, follow-up..."></textarea>
                </div>

                <div class="flex justify-end gap-3 pt-4">
                    <button type="button" onclick="closeCheckinModal()"
                        class="px-5 py-3 text-gray-400 rounded-xl font-bold text-[9px] uppercase hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit"
                        class="px-6 py-3 bg-blue-600 text-white rounded-xl font-black text-[9px] uppercase tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95 flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4"></i> Confirm Check-in
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- REGISTRATION MODAL -->
    <div id="registrationModal"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md transition-all duration-300 overflow-y-auto">
        <div id="registrationCard"
            class="bg-white rounded-[3rem] w-full max-w-2xl p-8 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300 my-8">

            <!-- Modal Header -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-100">
                        <i data-lucide="user-plus" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h2 class="text-xl font-black text-gray-800 tracking-tight">Patient Enrollment</h2>
                        <p class="text-[9px] text-gray-400 font-bold tracking-[0.2em] mt-1">Mattu Karl Specialized
                            Hospital</p>
                    </div>
                </div>
                <button onclick="closeRegistrationModal()"
                    class="text-gray-400 hover:text-red-500 transition-colors p-2 hover:bg-gray-100 rounded-xl">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <!-- Registration Form -->
            <form method="POST" class="space-y-6" novalidate>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-5">
                    <div class="md:col-span-2 space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Full
                            Patient Name</label>
                        <input type="text" name="full_name" required placeholder="Full Name"
                            value="<?php echo $form_data['full_name']; ?>"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                    </div>

                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Age
                            (0-120)</label>
                        <input type="number" name="age" id="ageInput" required placeholder="Years"
                            value="<?php echo $form_data['age']; ?>"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                    </div>

                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Biological
                            Sex</label>
                        <select name="gender" required
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-xs uppercase tracking-widest cursor-pointer shadow-inner">
                            <option value="">Select</option>
                            <option value="Male" <?php echo ($form_data['gender'] == 'Male') ? 'selected' : ''; ?>>Male
                            </option>
                            <option value="Female" <?php echo ($form_data['gender'] == 'Female') ? 'selected' : ''; ?>>
                                Female</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Contact
                            Phone</label>
                        <div class="relative">
                            <input type="text" name="contact_details" id="phoneInput" required placeholder="0912345678"
                                maxlength="10" value="<?php echo $form_data['contact_details']; ?>"
                                class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                            <span id="phoneCount"
                                class="absolute right-4 top-1/2 -translate-y-1/2 text-[8px] font-black text-gray-300"><?php echo strlen($form_data['contact_details']); ?>/10</span>
                        </div>
                    </div>

                    <div class="space-y-1">
                        <label class="text-[9px] font-black text-blue-600/60 uppercase tracking-widest ml-4">Residential
                            Address</label>
                        <input type="text" name="address" required placeholder="Sub-city, Town"
                            value="<?php echo $form_data['address']; ?>"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-gray-700 text-sm shadow-inner transition-all">
                    </div>
                </div>

                <div class="pt-4 border-t border-gray-100 flex justify-end gap-3">
                    <button type="button" onclick="closeRegistrationModal()"
                        class="px-6 py-3 text-gray-400 rounded-xl font-bold text-[9px] uppercase tracking-widest hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="reset"
                        class="px-6 py-3 text-gray-400 rounded-xl font-bold text-[9px] uppercase tracking-widest hover:bg-gray-50 transition-all">
                        Reset
                    </button>
                    <button type="submit" name="register_btn"
                        class="px-8 py-3 bg-blue-600 text-white rounded-xl font-black text-[9px] uppercase tracking-[0.2em] shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all active:scale-95">
                        Register Patient
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Modal Functions
        function openRegistrationModal() {
            const modal = document.getElementById('registrationModal');
            const card = document.getElementById('registrationCard');
            modal.classList.remove('hidden');
            setTimeout(() => card.classList.remove('scale-95'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeRegistrationModal() {
            const modal = document.getElementById('registrationModal');
            const card = document.getElementById('registrationCard');
            card.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 200);
        }

        function openCheckinModal(id, name) {
            document.getElementById('checkinID').value = id;
            document.getElementById('checkinPatientName').innerText = name;

            const modal = document.getElementById('checkinModal');
            const card = document.getElementById('checkinCard');
            modal.classList.remove('hidden');
            setTimeout(() => card.classList.remove('scale-95'), 10);
            document.body.style.overflow = 'hidden';
        }

        function closeCheckinModal() {
            const modal = document.getElementById('checkinModal');
            const card = document.getElementById('checkinCard');
            card.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 200);
        }

        // Close modals when clicking outside
        document.getElementById('checkinModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeCheckinModal();
        });

        document.getElementById('registrationModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeRegistrationModal();
        });

        // Age Input Validation
        const ageInput = document.getElementById('ageInput');
        if (ageInput) {
            ageInput.addEventListener('input', function () {
                this.value = this.value.replace(/[^0-9]/g, '');
                if (this.value > 120) this.value = 120;
            });
        }

        // Phone Input Validation
        const phoneInput = document.getElementById('phoneInput');
        const phoneCount = document.getElementById('phoneCount');

        if (phoneInput && phoneCount) {
            phoneInput.addEventListener('input', function () {
                this.value = this.value.replace(/\D/g, '');
                phoneCount.innerText = `${this.value.length}/10`;
                phoneCount.classList.toggle('text-blue-500', this.value.length === 10);
            });
        }

        // Initialize icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }

        // Toggle Patient Menu
        function togglePatientMenu(id) {
            // Close all other menus
            document.querySelectorAll('[id^="menu-patient-"]').forEach(el => {
                if (el.id !== 'menu-' + id) el.classList.add('hidden');
            });
            const menu = document.getElementById('menu-' + id);
            menu.classList.toggle('hidden');
        }

        // Edit Patient Modal Functions
        const editModal = document.getElementById('editPatientModal');
        const editId = document.getElementById('edit_patient_id');
        const editName = document.getElementById('edit_full_name');
        const editAge = document.getElementById('edit_age');
        const editGender = document.getElementById('edit_gender');
        const editContact = document.getElementById('edit_contact');
        const editAddress = document.getElementById('edit_address');

        function openEditPatientModal(id, name, age, gender, contact, address) {
            editId.value = id;
            editName.value = name;
            editAge.value = age;
            editGender.value = gender;
            editContact.value = contact;
            editAddress.value = address;

            editModal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
            if (typeof lucide !== 'undefined') lucide.createIcons();
        }

        function closeEditPatientModal() {
            editModal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Close menu when clicking outside
        window.addEventListener('click', function (e) {
            if (!e.target.closest('button')) {
                document.querySelectorAll('[id^="menu-patient-"]').forEach(el => el.classList.add('hidden'));
            }
        });

        // Close edit modal when clicking outside
        document.getElementById('editPatientModal')?.addEventListener('click', function (e) {
            if (e.target === this) closeEditPatientModal();
        });
    </script>