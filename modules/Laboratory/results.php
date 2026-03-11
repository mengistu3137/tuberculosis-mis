<?php
/**
 * LAB RESULT ENTRY MODULE
 * Functional: Fetch Request Context + Submit Findings
 */

// 1. Initialize Lab Object
$labObj = new Lab($db);

// 2. Capture the Request ID from URL
$req_id = $_GET['req_id'] ?? "";

// 3. Fetch real details about this request
$details = $labObj->getRequestDetails($req_id);

// Guard: If request doesn't exist or is already completed
if (!$details) {
    echo "<div class='p-10 text-center font-semibold text-gray-400 uppercase tracking-wider'>Invalid Request ID or Record Not Found</div>";
    exit;
}

if ($details['status'] === 'completed') {
    echo "<script>window.location.href='index.php?page=laboratory&msg=already_completed';</script>";
    exit;
}

// 4. Handle Form Submission
$msg = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['finalize_result'])) {
    $result_text = trim($_POST['result_details']);

    if (empty($result_text)) {
        $msg = "Please enter clinical findings before finalizing.";
    } else {
        $success = $labObj->submitResult($req_id, $result_text);
        if ($success) {
            // Redirect to main worklist with success flag
            echo "<script>window.location.href='index.php?page=laboratory&status=success';</script>";
            exit;
        } else {
            $msg = "System Error: Could not save results to infrastructure.";
        }
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">

<!-- Header with Back Link -->
<div class="flex items-center gap-4">

<a href="index.php?page=laboratory"
class="p-3 bg-white border border-gray-100 rounded-2xl text-gray-400 hover:text-teal-600 shadow-sm transition-all">
<i data-lucide="arrow-left"></i>
</a>

<div>
<h1 class="text-2xl font-bold text-gray-800 tracking-tight">
Laboratory Result Input
</h1>

<p class="text-[10px] text-teal-600 font-bold uppercase tracking-widest mt-1">
Diagnostic Unit • Official Report
</p>
</div>

</div>


<!-- Error Messaging -->
<?php if ($msg): ?>

        <div
            class="p-4 bg-red-50 text-red-600 rounded-2xl border border-red-100 font-bold text-xs animate-in fade-in slide-in-from-top-2">
            <i data-lucide="alert-circle" class="w-4 h-4 mr-2 inline"></i>
            <?php echo $msg; ?>
        </div>

    <?php endif; ?>


    <!-- PATIENT CONTEXT CARD -->
    <div
        class="bg-gradient-to-r from-teal-600 to-teal-500 rounded-2xl p-10 text-white shadow-xl shadow-teal-200 relative overflow-hidden">

        <!-- Background Icon -->
        <i data-lucide="microscope" class="absolute -right-10 -bottom-10 w-64 h-64 text-white/10 rotate-12"></i>

        <div class="relative z-10 flex flex-col md:flex-row justify-between items-start md:items-center gap-6">

            <div>

                <p class="text-teal-200 text-xs font-semibold uppercase tracking-wide mb-2">
                    Subject Identity
                </p>

                <h2 class="text-3xl font-bold tracking-tight mb-1">
                    <?php echo $details['patient_name']; ?>
                </h2>

                <div class="flex items-center gap-3 mt-4">

                    <span
                        class="px-3 py-1 bg-white/20 rounded-lg text-[10px] font-semibold uppercase border border-white/10 italic">
                        MRN: <?php echo $details['medical_record_number']; ?>
                    </span>

                    <span
                        class="px-3 py-1 bg-white/20 rounded-lg text-[10px] font-semibold uppercase border border-white/10 italic">
                        REF: <?php echo $details['request_id']; ?>
                    </span>

                </div>

            </div>


            <div class="text-right">

                <p class="text-teal-200 text-xs font-semibold uppercase tracking-wide mb-1">
                    Ordered Test
                </p>

                <p class="text-xl font-bold text-white">
                    <?php echo $details['test_type']; ?>
                </p>

                <p class="text-[9px] font-bold text-teal-200 uppercase mt-2">
                    Date: <?php echo date("M d, Y", strtotime($details['request_date'])); ?>
                </p>

            </div>

        </div>
    </div>



    <!-- RESULT ENTRY FORM -->
    <div class="bg-white rounded-2xl p-10 border border-gray-100 shadow-sm relative">

        <div class="flex items-center gap-3 mb-8">
            <div class="w-1.5 h-6 bg-teal-600 rounded-full"></div>
            <h3 class="text-lg font-bold text-gray-800 uppercase tracking-tight">
                Clinical Findings & Data
            </h3>
        </div>


        <form method="POST" class="space-y-8">

            <div class="space-y-3">

                <label class="text-[10px] font-semibold text-teal-600 uppercase tracking-wide ml-4">
                    Technician's Observations
                </label>

                <textarea name="result_details" rows="8" required
                    class="w-full bg-gray-50 border-none rounded-xl px-8 py-8 focus:ring-2 focus:ring-teal-500 outline-none transition-all font-medium text-gray-700 placeholder:text-gray-300 shadow-inner"
                    placeholder="Enter quantitative or qualitative results here (e.g. WBC: 10.5, HGB: 14.2, Malaria: Negative)">
</textarea>

            </div>


            <div class="flex flex-col md:flex-row items-center justify-between pt-8 border-t border-gray-50 gap-6">

                <div class="flex items-center gap-3">

                    <div class="w-10 h-10 bg-gray-100 rounded-xl flex items-center justify-center text-gray-400">
                        <i data-lucide="user-check" class="w-5 h-5"></i>
                    </div>

                    <div>

                        <p class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider leading-none">
                            Authorized Technician
                        </p>

                        <p class="text-xs font-bold text-gray-700 mt-1">
                            <?php echo $_SESSION['full_name']; ?>
                        </p>

                    </div>

                </div>


                <button type="submit" name="finalize_result"
                    class="w-full md:w-auto px-16 py-5 bg-teal-600 text-white rounded-2xl font-semibold text-[10px] uppercase tracking-wider shadow-xl hover:bg-teal-700 transition-all transform active:scale-95 flex items-center justify-center gap-3">

                    <i data-lucide="check-square" class="w-5 h-5"></i>

                    Finalize Report

                </button>

            </div>

        </form>

    </div>

</div>

<script>
    lucide.createIcons();
</script>