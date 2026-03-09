<?php
/**
 * DYNAMIC TOPBAR
 * Role-aware Workload Indicator Fix
 */

$myLoad = 0;
$uid = $_SESSION['user_id'];
$role = $_SESSION['role'];

try {
    // 1. Logic for Doctors, Nurses, and Admins (Visits)
    if ($role == 'Doctor' || $role == 'Admin' || $role == 'Clerk') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM medical_visits WHERE assigned_doctor_id = ? AND status = 'active'");
        $stmt->execute([$uid]);
        $myLoad = $stmt->fetchColumn();
    }
    // FIXED: Nurse logic looks at the nurse assignment column
    elseif ($role == 'Nurse') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM medical_visits WHERE assigned_nurse_id = ? AND status = 'active'");
        $stmt->execute([$uid]);
        $myLoad = $stmt->fetchColumn();
    }
    // Lab Tech & Radiologist: Check pending lab requests
    elseif ($role == 'Lab Technician' || $role == 'Radiologist') {
        $stmt = $db->prepare("SELECT COUNT(*) FROM lab_requests WHERE assigned_tech_id = ? AND status = 'pending'");
        $stmt->execute([$uid]);
        $myLoad = $stmt->fetchColumn();
    }
    // Pharmacist: Check undispensed prescriptions
    elseif ($role == 'Pharmacist') {
        $stmt = $db->query("SELECT COUNT(*) FROM prescriptions WHERE is_dispensed = 0");
        $myLoad = $stmt->fetchColumn();
    }
} catch (PDOException $e) {
    $myLoad = 0; // Fallback on error
}
?>

<header
    class="h-16 md:h-20 bg-white/80 backdrop-blur-md border-b border-gray-100 flex items-center justify-between px-4 md:px-8 sticky top-0 z-40">

    <!-- LEFT: Mobile Menu & Breadcrumbs -->
    <div class="flex items-center gap-3 md:gap-4">
        <button onclick="toggleSidebar()"
            class="p-2 -ml-2 text-gray-400 hover:text-blue-600 md:hidden transition-colors">
            <i data-lucide="menu" class="w-6 h-6"></i>
        </button>

        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-400">System</span>
            <i data-lucide="chevron-right" class="w-4 h-4 text-gray-300"></i>
            <span class="text-blue-600 font-semibold capitalize">
                <?php echo str_replace('-', ' ', $page); ?>
            </span>
        </div>
    </div>

  <!-- CENTER: Search Bar (Hidden on Admin Page) -->
<?php if ($page !== 'admin'): ?>
    <div class="hidden lg:flex flex-1 justify-center px-8">
        <div class="relative w-full max-w-md group">
            <i data-lucide="search"
                class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4 group-focus-within:text-blue-500 transition-colors"></i>
            <input type="text" onkeyup="searchPatients(this.value)" placeholder="Search patients, MRN, or files..."
                class="w-full pl-11 pr-4 py-2.5 bg-gray-100/50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500/20 focus:bg-white transition-all text-sm text-gray-700 font-medium">
        </div>
    </div>
<?php endif; ?>

    <!-- RIGHT: User Profile & Workload -->
    <div class="flex items-center gap-3 md:gap-4 shrink-0">

        <!-- WORKLOAD INDICATOR -->
        <div class="hidden md:flex items-center px-4 py-2 bg-blue-50 rounded-xl border border-blue-100">
            <div class="flex flex-col items-end mr-3">
                <span class="text-[8px] font-black text-blue-400 uppercase tracking-widest">Live Workload</span>
                <span class="text-xs font-black text-blue-600"><?php echo $myLoad; ?> Active Cases</span>
            </div>
            <div
                class="w-2 h-2 rounded-full <?php echo ($myLoad > 0) ? 'bg-emerald-500 animate-pulse' : 'bg-gray-300'; ?>">
            </div>
        </div>

        <button
            class="p-2 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-xl transition-all hidden sm:block">
            <i data-lucide="bell" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center gap-3 pl-3 border-l border-gray-100">
            <div class="text-right hidden sm:block">
                <p class="text-xs md:text-sm font-black text-gray-800 leading-tight">
                    <?php echo $_SESSION['full_name']; ?></p>
                <p class="text-[10px] font-bold text-blue-500 uppercase tracking-widest">
                    <?php echo $_SESSION['role']; ?></p>
            </div>

            <div
                class="w-9 h-9 md:w-11 md:h-11 rounded-full bg-gradient-to-br from-blue-600 to-blue-700 text-white flex items-center justify-center font-black text-sm md:text-base shadow-lg shadow-blue-100 border-2 border-white ring-1 ring-gray-100">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
            </div>
        </div>
    </div>
</header>