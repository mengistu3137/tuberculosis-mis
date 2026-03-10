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
    'radiology' => 'modules/radiology/index.php',
    'radiology-result' => 'modules/radiology/result.php',
    'referral-print' => 'modules/referral/print.php',
   
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
    <link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>

    <!-- IMPORTANT: Load main.js HERE so functions are defined before HTML is rendered -->
    <script src="assets/Js/main.js"></script>

    <style>
        body {
            font-family: 'Sora', sans-serif;
            background:
                radial-gradient(circle at 12% 16%, rgba(16, 185, 129, 0.08), transparent 32%),
                radial-gradient(circle at 85% 6%, rgba(245, 158, 11, 0.08), transparent 34%),
                #f7faf9;
        }

        /* Custom scrollbar for horizontal scrolling containers */
        .scrollbar-thin::-webkit-scrollbar {
            height: 4px;
        }

        .scrollbar-thin::-webkit-scrollbar-track {
            background: transparent;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 20px;
        }

        .scrollbar-thin::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }

        /* For Firefox */
        .scrollbar-thin {
            scrollbar-width: thin;
            scrollbar-color: rgba(255, 255, 255, 0.3) transparent;
        }

        /* Snap scrolling */
        .snap-start {
            scroll-snap-align: start;
        }

        /* Ensure the container doesn't clip shadows */
        .overflow-x-auto {
            overflow-x: auto;
            overflow-y: hidden;
        }

        /* Optional: Hide gradient edges on mobile if they cause issues */
        @media (max-width: 768px) {

            .bg-gradient-to-r,
            .bg-gradient-to-l {
                display: none;
            }
        }
    </style>
</head>

<body class="flex h-screen overflow-hidden">

    <!-- SIDEBAR -->
    <?php include('partials/sidebar.php'); ?>

    <!-- MAIN AREA -->
    <main class="flex-1 flex flex-col overflow-hidden">

        <!-- TOPBAR -->
        <?php include('partials/topbar.php'); ?>

        <!-- DYNAMIC CONTENT AREA -->
        <div class="flex-1 p-8 overflow-y-auto">
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