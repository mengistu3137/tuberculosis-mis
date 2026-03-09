    <?php
    /**
     * MASTER PATIENT REGISTRY (Clerk Workspace)
     * Brand: Mattu Hospital Blue
     */

    $limit = 8;
    $current_page = isset($_GET['p']) ? (int) $_GET['p'] : 1;
    $offset = ($current_page - 1) * $limit;
    $searchTerm = $_GET['q'] ?? "";

    // Fetch Master Registry
    $total_records = $patientObj->countSearch($searchTerm);
    $total_pages = ceil($total_records / $limit);
    $stmt = $patientObj->searchPaginated($searchTerm, $limit, $offset);

    
    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['perform_checkin'])) {
        $visit_id = $visitObj->create($_POST['patient_id'], $_POST['visit_type'], $_POST['clinical_notes']);

        if ($visit_id) {
            // ONLY assign the Doctor at this stage
            $assignObj->autoAssignDoctor($visit_id);

            $msg = "Check-in successful. Patient assigned to Physician.";
            $msgType = "emerald";

            echo "<script>
            setTimeout(function() {
                window.location.href='index.php?page=visit&status=success&vid=$visit_id';
            }, 1000);
        </script>";
            exit;
        }
    }
    ?>

    <div class="space-y-6">
        <!-- Header -->
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <h1 class="text-xl font-black text-gray-800 tracking-tight uppercase italic text-blue-600">Master Patient
                    Registry</h1>
                <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest mt-1">Hospital Database •
                    <?php echo $total_records; ?> Total Records</p>
            </div>

            <div class="flex items-center gap-3">
             
                <a href="index.php?page=registration"
                    class="px-5 py-3 bg-blue-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-widest shadow-lg shadow-blue-100 hover:bg-blue-700 transition-all">+
                    New Patient</a>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white rounded-[2.5rem] border border-gray-100 shadow-sm overflow-hidden transition-all">
            <table class="w-full text-left">
                <thead
                    class="bg-gray-50/50 text-[10px] font-black text-blue-600 uppercase tracking-[0.2em] border-b border-blue-100">
                    <tr>
                        <th class="px-8 py-6">Patient Identity</th>
                        <th class="px-8 py-6 text-center">Bio Stats</th>
                        <th class="px-8 py-6">Contact Info</th>
                        <th class="px-8 py-6 text-right">Administrative Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    <?php while ($p = $stmt->fetch(PDO::FETCH_ASSOC)):
                        $initials = strtoupper(substr($p['full_name'], 0, 1));
                        ?>
                        <tr class="hover:bg-blue-50/20 transition-colors group">
                            <td class="px-8 py-6">
                                <div class="flex items-center gap-4">
                                    <div
                                        class="w-10 h-10 rounded-xl bg-blue-600 text-white flex items-center justify-center font-black shadow-md">
                                        <?php echo $initials; ?></div>
                                    <div>
                                        <p class="font-black text-gray-800 text-sm"><?php echo $p['full_name']; ?></p>
                                        <p class="text-[9px] font-black text-blue-400 uppercase tracking-tighter mt-1">
                                            <?php echo $p['medical_record_number']; ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-8 py-6 text-center">
                                <span
                                    class="px-3 py-1 bg-white border border-gray-100 rounded-lg text-[10px] font-black text-gray-500 uppercase"><?php echo $p['age']; ?>Y
                                    • <?php echo $p['gender']; ?></span>
                            </td>
                            <td class="px-8 py-6 text-xs font-bold text-gray-500 italic"><?php echo $p['contact_details']; ?>
                            </td>
                            <td class="px-8 py-6 text-right">
                                <!-- THE CHECK-IN TRIGGER -->
                                <button
                                    onclick="openCheckinModal('<?php echo $p['patient_id']; ?>', '<?php echo addslashes($p['full_name']); ?>')"
                                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-xl font-black text-[9px] uppercase tracking-widest hover:bg-blue-600 transition-all shadow-md">
                                    <i data-lucide="log-in" class="w-3 h-3"></i> Check-in Visit
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>

            <!-- PAGINATION -->
            <div class="px-8 py-6 bg-gray-50/50 border-t border-gray-100 flex items-center justify-between">
                <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest italic">Registry Page
                    <?php echo $current_page; ?></span>
                <div class="flex gap-1.5">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="index.php?page=records&p=<?php echo $i; ?>&q=<?php echo $searchTerm; ?>"
                            class="w-9 h-9 flex items-center justify-center rounded-xl font-black text-[10px] transition-all <?php echo ($i == $current_page) ? 'bg-blue-600 text-white shadow-lg shadow-blue-100' : 'bg-white text-gray-400 border border-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ADMINISTRATIVE CHECK-IN MODAL -->
    <div id="checkinModal"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-md transition-all duration-300">
        <div id="checkinCard"
            class="bg-white rounded-[3rem] w-full max-w-md p-10 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300">
            <div class="flex justify-between items-start mb-8">
                <div
                    class="w-14 h-14 bg-blue-600 text-white rounded-2xl flex items-center justify-center font-black shadow-lg shadow-blue-100 italic">
                    Checkin</div>
                <button onclick="closeCheckinModal()" class="text-gray-300 hover:text-red-500 transition-colors"><i
                        data-lucide="x-circle"></i></button>
            </div>

            <h3 id="checkinPatientName" class="text-xl font-black text-gray-800 uppercase italic mb-6">--</h3>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="patient_id" id="checkinID">
                <input type="hidden" name="perform_checkin" value="1">

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest ml-4">Visit
                        Classification</label>
                    <select name="visit_type" required
                        class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 font-black text-xs uppercase tracking-widest bg-white shadow-inner">
                        <option value="Outpatient">Outpatient Visit</option>
                        <option value="Emergency">Emergency Triage</option>
                    </select>
                </div>

                <div class="space-y-2">
                    <label class="text-[10px] font-black text-blue-600 uppercase tracking-widest ml-4">Administrative
                        Notes</label>
                    <textarea name="clinical_notes" rows="3"
                        class="w-full bg-gray-50 border-none rounded-2xl px-6 py-4 text-sm font-medium shadow-inner"
                        placeholder="E.g. Routine follow-up, transfer from clinic..."></textarea>
                </div>

                <button type="submit"
                    class="w-full py-5 bg-blue-600 text-white rounded-[2rem] font-black text-[10px] uppercase tracking-[0.3em] shadow-xl shadow-blue-100 hover:bg-blue-700 transition-all flex items-center justify-center gap-3">
                    <i data-lucide="check-circle" class="w-5 h-5"></i> Confirm Check-in
                </button>
            </form>
        </div>
    </div>

    <script>
        lucide.createIcons();
        function openCheckinModal(id, name) {
            document.getElementById('checkinID').value = id;
            document.getElementById('checkinPatientName').innerText = name;
            document.getElementById('checkinModal').classList.remove('hidden');
            requestAnimationFrame(() => document.getElementById('checkinCard').classList.remove('scale-95'));
        }
        function closeCheckinModal() {
            document.getElementById('checkinCard').classList.add('scale-95');
            setTimeout(() => document.getElementById('checkinModal').classList.add('hidden'), 200);
        }
    </script>