<?php
// Access control
if (!in_array($_SESSION['role'], ['Radiologist', 'Admin'])) {
    echo "<div class='p-10 text-red-500 font-black uppercase tracking-widest text-center'>Access Denied</div>";
    exit;
}

$request_id = $_GET['id'] ?? "";

// Fetch request details
$query = "SELECT rr.*, p.full_name as patient_name, p.medical_record_number, p.patient_id,
          u.full_name as doctor_name, v.visit_id
          FROM radiology_requests rr
          JOIN medical_visits v ON rr.visit_id = v.visit_id
          JOIN patients p ON v.patient_id = p.patient_id
          JOIN users u ON rr.doctor_id = u.user_id
          WHERE rr.request_id = ?";

$stmt = $db->prepare($query);
$stmt->execute([$request_id]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$request) {
    echo "<div class='p-10 text-center font-black text-gray-400'>Request not found</div>";
    exit;
}

$msg = "";
$msgType = "blue";

// Handle result submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_result'])) {
    $result_id = "RADRES-" . strtoupper(substr(md5(uniqid()), 0, 8));

    // Handle file upload
    $image_path = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $upload_dir = 'uploads/radiology/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = $request_id . '_' . time() . '.' . $extension;
        $target_path = $upload_dir . $filename;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $target_path)) {
            $image_path = $target_path;
        }
    }

    // Insert result
    $insert = "INSERT INTO radiology_results 
               (result_id, request_id, radiologist_id, findings, impression, image_path, performed_date)
               VALUES (?, ?, ?, ?, ?, ?, ?)";

    $stmt = $db->prepare($insert);
    $result = $stmt->execute([
        $result_id,
        $request_id,
        $_SESSION['user_id'],
        $_POST['findings'],
        $_POST['impression'],
        $image_path,
        date('Y-m-d')
    ]);

    if ($result) {
        // Update request status
        $update = $db->prepare("UPDATE radiology_requests SET status = 'completed', updated_at = NOW() WHERE request_id = ?");
        $update->execute([$request_id]);

        $msg = "Radiology results saved successfully.";
        $msgType = "emerald";
    } else {
        $msg = "Error saving results.";
        $msgType = "red";
    }
}
?>

<div class="max-w-4xl mx-auto space-y-6">
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

    <!-- Patient Info Card -->
    <div class="bg-gradient-to-r from-fuchsia-600 to-purple-600 rounded-[2.5rem] p-8 text-white shadow-xl">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-black mb-2">Radiology Results Entry</h1>
                <p class="text-white/80 text-sm"><?php echo $request['patient_name']; ?> •
                    <?php echo $request['medical_record_number']; ?></p>
            </div>
            <div class="text-right">
                <span class="px-4 py-2 bg-white/20 rounded-xl text-xs font-black uppercase">Request
                    #<?php echo $request_id; ?></span>
            </div>
        </div>
    </div>

    <!-- Request Details -->
    <div class="bg-white rounded-[2.5rem] p-8 border border-gray-100 shadow-sm">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-[8px] font-black text-gray-400 uppercase">Exam Type</p>
                <p class="font-black text-gray-800"><?php echo $request['exam_type']; ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-[8px] font-black text-gray-400 uppercase">Body Part</p>
                <p class="font-black text-gray-800"><?php echo $request['body_part'] ?? 'Not specified'; ?></p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-[8px] font-black text-gray-400 uppercase">Priority</p>
                <p
                    class="font-black <?php echo ($request['priority'] == 'STAT') ? 'text-red-600' : 'text-gray-800'; ?>">
                    <?php echo $request['priority']; ?>
                </p>
            </div>
            <div class="bg-gray-50 p-4 rounded-xl">
                <p class="text-[8px] font-black text-gray-400 uppercase">Requested By</p>
                <p class="font-black text-gray-800">Dr. <?php echo $request['doctor_name']; ?></p>
            </div>
        </div>

        <?php if ($request['clinical_history']): ?>
            <div class="mb-6 p-4 bg-blue-50 rounded-xl">
                <p class="text-[8px] font-black text-blue-600 uppercase mb-1">Clinical History</p>
                <p class="text-sm text-gray-700"><?php echo $request['clinical_history']; ?></p>
            </div>
        <?php endif; ?>

        <!-- Results Form -->
        <form method="POST" enctype="multipart/form-data" class="space-y-6">
            <div class="space-y-4">
                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2 block mb-2">
                        Findings <span class="text-red-400">*</span>
                    </label>
                    <textarea name="findings" rows="4" required
                        class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-fuchsia-500 font-medium text-gray-700"
                        placeholder="Describe radiological findings..."></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2 block mb-2">
                        Impression / Conclusion <span class="text-red-400">*</span>
                    </label>
                    <textarea name="impression" rows="3" required
                        class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-fuchsia-500 font-medium text-gray-700"
                        placeholder="Clinical impression..."></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest ml-2 block mb-2">
                        Upload Image (Optional)
                    </label>
                    <input type="file" name="image" accept="image/*"
                        class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-fuchsia-500 text-sm">
                </div>
            </div>

            <div class="flex sticky -bottom-10 justify-end gap-4 pt-4 border-t border-gray-100">
                <a href="index.php?page=radiology"
                    class="px-8 py-4 bg-gray-100 text-gray-600 rounded-xl font-black text-[10px] uppercase hover:bg-gray-200 transition-all">
                    Cancel
                </a>
                <button type="submit" name="save_result"
                    class="px-8 py-4 bg-fuchsia-600 text-white rounded-xl font-black text-[10px] uppercase tracking-widest shadow-lg hover:bg-fuchsia-700 transition-all flex items-center gap-2">
                    <i data-lucide="save" class="w-4 h-4"></i> Save Results
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    lucide.createIcons();

    setTimeout(function () {
        const alert = document.getElementById('notification-alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }
    }, 5000);
</script>