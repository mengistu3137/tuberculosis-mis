<?php
/**
 * VISIT HISTORY MODULE
 * Display All Encounters (including discharged)
 * For discharged patients, only view access is allowed
 */

// Access Control
$allowed = ['Doctor', 'Nurse', 'Clerk', 'Admin'];
if (!in_array($_SESSION['role'], $allowed)) {
    echo "<div class='p-10 text-red-500 font-bold uppercase tracking-widest text-center'>Access Denied</div>";
    exit;
}

// Check if this is view-only mode (for discharged patients)
$viewOnly = isset($_GET['viewonly']) && $_GET['viewonly'] == 1;

// If view-only mode, disable all form submissions
if ($viewOnly) {
    // Override any POST requests
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        header("Location: index.php?page=consultation&id=$id&vid=$visit_id&viewonly=1&error=readonly");
        exit;
    }
}

// Pagination Settings
$limit = 10;
$current_page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
if ($current_page < 1)
    $current_page = 1;
$offset = ($current_page - 1) * $limit;

$userRole = $_SESSION['role'];
$userId = $_SESSION['user_id'];

$filterKey = $_GET['filter'] ?? 'all';
$filterMap = [
    'all' => [],
    'outpatient' => ['visit_type' => 'Outpatient'],
    'inpatient' => ['visit_type' => 'Inpatient'],
    'emergency' => ['visit_type' => 'Emergency'],
    'active' => ['status' => 'active'],
    'discharged' => ['status' => 'completed'],
];
$activeFilters = $filterMap[$filterKey] ?? [];

// Fetch all visits
$total_records = $visitObj->countAllVisits($userRole, $userId, $activeFilters);
$total_pages = ceil($total_records / $limit);
$logs = $visitObj->getAllPaginated($limit, $offset, $userRole, $userId, $activeFilters);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Visit History</h1>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                All patient encounters • <?php echo $total_records; ?> total
            </p>
        </div>
        <div class="flex items-center gap-2">
            <a href="index.php?page=visit"
                class="px-4 py-2 bg-teal-600 text-white rounded-xl text-[10px] font-semibold uppercase tracking-wide hover:bg-teal-700 transition-all">
                View Active
            </a>
        </div>
    </div>

    <div class="flex flex-wrap gap-2">
        <?php foreach ($filterMap as $key => $value):
            $labelMap = [
                'all' => 'All',
                'outpatient' => 'Outpatient',
                'inpatient' => 'Inpatient',
                'emergency' => 'Emergency',
                'active' => 'Active',
                'discharged' => 'Discharged'
            ];
            $label = $labelMap[$key];
            $isActive = ($filterKey === $key);
            $pillUrl = 'index.php?page=visit-history&filter=' . $key;
            ?>
            <a href="<?php echo $pillUrl; ?>"
                class="px-4 py-2 rounded-xl text-[10px] font-semibold uppercase tracking-wide border transition-all <?php echo $isActive ? 'bg-teal-600 text-white border-teal-600 shadow-lg shadow-teal-100' : 'bg-white text-gray-500 border-gray-100 hover:border-teal-200 hover:text-teal-600'; ?>">
                <?php echo $label; ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead
                class="bg-gray-50/30 text-[9px] font-semibold text-gray-400 uppercase tracking-wider border-b border-gray-100">
                <tr>
                    <th class="px-8 py-5">Visit Reference</th>
                    <th class="px-8 py-5">Patient Details</th>
                    <th class="px-8 py-5">Classification</th>
                    <th class="px-8 py-5">Status</th>
                    <th class="px-8 py-5">Discharged</th>
                    <th class="px-8 py-5 text-right">View</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php while ($v = $logs->fetch(PDO::FETCH_ASSOC)):
                    $bg_style = ($v['visit_type'] == 'Emergency') ? 'bg-red-50 text-red-600 border-red-100' : 'bg-teal-50 text-teal-600 border-teal-100';

                    $statusColor = ($v['status'] == 'active') ? 'emerald' : 'gray';
                    $statusText = ($v['status'] == 'active') ? 'Active' : 'Completed';
                    $isDischarged = ($v['status'] == 'completed');
                    $wardLocation = $v['ward_location'] ?? 'Not Recorded';
                    $doctorName = $v['doctor_name'] ?? 'Not Assigned';
                    $nurseName = $v['nurse_name'] ?? 'Not Assigned';
                    $doctorEmail = $v['doctor_email'] ?? 'N/A';
                    $nurseEmail = $v['nurse_email'] ?? 'N/A';
                    $startTs = strtotime($v['created_at']);
                    $endTs = $v['discharged_at'] ? strtotime($v['discharged_at']) : time();
                    $dur = max($endTs - $startTs, 0);
                    $los = floor($dur / 86400) . 'd ' . floor(($dur % 86400) / 3600) . 'h';
                    $timeline = $visitObj->getCareTeamHistoryByVisit($v['visit_id']);
                    ?>
                    <tr class="hover:bg-teal-50/20 transition-all group">
                        <td class="px-8 py-6">
                            <p class="text-xs font-semibold text-teal-600 mb-1 tracking-tighter">
                                <?php echo $v['visit_id']; ?>
                            </p>
                            <p class="text-[9px] font-bold text-gray-400 uppercase">
                                <?php echo date("M d, Y", strtotime($v['created_at'])); ?>
                            </p>
                        </td>
                        <td class="px-8 py-6">
                            <p class="font-semibold text-gray-800 text-sm">
                                <?php echo $v['full_name']; ?>
                            </p>
                            <p class="text-[10px] text-gray-400 font-bold uppercase mt-1">
                                <?php echo $v['medical_record_number']; ?>
                            </p>
                        </td>
                        <td class="px-8 py-6">
                            <span
                                class="px-3 py-1.5 <?php echo $bg_style; ?> text-[9px] font-semibold uppercase rounded-lg border">
                                <?php echo $v['visit_type']; ?>
                            </span>
                        </td>
                        <td class="px-8 py-6">
                            <span
                                class="px-3 py-1.5 bg-<?php echo $statusColor; ?>-100 text-<?php echo $statusColor; ?>-600 text-[9px] font-semibold uppercase rounded-lg border border-<?php echo $statusColor; ?>-200">
                                <?php echo $statusText; ?>
                            </span>
                        </td>
                        <td class="px-8 py-6">
                            <?php if ($isDischarged): ?>
                                <span class="text-[10px] font-medium text-gray-400 bg-gray-100 px-2 py-1 rounded-full">Yes</span>
                            <?php else: ?>
                                <span class="text-[10px] font-medium text-gray-300">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="px-8 py-6 text-right">
                            <?php $assignmentMatch = ($userRole === 'Doctor' && $v['assigned_doctor_id'] == $userId) || ($userRole === 'Nurse' && $v['assigned_nurse_id'] == $userId); ?>
                            <?php if ($isDischarged): ?>
                                <button type="button"
                                    class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                    title="Visit Snapshot"
                                    onclick="openHistoryModal({
                                        visitId: '<?php echo htmlspecialchars($v['visit_id'], ENT_QUOTES); ?>',
                                        patient: '<?php echo htmlspecialchars($v['full_name'], ENT_QUOTES); ?>',
                                        mrn: '<?php echo htmlspecialchars($v['medical_record_number'], ENT_QUOTES); ?>',
                                        type: '<?php echo htmlspecialchars($v['visit_type'], ENT_QUOTES); ?>',
                                        status: '<?php echo htmlspecialchars($v['status'], ENT_QUOTES); ?>',
                                        doctor: '<?php echo htmlspecialchars($doctorName, ENT_QUOTES); ?>',
                                        nurse: '<?php echo htmlspecialchars($nurseName, ENT_QUOTES); ?>',
                                        doctorEmail: '<?php echo htmlspecialchars($doctorEmail, ENT_QUOTES); ?>',
                                        nurseEmail: '<?php echo htmlspecialchars($nurseEmail, ENT_QUOTES); ?>',
                                        ward: '<?php echo htmlspecialchars($wardLocation, ENT_QUOTES); ?>',
                                        createdAt: '<?php echo htmlspecialchars($v['created_at'], ENT_QUOTES); ?>',
                                        dischargedAt: '<?php echo htmlspecialchars($v['discharged_at'], ENT_QUOTES); ?>',
                                        los: '<?php echo $los; ?>',
                                        timelineId: 'timeline-<?php echo $v['visit_id']; ?>'
                                    })">
                                    <i data-lucide="eye" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                </button>
                            <?php else: ?>
                                <?php if ($_SESSION['role'] === 'Nurse'): ?>
                                    <div class="flex items-center justify-end gap-2">
                                        <a href="index.php?page=record-vital&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group relative"
                                        title="Record Vital Signs">
                                        <i data-lucide="activity" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                        <a href="index.php?page=ward-assignment&vid=<?php echo $v['visit_id']; ?>"
                                            class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                            title="Assign Ward / Bed">
                                            <i data-lucide="home" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                        </a>
                                        <button type="button"
                                            class="p-3 <?php echo $assignmentMatch ? 'bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50' : 'bg-gray-100 text-gray-300 cursor-not-allowed'; ?> rounded-2xl transition-all inline-block shadow-inner group"
                                            title="Visit Snapshot" <?php echo $assignmentMatch ? '' : 'disabled'; ?> onclick="<?php if ($assignmentMatch): ?>openHistoryModal({
                                                                                    visitId: '<?php echo htmlspecialchars($v['visit_id'], ENT_QUOTES); ?>',
                                                                                    patient: '<?php echo htmlspecialchars($v['full_name'], ENT_QUOTES); ?>',
                                                                                    mrn: '<?php echo htmlspecialchars($v['medical_record_number'], ENT_QUOTES); ?>',
                                                                                    type: '<?php echo htmlspecialchars($v['visit_type'], ENT_QUOTES); ?>',
                                                                                    status: '<?php echo htmlspecialchars($v['status'], ENT_QUOTES); ?>',
                                                                                    doctor: '<?php echo htmlspecialchars($doctorName, ENT_QUOTES); ?>',
                                                                                    nurse: '<?php echo htmlspecialchars($nurseName, ENT_QUOTES); ?>',
                                                                                    doctorEmail: '<?php echo htmlspecialchars($doctorEmail, ENT_QUOTES); ?>',
                                                                                    nurseEmail: '<?php echo htmlspecialchars($nurseEmail, ENT_QUOTES); ?>',
                                                                                    ward: '<?php echo htmlspecialchars($wardLocation, ENT_QUOTES); ?>',
                                                                                    createdAt: '<?php echo htmlspecialchars($v['created_at'], ENT_QUOTES); ?>',
                                                                                    dischargedAt: '<?php echo htmlspecialchars($v['discharged_at'], ENT_QUOTES); ?>',
                                                                                    los: '<?php echo $los; ?>',
                                                                                    timelineId: 'timeline-<?php echo $v['visit_id']; ?>'
                                                                                })<?php endif; ?>">
                                            <i data-lucide="eye" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                        </button>
                                        </div>
                                        <?php elseif ($_SESSION['role'] === 'Doctor'): ?>
                                        <div class="flex items-center justify-end gap-2">
                                    <a href="index.php?page=consultation&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                        title="Clinical Consultation">
                                        <i data-lucide="stethoscope" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                    <button type="button"
                                        class="p-3 <?php echo $assignmentMatch ? 'bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50' : 'bg-gray-100 text-gray-300 cursor-not-allowed'; ?> rounded-2xl transition-all inline-block shadow-inner group"
                                        title="Visit Snapshot" <?php echo $assignmentMatch ? '' : 'disabled'; ?> onclick="<?php if ($assignmentMatch): ?>openHistoryModal({
                                                                                    visitId: '<?php echo htmlspecialchars($v['visit_id'], ENT_QUOTES); ?>',
                                                                                    patient: '<?php echo htmlspecialchars($v['full_name'], ENT_QUOTES); ?>',
                                                                                    mrn: '<?php echo htmlspecialchars($v['medical_record_number'], ENT_QUOTES); ?>',
                                                                                    type: '<?php echo htmlspecialchars($v['visit_type'], ENT_QUOTES); ?>',
                                                                                    status: '<?php echo htmlspecialchars($v['status'], ENT_QUOTES); ?>',
                                                                                    doctor: '<?php echo htmlspecialchars($doctorName, ENT_QUOTES); ?>',
                                                                                    nurse: '<?php echo htmlspecialchars($nurseName, ENT_QUOTES); ?>',
                                                                                    doctorEmail: '<?php echo htmlspecialchars($doctorEmail, ENT_QUOTES); ?>',
                                                                                    nurseEmail: '<?php echo htmlspecialchars($nurseEmail, ENT_QUOTES); ?>',
                                                                                    ward: '<?php echo htmlspecialchars($wardLocation, ENT_QUOTES); ?>',
                                                                                    createdAt: '<?php echo htmlspecialchars($v['created_at'], ENT_QUOTES); ?>',
                                                                                    dischargedAt: '<?php echo htmlspecialchars($v['discharged_at'], ENT_QUOTES); ?>',
                                                                                    los: '<?php echo $los; ?>',
                                                                                    timelineId: 'timeline-<?php echo $v['visit_id']; ?>'
                                                                                })<?php endif; ?>">
                                        <i data-lucide="eye" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </button>
                                    </div>
                                <?php else: ?>
                                    <button type="button"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                        title="Visit Snapshot"
                                        onclick="openHistoryModal({
                                            visitId: '<?php echo htmlspecialchars($v['visit_id'], ENT_QUOTES); ?>',
                                            patient: '<?php echo htmlspecialchars($v['full_name'], ENT_QUOTES); ?>',
                                            mrn: '<?php echo htmlspecialchars($v['medical_record_number'], ENT_QUOTES); ?>',
                                            type: '<?php echo htmlspecialchars($v['visit_type'], ENT_QUOTES); ?>',
                                            status: '<?php echo htmlspecialchars($v['status'], ENT_QUOTES); ?>',
                                            doctor: '<?php echo htmlspecialchars($doctorName, ENT_QUOTES); ?>',
                                            nurse: '<?php echo htmlspecialchars($nurseName, ENT_QUOTES); ?>',
                                            doctorEmail: '<?php echo htmlspecialchars($doctorEmail, ENT_QUOTES); ?>',
                                            nurseEmail: '<?php echo htmlspecialchars($nurseEmail, ENT_QUOTES); ?>',
                                            ward: '<?php echo htmlspecialchars($wardLocation, ENT_QUOTES); ?>',
                                            createdAt: '<?php echo htmlspecialchars($v['created_at'], ENT_QUOTES); ?>',
                                            dischargedAt: '<?php echo htmlspecialchars($v['discharged_at'], ENT_QUOTES); ?>',
                                            los: '<?php echo $los; ?>',
                                            timelineId: 'timeline-<?php echo $v['visit_id']; ?>'
                                        })">
                                        <i data-lucide="eye" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                                    <div id="timeline-<?php echo $v['visit_id']; ?>" class="hidden">
                                    <?php if (!empty($timeline)): ?>
                                        <div class="space-y-2">
                                            <?php foreach ($timeline as $item): ?>
                                                <div class="flex items-center justify-between bg-gray-50 border border-gray-100 rounded-lg px-3 py-2">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-800"><?php echo $item['full_name'] ?? 'Staff'; ?>
                                                            (<?php echo $item['role']; ?>)</p>
                                                        <p class="text-[11px] text-gray-500"><?php echo $item['email'] ?? 'N/A'; ?></p>
                                                    </div>
                                                    <p class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                                                        <?php echo date('M d, Y H:i', strtotime($item['assigned_at'])); ?>
                                                    </p>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                    <p class="text-[11px] text-gray-400">No care-team events recorded.</p>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
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
                                $params = ['page' => 'visit-history', 'p' => $i];
                                if ($filterKey !== 'all') {
                                    $params['filter'] = $filterKey;
                                }
                                $url = 'index.php?' . http_build_query($params);
                                ?>
                            <a href="<?php echo $url; ?>"
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-semibold text-[10px] transition-all <?php echo ($i == $current_page) ? 'bg-teal-600 text-white' : 'bg-white text-gray-400 border border-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<div id="historyOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm hidden"></div>
<div id="historyModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-6">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-3xl relative overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Visit Snapshot</p>
                <h4 class="text-lg font-bold text-gray-800" id="hist_patient"></h4>
                <p class="text-[11px] text-gray-500 font-semibold" id="hist_mrn"></p>
            </div>
            <button class="p-2 rounded-full hover:bg-gray-100" onclick="closeHistoryModal()">
                <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="grid grid-cols-3 gap-3">
                <div class="bg-teal-50 border border-teal-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-teal-500">Visit Type</p>
                    <p class="text-sm font-bold text-gray-800" id="hist_type"></p>
                </div>
                <div class="bg-amber-50 border border-amber-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-amber-500">Status</p>
                    <p class="text-sm font-bold text-gray-800" id="hist_status"></p>
                </div>
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-indigo-500">Ward / Room</p>
                    <p class="text-sm font-bold text-gray-800" id="hist_ward"></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-gray-500">Visit ID</p>
                    <p class="text-sm font-bold text-gray-800" id="hist_visit_id"></p>
                    <p class="text-[11px] text-gray-500" id="hist_los"></p>
                </div>
                <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-gray-500">Admission / Discharge</p>
                    <p class="text-sm font-bold text-gray-800" id="hist_admit"></p>
                    <p class="text-[11px] text-gray-500" id="hist_discharge"></p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3">
                <div class="bg-white border border-gray-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-teal-500">Attending Doctor</p>
                    <p class="text-sm font-bold text-gray-800" id="hist_doctor"></p>
                    <p class="text-[11px] text-gray-500" id="hist_doctor_email"></p>
                </div>
                <div class="bg-white border border-gray-100 rounded-xl p-3">
                    <p class="text-[10px] uppercase font-semibold text-indigo-500">Assigned Nurse</p>
                    <p class="text-sm font-bold text-gray-800" id="hist_nurse"></p>
                    <p class="text-[11px] text-gray-500" id="hist_nurse_email"></p>
                </div>
            </div>
            <div class="bg-gray-50 border border-gray-100 rounded-xl p-3">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] uppercase font-semibold text-gray-500">Care-team Timeline</p>
                </div>
                <div id="hist_timeline" class="mt-3 space-y-2"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function openHistoryModal(payload) {
        document.getElementById('hist_patient').textContent = payload.patient;
        document.getElementById('hist_mrn').textContent = payload.mrn;
        document.getElementById('hist_type').textContent = payload.type;
        document.getElementById('hist_status').textContent = payload.status;
        document.getElementById('hist_ward').textContent = payload.ward;
        document.getElementById('hist_visit_id').textContent = payload.visitId;
        document.getElementById('hist_los').textContent = 'Length of stay: ' + payload.los;
        document.getElementById('hist_admit').textContent = 'Admitted: ' + formatDate(payload.createdAt);
        document.getElementById('hist_discharge').textContent = payload.dischargedAt ? 'Discharged: ' + formatDate(payload.dischargedAt) : 'Discharged: Pending';
        document.getElementById('hist_doctor').textContent = payload.doctor;
        document.getElementById('hist_doctor_email').textContent = payload.doctorEmail;
        document.getElementById('hist_nurse').textContent = payload.nurse;
        document.getElementById('hist_nurse_email').textContent = payload.nurseEmail;

        const timelineRoot = document.getElementById('hist_timeline');
        const src = document.getElementById(payload.timelineId);
        timelineRoot.innerHTML = src ? src.innerHTML : '<p class="text-[11px] text-gray-400">No care-team events recorded.</p>';

        document.getElementById('historyModal').classList.remove('hidden');
        document.getElementById('historyOverlay').classList.remove('hidden');
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    function closeHistoryModal() {
        document.getElementById('historyModal').classList.add('hidden');
        document.getElementById('historyOverlay').classList.add('hidden');
    }

    function formatDate(ts) {
        if (!ts) return '—';
        const d = new Date(ts.replace(' ', 'T'));
        if (Number.isNaN(d.getTime())) return ts;
        return d.toLocaleString();
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>