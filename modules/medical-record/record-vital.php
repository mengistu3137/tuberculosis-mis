<?php
// Get patient ID from URL
$id = $_GET['id'] ?? "";
$visit_id = $_GET['vid'] ?? "";

// Fetch patient data
$patient = $patientObj->getById($id);
$role = $_SESSION['role'];

if (!$patient) {
    echo "<div class='p-10 text-center font-black text-gray-400 uppercase tracking-widest'>Patient Record Not Found</div>";
    exit;
}

// Get active visit if visit_id not provided
if (empty($visit_id)) {
    $vQuery = $db->prepare("SELECT * FROM medical_visits WHERE patient_id = ? ORDER BY created_at DESC LIMIT 1");
    $vQuery->execute([$id]);
    $activeVisit = $vQuery->fetch(PDO::FETCH_ASSOC);
    $visit_id = $activeVisit['visit_id'] ?? "NO_ACTIVE_VISIT";
}

// Handle POST requests
$msg = "";
$msgType = "blue";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Save new vital signs
    if (isset($_POST['save_triage'])) {
        if ($clinicalObj->recordVitals($_POST)) {
            $msg = "Vital signs recorded successfully.";
            $msgType = "emerald";
        } else {
            $msg = "Failed to record vital signs.";
            $msgType = "red";
        }
    }

    // Update vital signs
    if (isset($_POST['update_vital'])) {
        $vital_id = $_POST['vital_id'];
        $temperature = $_POST['temperature'];
        $pulse = $_POST['pulse'];
        $blood_pressure = $_POST['blood_pressure'];

        $query = "UPDATE vital_signs SET temperature = :temp, pulse = :pulse, blood_pressure = :bp WHERE vital_id = :vid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':temp', $temperature);
        $stmt->bindParam(':pulse', $pulse);
        $stmt->bindParam(':bp', $blood_pressure);
        $stmt->bindParam(':vid', $vital_id);

        if ($stmt->execute()) {
            $msg = "Vital signs updated successfully.";
            $msgType = "blue";
        } else {
            $msg = "Failed to update vital signs.";
            $msgType = "red";
        }
    }

    // Delete vital signs
    if (isset($_POST['delete_vital_confirm'])) {
        $vital_id = $_POST['vital_id'];
        $query = "DELETE FROM vital_signs WHERE vital_id = :vid";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':vid', $vital_id);

        if ($stmt->execute()) {
            $msg = "Vital signs record deleted.";
            $msgType = "red";
        } else {
            $msg = "Failed to delete vital signs.";
            $msgType = "red";
        }
    }
}

// Fetch vital signs history for this patient
$vitalsHistory = [];
if ($visit_id !== "NO_ACTIVE_VISIT") {
    $query = "SELECT v.*, u.full_name as recorded_by_name, 
              DATE_FORMAT(v.recorded_at, '%Y-%m-%d') as record_date,
              DATE_FORMAT(v.recorded_at, '%H:%i') as record_time
              FROM vital_signs v
              JOIN users u ON v.recorded_by = u.user_id
              WHERE v.visit_id = :vid
              ORDER BY v.recorded_at DESC";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':vid', $visit_id);
    $stmt->execute();
    $vitalsHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get current vitals for this visit (most recent)
$currentVitals = [];
if (!empty($vitalsHistory)) {
    $currentVitals = $vitalsHistory[0];
}

$exitPath = "visit";
?>

<div class="space-y-6">
    <!-- Notification Alert -->
    <?php if ($msg): ?>
        <div id="notification-alert"
            class="p-4 bg-<?php echo $msgType; ?>-50 text-<?php echo $msgType; ?>-700 rounded-2xl border border-<?php echo $msgType; ?>-100 font-bold text-xs flex items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2 transition-all duration-500 shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="info" class="w-4 h-4"></i>
                <span><?php echo $msg; ?></span>
            </div>
            <button onclick="closeNotification()"
                class="p-1 hover:bg-<?php echo $msgType; ?>-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-4 h-4 text-<?php echo $msgType; ?>-400"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Patient Context Bar -->
    <div
        class="sticky -top-10 z-20 bg-white/90 backdrop-blur-md border border-blue-100 p-5 rounded-[2.5rem] shadow-sm flex flex-col md:flex-row items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            <div
                class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black text-xl shadow-lg shadow-blue-100">
                <?php echo strtoupper(substr($patient['full_name'], 0, 1)); ?>
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h2 class="text-xl font-black text-gray-800 tracking-tight leading-none">
                        <?php echo $patient['full_name']; ?></h2>
                    <span
                        class="px-2 py-0.5 bg-blue-50 text-blue-600 text-[10px] font-black uppercase rounded-md border border-blue-100 italic">
                        <?php echo $patient['medical_record_number']; ?>
                    </span>
                </div>
                <p class="text-[10px] text-gray-400 font-black uppercase tracking-[0.2em] mt-2 italic">
                    <span class="text-blue-500">Recording Vital Signs</span>
                </p>
            </div>
        </div>
        <div class="flex items-center gap-3">
            <a href="index.php?page=<?php echo $exitPath; ?>"
                class="px-6 py-3 bg-gray-100 text-gray-600 rounded-xl font-black text-[10px] uppercase hover:bg-gray-200 transition-all flex items-center gap-2">
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
                Back to Consultation
            </a>
        </div>
    </div>

    <?php if ($visit_id === "NO_ACTIVE_VISIT"): ?>
        <div class="p-12 bg-blue-50 border-2 border-dashed border-blue-200 rounded-[3rem] text-center">
            <i data-lucide="calendar-clock" class="w-12 h-12 text-blue-600 mx-auto mb-4"></i>
            <h3 class="text-blue-700 font-black uppercase tracking-widest mb-2">No Active Visit</h3>
            <p class="text-xs text-blue-500 font-medium max-w-sm mx-auto mb-6">This patient doesn't have an active visit.
                Please check-in the patient first.</p>
            <a href="index.php?page=records"
                class="inline-block px-8 py-3 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase shadow-lg hover:bg-blue-700 transition-all">Go
                to Registry</a>
        </div>
    <?php else: ?>

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            <!-- Column 1: Vital Signs Form -->
            <div class="xl:col-span-1">
                <div class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm">
                    <div class="flex items-center gap-3 mb-8">
                        <div class="w-1.5 h-6 bg-blue-600 rounded-full shadow-lg"></div>
                        <h3 class="text-lg font-black text-gray-800 uppercase italic">Record New Vitals</h3>
                    </div>

                    <form method="POST" class="space-y-6">
                        <input type="hidden" name="visit_id" value="<?php echo $visit_id; ?>">

                        <div class="space-y-4">
                            <div>
                                <label class="text-[9px] font-black text-gray-400 uppercase ml-4 block mb-2">Temperature
                                    (°C)</label>
                                <input type="number" step="0.1" name="temperature" placeholder="36.5" required
                                    class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl font-bold text-sm shadow-inner focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="text-[9px] font-black text-gray-400 uppercase ml-4 block mb-2">Heart Rate
                                    (BPM)</label>
                                <input type="number" name="pulse" placeholder="72" required
                                    class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl font-bold text-sm shadow-inner focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <label class="text-[9px] font-black text-gray-400 uppercase ml-4 block mb-2">Blood
                                    Pressure</label>
                                <input type="text" name="blood_pressure" placeholder="120/80" required
                                    class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl font-bold text-sm shadow-inner focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>

                        <button type="submit" name="save_triage"
                            class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black text-xs uppercase tracking-widest hover:bg-blue-700 transition-all shadow-xl active:scale-95 flex items-center justify-center gap-3">
                            <i data-lucide="heart-pulse" class="w-5 h-5"></i>
                            Record Vital Signs
                        </button>
                    </form>

                    <!-- Quick reference guide -->
                    <div class="mt-8 p-5 bg-blue-50/50 rounded-2xl border border-blue-100">
                        <h4 class="text-[9px] font-black text-blue-600 uppercase tracking-wider mb-3">Normal Ranges</h4>
                        <div class="grid grid-cols-3 gap-2 text-center">
                            <div class="bg-white p-2 rounded-xl">
                                <p class="text-[8px] font-black text-gray-400">TEMP</p>
                                <p class="text-xs font-black text-gray-700">36.5-37.5</p>
                            </div>
                            <div class="bg-white p-2 rounded-xl">
                                <p class="text-[8px] font-black text-gray-400">PULSE</p>
                                <p class="text-xs font-black text-gray-700">60-100</p>
                            </div>
                            <div class="bg-white p-2 rounded-xl">
                                <p class="text-[8px] font-black text-gray-400">BP</p>
                                <p class="text-xs font-black text-gray-700">120/80</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Column 2-3: Vital Signs History -->
            <div class="xl:col-span-2">
                <div class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm">
                    <div class="flex items-center justify-between mb-8">
                        <div class="flex items-center gap-3">
                            <div class="w-1.5 h-6 bg-emerald-500 rounded-full shadow-lg"></div>
                            <h3 class="text-lg font-black text-gray-800 uppercase italic">Vital Signs History</h3>
                        </div>
                        <span class="px-3 py-1 bg-emerald-50 text-emerald-600 text-[9px] font-black rounded-full">
                            <?php echo count($vitalsHistory); ?> Records
                        </span>
                    </div>

                    <?php if (empty($vitalsHistory)): ?>
                        <div class="text-center py-16">
                            <i data-lucide="activity" class="w-16 h-16 text-gray-200 mx-auto mb-4"></i>
                            <p class="text-sm font-black text-gray-300 uppercase tracking-[0.2em]">No vital signs recorded</p>
                            <p class="text-[9px] text-gray-300 mt-2">Use the form to record patient's vital signs</p>
                        </div>
                    <?php else: ?>

                        <!-- Current Vitals Summary Card -->
                        <div class="mb-8 bg-gradient-to-br from-blue-600 to-indigo-600 rounded-[2rem] p-6 text-white">
                            <h4 class="text-[9px] font-black uppercase tracking-wider text-blue-200 mb-4">Latest Reading</h4>
                            <div class="grid grid-cols-3 gap-4">
                                <div>
                                    <p class="text-[10px] text-blue-200 font-black mb-1">Temperature</p>
                                    <p class="text-md font-black"><?php echo $currentVitals['temperature'] ?? '--'; ?>°C</p>
                                    <p class="text-sm text-blue-200 mt-1">Recorded at
                                        <?php echo $currentVitals['record_time'] ?? '--'; ?></p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-blue-200 font-black mb-1">Heart Rate</p>
                                    <p class="text-md font-black"><?php echo $currentVitals['pulse'] ?? '--'; ?> BPM</p>
                                </div>
                                <div>
                                    <p class="text-[10px] text-blue-200 font-black mb-1">Blood Pressure</p>
                                    <p class="text-md font-black"><?php echo $currentVitals['blood_pressure'] ?? '--/--'; ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- History Timeline -->
                        <div class="space-y-4 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                            <?php foreach ($vitalsHistory as $index => $vital):
                                // Calculate time difference for edit/delete permission (30 minutes)
                                $recordedTime = strtotime($vital['recorded_at']);
                                $diffMinutes = round((time() - $recordedTime) / 60);
                                $canEdit = ($diffMinutes <= 30 && ($role == 'Nurse' || $role == 'Admin'));

                                // Skip the first one as it's already shown in latest reading
                                if ($index == 0)
                                    continue;
                                ?>
                                <div class="relative pl-5 border-l-2 border-gray-100 group hover:border-blue-300 transition-colors">
                                    <div
                                        class="absolute -left-[5px] top-0 w-2 h-2 bg-gray-300 rounded-full border-2 border-white shadow-sm group-hover:bg-blue-500">
                                    </div>

                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <p class="text-[9px] font-black text-gray-400 uppercase">
                                                    <?php echo $vital['record_date']; ?> • <?php echo $vital['record_time']; ?>
                                                </p>
                                                <span class="text-[8px] text-gray-300">by
                                                    <?php echo $vital['recorded_by_name']; ?></span>
                                            </div>

                                            <div class="grid grid-cols-3 gap-4 bg-gray-50 p-4 rounded-2xl">
                                                <div>
                                                    <p class="text-[8px] font-black text-gray-400 uppercase">Temp</p>
                                                    <p class="text-sm font-black text-gray-700">
                                                        <?php echo $vital['temperature']; ?>°C</p>
                                                </div>
                                                <div>
                                                    <p class="text-[8px] font-black text-gray-400 uppercase">Pulse</p>
                                                    <p class="text-sm font-black text-gray-700"><?php echo $vital['pulse']; ?> BPM
                                                    </p>
                                                </div>
                                                <div>
                                                    <p class="text-[8px] font-black text-gray-400 uppercase">BP</p>
                                                    <p class="text-sm font-black text-gray-700">
                                                        <?php echo $vital['blood_pressure']; ?></p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Kebab Menu for Edit/Delete (only within 30 minutes) -->
                                        <?php if ($canEdit): ?>
                                            <div class="relative ml-4">
                                                <button onclick="toggleMenu('vit-<?php echo $vital['vital_id']; ?>')"
                                                    class="text-gray-400 hover:text-blue-600 transition-colors p-2 rounded-full hover:bg-gray-100">
                                                    <i data-lucide="more-vertical" class="w-4 h-4"></i>
                                                </button>

                                                <div id="menu-vit-<?php echo $vital['vital_id']; ?>"
                                                    class="hidden absolute right-0 mt-2 w-36 bg-white rounded-xl shadow-xl border border-gray-100 z-50 overflow-hidden">

                                                    <!-- Edit Button -->
                                                    <button onclick="openEditVitalModal(
                                        '<?php echo $vital['vital_id']; ?>',
                                        '<?php echo $vital['temperature']; ?>',
                                        '<?php echo $vital['pulse']; ?>',
                                        '<?php echo $vital['blood_pressure']; ?>'
                                    )"
                                                        class="w-full text-left px-4 py-3 text-[10px] font-bold text-gray-700 hover:bg-blue-50 hover:text-blue-600 flex items-center gap-2">
                                                        <i data-lucide="edit-2" class="w-3 h-3"></i> Edit
                                                    </button>

                                                    <!-- Delete Button -->
                                                    <form method="POST"
                                                        onsubmit="return confirm('Are you sure you want to delete this vital signs record?');">
                                                        <input type="hidden" name="vital_id" value="<?php echo $vital['vital_id']; ?>">
                                                        <button type="submit" name="delete_vital_confirm"
                                                            class="w-full text-left px-4 py-3 text-[10px] font-bold text-red-600 hover:bg-red-50 flex items-center gap-2">
                                                            <i data-lucide="trash-2" class="w-3 h-3"></i> Delete
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Edit Vital Signs Modal -->
<div id="editVitalModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeEditVitalModal()"></div>
    <div
        class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-md bg-white rounded-[2rem] shadow-2xl p-8 animate-in zoom-in-95 duration-200">
        <div class="flex justify-between items-center mb-6">
            <h3 class="text-lg font-black text-gray-800 uppercase italic">Edit Vital Signs</h3>
            <button onclick="closeEditVitalModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="vital_id" id="edit_vital_id">

            <div>
                <label class="text-[9px] font-black text-gray-400 uppercase ml-2 block mb-2">Temperature (°C)</label>
                <input type="number" step="0.1" name="temperature" id="edit_temperature" required
                    class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-[9px] font-black text-gray-400 uppercase ml-2 block mb-2">Heart Rate (BPM)</label>
                <input type="number" name="pulse" id="edit_pulse" required
                    class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="text-[9px] font-black text-gray-400 uppercase ml-2 block mb-2">Blood Pressure</label>
                <input type="text" name="blood_pressure" id="edit_blood_pressure" required
                    class="w-full px-5 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold text-sm focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex justify-end gap-3 pt-4">
                <button type="button" onclick="closeEditVitalModal()"
                    class="px-6 py-3 rounded-xl font-black text-[10px] uppercase text-gray-500 hover:bg-gray-100">
                    Cancel
                </button>
                <button type="submit" name="update_vital"
                    class="px-8 py-3 bg-blue-600 text-white rounded-xl font-black text-[10px] uppercase hover:bg-blue-700 shadow-lg">
                    Update Record
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Close notification
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

    // Toggle dropdown menus
    function toggleMenu(id) {
        document.querySelectorAll('[id^="menu-"]').forEach(el => {
            if (el.id !== 'menu-' + id) el.classList.add('hidden');
        });
        const menu = document.getElementById('menu-' + id);
        menu.classList.toggle('hidden');
    }

    // Close menus when clicking outside
    window.addEventListener('click', function (e) {
        if (!e.target.closest('button')) {
            document.querySelectorAll('[id^="menu-"]').forEach(el => el.classList.add('hidden'));
        }
    });

    // Edit Vital Modal
    const vitalModal = document.getElementById('editVitalModal');
    const vitalIdInput = document.getElementById('edit_vital_id');
    const vitalTempInput = document.getElementById('edit_temperature');
    const vitalPulseInput = document.getElementById('edit_pulse');
    const vitalBPInput = document.getElementById('edit_blood_pressure');

    function openEditVitalModal(id, temp, pulse, bp) {
        vitalIdInput.value = id;
        vitalTempInput.value = temp;
        vitalPulseInput.value = pulse;
        vitalBPInput.value = bp;
        vitalModal.classList.remove('hidden');
        if (typeof lucide !== 'undefined') lucide.createIcons();
    }

    function closeEditVitalModal() {
        vitalModal.classList.add('hidden');
    }

    // Initialize Lucide icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>