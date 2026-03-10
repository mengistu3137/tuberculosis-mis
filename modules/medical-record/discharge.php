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

$msg = "";
$msgType = "teal";

// Handle discharge submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['process_discharge'])) {
    $data = [
        'patient_id' => $id,
        'visit_id' => $visit_id,
        'summary' => $_POST['summary'],
        'condition' => $_POST['condition'],
        'instructions' => $_POST['instructions'],
        'follow_up' => $_POST['follow_up']
    ];

    $result = $clinicalObj->processDischarge($data, $_SESSION['user_id']);

    if ($result) {
        $msg = "Patient discharged successfully. Visit completed.";
        $msgType = "emerald";

        // Redirect after 2 seconds
        echo "<script>
            setTimeout(function() {
                window.location.href = 'index.php?page=visit';
            }, 2000);
        </script>";
    } else {
        $msg = "Error processing discharge.";
        $msgType = "red";
    }
}

// Get visit details
$visitDetails = $db->prepare("SELECT * FROM medical_visits WHERE visit_id = ?");
$visitDetails->execute([$visit_id]);
$visit = $visitDetails->fetch(PDO::FETCH_ASSOC);
?>

<div class="max-w-3xl mx-auto space-y-6">
    <!-- Notification -->
    <?php if ($msg): ?>
        <div id="notification-alert"
            class="p-4 bg-<?php echo $msgType; ?>-50 text-<?php echo $msgType; ?>-700 rounded-2xl border border-<?php echo $msgType; ?>-100 font-bold text-xs flex items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2 shadow-sm">
            <div class="flex items-center gap-3">
                <i data-lucide="info" class="w-4 h-4"></i>
                <span><?php echo $msg; ?></span>
            </div>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800 uppercase tracking-tighter">Clinical Discharge Summary</h1>
        <div class="px-4 py-2 bg-red-50 text-red-600 rounded-xl text-xs font-semibold uppercase">Final Report</div>
    </div>

    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="bg-teal-600 p-10 text-white flex justify-between items-center">
            <div>
                <h2 class="text-3xl font-bold mb-1"><?php echo $patient['full_name']; ?></h2>
                <p class="text-gray-400 text-xs font-bold uppercase tracking-widest">
                    MRN: <?php echo $patient['medical_record_number']; ?>
                </p>
            </div>
            <div class="text-right">
                <p class="text-gray-400 text-[10px] font-bold uppercase">Visit Date</p>
                <p class="font-bold"><?php echo date('M d, Y', strtotime($visit['visit_date'] ?? 'now')); ?></p>
                <p class="text-gray-400 text-[8px] mt-1"><?php echo $visit['visit_type'] ?? 'Outpatient'; ?></p>
            </div>
        </div>

        <form method="POST" class="p-10 space-y-10">
            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-red-600 rounded-full"></div>
                    <label class="text-xs font-semibold uppercase text-gray-400 tracking-widest">Summary of Care</label>
                </div>
                <textarea name="summary" rows="3" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-6 py-5 focus:ring-2 focus:ring-red-500 outline-none transition-all placeholder:text-gray-300"
                    placeholder="Summarize the care provided during this visit..."></textarea>
            </div>

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-red-600 rounded-full"></div>
                    <label class="text-xs font-semibold uppercase text-gray-400 tracking-widest">Condition at
                        Discharge</label>
                </div>
                <textarea name="condition" rows="3" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-6 py-5 focus:ring-2 focus:ring-red-500 outline-none transition-all placeholder:text-gray-300"
                    placeholder="Describe patient's condition at discharge..."></textarea>
            </div>

            <div class="space-y-4">
                <div class="flex items-center gap-3">
                    <div class="w-1.5 h-6 bg-red-600 rounded-full"></div>
                    <label class="text-xs font-semibold uppercase text-gray-400 tracking-widest">Post-Discharge
                        Instructions</label>
                </div>
                <textarea name="instructions" rows="3" required
                    class="w-full bg-gray-50 border-none rounded-2xl px-6 py-5 focus:ring-2 focus:ring-red-500 outline-none transition-all placeholder:text-gray-300"
                    placeholder="Medications, restrictions, follow-up care..."></textarea>
            </div>

            <div class="grid grid-cols-2 gap-6 pt-6">
                <div class="space-y-2">
                    <label
                        class="text-[10px] font-semibold text-gray-400 tracking-wide uppercase ml-2 text-center block">
                        Follow-up Appointment
                    </label>
                    <input type="date" name="follow_up"
                        class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-red-500 outline-none font-bold text-gray-700">
                </div>
                <div class="flex items-end">
                    <button type="submit" name="process_discharge"
                        class="w-full py-4 bg-red-600 text-white rounded-2xl font-bold shadow-md hover:bg-red-700 hover:shadow-lg transition-all flex items-center justify-center gap-2">
                        <i data-lucide="check-circle-2" class="w-5 h-5"></i> Finalize & Discharge
                    </button>
                </div>
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
</script>