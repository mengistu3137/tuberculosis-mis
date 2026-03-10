<?php
/**
 * TB REGIMEN PRESCRIPTION MODULE
 * Includes TB phase and follow-up scheduling metadata.
 */

$statusMsg = "";
$messageType = "teal";

$patient_id = $_GET['id'] ?? "";
$visit_id = $_GET['vid'] ?? "";
$patient = $patientObj->getById($patient_id);

if (!$patient) {
    echo "<div class='p-10 text-center font-semibold text-gray-400 uppercase tracking-wider'>Error: Patient record not found.</div>";
    exit;
}

$hasActiveVisit = ($visit_id !== "NO_ACTIVE_VISIT" && !empty($visit_id));
$frequencies = ["1x1 (Once Daily)", "1x2 (Twice Daily)", "1x3 (Three Daily)", "Before Meals", "Stat (Immediate)"];
$routes = ["Oral", "IV Injection", "Topical", "Inhalation"];
$tbPhases = ["Intensive Phase", "Continuation Phase", "MDR-TB Phase", "Preventive Therapy"];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_rx'])) {
    if (!$hasActiveVisit) {
        // This will show if the user somehow submits without a visit
        header("Location: index.php?page=prescribe&id=$patient_id&vid=$visit_id&status=rx_failed");
        exit;
    } else {
        $full_dosage = $_POST['dosage_amount'] . " - " . $_POST['frequency'];
        $success = $clinicalObj->addPrescription(
            $visit_id,
            $_POST['medication_name'],
            $full_dosage,
            $_SESSION['user_id'],
            $_POST['tb_phase'] ?? null,
            $_POST['next_followup_date'] ?? null
        );

        if ($success) {
            // SUCCESS: Redirect back to consultation with specific status
            echo "<script>window.location.href='index.php?page=consultation&id=$patient_id&vid=$visit_id&status=rx_sent';</script>";
            exit;
        } else {
            // FAILURE: Redirect back to consultation with error status
            echo "<script>window.location.href='index.php?page=consultation&id=$patient_id&vid=$visit_id&status=rx_failed';</script>";
            exit;
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex items-center gap-4">
        <a href="index.php?page=consultation&id=<?php echo $patient_id; ?>&vid=<?php echo $visit_id; ?>"
            class="p-3 bg-white border border-gray-100 rounded-2xl text-gray-400 hover:text-teal-600 shadow-sm transition-all">
            <i data-lucide="arrow-left"></i>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-gray-800 tracking-tight">Electronic
                TB Regimen Prescription</h1>
            <p class="text-[10px] text-emerald-700 font-bold uppercase tracking-widest mt-1">TB Drug Unit Integration</p>
        </div>
    </div>

    <?php if (!$hasActiveVisit): ?>
        <div class="p-12 bg-teal-50 border-2 border-dashed border-teal-200 rounded-2xl text-center">
            <div class="w-16 h-16 bg-teal-100 text-teal-600 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i data-lucide="alert-triangle" class="w-8 h-8"></i>
            </div>
            <h2 class="text-lg font-bold text-teal-800 uppercase tracking-tighter">Visit Required</h2>
            <p class="text-xs text-teal-600/70 mb-6 font-medium">Authorizations require an active medical visit link.</p>
            <a href="index.php?page=records"
                class="px-8 py-3 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase shadow-lg hover:bg-teal-700 transition-all">Registry</a>
        </div>
    <?php else: ?>
        <div class="bg-white rounded-2xl p-10 border border-gray-100 shadow-sm relative overflow-hidden">
            <i data-lucide="pill" class="absolute -right-10 -bottom-10 w-48 h-48 text-gray-50 opacity-50 rotate-12"></i>

            <div class="flex items-center justify-between mb-10 pb-6 border-b border-gray-50 relative z-10">
                <div class="flex items-center gap-4">
                    <div
                        class="w-12 h-12 bg-teal-600 text-white rounded-2xl flex items-center justify-center font-bold italic shadow-md">
                        Rx</div>
                    <div>
                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">Authorized For</p>
                        <h2 class="text-lg font-bold text-gray-800">
                            <?php echo $patient['full_name']; ?>
                            <span
                                class="text-teal-500 ml-2 font-semibold tracking-normal">(<?php echo $patient['medical_record_number']; ?>)</span>
                        </h2>
                    </div>
                </div>
            </div>

            <form method="POST" class="space-y-8 relative z-10">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    <div class="space-y-2">
                        <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">Medication
                            Name</label>
                        <input type="text" name="medication_name" required
                            class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-bold text-gray-700 shadow-inner transition-all"
                            placeholder="E.g. Amoxicillin">
                    </div>
                    <div class="space-y-2">
                        <label
                            class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">Strength/Dosage</label>
                        <input type="text" name="dosage_amount" required
                            class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-bold text-gray-700 shadow-inner transition-all"
                            placeholder="E.g. 500mg">
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">Frequency</label>
                        <select name="frequency" required
                            class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-semibold text-xs uppercase tracking-widest bg-white cursor-pointer shadow-inner">
                            <?php foreach ($frequencies as $f): ?>
                                <option value="<?php echo $f; ?>"><?php echo $f; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-2">
                        <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">Route</label>
                        <select name="route" required
                            class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-semibold text-xs uppercase tracking-widest bg-white cursor-pointer shadow-inner">
                            <?php foreach ($routes as $r): ?>
                                <option value="<?php echo $r; ?>"><?php echo $r; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">TB Treatment Phase</label>
                    <select name="tb_phase" required
                        class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-semibold text-xs uppercase tracking-widest bg-white cursor-pointer shadow-inner">
                        <?php foreach ($tbPhases as $phase): ?>
                            <option value="<?php echo $phase; ?>"><?php echo $phase; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">Next Follow-up Date</label>
                    <input type="date" name="next_followup_date"
                        class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-bold text-gray-700 shadow-inner transition-all">
                </div>

                <div class="space-y-2 md:col-span-2">
                    <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">Clinical
                        Instructions</label>
                    <textarea rows="3" name="instructions"
                        class="w-full bg-gray-50 border-none rounded-xl px-6 py-4 focus:ring-2 focus:ring-teal-500 font-medium text-gray-600 shadow-inner transition-all"
                        placeholder="Notes for pharmacist..."></textarea>
                </div>

                <button type="submit" name="submit_rx"
                    class="w-full py-5 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase tracking-wider shadow-md hover:bg-teal-700 hover:shadow-lg transition-all flex items-center justify-center gap-3 active:scale-95">
                    <i data-lucide="send" class="w-5 h-5"></i> Dispatch Rx
                </button>
            </form>
        </div>
    <?php endif; ?>
</div>
<script>lucide.createIcons();</script>