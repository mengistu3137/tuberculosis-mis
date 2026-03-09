<?php
/**
 * PHARMACY DISPENSING MODULE (TABLE UI)
 * Brand: Mattu Medical Blue
 */

$msg = "";
$msgType = "blue";

// 1. Handle the Dispense Action (Committing to DB)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_dispense'])) {
    $rx_id = $_POST['prescription_id'];
    $success = $pharmacyObj->dispenseMedication($rx_id, $_SESSION['user_id']);

    if ($success) {
        $msg = "Medication successfully dispensed and logged.";
        $msgType = "emerald";
    } else {
        $msg = "System Error: Transaction could not be completed.";
        $msgType = "red";
    }
}

// 2. Fetch Real Data from global $pharmacyObj
$stmt = $pharmacyObj->getPendingPrescriptions();
$prescriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
$pendingCount = count($prescriptions);
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-gray-100 pb-6">
        <div>
            <h1 class="text-xl font-black text-gray-800 tracking-tighter uppercase italic">Pharmacy Queue</h1>
            <p class="text-[10px] text-blue-600 font-bold uppercase tracking-widest mt-1">Dispensing Infrastructure • Live Orders</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="px-5 py-2.5 bg-blue-600 text-white rounded-2xl shadow-lg shadow-blue-100 flex items-center gap-2 transition-all">
                <i data-lucide="package-search" class="w-4 h-4"></i>
                <span class="text-[10px] font-black uppercase tracking-widest"><?php echo $pendingCount; ?> Pending Rx</span>
            </div>
        </div>
    </div>

    <?php if ($msg): ?>
        <div class="p-4 bg-<?php echo $msgType; ?>-50 text-<?php echo $msgType; ?>-700 rounded-2xl border border-<?php echo $msgType; ?>-100 font-bold text-xs flex items-center gap-3 animate-in fade-in slide-in-from-top-2">
            <i data-lucide="info" class="w-4 h-4"></i>
            <span><?php echo $msg; ?></span>
        </div>
    <?php endif; ?>

    <!-- Table Container -->
    <div class="bg-white rounded-[2.5rem] shadow-sm border border-gray-100 overflow-hidden transition-all">
        <table class="w-full text-left">
            <thead class="bg-blue-50/50 border-b border-blue-100">
                <tr>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Patient Profile</th>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Prescribed Medication</th>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em]">Request Date</th>
                    <th class="px-8 py-5 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if ($pendingCount > 0): ?>
                    <?php foreach ($prescriptions as $rx): ?>
                        <tr class="hover:bg-blue-50/20 transition-all group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div class="w-11 h-11 rounded-2xl bg-blue-600 text-white flex items-center justify-center font-black text-sm shadow-md group-hover:scale-110 transition-transform">
                                        <?php echo strtoupper(substr($rx['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="font-black text-gray-800 text-sm italic"><?php echo $rx['full_name']; ?></p>
                                        <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mt-1"><?php echo $rx['medical_record_number']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6">
                                <span class="px-3 py-1.5 bg-blue-50 text-blue-700 text-[10px] font-black uppercase rounded-lg border border-blue-100 italic">
                                    <?php echo $rx['medication_name']; ?>
                                </span>
                                <p class="text-[9px] font-bold text-gray-400 mt-2 uppercase tracking-tighter"><?php echo $rx['dosage']; ?></p>
                            </td>
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-2">
                                    <i data-lucide="clock" class="w-3 h-3 text-gray-300"></i>
                                    <span class="text-xs font-bold text-gray-500"><?php echo date("M d, Y | H:i", strtotime($rx['created_at'])); ?></span>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <button type="button"
                                    onclick="showPrescriptionDetail(<?php echo htmlspecialchars(json_encode($rx)); ?>)"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-600 shadow-md transition-all active:scale-95">
                                    Process Rx <i data-lucide="external-link" class="w-3 h-3"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" class="p-24 text-center opacity-30">
                            <div class="flex flex-col items-center">
                                <i data-lucide="inbox" class="w-12 h-12 mb-4"></i>
                                <p class="text-xs font-black uppercase tracking-[0.3em]">No pending dispensations</p>
                            </div>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODERN DISPENSING MODAL -->
<div id="rxModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-md transition-all duration-300">
    <div id="rxCard" class="bg-white rounded-[3rem] w-full max-w-md p-10 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300">

        <div class="flex justify-between items-start mb-8">
            <div class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black shadow-lg shadow-blue-100 italic">Rx</div>
            <button onclick="closeRxModal()" class="text-gray-300 hover:text-red-500 transition-colors"><i data-lucide="x-circle"></i></button>
        </div>

        <div class="space-y-6 mb-10">
            <div>
                <p class="text-[9px] font-black text-gray-400 uppercase tracking-widest mb-1">Patient Subject</p>
                <h3 id="modalPatientName" class="text-xl font-black text-gray-800 uppercase italic">--</h3>
                <p id="modalMRN" class="text-[10px] font-bold text-blue-500 uppercase tracking-tighter">--</p>
            </div>

            <div class="bg-blue-50 p-6 rounded-[2rem] border border-blue-100">
                <p class="text-[9px] font-black text-blue-400 uppercase tracking-widest mb-3">Order Details</p>
                <div class="space-y-4">
                    <div class="flex justify-between border-b border-blue-100/50 pb-2">
                        <span class="text-[10px] font-bold text-blue-600/50 uppercase">Medication</span>
                        <span id="modalMed" class="text-sm font-black text-blue-700 uppercase">--</span>
                    </div>
                    <div class="flex justify-between border-b border-blue-100/50 pb-2">
                        <span class="text-[10px] font-bold text-blue-600/50 uppercase">Regimen</span>
                        <span id="modalDose" class="text-sm font-black text-blue-700 uppercase">--</span>
                    </div>
                    <!-- NEW: PRESCRIBED BY SECTION -->
                    <div class="flex justify-between">
                        <span class="text-[10px] font-bold text-blue-600/50 uppercase">Prescribed By</span>
                        <span id="modalDoctor" class="text-[10px] font-black text-blue-700 uppercase italic">--</span>
                    </div>
                </div>
            </div>
        </div>

        <form method="POST">
            <input type="hidden" name="prescription_id" id="modalRxID">
            <button type="submit" name="confirm_dispense"
                class="w-full py-5 bg-blue-600 text-white rounded-[2rem] font-black text-[10px] uppercase tracking-[0.3em] shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all flex items-center justify-center gap-3 active:scale-95">
                <i data-lucide="package-check" class="w-5 h-5"></i>
                Confirm & Record Dispensing
            </button>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    function showPrescriptionDetail(data) {
        document.getElementById('modalPatientName').innerText = data.full_name;
        document.getElementById('modalMRN').innerText = `ID: ${data.medical_record_number}`;
        document.getElementById('modalMed').innerText = data.medication_name;
        document.getElementById('modalDose').innerText = data.dosage;
        // Populate the new Doctor ID/Name field
        document.getElementById('modalDoctor').innerText = data.prescribed_by; 
        document.getElementById('modalRxID').value = data.prescription_id;

        const modal = document.getElementById('rxModal');
        const card = document.getElementById('rxCard');

        modal.classList.remove('hidden');
        requestAnimationFrame(() => {
            card.classList.remove('scale-95');
            card.classList.add('scale-100');
        });
    }

    function closeRxModal() {
        const modal = document.getElementById('rxModal');
        const card = document.getElementById('rxCard');
        card.classList.remove('scale-100');
        card.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }
</script>