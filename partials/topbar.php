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
    class="h-16 bg-white/70 backdrop-blur-xl border-b border-gray-200/50 flex items-center justify-between px-4 md:px-6 sticky top-0 z-40 shadow-sm shadow-gray-100/50">

    <!-- LEFT: Mobile Menu & Breadcrumbs -->
    <div class="flex items-center gap-3 md:gap-4">
        <button onclick="toggleSidebar()"
            class="p-2 -ml-2 text-gray-400 hover:text-primary-700 hover:bg-primary-50 rounded-lg md:hidden transition-all duration-200">
            <i data-lucide="menu" class="w-5 h-5"></i>
        </button>

        <div class="flex items-center gap-2 text-sm">
            <span class="text-gray-400 font-medium">System</span>
            <i data-lucide="chevron-right" class="w-3.5 h-3.5 text-gray-300"></i>
            <span class="text-primary-700 font-semibold capitalize">
                <?php echo str_replace('-', ' ', $page); ?>
            </span>
        </div>
    </div>

  <!-- CENTER: Search Bar (Hidden on Admin Page) -->
<?php if ($page !== 'admin'): ?>
    <div class="hidden lg:flex flex-1 justify-center px-8">
        <div class="relative w-full max-w-md group">
            <i data-lucide="search"
                class="absolute left-3.5 top-1/2 -translate-y-1/2 text-gray-400 w-4 h-4 group-focus-within:text-primary-600 transition-colors"></i>
            <input type="text" onkeyup="searchPatients(this.value)" placeholder="Search TB patient, TBMRN, regimen..."
                class="w-full pl-10 pr-4 py-2.5 bg-gray-100/60 border border-transparent rounded-xl focus:ring-2 focus:ring-primary-500/20 focus:border-primary-400 focus:bg-white transition-all duration-200 text-sm text-gray-700 font-medium placeholder:text-gray-400">
        </div>
    </div>
<?php endif; ?>

    <!-- RIGHT: User Profile & Workload -->
    <div class="flex items-center gap-3 md:gap-4 shrink-0">

        <!-- WORKLOAD INDICATOR -->
        <div class="hidden md:flex items-center px-3.5 py-2 bg-primary-50 rounded-xl border border-primary-100/80">
            <div class="flex flex-col items-end mr-2.5">
                <span class="text-[9px] font-semibold text-primary-500 uppercase tracking-wider">TB Desk</span>
                <span class="text-xs font-bold text-primary-700"><?php echo $myLoad; ?> Active</span>
            </div>
            <div class="w-2 h-2 rounded-full <?php echo ($myLoad > 0) ? 'bg-emerald-500 pulse-glow' : 'bg-gray-300'; ?>"></div>
        </div>

        <button class="p-2 text-gray-400 hover:text-primary-700 hover:bg-primary-50 rounded-xl transition-all duration-200 hidden sm:block relative">
            <i data-lucide="bell" class="w-4 h-4"></i>
        </button>

        <div class="flex items-center gap-3 pl-3 border-l border-gray-200/60">
            <div class="text-right hidden sm:block">
                <p class="text-sm font-semibold text-gray-800 leading-tight">
                    <?php echo $_SESSION['full_name']; ?></p>
                <p class="text-[11px] font-medium text-primary-600 mt-0.5">
                    <?php echo $_SESSION['role']; ?></p>
            </div>

            <div class="w-9 h-9 md:w-10 md:h-10 rounded-xl bg-gradient-to-br from-primary-600 to-primary-800 text-white flex items-center justify-center font-bold text-sm shadow-md shadow-primary-200/40 border-2 border-white ring-1 ring-gray-100">
                <?php echo strtoupper(substr($_SESSION['full_name'], 0, 1)); ?>
            </div>
        </div>
    </div>
</header>