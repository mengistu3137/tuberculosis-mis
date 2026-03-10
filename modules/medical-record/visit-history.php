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

// Fetch all visits
$total_records = $visitObj->countAllVisits();
$total_pages = ceil($total_records / $limit);
$logs = $visitObj->getAllPaginated($limit, $offset);
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
        <a href="index.php?page=visit"
            class="px-4 py-2 bg-teal-600 text-white rounded-xl text-[10px] font-semibold uppercase tracking-wide hover:bg-teal-700 transition-all">
            View Active
        </a>
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
                            <?php if ($isDischarged): ?>
                                <!-- For discharged patients - VIEW ONLY (eye icon) -->
                                <a href="index.php?page=consultation&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>&viewonly=1"
                                    class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                    title="View Patient History (Read Only)">
                                    <i data-lucide="eye" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                </a>
                            <?php else: ?>
                                <!-- For active patients - role-based actions -->
                                <?php if ($_SESSION['role'] == 'Nurse'): ?>
                                    <a href="index.php?page=record-vital&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group relative"
                                        title="Record Vital Signs">
                                        <i data-lucide="activity" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                <?php elseif ($_SESSION['role'] == 'Doctor'): ?>
                                    <a href="index.php?page=consultation&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                        title="Clinical Consultation">
                                        <i data-lucide="stethoscope" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                <?php else: ?>
                                    <a href="index.php?page=records&id=<?php echo $v['patient_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-teal-600 hover:bg-teal-50 rounded-2xl transition-all inline-block shadow-inner group"
                                        title="View Records">
                                        <i data-lucide="folder-open" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
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
                        <a href="index.php?page=visit-history&p=<?php echo $i; ?>"
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-semibold text-[10px] transition-all <?php echo ($i == $current_page) ? 'bg-teal-600 text-white' : 'bg-white text-gray-400 border border-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>lucide.createIcons();</script>