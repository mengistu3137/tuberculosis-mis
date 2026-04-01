<?php
// Access control - only radiologists and doctors can view
if (!in_array($_SESSION['role'], ['Radiologist', 'Doctor', 'Admin'])) {
    echo "<div class='p-10 text-red-500 font-bold uppercase tracking-widest text-center'>Access Denied</div>";
    exit;
}

$role = $_SESSION['role'];

// Handle result submission via AJAX/Modal
$msg = "";
$msgType = "teal";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_radiology_result'])) {
    $result_id = "RADRES-" . strtoupper(substr(md5(uniqid()), 0, 8));
    $request_id = $_POST['request_id'];

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

// Pagination
$limit = 10;
$current_page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
$offset = ($current_page - 1) * $limit;

// Count total pending requests
$total_query = "SELECT COUNT(*) FROM radiology_requests WHERE status = 'pending'";
$total_pending = $db->query($total_query)->fetchColumn();

// Fetch pending radiology requests
$query = "SELECT rr.*, p.full_name as patient_name, p.medical_record_number, p.patient_id,
          u.full_name as doctor_name, v.visit_type, v.visit_id
          FROM radiology_requests rr
          JOIN medical_visits v ON rr.visit_id = v.visit_id
          JOIN patients p ON v.patient_id = p.patient_id
          JOIN users u ON rr.doctor_id = u.user_id
          WHERE rr.status = 'pending'
          ORDER BY 
            CASE rr.priority WHEN 'STAT' THEN 1 ELSE 2 END,
            rr.created_at ASC
          LIMIT :limit OFFSET :offset";

$stmt = $db->prepare($query);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total_pages = ceil($total_pending / $limit);
?>

<div class="space-y-6">
    <!-- Notification -->
    <?php if ($msg): ?>
        <div id="notification-alert"
            class="p-4 bg-<?php echo $msgType; ?>-50 text-<?php echo $msgType; ?>-700 rounded-2xl border border-<?php echo $msgType; ?>-100 font-bold text-xs flex items-center justify-between gap-3 animate-in fade-in slide-in-from-top-2 shadow-sm mb-4">
            <div class="flex items-center gap-3">
                <i data-lucide="info" class="w-4 h-4"></i>
                <span><?php echo $msg; ?></span>
            </div>
            <button onclick="closeNotification()"
                class="p-1 hover:bg-<?php echo $msgType; ?>-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-4 h-4"></i>
            </button>
        </div>
    <?php endif; ?>

    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 uppercase tracking-tighter">Radiology Department</h1>
            <p class="text-[10px] text-fuchsia-600 font-bold uppercase tracking-widest mt-1">
                <i data-lucide="scan" class="w-3 h-3 inline mr-1"></i>
                Pending Imaging Requests
            </p>
        </div>
        <div class="text-right">
            <span class="px-4 py-2 bg-fuchsia-100 text-fuchsia-600 rounded-xl font-semibold text-sm">
                <?php echo $total_pending; ?> Pending
            </span>
        </div>
    </div>

    <!-- Requests Table -->
    <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
        <table class="w-full text-left">
            <thead
                class="bg-gray-50/50 text-[10px] font-medium text-gray-500 tracking-wide border-b border-gray-100">
                <tr>
                    <th class="px-8 py-5">Patient</th>
                    <th class="px-8 py-5">Exam Details</th>
                    <th class="px-8 py-5">Requested By</th>
                    <th class="px-8 py-5">Priority</th>
                    <th class="px-8 py-5">Status</th>
                    <th class="px-8 py-5 text-right">Action</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-50">
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="6" class="p-20 text-center">
                            <i data-lucide="scan" class="w-12 h-12 text-gray-200 mx-auto mb-4"></i>
                            <p class="text-sm font-semibold text-gray-400 tracking-wide">No pending requests</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req):
                        $priorityColor = ($req['priority'] == 'STAT') ? 'red' : 'fuchsia';
                        ?>
                        <tr class="hover:bg-fuchsia-50/20 transition-all group">
                            <td class="px-8 py-5">
                                <p class="font-bold text-gray-800"><?php echo $req['patient_name']; ?></p>
                                <p class="text-[8px] font-medium text-gray-400 mt-1"><?php echo $req['medical_record_number']; ?>
                                </p>
                            </td>
                            <td class="px-8 py-5">
                                <p class="font-bold text-gray-700"><?php echo $req['exam_type']; ?></p>
                                <?php if ($req['body_part']): ?>
                                    <p class="text-[9px] text-gray-500"><?php echo $req['body_part']; ?></p>
                                <?php endif; ?>
                            </td>
                            <td class="px-8 py-5">
                                <p class="text-xs font-bold text-gray-700">Dr. <?php echo $req['doctor_name']; ?></p>
                            </td>
                            <td class="px-8 py-5">
                                <span
                                    class="px-2 py-1 bg-<?php echo $priorityColor; ?>-100 text-<?php echo $priorityColor; ?>-600 text-[8px] font-medium rounded-full">
                                    <?php echo $req['priority']; ?>
                                </span>
                            </td>
                            <td class="px-8 py-5">
                                <span class="px-2 py-1 bg-amber-100 text-amber-600 text-[8px] font-medium rounded-full">
                                    Pending
                                </span>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button onclick="openResultModal(
                                    '<?php echo $req['request_id']; ?>',
                                    '<?php echo addslashes($req['patient_name']); ?>',
                                    '<?php echo $req['medical_record_number']; ?>',
                                    '<?php echo addslashes($req['exam_type']); ?>',
                                    '<?php echo addslashes($req['body_part']); ?>',
                                    '<?php echo addslashes($req['clinical_history']); ?>',
                                    '<?php echo $req['priority']; ?>'
                                )"
                                    class="inline-flex items-center gap-2 px-4 py-2 bg-primary-600 text-white rounded-xl font-medium text-[8px] uppercase tracking-widest hover:bg-teal-700transition-all">
                                    <i data-lucide="upload" class="w-3 h-3"></i> Enter Results
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[9px] font-semibold text-gray-400 uppercase tracking-wider">
                    Page <?php echo $current_page; ?> of <?php echo $total_pages; ?>
                </span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="index.php?page=radiology&p=<?php echo $i; ?>"
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-semibold text-[10px] transition-all <?php echo ($i == $current_page) ? 'bg-fuchsia-600 text-white' : 'bg-white text-gray-400 border border-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Radiology Results Entry Modal -->
<div id="radiologyResultModal" class="fixed inset-0 z-[100] hidden">
    
<div class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity" onclick="closeResultModal()"></div>

<div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full max-w-3xl bg-white rounded-2xl shadow-xl p-8 animate-in zoom-in-95 duration-200 max-h-[90vh] overflow-y-auto custom-scrollbar">

<!-- Modal Header -->
<div class="sticky -top-8 bg-white pt-2 pb-4 z-10 border-b border-gray-100 mb-6">

<div class="flex justify-between items-start">

<div class="flex items-center gap-4">

<div class="w-14 h-14 bg-teal-600 text-white rounded-2xl flex items-center justify-center shadow-lg">
<i data-lucide="scan" class="w-7 h-7"></i>
</div>

<div>
<h2 class="text-2xl font-bold text-gray-800 tracking-tight">Enter Radiology Results</h2>
<p id="modalPatientInfo" class="text-[10px] text-gray-500 font-bold mt-1">Loading...</p>
</div>

</div>

<button onclick="closeResultModal()"
class="text-gray-400 hover:text-red-500 transition-colors p-2 hover:bg-gray-100 rounded-xl">
<i data-lucide="x" class="w-6 h-6"></i>
</button>

</div>
</div>


<!-- Scrollable Content -->
<div class="space-y-6">

<!-- Request Summary Card -->
<div class="bg-gradient-to-r from-teal-50 to-teal-100 rounded-2xl p-6 border border-teal-100">

<div class="grid grid-cols-2 md:grid-cols-4 gap-4">

<div>
<p class="text-[8px] font-semibold text-teal-600 uppercase">Exam Type</p>
<p id="modalExamType" class="font-bold text-gray-800">-</p>
</div>

<div>
<p class="text-[8px] font-semibold text-teal-600 uppercase">Body Part</p>
<p id="modalBodyPart" class="font-bold text-gray-800">-</p>
</div>

<div>
<p class="text-[8px] font-semibold text-teal-600 uppercase">Priority</p>
<p id="modalPriority" class="font-bold text-gray-800">-</p>
</div>

<div>
<p class="text-[8px] font-semibold text-teal-600 uppercase">Request ID</p>
<p id="modalRequestId" class="font-bold text-gray-800">-</p>
</div>

</div>

<div id="modalClinicalHistory" class="mt-4 p-3 bg-white rounded-xl text-sm text-gray-600 hidden">
</div>

</div>


<!-- Results Form -->
<form method="POST" enctype="multipart/form-data" id="radiologyForm">

<input type="hidden" name="request_id" id="requestId">
<input type="hidden" name="save_radiology_result" value="1">

<div class="space-y-4">

<div>
<label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider ml-2 block mb-2">
Findings <span class="text-red-400">*</span>
</label>

<textarea
name="findings"
id="findings"
rows="4"
required
class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-teal-500 font-medium text-gray-700"
placeholder="Describe radiological findings in detail..."></textarea>
</div>


<div>
<label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider ml-2 block mb-2">
Impression / Conclusion <span class="text-red-400">*</span>
</label>

<textarea
name="impression"
id="impression"
rows="3"
required
class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-teal-500 font-medium text-gray-700"
placeholder="Clinical impression and conclusions..."></textarea>

</div>


<div>
<label class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider ml-2 block mb-2">
Upload Image (Optional)
</label>

<div class="relative">

<input
type="file"
name="image"
accept="image/*"
id="imageUpload"
class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-teal-500 text-sm file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-[8px] file:font-semibold file:bg-teal-600 file:text-white hover:file:bg-teal-700">

</div>

<p class="text-[8px] text-gray-400 mt-1 ml-2">
Accepted formats: JPG, PNG, GIF (Max: 5MB)
</p>

</div>

</div>

</form>

</div>


<!-- Footer -->
<div class="sticky bottom-0 bg-white pt-4 pb-2 mt-6 border-t border-gray-100">

<div class="flex justify-end gap-4">

<button
type="button"
onclick="closeResultModal()"
class="px-8 py-4 bg-gray-100 text-gray-600 rounded-xl font-semibold text-[10px] uppercase hover:bg-gray-200 transition-all">
Cancel
</button>

<button
type="submit"
form="radiologyForm"
id="submitRadiologyBtn"
class="px-8 py-4 bg-teal-600 text-white rounded-xl font-semibold text-[10px] uppercase tracking-widest shadow-lg hover:bg-teal-700 transition-all flex items-center gap-2">

<i data-lucide="save" class="w-4 h-4"></i>
Save Results

</button>

</div>

</div>

</div>
</div>

<!-- Add custom scrollbar styles -->


<script>
    lucide.createIcons();

    // Notification close function
    function closeNotification() {
        const alert = document.getElementById('notification-alert');
        if (alert) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateY(-20px)';
            setTimeout(() => alert.remove(), 500);
        }
    }

    // Auto-hide notification after 5 seconds
    setTimeout(closeNotification, 5000);

    // Modal functions
    function openResultModal(requestId, patientName, mrn, examType, bodyPart, clinicalHistory, priority) {
        document.getElementById('requestId').value = requestId;
        document.getElementById('modalPatientInfo').innerHTML = `${patientName} <span class="text-fuchsia-600 ml-2">${mrn}</span>`;
        document.getElementById('modalExamType').innerText = examType;
        document.getElementById('modalBodyPart').innerText = bodyPart || 'Not specified';
        document.getElementById('modalRequestId').innerText = requestId;

        // Set priority with color
        const priorityEl = document.getElementById('modalPriority');
        priorityEl.innerText = priority;
        priorityEl.className = priority === 'STAT' ? 'font-bold text-red-600' : 'font-bold text-gray-800';

        // Show clinical history if exists
        const historyEl = document.getElementById('modalClinicalHistory');
        if (clinicalHistory && clinicalHistory.trim() !== '') {
            historyEl.innerHTML = `<span class="text-[8px] font-semibold text-fuchsia-600 uppercase block mb-1">Clinical History</span>${clinicalHistory}`;
            historyEl.classList.remove('hidden');
        } else {
            historyEl.classList.add('hidden');
        }

        // Clear previous form values
        document.getElementById('findings').value = '';
        document.getElementById('impression').value = '';
        document.getElementById('imageUpload').value = '';

        // Show modal
        document.getElementById('radiologyResultModal').classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    }

    function closeResultModal() {
        document.getElementById('radiologyResultModal').classList.add('hidden');
        document.body.style.overflow = 'auto';
    }

    // Close modal when clicking outside
    document.getElementById('radiologyResultModal')?.addEventListener('click', function (e) {
        if (e.target === this) {
            closeResultModal();
        }
    });

    // Close modal with Escape key
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            const modal = document.getElementById('radiologyResultModal');
            if (!modal.classList.contains('hidden')) {
                closeResultModal();
            }
        }
    });
</script>