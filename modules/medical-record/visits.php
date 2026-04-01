<?php
/**
 * VISIT MANAGEMENT MODULE
 * Display Active Encounters Only
 */

// Access Control
// Access Control
$allowed = ['Doctor', 'Nurse', 'Clerk', 'Admin'];
if (!in_array($_SESSION['role'], $allowed)) {
    echo "<div class='p-10 text-red-500 font-bold uppercase tracking-widest text-center'>Access Denied</div>";
    exit;
}
$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];

// Pagination Settings
$limit = 8;
$current_page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
if ($current_page < 1)
    $current_page = 1;
$offset = ($current_page - 1) * $limit;
$filter = $_GET['filter'] ?? '';
$filters = [];
if ($filter === 'today') {
    $filters['today'] = true;
}
if ($filter === 'emergency') {
    $filters['visit_type'] = 'Emergency';
}

// MODIFIED: We pass the role and user ID to the fetch functions
// Clerks/Admins see ALL active, Doctors/Nurses see ONLY ASSIGNED
$total_records = $visitObj->countAll($userRole, $userId, $filters);
$total_pages = ceil($total_records / $limit);
$logs = $visitObj->getPaginated($limit, $offset, $userRole, $userId, $filters);
 // This now gets only active

// Pull flash message (e.g., assignment details after check-in)
$flashMsg = $_SESSION['flash_msg'] ?? '';
$flashType = $_SESSION['flash_type'] ?? 'teal';
unset($_SESSION['flash_msg'], $_SESSION['flash_type']);

?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Active Patient Encounters</h1>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                Currently admitted/checked-in patients
            </p>
        </div>

        <div class="flex items-center gap-2">
            <a href="index.php?page=visit&filter=today"
                class="px-4 py-2 bg-white border border-gray-200 text-gray-700 rounded-xl text-[10px] font-semibold uppercase tracking-wide hover:border-teal-300 hover:text-teal-600 transition-all <?php echo ($filter === 'today') ? 'bg-teal-50 border-teal-200 text-teal-600' : ''; ?>">
                Today's Visits
            </a>
            <?php if ($filter === 'today'): ?>
                <a href="index.php?page=visit"
                    class="px-3 py-2 bg-gray-100 text-gray-500 rounded-xl text-[10px] font-semibold uppercase tracking-wide hover:bg-gray-200 transition-all">
                    Clear
                </a>
            <?php endif; ?>
            <a href="index.php?page=visit-history"
                class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-[10px] font-semibold uppercase tracking-wide hover:bg-gray-200 transition-all">
                View All History
            </a>
        </div>
    </div>

    <?php if (!empty($flashMsg)): ?>
        <div
            class="flex items-center gap-3 px-4 py-3 bg-<?php echo $flashType; ?>-50 text-<?php echo $flashType; ?>-700 border border-<?php echo $flashType; ?>-100 rounded-xl shadow-sm">
            <i data-lucide="info" class="w-4 h-4"></i>
            <span class="text-xs font-semibold uppercase tracking-wide"><?php echo $flashMsg; ?></span>
        </div>
    <?php endif; ?>

    <!-- Live Feed Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition-all">
        <div class="px-8 py-6 bg-gray-50/50 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">Active Patient Encounters</h3>
            <span class="text-[9px] font-semibold text-teal-500">
                <?php echo ($filter === 'today') ? 'Today Only' : 'Active'; ?>: <?php echo $total_records; ?> Encounters
            </span>
        </div>

        <table class="w-full text-left">
            <thead
                class="bg-gray-50/30 text-[9px] font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                <tr>
                    <th class="px-8 py-5">Visit Reference</th>
                    <th class="px-8 py-5">Patient Details</th>
                    <th class="px-8 py-5">Classification</th>
                    <th class="px-8 py-5">Status</th>
                    <th class="px-8 py-5 text-right">Workspace</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php
                if ($logs->rowCount() > 0):
                    while ($v = $logs->fetch(PDO::FETCH_ASSOC)):
                        $bg_style = ($v['visit_type'] == 'Emergency') ? 'bg-red-50 text-red-600 border-red-100' : 'bg-teal-50 text-teal-600 border-teal-100';
                        $hasWardRequest = isset($v['ward_request_status']) && strtolower($v['ward_request_status']) === 'pending';
                        ?>
                        <tr class="hover:bg-teal-50/20 transition-all group">
                            <td class="px-8 py-6">
                                <p class="text-xs font-semibold text-teal-600 mb-1 tracking-tighter">
                                    <?php echo $v['visit_id']; ?>
                                </p>
                                <p class="text-[9px] font-bold text-gray-400 uppercase">
                                    <?php echo date("M d, Y | H:i", strtotime($v['created_at'])); ?>
                                </p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="font-semibold text-gray-800 text-sm leading-none"><?php echo $v['full_name']; ?></p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase mt-1.5">
                                    <?php echo $v['medical_record_number']; ?>
                                </p>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1.5 <?php echo $bg_style; ?> text-[9px] font-semibold uppercase rounded-lg border transition-all duration-300 shadow-sm">
    <?php if($v['visit_type'] == 'Emergency'): ?>
        <i data-lucide="zap" class="w-3 h-3 inline mr-1 mb-0.5"></i>
    <?php elseif($v['visit_type'] == 'Inpatient'): ?>
        <i data-lucide="home" class="w-3 h-3 inline mr-1 mb-0.5"></i>
    <?php else: ?>
        <i data-lucide="user" class="w-3 h-3 inline mr-1 mb-0.5"></i>
    <?php endif; ?>
    
    <?php echo $v['visit_type']; ?>
</span>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <span
                                        class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.5)] animate-pulse"></span>
                                    <span class="text-[10px] font-semibold uppercase text-gray-500 tracking-widest">
                                        Active
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <?php if ($_SESSION['role'] === 'Doctor'): ?>
                                    <a href="index.php?page=consultation&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                        title="Clinical Consultation">
                                        <i data-lucide="stethoscope" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                <?php elseif ($_SESSION['role'] === 'Nurse'): ?>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="index.php?page=record-vital&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                            title="Record Vital Signs">
                                            <i data-lucide="activity" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                        </a>
                                        <?php if ($hasWardRequest): ?>
                                            <a href="index.php?page=ward-assignment&vid=<?php echo $v['visit_id']; ?>"
                                                class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                                title="Assign Ward / Bed">
                                                <i data-lucide="home" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                            </a>
                                        <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <?php
                                        $doctorName = $v['doctor_name'] ?? 'Not Assigned';
                                        $doctorEmail = $v['doctor_email'] ?? 'N/A';
                                        $nurseName = $v['nurse_name'] ?? 'Not Assigned';
                                        $wardLocation = $v['ward_location'] ?? 'Not Recorded';
                                        ?>
                                        <button type="button"
                                            class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                            title="Assignment Snapshot"
                                            onclick="openAssignmentModal({
                                                                                                                                                                    visitId: '<?php echo htmlspecialchars($v['visit_id'], ENT_QUOTES); ?>',
                                                                                                                                                                    patient: '<?php echo htmlspecialchars($v['full_name'], ENT_QUOTES); ?>',
                                                                                                                                                                    mrn: '<?php echo htmlspecialchars($v['medical_record_number'], ENT_QUOTES); ?>',
                                                                                                                                                                    doctor: '<?php echo htmlspecialchars($doctorName, ENT_QUOTES); ?>',
                                                                                                                                                                    doctorEmail: '<?php echo htmlspecialchars($doctorEmail, ENT_QUOTES); ?>',
                                                                                                                                                                    nurse: '<?php echo htmlspecialchars($nurseName, ENT_QUOTES); ?>',
                                                                                                                                                                    ward: '<?php echo htmlspecialchars($wardLocation, ENT_QUOTES); ?>',
                                                                                                                                                                    createdAt: '<?php echo htmlspecialchars($v['created_at'], ENT_QUOTES); ?>'
                                                                                                                                                                })">
                                            <i data-lucide="eye" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                        </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="5" class="p-20 text-center">
                            <i data-lucide="users" class="w-16 h-16 text-gray-200 mx-auto mb-4"></i>
                            <p class="text-sm font-semibold text-gray-400 tracking-wide">No Active Encounters</p>
                            <p class="text-[10px] text-gray-300 mt-2">All patients have been discharged</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider italic">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                </span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <?php
                                        $pageParams = ['page' => 'visit', 'p' => $i];
                                        if ($filter === 'today') {
                                            $pageParams['filter'] = 'today';
                                        }
                                        $pageUrl = 'index.php?' . http_build_query($pageParams);
                                        ?>
                                    <a href="<?php echo $pageUrl; ?>"
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-semibold text-[10px] transition-all <?php echo ($i == $current_page) ? 'bg-teal-600 text-white shadow-lg shadow-teal-100' : 'bg-white text-gray-400 border border-gray-100 hover:border-teal-200'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }

    const assignmentModal = document.getElementById('assignmentModal');
    const assignmentOverlay = document.getElementById('assignmentOverlay');

    function openAssignmentModal(payload) {
        document.getElementById('assign_visit_id').textContent = payload.visitId;
        document.getElementById('assign_patient').textContent = payload.patient;
        document.getElementById('assign_mrn').textContent = payload.mrn;
        document.getElementById('assign_doctor').textContent = payload.doctor;
        document.getElementById('assign_doctor_email').textContent = payload.doctorEmail;
        document.getElementById('assign_nurse').textContent = payload.nurse;
        document.getElementById('assign_ward').textContent = payload.ward;
        document.getElementById('assign_created').textContent = formatTimestamp(payload.createdAt);
        assignmentModal.classList.remove('hidden');
        assignmentOverlay.classList.remove('hidden');
    }

    function closeAssignmentModal() {
        assignmentModal.classList.add('hidden');
        assignmentOverlay.classList.add('hidden');
    }

    function formatTimestamp(ts) {
        if (!ts) return '—';
        const d = new Date(ts.replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return ts;
        return d.toLocaleString();
    }
</script>

<div id="assignmentOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden"></div>
<div id="assignmentModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-lg relative overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Assigned Provider Snapshot</p>
                <h4 class="text-lg font-bold text-gray-800" id="assign_patient"></h4>
                <p class="text-[11px] text-gray-500 font-semibold" id="assign_mrn"></p>
            </div>
            <button class="p-2 rounded-full hover:bg-gray-100" onclick="closeAssignmentModal()">
                <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div class="bg-teal-50 border border-teal-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-teal-500">Assigned Doctor</p>
                    <p class="text-sm font-bold text-gray-800" id="assign_doctor">Not Assigned</p>
                    <p class="text-[11px] text-gray-500" id="assign_doctor_email">N/A</p>
                </div>
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-indigo-500">Assigned Nurse</p>
                    <p class="text-sm font-bold text-gray-800" id="assign_nurse">Not Assigned</p>
                    <p class="text-[11px] text-gray-500" id="assign_ward">Ward / Room: Not Recorded</p>
                </div>
            </div>
            <div class="bg-gray-50 border border-gray-100 rounded-xl p-3 flex items-center justify-between">
                <div>
                    <p class="text-[10px] uppercase font-semibold text-gray-500">Visit ID</p>
                    <p class="text-sm font-bold text-gray-800" id="assign_visit_id"></p>
                </div>
                <div class="text-right">
                    <p class="text-[10px] uppercase font-semibold text-gray-500">Check-in Time</p>
                    <p class="text-sm font-bold text-gray-800" id="assign_created"></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>