<?php
/**
 * VISIT MANAGEMENT MODULE
 * Display Active Encounters Only
 */

// Access Control
// Access Control
$allowed = ['Doctor', 'Nurse', 'Clerk', 'Admin'];
if (!in_array($_SESSION['role'], $allowed)) {
    echo "<div class='p-10 text-red-500 font-black uppercase tracking-widest text-center'>Access Denied</div>";
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

// MODIFIED: We pass the role and user ID to the fetch functions
// Clerks/Admins see ALL active, Doctors/Nurses see ONLY ASSIGNED
$total_records = $visitObj->countAll($userRole, $userId);
$total_pages = ceil($total_records / $limit);
$logs = $visitObj->getPaginated($limit, $offset, $userRole, $userId);
 // This now gets only active

?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-black text-gray-800 tracking-tighter uppercase">Active Patient Encounters</h1>
            <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">
                Currently admitted/checked-in patients
            </p>
        </div>

        <!-- Optional: Link to view all history -->
        <a href="index.php?page=visit-history"
            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl text-[9px] font-black uppercase tracking-widest hover:bg-gray-200 transition-all">
            View All History
        </a>
    </div>

    <!-- Live Feed Table -->
    <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden transition-all">
        <div class="px-8 py-6 bg-gray-50/50 border-b border-gray-100 flex justify-between items-center">
            <h3 class="text-[10px] font-black text-gray-400 uppercase tracking-[0.3em]">Active Patient Encounters</h3>
            <span class="text-[9px] font-black text-blue-500 italic uppercase">
                Active: <?php echo $total_records; ?> Encounters
            </span>
        </div>

        <table class="w-full text-left">
            <thead
                class="bg-gray-50/30 text-[9px] font-black text-gray-400 uppercase tracking-[0.3em] border-b border-gray-100">
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
                        $bg_style = ($v['visit_type'] == 'Emergency') ? 'bg-red-50 text-red-600 border-red-100' : 'bg-blue-50 text-blue-600 border-blue-100';
                        ?>
                        <tr class="hover:bg-blue-50/20 transition-all group">
                            <td class="px-8 py-6">
                                <p class="text-[10px] font-black text-blue-600 mb-1 tracking-tighter">
                                    <?php echo $v['visit_id']; ?>
                                </p>
                                <p class="text-[9px] font-bold text-gray-400 uppercase">
                                    <?php echo date("M d, Y | H:i", strtotime($v['created_at'])); ?>
                                </p>
                            </td>
                            <td class="px-8 py-6">
                                <p class="font-black text-gray-800 text-sm leading-none"><?php echo $v['full_name']; ?></p>
                                <p class="text-[10px] text-gray-400 font-bold uppercase mt-1.5">
                                    <?php echo $v['medical_record_number']; ?>
                                </p>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1.5 <?php echo $bg_style; ?> text-[9px] font-black uppercase rounded-lg border transition-all duration-300 shadow-sm">
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
                                    <span class="text-[10px] font-black uppercase text-gray-500 tracking-widest">
                                        Active
                                    </span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <?php if ($_SESSION['role'] == 'Nurse'||$_SESSION['role'] == 'Doctor'): ?>
                                    <a href="index.php?page=consultation&id=<?php echo $v['patient_id']; ?>&vid=<?php echo $v['visit_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-2xl transition-all inline-block shadow-inner group"
                                        title="Clinical Consultation">
                                        <i data-lucide="stethoscope" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                    
                                <?php else: ?>
                                    <a href="index.php?page=records&id=<?php echo $v['patient_id']; ?>"
                                        class="p-3 bg-gray-50 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-2xl transition-all inline-block shadow-inner group"
                                        title="View Records">
                                        <i data-lucide="folder-open" class="w-4 h-4 group-hover:scale-110 transition-transform"></i>
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endwhile;
                else: ?>
                    <tr>
                        <td colspan="5" class="p-20 text-center">
                            <i data-lucide="users" class="w-16 h-16 text-gray-200 mx-auto mb-4"></i>
                            <p class="text-sm font-black text-gray-300 uppercase tracking-widest">No Active Encounters</p>
                            <p class="text-[10px] text-gray-300 mt-2">All patients have been discharged</p>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest italic">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                </span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="index.php?page=visit&p=<?php echo $i; ?>"
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-black text-[10px] transition-all <?php echo ($i == $current_page) ? 'bg-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white text-gray-400 border border-gray-100 hover:border-blue-200'; ?>">
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
</script>