<?php
/**
 * LABORATORY WORKLIST MODULE
 * Refactored: Added Database Pagination
 */

// 1. Pagination Settings
$limit = 7; // Items per page
$current_page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
if ($current_page < 1)
    $current_page = 1;
$offset = ($current_page - 1) * $limit;

// 2. Fetch Data using global $labObj
$total_records = $labObj->countAllRequests();
$total_pages = ceil($total_records / $limit);
$stmt = $labObj->getPaginatedRequests($limit, $offset);
$allRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 3. Logic: Calculate TOTAL Pending Count (for the header badge)
// We query the DB for the count of pending specifically
$pendingCount = $db->query("SELECT COUNT(*) FROM lab_requests WHERE status = 'pending'")->fetchColumn();
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-6">
        <div>
            <h1 class="text-xl font-black text-gray-800 tracking-tight uppercase italic">Laboratory Worklist</h1>
            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest mt-1">Diagnostic Queue • Mattu Karl
                Specialized Hospital</p>
        </div>

        <!-- Status Summary Badge -->
        <div
            class="flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-2xl shadow-lg shadow-blue-100 transition-all active:scale-95">
            <i data-lucide="flask-conical" class="w-4 h-4 text-blue-200"></i>
            <span class="text-[10px] font-black uppercase tracking-widest"><?php echo $pendingCount; ?> Global
                Pending</span>
        </div>
    </div>

    <!-- Main Data Table Container -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden transition-all">
        <table class="w-full text-left">
            <thead class="bg-blue-50/50 border-b border-blue-100">
                <tr>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Subject &
                        Order</th>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Authorized By
                    </th>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] text-center">
                        Status</th>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] text-right">
                        Workflow</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (count($allRequests) > 0): ?>
                    <?php foreach ($allRequests as $row):
                        $status = strtolower($row['status']);
                        $statusColor = ($status == 'pending') ? 'orange' : 'emerald';
                        ?>
                        <tr class="hover:bg-blue-50/20 transition-all group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-black group-hover:bg-blue-600 group-hover:text-white transition-all duration-500 shadow-inner">
                                        <?php echo strtoupper(substr($row['patient_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-800 text-sm leading-none italic">
                                            <?php echo $row['patient_name']; ?></p>
                                        <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mt-2">
                                            <?php echo $row['test_type']; ?></p>
                                    </div>
                                </div>
                            </td>

                            <td class="px-8 py-6">
                                <p class="text-xs font-bold text-gray-600">Dr. <?php echo $row['doctor_name']; ?></p>
                                <p class="text-[9px] text-gray-400 font-bold uppercase mt-1 tracking-tighter">
                                    <?php echo date("M d, Y | H:i", strtotime($row['created_at'])); ?>
                                </p>
                            </td>

                            <td class="px-8 py-6 text-center">
                                <span
                                    class="inline-flex items-center gap-2 px-4 py-1.5 bg-<?php echo $statusColor; ?>-50 text-<?php echo $statusColor; ?>-700 rounded-full border border-<?php echo $statusColor; ?>-100 text-[10px] font-black uppercase tracking-widest shadow-sm">
                                    <span
                                        class="w-1.5 h-1.5 bg-<?php echo $statusColor; ?>-500 rounded-full <?php echo ($status == 'pending') ? 'animate-pulse' : ''; ?>"></span>
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>

                            <td class="px-8 py-6 text-right">
                                <?php if ($status == 'pending'): ?>
                                    <a href="index.php?page=lab-results&req_id=<?php echo $row['request_id']; ?>"
                                        class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-600 shadow-lg transition-all active:scale-95">
                                        Input Result <i data-lucide="arrow-right" class="w-3 h-3"></i>
                                    </a>
                                <?php else: ?>
                                    <div
                                        class="inline-flex items-center gap-2 px-4 py-2 bg-gray-50 text-gray-400 rounded-xl text-[9px] font-black uppercase tracking-widest border border-gray-100 italic">
                                        <i data-lucide="check-check" class="w-4 h-4 text-emerald-500"></i> Reported
                                    </div>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="p-24 text-center">
                            <div class="flex flex-col items-center opacity-30">
                                <i data-lucide="flask-conical-off" class="w-10 h-10 text-gray-400 mb-4"></i>
                                <p class="text-xs font-black uppercase tracking-[0.3em] text-gray-500">Queue is Currently
                                    Empty</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- 4. PAGINATION FOOTER -->
        <?php if ($total_pages > 1): ?>
            <div
                class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest italic">
                    Diagnostic Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                </span>

                <div class="flex items-center gap-1.5">
                    <!-- Previous -->
                    <?php if ($current_page > 1): ?>
                        <a href="index.php?page=laboratory&p=<?php echo $current_page - 1; ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-100 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                            <i data-lucide="chevron-left" class="w-4 h-4"></i>
                        </a>
                    <?php endif; ?>

                    <!-- Page Numbers -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="index.php?page=laboratory&p=<?php echo $i; ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-xl font-black text-[10px] transition-all
                               <?php echo ($i == $current_page) ? 'bg-blue-600 text-white shadow-lg shadow-blue-100 scale-110' : 'bg-white text-gray-400 border border-gray-100 hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if ($current_page < $total_pages): ?>
                        <a href="index.php?page=laboratory&p=<?php echo $current_page + 1; ?>"
                            class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-gray-100 text-gray-400 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm">
                            <i data-lucide="chevron-right" class="w-4 h-4"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    lucide.createIcons();
</script>