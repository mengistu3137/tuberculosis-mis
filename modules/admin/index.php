<?php
/**
 * ADMIN USER MANAGEMENT MODULE
 * Fully functional: CRUD, CSV Import, Bulk Actions, Pagination, Error Handling.
 */

if ($_SESSION['role'] !== 'Admin') {
    echo "<div class='p-10 text-red-500 font-bold uppercase tracking-widest'>Access Denied. Admins Only.</div>";
    exit;
}

 $message = "";
 $messageType = "teal"; 

// --- PAGINATION SETTINGS ---
 $limit = 7; 
 $page_num = isset($_GET['p']) ? (int) $_GET['p'] : 1;
// If a search is active, we should reset to page 1 unless the user specifically clicked a page link
if (!empty($_GET['q']) && !isset($_GET['p'])) {
    $page_num = 1;
}
 $offset = ($page_num - 1) * $limit;

// --- 1. HANDLE SYSTEM ACTIONS ---

// Single Delete Action
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    if ($userObj->delete($_GET['id'])) {
        $message = "Staff record successfully purged.";
        $messageType = "red";
    } else {
        $message = "Security Error: You cannot delete yourself.";
        $messageType = "orange";
    }
}

// Bulk Delete Action (Triggered by Modal)
if (isset($_POST['confirm_bulk_delete']) && !empty($_POST['selected_users'])) {
    if ($userObj->bulkDelete($_POST['selected_users'])) {
        $message = "Selected staff accounts removed.";
        $messageType = "red";
    }
}

// Inline Role Update
if (isset($_POST['update_role_btn'])) {
    $userObj->updateRole($_POST['user_id'], $_POST['role']);
    $message = "Role updated for " . $_POST['user_id'];
    $messageType = "teal";
}

// A. Catch the success status after redirect
if (isset($_GET['status']) && $_GET['status'] == 'success') {
    $msg = isset($_GET['msg']) ? urldecode($_GET['msg']) : "Operation successful.";
    $message = $msg;
    $messageType = "emerald";
}

// B. Create User (Modified with Redirect)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_user'])) {
    if ($userObj->create($_POST)) {
        // Preserve search term on redirect if it exists
        $qParam = isset($_GET['q']) ? "&q=" . urlencode($_GET['q']) : "";
        echo "<script>window.location.href='index.php?page=admin&status=success" . $qParam . "';</script>";
        exit;
    } else {
        $message = "Error: Email exists or data invalid. Record skipped.";
        $messageType = "orange";
    }
}


// CSV Bulk Import
if (isset($_POST['import_btn']) && isset($_FILES['excel_file'])) {
    $file = $_FILES['excel_file']['tmp_name'];

    // Check if file was uploaded successfully
    if ($_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        $message = "Error uploading file. Please try again.";
        $messageType = "red";
    } else {
        if (($handle = fopen($file, "r")) !== FALSE) {
            $success = 0;
            $skipped = 0;
            $lineNumber = 0;
            $headerSkipped = false;

            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                $lineNumber++;

                // Skip empty rows
                if (empty(array_filter($data))) {
                    continue;
                }

                // Check if this is the header row (first row)
                if (!$headerSkipped) {
                    $headerSkipped = true;

                    // Check if it's actually a header row (contains column names)
                    $firstCell = strtolower(trim($data[0] ?? ''));
                    if (
                        $firstCell === 'full_name' ||
                        strpos($firstCell, 'name') !== false ||
                        count($data) >= 4 && strtolower(trim($data[1] ?? '')) === 'email'
                    ) {
                        // This is likely a header row, skip it
                        continue;
                    } else {
                        // This appears to be actual data, so process it
                        // Reset the pointer to process this row
                        $headerSkipped = false;
                    }
                }

                // Validate that we have at least 4 columns
                if (count($data) < 4) {
                    $skipped++;
                    error_log("CSV Import: Skipped line $lineNumber - insufficient columns (found " . count($data) . ", need 4)");
                    continue;
                }

                // Clean and validate each field
                $full_name = trim($data[0] ?? '');
                $email = trim($data[1] ?? '');
                $password = trim($data[2] ?? '');
                $role = trim($data[3] ?? '');

                // Skip if any required field is empty
                if (empty($full_name) || empty($email) || empty($password) || empty($role)) {
                    $skipped++;
                    error_log("CSV Import: Skipped line $lineNumber - empty required field(s)");
                    continue;
                }

                // Skip if it looks like a header row being processed as data
                if (
                    strtolower($full_name) === 'full_name' ||
                    strtolower($email) === 'email' ||
                    strtolower($password) === 'password' ||
                    strtolower($role) === 'role'
                ) {
                    $skipped++;
                    error_log("CSV Import: Skipped line $lineNumber - appears to be header row");
                    continue;
                }

                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $skipped++;
                    error_log("CSV Import: Skipped line $lineNumber - invalid email format: $email");
                    continue;
                }

                // Validate role is acceptable
                $validRoles = ['Doctor', 'Nurse', 'Lab Technician', 'Pharmacist', 'Clerk', 'Admin', 'Radiologist'];
                if (!in_array($role, $validRoles)) {
                    $skipped++;
                    error_log("CSV Import: Skipped line $lineNumber - invalid role: $role");
                    continue;
                }

                $res = $userObj->create([
                    'full_name' => $full_name,
                    'email' => $email,
                    'password' => $password,
                    'role' => $role
                ]);

                if ($res) {
                    $success++;
                } else {
                    $skipped++;
                    error_log("CSV Import: Skipped line $lineNumber - database error (likely duplicate email)");
                }
            }
            fclose($handle);

            if ($success > 0) {
                $message = "Import results: $success staff added successfully";
                if ($skipped > 0) {
                    $message .= ", $skipped rows skipped (invalid or duplicate entries)";
                }
                $messageType = ($skipped > 0) ? "orange" : "emerald";
            } else {
                $message = "No valid entries found to import. $skipped rows were skipped.";
                $messageType = "orange";
            }
        } else {
            $message = "Error: Could not open uploaded file.";
            $messageType = "red";
        }
    }
}

// --- UPDATED STATUS CHANGE HANDLER ---
if (isset($_POST['confirm_status_change'])) {
    $uid = $_POST['target_uid'];
    $current_status = $_POST['current_status'];
    $reason = $_POST['reason'];
    $userObj->toggleStatusWithCare($uid, $current_status, $reason, $assignObj);

    $msg = (strpos($reason, 'Shift') !== false) ? "Shift Handover Successful. Patients Re-assigned." : "User status updated.";
    // Preserve search term
    $qParam = isset($_GET['q']) ? "&q=" . urlencode($_GET['q']) : "";
    echo "<script>window.location.href='index.php?page=admin&status=success&msg=" . urlencode($msg) . $qParam . "';</script>";
    exit;
}

// --- 2. FETCH PAGINATED DATA ---
 $searchTerm = $_GET['q'] ?? ""; 

// Use the methods that support the $searchTerm
 $total_users = $userObj->countAll($searchTerm);
 $total_pages = ceil($total_users / $limit);
 $stmt = $userObj->getPaginated($limit, $offset, $searchTerm);
?>

<div class="space-y-6">
    <!-- Header Section -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-gray-800 tracking-tight first-letter:uppercase ">TBMIS Staff Command Center</h1>
        <?php if ($message): ?>
            <div id="notification-message"
                class="mt-2 flex items-center justify-between gap-2 text-<?php echo $messageType; ?>-600 bg-<?php echo $messageType; ?>-50 px-3 py-1.5 rounded-xl w-fit border border-<?php echo $messageType; ?>-100">
                <div class="flex items-center gap-2">
                    <i data-lucide="bell" class="w-4 h-4"></i>
                    <span class="text-[10px] font-semibold tracking-wide"><?php echo $message; ?></span>
                </div>
                <button onclick="this.parentElement.remove()"
                    class="ml-3 text-<?php echo $messageType; ?>-600 hover:text-<?php echo $messageType; ?>-800">
                    <i data-lucide="x" class="w-3 h-3"></i>
                </button>
            </div>
        <?php endif; ?>
        </div>

        <div class="flex items-center gap-3">
            <form method="POST" enctype="multipart/form-data" id="importForm" class="hidden">
                <input type="file" name="excel_file" id="fileInput" onchange="document.getElementById('importForm').submit()" accept=".csv">
                <input type="hidden" name="import_btn">
            </form>

            <button onclick="document.getElementById('fileInput').click()" class="px-5 py-3 bg-white border border-gray-200 text-gray-400 rounded-2xl font-bold text-[10px] tracking-wide hover:bg-gray-50 transition-all">
                <i data-lucide="file-up" class="w-4 h-4 inline mr-2"></i> Import CSV
            </button>

            <button onclick="document.getElementById('addModal').classList.toggle('hidden')" class="px-5 py-3 bg-teal-600 text-white rounded-2xl font-bold text-[10px] tracking-wide shadow-md hover:bg-teal-700 hover:shadow-lg transition-all flex items-center">
                <i data-lucide="plus" class="w-4 h-4 mr-2"></i> New Staff
            </button>
        </div>
    </div>

    <!-- ADD USER FORM (Collapsible) -->
    <div id="addModal" class="hidden bg-teal-50/50 border border-teal-100 p-10 rounded-2xl mb-8 shadow-sm animate-in fade-in zoom-in duration-300">
        <div class="flex items-center gap-3 mb-8 ml-2">
            <div class="w-1.5 h-6 bg-teal-600 rounded-full"></div>
            <h2 class="text-xl font-bold text-gray-800 first-letter:uppercase tracking-tight">Provision TB Department Staff</h2>
        </div>
        
        <form method="POST" class="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="space-y-2">
                <label class="text-[9px] font-semibold text-teal-600/60 first-letter:uppercase tracking-wide ml-4">Full Name</label>
                <input type="text" name="full_name" placeholder="E.g. Dr. Abel Tesfaye" required class="w-full px-6 py-4 rounded-2xl bg-white border-none focus:ring-2 focus:ring-teal-500 font-bold text-sm shadow-sm transition-all">
            </div>
            <div class="space-y-2">
                <label class="text-[9px] font-semibold text-teal-600/60 first-letter:uppercase tracking-wide ml-4">Email</label>
                <input type="email" name="email" placeholder="name@mattu.edu" required class="w-full px-6 py-4 rounded-2xl bg-white border-none focus:ring-2 focus:ring-teal-500 font-bold text-sm shadow-sm transition-all">
            </div>
          <div class="space-y-2">
            <label class="text-[9px] font-semibold text-teal-600/60 first-letter:uppercase tracking-wide ml-4">Security Password</label>
            <div class="relative">
                <input type="password" name="password" id="password" required placeholder="••••••••"
                    class="w-full px-6 py-4 bg-white border-none rounded-2xl focus:ring-2 focus:ring-teal-500 font-bold text-sm shadow-sm transition-all pr-12">
                <button type="button" onclick="togglePassword()"
                    class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-teal-600 transition-colors">
                    <i id="eye-icon" data-lucide="eye" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
            <div class="space-y-2">
                <label class="text-[9px] font-semibold text-teal-600/60 first-letter:uppercase tracking-wide ml-4">Role</label>
                <select name="role" class="w-full px-6 py-4 rounded-2xl bg-white border-none focus:ring-2 focus:ring-teal-500 font-semibold text-xs tracking-wide shadow-sm bg-white cursor-pointer">
                    <option value="Doctor">TB Doctor</option>
                    <option value="Nurse">TB Nurse</option>
                    <option value="Lab Technician">TB Lab Technician</option>
                    <option value="Pharmacist">TB Pharmacist</option>
                    <option value="Clerk">TB Registration Clerk</option>
                    <option value="Admin">TBMIS Admin</option>
                    <option value="Radiologist">TB Radiology Focal</option>
                </select>
            </div>
            <div class="md:col-span-4 flex justify-end pt-6 mt-2 border-t border-teal-100">
                <button type="submit" name="create_user" class="px-12 py-4 bg-teal-600 text-white font-bold rounded-2xl first-letter:uppercase text-[10px] tracking-wider hover:bg-teal-700 hover:shadow-xl hover:shadow-teal-200 transition-all scale-100 active:scale-95">
                    Save  Profile
                </button>
            </div>
        </form>
    </div>

    <!-- CENTER: Search Bar -->
    <div class="hidden lg:flex flex-1 justify-center px-8">
        <div class="relative w-full max-w-md group">
            <i data-lucide="search"
                class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4 group-focus-within:text-teal-500 transition-colors"></i>
            
            <!-- UPDATED: Added ID and value to keep state -->
            <input type="text" id="staffSearchInput" value="<?php echo htmlspecialchars($searchTerm); ?>" 
                onkeyup="searchStaff(this.value)" placeholder="Search staff by name, email or ID..."
                class="w-full pl-11 pr-4 py-2.5 bg-gray-100/50 border-none rounded-2xl focus:ring-2 focus:ring-teal-500/20 focus:bg-white transition-all text-sm text-gray-700 font-medium">
                
            <!-- Clear 'X' button if there is a search term -->
            <?php if (!empty($searchTerm)): ?>
                <button onclick="clearSearch()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-red-500 transition-colors">
                    <i data-lucide="x-circle" class="w-4 h-4"></i>
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- MAIN DATA MANAGEMENT TABLE -->
    <form method="POST" id="mainStaffForm">
        <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden transition-all">
            
            <div id="bulkActionBar" class="hidden px-8 py-4 bg-red-600 border-b border-red-700 flex justify-between items-center animate-in slide-in-from-top duration-300">
                <div class="flex items-center gap-3 text-white">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span id="selectionCount" class="text-xs font-semibold uppercase tracking-widest">0 Selected</span>
                </div>
                <button type="button" onclick="triggerBulkDelete()" class="px-6 py-2 bg-white text-red-600 rounded-xl font-semibold text-[10px] uppercase tracking-widest hover:bg-red-50 transition-all shadow-md">
                    Delete  Selected
                </button>
            </div>

            <table class="w-full text-left">
                <thead class="bg-gray-50 text-[10px] font-semibold text-gray-400 uppercase tracking-wider">
                    <tr>
                        <th class="px-8 py-6 w-10">
                            <input type="checkbox" id="selectAll" class="w-5 h-5 rounded-lg border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                        </th>
                        <th class="px-4 py-6">Staff Member</th>
                        <th class="px-8 py-6">Role</th>
                        <th class="px-8 py-6">Status</th>
                        <th class="px-8 py-6 text-right text-gray-300">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php while ($row = $stmt->fetch(PDO::FETCH_ASSOC)): ?>
                        <tr class="hover:bg-teal-50/20 transition-colors group">
                            <td class="px-8 py-5">
                                <input type="checkbox" name="selected_users[]" value="<?php echo $row['user_id']; ?>" 
                                       class="user-checkbox w-5 h-5 rounded-lg border-gray-300 text-teal-600 focus:ring-teal-500 cursor-pointer">
                            </td>
                            <td class="px-4 py-5">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl bg-gray-50 text-gray-300 flex items-center justify-center font-semibold group-hover:bg-teal-600 group-hover:text-white transition-all duration-300">
                                        <?php echo strtoupper(substr($row['full_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <p class="text-[9px] font-semibold text-teal-400 tracking-tighter uppercase mb-0.5"><?php echo $row['user_id']; ?></p>
                                        <p class="font-bold text-gray-800 leading-none"><?php echo $row['full_name']; ?></p>
                                        <p class="text-[10px] text-gray-400 font-medium mt-1 italic"><?php echo $row['email']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="relative w-fit">
                                    <select name="role" onchange="updateRowRole('<?php echo $row['user_id']; ?>', this.value)" 
                                            class="bg-teal-50 text-teal-600 text-[10px] font-semibold uppercase rounded-xl border-none focus:ring-1 focus:ring-teal-500 py-2 pl-3 pr-8 appearance-none transition-all cursor-pointer">
                                        <?php 
                                        $roles = ['Admin', 'Doctor', 'Nurse', 'Lab Technician', 'Pharmacist', 'Clerk', 'Radiologist'];
                                        foreach($roles as $r):
                                            $selected = ($row['role'] == $r) ? "selected" : "";
                                            echo "<option value='$r' $selected>$r</option>";
                                        endforeach;
                                        ?>
                                    </select>
                                    <i data-lucide="chevron-down" class="w-3 h-3 absolute right-3 top-1/2 -translate-y-1/2 text-teal-300 pointer-events-none"></i>
                                </div>
                            </td>
                            <td class="px-8 py-5">
                                <div class="flex flex-col items-start gap-1">
                                    <button type="button" 
                                       onclick="openStatusModal('<?php echo $row['user_id']; ?>', '<?php echo addslashes($row['full_name']); ?>', '<?php echo $row['status']; ?>', <?php echo $userObj->getActiveLoadCount($row['user_id'], $row['role']); ?>)"
                                        class="inline-flex items-center gap-2 px-3 py-1 bg-white border border-gray-100 rounded-full text-[9px] font-semibold uppercase text-gray-500 hover:border-teal-200 transition-all shadow-sm group">
                                        <span class="w-2 h-2 rounded-full <?php echo ($row['status'] == 'active') ? 'bg-emerald-500 animate-pulse' : 'bg-red-400'; ?>"></span>
                                        <span class="group-hover:text-teal-600"><?php echo $row['status']; ?></span>
                                    </button>
                                    
                                    <?php if ($row['status'] == 'disabled' && !empty($row['status_reason'])): ?>
                                        <div class="flex items-center gap-1 ml-2 text-red-400">
                                            <i data-lucide="info" class="w-2.5 h-2.5"></i>
                                            <span class="text-[8px] font-bold first-letter:uppercase italic tracking-tighter">
                                                <?php echo str_replace('_', ' ', $row['status_reason']); ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-8 py-5 text-right">
                                <button type="button" onclick="openDeleteModal('index.php?page=admin&action=delete&id=<?php echo $row['user_id']; ?>', 'Delete Staff Member?', 'This will permanently remove access for <?php echo addslashes($row['full_name']); ?>. Account data cannot be recovered.')"
                                        class="p-3 text-gray-300 hover:text-red-500 transition-all hover:bg-red-50 rounded-2xl">
                                    <i data-lucide="trash-2" class="w-5 h-5"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- PAGINATION FOOTER -->
            <div class="px-8 py-4 bg-gray-50/50 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <span class="text-[10px] font-semibold text-gray-400 uppercase tracking-wider italic">
                    <?php echo $total_users; ?> Global Staff Accounts
                    <?php if(!empty($searchTerm)): ?> 
                        <span class="text-teal-500 normal-case ml-2">(Filtered)</span>
                    <?php endif; ?>
                </span>
                <div class="flex gap-1">
                    <?php if($total_pages > 1): ?>
                        <?php for($i=1; $i<=$total_pages; $i++): ?>
                            <a href="index.php?page=admin&p=<?php echo $i; ?>&q=<?php echo urlencode($searchTerm); ?>" 
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-semibold text-[10px] transition-all 
                            <?php echo ($i==$page_num)?'bg-teal-600 text-white shadow-lg':'bg-white text-gray-400 border border-gray-100 hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <input type="hidden" name="confirm_bulk_delete" id="confirmBulkDeleteInput" value="">
        <input type="hidden" name="user_id" id="roleUpdateId">
        <input type="hidden" name="role" id="roleUpdateVal">
        <input type="hidden" name="update_role_btn" value="1">
    </form>
</div>

<script>
lucide.createIcons();

 // Auto-dismiss notification after 5 seconds
setTimeout(function() {
    const notification = document.getElementById('notification-message');
    if (notification) {
        notification.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        notification.style.opacity = '0';
        notification.style.transform = 'translateY(-10px)';
        setTimeout(() => {
            if (notification && notification.parentNode) {
                notification.remove();
            }
        }, 500);
    }
}, 5000);

   function togglePassword() {
    const passwordInput = document.getElementById('password');
    const eyeIcon = document.getElementById('eye-icon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.setAttribute('data-lucide', 'eye-off');
    } else {
        passwordInput.type = 'password';
        eyeIcon.setAttribute('data-lucide', 'eye');
    }
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
}

    // Select All Logic
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.user-checkbox');
    const bulkBar = document.getElementById('bulkActionBar');
    const countSpan = document.getElementById('selectionCount');

    function updateBulkUI() {
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        if (checkedCount >= 2) {
            bulkBar.classList.remove('hidden');
            countSpan.innerText = `${checkedCount} Users selected `;
        } else {
            bulkBar.classList.add('hidden');
        }
    }

    if(selectAll) {
        selectAll.addEventListener('change', () => {
            checkboxes.forEach(cb => cb.checked = selectAll.checked);
            updateBulkUI();
        });

        checkboxes.forEach(cb => {
            cb.addEventListener('change', updateBulkUI);
        });
    }

    function updateRowRole(id, role) {
        document.getElementById('roleUpdateId').value = id;
        document.getElementById('roleUpdateVal').value = role;
        document.getElementById('mainStaffForm').submit();
    }

    function triggerBulkDelete() {
        const modal = document.getElementById('deleteModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const count = Array.from(checkboxes).filter(cb => cb.checked).length;
        
        document.getElementById('modalTitle').innerText = "Bulk Purge Staff?";
        document.getElementById('modalDesc').innerText = `You are about to delete ${count} staff records from the system database. This action is irreversible.`;
        
        confirmBtn.onclick = function(e) {
            e.preventDefault();
            document.getElementById('confirmBulkDeleteInput').value = "1";
            document.getElementById('mainStaffForm').submit();
        };
        confirmBtn.href = "#";

        modal.classList.remove('hidden');
        setTimeout(() => document.getElementById('modalCard').classList.remove('scale-95'), 10);
    }

    // --- GLOBAL SEARCH FUNCTION ---
    // This function reloads the page with the search query, allowing PHP to fetch results from ALL pages
    let searchTimeout;
    function searchStaff(query) {
        clearTimeout(searchTimeout);
        // Wait 500ms after user stops typing to reload (Debounce)
        searchTimeout = setTimeout(function() {
            let currentUrl = new URL(window.location.href);
            
            if (query.trim() === "") {
                // If empty, remove the query param and reset to page 1
                currentUrl.searchParams.delete('q');
                currentUrl.searchParams.set('p', '1');
            } else {
                // Set the query param and reset to page 1
                currentUrl.searchParams.set('q', query);
                currentUrl.searchParams.set('p', '1');
            }
            
            // Reload the page
            window.location.href = currentUrl.toString();
        }, 500); 
    }

    // Function to clear search manually
    function clearSearch() {
        let currentUrl = new URL(window.location.href);
        currentUrl.searchParams.delete('q');
        currentUrl.searchParams.set('p', '1');
        window.location.href = currentUrl.toString();
    }
</script>