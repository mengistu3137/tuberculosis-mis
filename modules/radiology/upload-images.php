<?php
// Access control - only radiology team
if (!in_array($_SESSION['role'], ['Radiologist', 'Doctor', 'Admin'])) {
    echo "<div class='p-10 text-red-500 font-bold uppercase tracking-widest text-center'>Access Denied</div>";
    exit;
}

$userId = $_SESSION['user_id'];
$msg = '';
$msgType = 'teal';

// Handle direct upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_radiology_image'])) {
    $requestId = $_POST['request_id'] ?? '';
    $findings = trim($_POST['findings'] ?? '');
    $impression = trim($_POST['impression'] ?? '');

    // Confirm request is pending and visible to this user
    $reqCheck = $db->prepare("SELECT rr.request_id, p.full_name AS patient_name, p.medical_record_number
        FROM radiology_requests rr
        JOIN medical_visits v ON rr.visit_id = v.visit_id
        JOIN patients p ON v.patient_id = p.patient_id
        WHERE rr.request_id = ? AND rr.status = 'pending' AND (rr.assigned_rad_id = ? OR rr.assigned_rad_id IS NULL)");
    $reqCheck->execute([$requestId, $userId]);
    $targetReq = $reqCheck->fetch(PDO::FETCH_ASSOC);

    if (!$targetReq) {
        $msg = 'Request not available or already completed.';
        $msgType = 'red';
    } elseif ($findings === '' || $impression === '') {
        $msg = 'Findings and impression are required.';
        $msgType = 'red';
    } elseif (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
        $msg = 'Please attach an imaging file to upload.';
        $msgType = 'red';
    } else {
        $allowedExt = ['png', 'jpg', 'jpeg', 'gif', 'bmp'];
        $extension = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));

        if (!in_array($extension, $allowedExt, true)) {
            $msg = 'Unsupported file type. Use PNG, JPG, GIF, or BMP.';
            $msgType = 'red';
        } else {
            $uploadDir = 'uploads/radiology/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $filename = $requestId . '_' . time() . '.' . $extension;
            $targetPath = $uploadDir . $filename;

            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
                $resultId = 'RADRES-' . strtoupper(substr(md5(uniqid()), 0, 8));

                $insert = $db->prepare("INSERT INTO radiology_results
                    (result_id, request_id, radiologist_id, findings, impression, image_path, performed_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?)");

                $saved = $insert->execute([
                    $resultId,
                    $requestId,
                    $userId,
                    $findings,
                    $impression,
                    $targetPath,
                    date('Y-m-d')
                ]);

                if ($saved) {
                    $update = $db->prepare("UPDATE radiology_requests SET status = 'completed', updated_at = NOW(), assigned_rad_id = ? WHERE request_id = ?");
                    $update->execute([$userId, $requestId]);

                    $msg = 'Image uploaded and result saved for ' . $targetReq['patient_name'] . ' (' . $targetReq['medical_record_number'] . ').';
                    $msgType = 'emerald';
                } else {
                    $msg = 'Error saving radiology result.';
                    $msgType = 'red';
                }
            } else {
                $msg = 'File upload failed. Please try again.';
                $msgType = 'red';
            }
        }
    }
}

// Fetch pending requests available to this radiologist
$queueSql = "SELECT rr.request_id, rr.exam_type, rr.body_part, rr.priority, rr.created_at,
    p.full_name AS patient_name, p.medical_record_number, u.full_name AS doctor_name
    FROM radiology_requests rr
    JOIN medical_visits v ON rr.visit_id = v.visit_id
    JOIN patients p ON v.patient_id = p.patient_id
    JOIN users u ON rr.doctor_id = u.user_id
    WHERE rr.status = 'pending' AND (rr.assigned_rad_id = :uid OR rr.assigned_rad_id IS NULL)
    ORDER BY CASE rr.priority WHEN 'STAT' THEN 1 ELSE 2 END, rr.created_at ASC";

$qStmt = $db->prepare($queueSql);
$qStmt->execute([':uid' => $userId]);
$pending = $qStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="space-y-6">
    <?php if ($msg): ?>
        <div id="notification-alert"
            class="p-4 bg-<?php echo $msgType; ?>-50 text-<?php echo $msgType; ?>-700 rounded-2xl border border-<?php echo $msgType; ?>-100 font-bold text-xs flex items-center justify-between gap-3 shadow-sm animate-in fade-in slide-in-from-top-2">
            <div class="flex items-center gap-3">
                <i data-lucide="info" class="w-4 h-4"></i>
                <span><?php echo $msg; ?></span>
            </div>
            <button onclick="closeNotification()" class="p-1 hover:bg-<?php echo $msgType; ?>-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    <?php endif; ?>

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 uppercase tracking-tighter">Upload Imaging Files</h1>
            <p class="text-[10px] text-teal-600 font-bold uppercase tracking-widest mt-1 flex items-center gap-2">
                <i data-lucide="upload" class="w-3 h-3"></i>
                Attach images and finalize results
            </p>
        </div>
        <a href="index.php?page=radiology"
            class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl font-semibold text-[10px] uppercase hover:bg-gray-200 transition-all flex items-center gap-2">
            <i data-lucide="clock" class="w-4 h-4"></i> Imaging Queue
        </a>
    </div>

    <div class="grid lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6 space-y-5">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 bg-teal-100 text-teal-700 rounded-xl flex items-center justify-center">
                    <i data-lucide="folder-plus" class="w-5 h-5"></i>
                </div>
                <div>
                    <h3 class="font-bold text-gray-800">New Upload</h3>
                    <p class="text-xs text-gray-500">Pick a pending request and attach imaging files.</p>
                </div>
            </div>

            <form method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider ml-1 block mb-2">Select Request</label>
                    <select name="request_id" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-teal-500">
                        <option value="">-- Choose pending request --</option>
                        <?php foreach ($pending as $req): ?>
                            <option value="<?php echo $req['request_id']; ?>">
                                <?php echo $req['request_id']; ?> • <?php echo $req['patient_name']; ?> (<?php echo $req['medical_record_number']; ?>) — <?php echo $req['exam_type']; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider ml-1 block mb-2">Findings</label>
                    <textarea name="findings" rows="3" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-teal-500" placeholder="Brief imaging findings..."></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider ml-1 block mb-2">Impression</label>
                    <textarea name="impression" rows="2" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-teal-500" placeholder="Clinical impression..."></textarea>
                </div>

                <div>
                    <label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider ml-1 block mb-2">Upload Image</label>
                    <input type="file" name="image" accept="image/*" required class="w-full px-4 py-3 bg-gray-50 border border-gray-100 rounded-xl text-sm focus:ring-2 focus:ring-teal-500">
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="reset" class="px-4 py-2 bg-gray-100 text-gray-600 rounded-xl font-semibold text-[10px] uppercase hover:bg-gray-200 transition-all">Clear</button>
                    <button type="submit" name="upload_radiology_image" class="px-5 py-2 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase tracking-widest hover:bg-teal-700 transition-all flex items-center gap-2">
                        <i data-lucide="upload-cloud" class="w-4 h-4"></i> Upload
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm p-6">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-gray-800">Pending Requests</h3>
                    <p class="text-xs text-gray-500">Filtered to your assignments or unassigned queue.</p>
                </div>
                <span class="px-3 py-1 bg-teal-50 text-teal-700 rounded-full text-[10px] font-semibold">
                    <?php echo count($pending); ?> Pending
                </span>
            </div>

            <div class="divide-y divide-gray-50">
                <?php if (empty($pending)): ?>
                    <div class="py-10 text-center text-gray-400 text-sm">No pending imaging requests.</div>
                <?php else: ?>
                    <?php foreach ($pending as $req): ?>
                        <div class="py-3 flex items-start gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center">
                                <i data-lucide="scan" class="w-4 h-4"></i>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2 text-sm font-semibold text-gray-800">
                                    <span><?php echo $req['patient_name']; ?></span>
                                    <span class="text-[10px] text-gray-400"><?php echo $req['medical_record_number']; ?></span>
                                </div>
                                <p class="text-xs text-gray-600 mt-0.5">Exam: <?php echo $req['exam_type']; ?><?php echo $req['body_part'] ? ' • ' . $req['body_part'] : ''; ?></p>
                                <p class="text-[10px] text-gray-500">Request #<?php echo $req['request_id']; ?> • Dr. <?php echo $req['doctor_name']; ?></p>
                            </div>
                            <div class="text-right">
                                <span class="px-2 py-1 bg-<?php echo ($req['priority'] === 'STAT') ? 'red' : 'fuchsia'; ?>-100 text-<?php echo ($req['priority'] === 'STAT') ? 'red' : 'fuchsia'; ?>-600 text-[10px] font-semibold rounded-full">
                                    <?php echo $req['priority']; ?>
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
    function closeNotification() {
        const alert = document.getElementById('notification-alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-10px)';
            setTimeout(() => alert.remove(), 300);
        }
    }

    setTimeout(closeNotification, 5000);
    lucide.createIcons();
</script>
