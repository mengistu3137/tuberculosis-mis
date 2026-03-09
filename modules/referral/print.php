<?php
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/Database.php';

use Dompdf\Dompdf;
use Dompdf\Options;

/* ==============================
   1. DATABASE CONNECTION
============================== */
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Database connection failed.");
}

/* ==============================
   2. VALIDATE REFERRAL ID
============================== */
$referral_id = $_GET['id'] ?? '';

if (empty($referral_id)) {
    die("Invalid referral ID.");
}

/* Optional: Validate format (since yours is REF-XXXXXX) */
if (!preg_match('/^REF-[A-Z0-9]+$/', $referral_id)) {
    die("Invalid referral ID format.");
}

/* ==============================
   3. FETCH REFERRAL (USE LEFT JOIN)
============================== */
$query = "
SELECT r.*, 
       p.full_name AS patient_name,
       p.medical_record_number,
       p.gender,
       p.age,
       u.full_name AS doctor_name
FROM referrals r
LEFT JOIN patients p ON r.patient_id = p.patient_id
LEFT JOIN users u ON r.source_doctor_id = u.user_id
WHERE r.referral_id = ?
";

$stmt = $db->prepare($query);
$stmt->bindParam(1, $referral_id, PDO::PARAM_STR);
$stmt->execute();

$referral = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$referral) {
    die("Referral not found. ID used: " . htmlspecialchars($referral_id));
}

/* ==============================
   4. DECODE JSON PROPERLY
============================== */

/* Diagnoses */
$diagnosisData = json_decode($referral['diagnoses_json'] ?? '{}', true);
$diagnoses = $diagnosisData['items'] ?? [];
$otherDiagnosis = $diagnosisData['other'] ?? '';

/* Treatments */
$treatmentData = json_decode($referral['treatments_json'] ?? '{}', true);
$treatmentsInitiated = $treatmentData['initiated'] ?? '';
$treatments = $treatmentData['items'] ?? [];
$medicationChartAttached = $treatmentData['medication_chart_attached'] ?? false;

/* Functional Status */
$functional = json_decode($referral['functional_status_json'] ?? '{}', true);

/* ==============================
   5. LOGO (DYNAMIC SAFE PATH)
============================== */



/* ==============================
   6. START OUTPUT BUFFER
============================== */
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; line-height: 1.4; }
        .header { border-bottom: 2px solid #2563eb; padding-bottom: 10px; margin-bottom: 20px; }
        .header table { width: 100%; }
        .title { font-size: 20px; font-weight: bold; color: #1f2937; }
        .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #2563eb; margin-top: 15px; border-bottom: 1px solid #e5e7eb; }
        .grid { width: 100%; margin-top: 10px; }
        .grid td { width: 50%; vertical-align: top; padding: 5px 0; }
        .label { font-size: 10px; color: #6b7280; font-weight: bold; text-transform: uppercase; }
        .value { font-size: 12px; font-weight: bold; color: #111; }
        .list-item { margin-bottom: 3px; }

             .footer { position: fixed; bottom: 0; width: 100%; border-top: 2px solid #2563eb; padding-top: 10px; font-size: 9px; }
        .logo {
        width: 80px; /* Adjust size as needed */
        height: auto;
    }
    .header-table {
        width: 100%;
        border-bottom: 2px solid #2563eb;
        padding-bottom: 10px;
        margin-bottom: 20px;
    }
    #watermark {
    position: fixed;
    top:25%;
    bottom: 25%;
    left: 15%;
    transform: rotate(-45deg);
    transform-origin: 50% 50%;
    opacity: .1;
    font-size: 60px;
    color: #000;
    width: 100%;
    text-align: center;
    z-index: -1000;
}

/* Fix text distortion for long notes */
.grid { 
    table-layout: fixed; /* Crucial for preventing columns from expanding */
    width: 100%; 
}
.list-item { 
    word-wrap: break-word; /* Forces long words to break */
    margin-bottom: 5px;
    padding-right: 10px;
}
.value { 
    word-wrap: break-word; 
}
    </style>
</head>
<body>
     <div id="watermark">MATTU KARL HOSPITAL</div>
    <div class="header">
        <table>
            <tr>
                  
                <td>
                    <div class="title">PATIENT REFERRAL</div>
                    <div style="color: #666;">Mattu Karl Specialized Hospital</div>
                </td>
                <td style="text-align: right;">
                    <div class="label">Referral ID: <?php echo $referral_id; ?></div>
                    <div class="label">Date: <?php echo date('d/m/Y', strtotime($referral['referral_date'])); ?></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="grid">
        <tr>
            <td>
                <div class="section-title">Referring From</div>
                <div class="value"><?php echo $referral['referring_facility']; ?></div>
                <div class="label">Focal: <?php echo $referral['referring_focal_point']; ?></div>
                <div class="label">Phone: <?php echo $referral['referring_phone']; ?></div>
            </td>
            <td>
                <div class="section-title">Referral To</div>
                <div class="value"><?php echo $referral['target_facility']; ?></div>
                <div class="label">Focal: <?php echo $referral['target_focal_point']; ?></div>
                <div class="label">Phone: <?php echo $referral['target_phone']; ?></div>
            </td>
        </tr>
    </table>

    <div class="section-title">Patient Information</div>
    <table class="grid">
        <tr>
            <td>
                <div class="label">Full Name</div>
                <div class="value"><?php echo $referral['patient_name']; ?></div>
            </td>
            <td>
                <div class="label">MRN</div>
                <div class="value"><?php echo $referral['medical_record_number']; ?></div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="label">Age / Gender</div>
                <div class="value"><?php echo $referral['age']; ?> Yrs / <?php echo $referral['gender']; ?></div>
            </td>
            <td>
                <div class="label">Target Dept</div>
                <div class="value"><?php echo $referral['target_department']; ?> (<?php echo $referral['priority']; ?>)</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Clinical Details</div>
    <div style="margin-top:5px;">
        <div class="label">Reason for Referral</div>
        <div class="value"><?php echo nl2br($referral['reason']); ?></div>
    </div>

   <div class="section-title">Diagnoses & Treatments</div>
<table class="grid" style="margin-top: 10px;">
    <tr>
        <td style="width: 50%; vertical-align: top; border-right: 1px solid #eee;">
            <div class="label" style="margin-bottom: 5px;">Diagnoses</div>
            <?php if (!empty($diagnoses)): ?>
                <?php foreach ($diagnoses as $dx): ?>
                    <div class="list-item">• <?php echo $dx; ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="list-item text-gray-400">No specific diagnoses listed</div>
            <?php endif; ?>
        </td>
        <td style="width: 50%; vertical-align: top; padding-left: 15px;">
            <div class="label" style="margin-bottom: 5px;">Ongoing Treatments</div>
            <?php if (!empty($treatments)): ?>
                <?php foreach ($treatments as $tx): ?>
                    <div class="list-item">• <?php echo $tx; ?></div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="list-item text-gray-400">No ongoing treatments listed</div>
            <?php endif; ?>
        </td>
    </tr>
</table>

      <div class="footer">
        <table style="width: 100%;">
            <tr>
                <td>
                    <strong>Authorized by:</strong> Dr. <?php echo $referral['doctor_name']; ?><br>
                    <strong>Designation:</strong> <?php echo $referral['compiled_position'] ?? 'Medical Officer'; ?>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <div style="border-top: 1px solid #333; width: 200px; float: right; margin-top: 10px;"></div><br>
                    <strong>Official Signature & Stamp</strong>
                </td>
            </tr>
        </table>
    </div>
</body>
</html>
<?php
$html = ob_get_clean();

// 5. Initialize Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true); 

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);

// (Optional) Set Paper Size
$dompdf->setPaper('A4', 'portrait');

// Render the PDF
$dompdf->render();

// Output the PDF to Browser
$filename = "Referral_" . $referral['medical_record_number'] . ".pdf";
$dompdf->stream($filename, ["Attachment" => false]); // Set to true to force download
?>