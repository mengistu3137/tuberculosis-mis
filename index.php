<?php
session_start();

// --- ADDED: BOOTSTRAP DATABASE & CLASSES ---
require_once 'config/Database.php';
require_once 'includes/classes/User.php';
require_once 'includes/classes/Patient.php';
require_once 'includes/classes/Visit.php';
require_once 'includes/classes/Clinical.php';
require_once 'includes/classes/Lab.php';
require_once 'includes/classes/Pharmacy.php';
require_once 'includes/classes/AssignmentEngine.php';
// Add this

$database = new Database();
$db = $database->getConnection();
// Initialize the user object so it's available everywhere
$userObj = new User($db);
$patientObj = new Patient($db);
$visitObj = new Visit($db);
$clinicalObj = new Clinical($db);
$labObj = new Lab($db);
$pharmacyObj = new Pharmacy($db);
$assignObj = new AssignmentEngine($db);
// -------------------------------------------

// 1. Protect the page: Redirect to login if not authenticated
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Define the Routing Logic
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

$routes = [
    'dashboard' => 'modules/dashboard.php',
    'registration' => 'modules/Registration/index.php',
    // 'record' => 'modules/registration/records.php',
 
    'records' => 'modules/medical-record/index.php',
    'visit-history' => 'modules/medical-record/visit-history.php',

    'consultation' => 'modules/medical-record/consultation.php',
    'referral' => 'modules/referral/index.php',
    'laboratory' => 'modules/Laboratory/index.php',
    'lab-results' => 'modules/Laboratory/results.php',
    'pharmacy' => 'modules/pharmacy/index.php',
    'admin' => 'modules/admin/index.php',
    'prescribe' => 'modules/medical-record/prescribe.php',
    'discharge' => 'modules/medical-record/discharge.php',
    'visit' => 'modules/medical-record/visits.php',
    'record-vital' => 'modules/medical-record/record-vital.php',
    'ward-assignment' => 'modules/medical-record/ward-assignment.php',
    'radiology' => 'modules/radiology/index.php',
    'radiology-result' => 'modules/radiology/result.php',
    'upload-images' => 'modules/radiology/upload-images.php',
    'referral-print' => 'modules/referral/print.php',
    'reports' => 'modules/reports/index.php',
   
];



if (array_key_exists($page, $routes)) {
    $content = $routes[$page];
} else {
    $content = 'modules/404.php';
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TBMIS | Mettu Karl Referral Hospital</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f0fdfa', 100: '#ccfbf1', 200: '#99f6e4', 300: '#5eead4',
                            400: '#2dd4bf', 500: '#14b8a6', 600: '#0d9488', 700: '#0F766E',
                            800: '#115e59', 900: '#134e4a', 950: '#042f2e',
                        },
                        secondary: {
                            50: '#eff6ff', 100: '#dbeafe', 200: '#bfdbfe', 300: '#93c5fd',
                            400: '#60a5fa', 500: '#3b82f6', 600: '#2563EB', 700: '#1d4ed8',
                            800: '#1e40af', 900: '#1e3a8a',
                        },
                    },
                    fontFamily: {
                        sans: ['Sora', 'Inter', 'system-ui', 'sans-serif'],
                    },
                },
            },
        }
    </script>
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- IMPORTANT: Load main.js HERE so functions are defined before HTML is rendered -->
    <script src="assets/Js/main.js"></script>

    <style>
        body {
            font-family: 'Sora', sans-serif;
            background: #F8FAFC;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0; left: 0; right: 0; bottom: 0;
            background:
                radial-gradient(circle at 10% 15%, rgba(15, 118, 110, 0.06), transparent 40%),
                radial-gradient(circle at 85% 5%, rgba(13, 148, 136, 0.04), transparent 40%),
                radial-gradient(circle at 50% 80%, rgba(16, 185, 129, 0.03), transparent 50%);
            pointer-events: none;
            z-index: 0;
        }

        /* Smooth transitions for all interactive elements */
        *, *::before, *::after {
            transition-property: color, background-color, border-color, box-shadow, transform, opacity;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        }

        /* Custom scrollbar for horizontal scrolling containers */
        .scrollbar-thin::-webkit-scrollbar { height: 4px; }
        .scrollbar-thin::-webkit-scrollbar-track { background: transparent; }
        .scrollbar-thin::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.3); border-radius: 20px; }
        .scrollbar-thin::-webkit-scrollbar-thumb:hover { background: rgba(255, 255, 255, 0.5); }
        .scrollbar-thin { scrollbar-width: thin; scrollbar-color: rgba(255, 255, 255, 0.3) transparent; }

        /* Global scrollbar styling */
        ::-webkit-scrollbar { width: 6px; height: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .snap-start { scroll-snap-align: start; }
        .overflow-x-auto { overflow-x: auto; overflow-y: hidden; }

        /* Glassmorphism utility */
        .glass { background: rgba(255,255,255,0.7); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid rgba(255,255,255,0.3); }

        /* Card hover lift effect */
        .card-hover { transition: transform 0.2s ease, box-shadow 0.2s ease; }
        .card-hover:hover { transform: translateY(-2px); box-shadow: 0 12px 40px -12px rgba(15, 118, 110, 0.15); }

        /* Smooth fade-in animation */
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(12px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeInUp 0.4s ease-out forwards; }

        /* Pulse glow for status indicators */
        @keyframes pulseGlow { 0%, 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 50% { box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); } }
        .pulse-glow { animation: pulseGlow 2s ease-in-out infinite; }

        @media (max-width: 768px) {
            .bg-gradient-to-r, .bg-gradient-to-l { display: none; }
        }
    </style>
</head>

<body class="flex h-screen overflow-hidden bg-[#F8FAFC]">

    <!-- SIDEBAR -->
    <?php include('partials/sidebar.php'); ?>

    <!-- MAIN AREA -->
    <main class="flex-1 flex flex-col overflow-hidden relative">

        <!-- TOPBAR -->
        <?php include('partials/topbar.php'); ?>

        <!-- DYNAMIC CONTENT AREA -->
        <div class="flex-1 p-6 lg:p-8 overflow-y-auto animate-fade-in">
            <?php
            if (file_exists($content)) {
                include($content);
            } else {
                echo "<div class='p-10 bg-red-50 text-red-500 rounded-xl'>Module file missing: $content</div>";
            }
            ?>
        </div>

    </main>
    <?php include('partials/modals.php'); ?>

    <!-- Only lucide icons need to be initialized at the end -->
    <script>lucide.createIcons();</script>
</body>

</html>