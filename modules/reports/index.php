<?php
$reportTypes = [
    ["title" => "Daily TB Intake Summary", "desc" => "TB suspect registrations and case entries", "icon" => "calendar", "color" => "emerald"],
    ["title" => "TB Diagnosis Burden", "desc" => "Pulmonary, extrapulmonary, and MDR-TB trend", "icon" => "activity", "color" => "red"],
    ["title" => "TB Drug Dispensing", "desc" => "Regimen dispatch and adherence risk monitoring", "icon" => "pill", "color" => "amber"],
    ["title" => "TB Follow-up Outcomes", "desc" => "Missed visits, outcomes, and treatment continuity", "icon" => "clock", "color" => "teal"]
];
?>

<div class="space-y-8">
    <div>
        <h1 class="text-3xl font-black text-gray-800">TB Program Intelligence & Reports</h1>
        <p class="text-gray-500 font-medium italic">Generate data-driven outputs for tuberculosis program management</p>
    </div>

    <!-- Report Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <?php foreach ($reportTypes as $report): ?>
            <div
                class="bg-white p-8 rounded-[2.5rem] border border-gray-100 shadow-sm flex items-start gap-6 hover:shadow-xl hover:scale-[1.01] transition-all cursor-pointer group">
                <div
                    class="w-16 h-16 bg-<?php echo $report['color']; ?>-50 text-<?php echo $report['color']; ?>-500 rounded-3xl flex items-center justify-center shrink-0 group-hover:bg-<?php echo $report['color']; ?>-500 group-hover:text-white transition-colors">
                    <i data-lucide="<?php echo $report['icon']; ?>" class="w-8 h-8"></i>
                </div>
                <div class="flex-1">
                    <h3 class="font-black text-gray-800 text-lg mb-1">
                        <?php echo $report['title']; ?>
                    </h3>
                    <p class="text-sm text-gray-400 mb-6 font-medium">
                        <?php echo $report['desc']; ?>
                    </p>
                    <div class="flex items-center gap-3">
                        <button
                            class="px-4 py-2 bg-gray-50 text-gray-600 rounded-xl text-xs font-bold hover:bg-gray-100">Preview</button>
                        <button
                            class="px-4 py-2 bg-blue-50 text-blue-600 rounded-xl text-xs font-bold hover:bg-blue-600 hover:text-white transition-all">Download
                            PDF</button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>
<script>lucide.createIcons();</script>