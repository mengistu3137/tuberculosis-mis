<?php
// Access Control
$allowed = ['Nurse', 'Admin'];
if (!in_array($_SESSION['role'], $allowed)) {
    echo "<div class='p-10 text-red-500 font-bold uppercase tracking-widest text-center'>Access Denied</div>";
    exit;
}

$visitId = $_GET['vid'] ?? '';
if (!$visitId) {
    echo "<div class='p-10 text-red-500 font-bold uppercase tracking-widest text-center'>Visit not specified</div>";
    exit;
}

$currentWard = $visitObj->getWardAssignment($visitId);
$message = '';
$messageType = 'teal';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = trim($_POST['assignment_location'] ?? '');
    if ($location !== '') {
        if ($visitObj->upsertWardAssignment($visitId, $location, $_SESSION['user_id'])) {
            if (method_exists($assignObj, 'completeWardAssignmentRequest')) {
                $assignObj->completeWardAssignmentRequest($visitId);
            }
            $message = 'Ward assignment saved successfully.';
            $messageType = 'emerald';
            $currentWard = $visitObj->getWardAssignment($visitId);
        } else {
            $message = 'Failed to save ward assignment.';
            $messageType = 'red';
        }
    } else {
        $message = 'Please provide a ward / room / bed description.';
        $messageType = 'orange';
    }
}
?>

<div id="wardOverlay" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-6 z-50">
    <div class="bg-white rounded-2xl shadow-2xl border border-gray-100 w-full max-w-xl relative overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div>
                <p class="text-[10px] uppercase font-bold text-gray-400 tracking-wider">Ward Assignment</p>
                <h1 class="text-lg font-bold text-gray-800 tracking-tight">Visit: <?php echo htmlspecialchars($visitId, ENT_QUOTES); ?></h1>
            </div>
            <button class="p-2 rounded-full hover:bg-gray-100" onclick="closeWardModal()">
                <i data-lucide="x" class="w-4 h-4 text-gray-500"></i>
            </button>
        </div>

        <div class="p-6 space-y-5">
            <?php if ($message): ?>
                <div class="flex items-center gap-3 px-4 py-3 bg-<?php echo $messageType; ?>-50 text-<?php echo $messageType; ?>-700 border border-<?php echo $messageType; ?>-100 rounded-xl shadow-sm">
                    <i data-lucide="info" class="w-4 h-4"></i>
                    <span class="text-xs font-semibold uppercase tracking-wide"><?php echo $message; ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4">
                <label class="block text-[10px] font-semibold uppercase tracking-wide text-gray-500">Ward / Room / Bed</label>
                <input type="text" name="assignment_location" required value="<?php echo htmlspecialchars($currentWard['assignment_location'] ?? '', ENT_QUOTES); ?>" class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-teal-500 text-sm font-semibold" placeholder="e.g., Medical Ward • Room 3 • Bed 2">
                <button type="submit" class="w-full py-3 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase tracking-widest shadow-lg hover:bg-teal-700 transition-all">Save Assignment</button>
            </form>

            <div class="bg-gray-50 border border-gray-100 rounded-xl p-4">
                <p class="text-[10px] uppercase font-semibold text-gray-500">Last Update</p>
                <p class="text-sm font-bold text-gray-800">Ward / Room / Bed: <?php echo htmlspecialchars($currentWard['assignment_location'] ?? 'Not Recorded', ENT_QUOTES); ?></p>
                <p class="text-[11px] text-gray-500">Assigned By: <?php echo htmlspecialchars($currentWard['assigned_by_name'] ?? '—', ENT_QUOTES); ?></p>
                <p class="text-[11px] text-gray-500">Updated: <?php echo isset($currentWard['assigned_at']) ? htmlspecialchars($currentWard['assigned_at'], ENT_QUOTES) : '—'; ?></p>
            </div>
        </div>
    </div>
</div>

<script>
    function closeWardModal() {
        window.location.href = 'index.php?page=visit';
    }

    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>
