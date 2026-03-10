<?php
$diseaseStats = [
    ['name' => 'Malaria', 'count' => 450, 'color' => 'bg-red-500'],
    ['name' => 'Gastritis', 'count' => 320, 'color' => 'bg-teal-500'],
    ['name' => 'Respiratory Infection', 'count' => 280, 'color' => 'bg-emerald-500'],
    ['name' => 'Typhoid', 'count' => 150, 'color' => 'bg-orange-500']
];
?>

<div class="space-y-8">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold text-gray-800">Hospital Analytics</h1>
        <button onclick="window.print()"
            class="px-4 py-2 bg-white border border-gray-200 rounded-xl text-sm font-bold flex items-center gap-2 hover:bg-gray-50">
            <i data-lucide="printer" class="w-4 h-4"></i> Export PDF Report
        </button>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Disease Statistics (Progress Bars) -->
        <div class="lg:col-span-2 bg-white rounded-3xl p-8 border border-gray-100 shadow-sm">
            <h3 class="font-bold text-gray-800 mb-6 uppercase text-xs tracking-widest">Disease Prevalence (Top Cases)
            </h3>
            <div class="space-y-6">
                <?php foreach ($diseaseStats as $d):
                    $percentage = ($d['count'] / 500) * 100; // Mock calculation
                    ?>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm font-bold">
                            <span class="text-gray-600">
                                <?php echo $d['name']; ?>
                            </span>
                            <span class="text-gray-400">
                                <?php echo $d['count']; ?> Cases
                            </span>
                        </div>
                        <div class="w-full bg-gray-100 h-3 rounded-full overflow-hidden">
                            <div class="<?php echo $d['color']; ?> h-full" style="width: <?php echo $percentage; ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Service Utilization -->
        <div class="bg-teal-600 rounded-3xl p-8 text-white shadow-xl shadow-teal-100">
            <h3 class="font-bold text-teal-200 mb-8 uppercase text-[10px] tracking-widest">Service Utilization</h3>
            <div class="space-y-8">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-teal-500 rounded-2xl flex items-center justify-center font-bold">85%</div>
                    <div>
                        <p class="font-bold">Bed Occupancy</p>
                        <p class="text-xs text-teal-300">Inpatient Ward</p>
                    </div>
                </div>
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-teal-500 rounded-2xl flex items-center justify-center font-bold">12m</div>
                    <div>
                        <p class="font-bold">Avg. Wait Time</p>
                        <p class="text-xs text-teal-300">Registration Desk</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>