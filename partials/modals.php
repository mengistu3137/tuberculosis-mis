<!-- Staff Status Management Modal -->
<div id="statusModal"
    class="hidden fixed inset-0 z-[110] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-md transition-all">
    <div id="statusCard"
        class="bg-white rounded-[3rem] w-full max-w-md p-10 shadow-2xl border border-gray-100 transform scale-95 transition-all">

        <div class="flex justify-between items-start mb-6">
            <div id="statusIconBox" class="w-14 h-14 rounded-2xl flex items-center justify-center shadow-inner">
                <i id="mainStatusIcon" data-lucide="user-cog" class="w-7 h-7"></i>
            </div>
            <button onclick="closeStatusModal()" class="text-gray-300 hover:text-red-500"><i
                    data-lucide="x-circle"></i></button>
        </div>

        <h3 id="statusTargetName" class="text-xl font-black text-gray-800 uppercase italic mb-2">--</h3>

        <!-- DYNAMIC WARNING BOX -->
        <div id="loadWarningBox" class="mb-8 p-5 rounded-2xl border transition-all">
            <p id="loadText" class="text-[10px] font-black uppercase tracking-widest mb-1">--</p>
            <p id="loadDescription" class="text-[10px] font-medium leading-relaxed italic">--</p>
        </div>

        <form method="POST" class="space-y-6">
            <input type="hidden" name="target_uid" id="statusTargetID">
            <input type="hidden" name="current_status" id="statusCurrentVal">

           <div id="reasonField" class="space-y-2">
    <label class="text-[9px] font-black text-blue-600 uppercase tracking-widest ml-4">Reason for Status Change</label>
    <select name="reason" required 
        class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl font-bold text-sm shadow-inner cursor-pointer bg-white focus:ring-2 focus:ring-blue-500 transition-all">
        <!-- SHIFT OPTIONS (New) -->
        <optgroup label="Shift Management">
            <option value="Shift_End_Day">End Day Shift (Transition to Night)</option>
            <option value="Shift_End_Night">End Night Shift (Transition to Day)</option>
        </optgroup>
        
        <!-- ADMINISTRATIVE OPTIONS -->
        <optgroup label="Administrative">
            <option value="Leave">Annual / Sick Leave</option>
            <option value="Training">Professional Training</option>
            <option value="Termination">Contract Termination</option>
        </optgroup>
    </select>
    <p class="text-[8px] text-gray-400 ml-4 italic">* Selecting a Shift End will trigger immediate patient handovers.</p>
</div>

            <button type="submit" name="confirm_status_change" id="statusSubmitBtn"
                class="w-full py-5 rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] shadow-xl transition-all active:scale-95">
                Update Status
            </button>
        </form>
    </div>
</div>
<!-- Custom Delete Modal -->

<div id="deleteModal"
    class="hidden fixed inset-0 z-[100] flex items-center justify-center p-6 bg-slate-900/40 backdrop-blur-[2px] transition-all duration-300">

    <!-- Modal Card: Reduced max-width and padding for better focus -->
    <div class="bg-white rounded-[2rem] w-full max-w-[340px] p-6 md:p-8 shadow-2xl border border-gray-100 transform scale-95 transition-transform duration-300"
        id="modalCard">

        <!-- Icon Section: Smaller and more elegant -->
        <div class="w-16 h-16 bg-red-50 text-red-500 rounded-2xl flex items-center justify-center mx-auto mb-6">
            <i data-lucide="trash-2" class="w-6 h-6"></i>
        </div>

        <!-- Text Section: Balanced typography -->
        <div class="text-center mb-8">
            <h3 id="modalTitle" class="text-xl font-black text-gray-800 tracking-tight mb-2">Delete Record?</h3>
            <p id="modalDesc" class="text-gray-500 text-sm font-medium leading-relaxed">
                This action is permanent. Are you sure you want to proceed?
            </p>
        </div>

        <!-- Action Buttons: Side-by-side even on small screens for better UX -->
        <div class="flex gap-3">
            <button onclick="closeDeleteModal()"
                class="flex-1 py-3 bg-gray-50 text-gray-500 rounded-xl font-bold uppercase text-[10px] tracking-widest hover:bg-gray-100 hover:text-gray-700 transition-all">
                Cancel
            </button>
            <a id="confirmDeleteBtn" href="#"
                class="flex-1 py-3 bg-red-600 text-white rounded-xl font-bold uppercase text-[10px] tracking-widest text-center shadow-lg shadow-red-100 hover:bg-red-700 hover:shadow-red-200 transition-all">
                Delete
            </a>
        </div>
    </div>
</div>

<script>
   function openStatusModal(id, name, status, loadCount) {
        document.getElementById('statusTargetID').value = id;
        document.getElementById('statusTargetName').innerText = name;
        document.getElementById('statusCurrentVal').value = status;
        
        const box = document.getElementById('loadWarningBox');
        const loadText = document.getElementById('loadText');
        const loadDesc = document.getElementById('loadDescription');
        const btn = document.getElementById('statusSubmitBtn');
        const iconBox = document.getElementById('statusIconBox');
        const icon = document.getElementById('mainStatusIcon');

        if (status === 'active') {
            // SCENARIO: DISABLING STAFF
            if (loadCount > 0) {
                // Scenario B: Has active tasks
                box.className = "mb-8 p-5 rounded-2xl border bg-orange-50 border-orange-200 text-orange-700";
                loadText.innerText = `Critical: ${loadCount} Active Tasks Detected`;
                loadDesc.innerText = "System will automatically migrate these tasks to the least-loaded available staff members upon deactivation.";
                btn.className = "w-full py-5 bg-orange-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:bg-orange-700";
                iconBox.className = "w-14 h-14 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center shadow-inner";
                icon.setAttribute('data-lucide', 'alert-triangle');
            } else {
                // Scenario A: No tasks
                box.className = "mb-8 p-5 rounded-2xl border bg-blue-50 border-blue-200 text-blue-700";
                loadText.innerText = "Status: Idle (0 Active Tasks)";
                loadDesc.innerText = "This staff member currently has no tasks assignments. Deactivation will be instant.";
                btn.className = "w-full py-5 bg-blue-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:bg-blue-700";
                iconBox.className = "w-14 h-14 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center shadow-inner";
                icon.setAttribute('data-lucide', 'user-cog');
            }
        } else {
            // SCENARIO: ENABLING STAFF
            box.className = "mb-8 p-5 rounded-2xl border bg-emerald-50 border-emerald-200 text-emerald-700";
            loadText.innerText = "Action: Restore System Access";
            loadDesc.innerText = "Enabling this account will make them available for new automatic patient assignments immediately.";
            btn.className = "w-full py-5 bg-emerald-600 text-white rounded-2xl font-black text-[10px] uppercase tracking-[0.3em] hover:bg-emerald-700";
            iconBox.className = "w-14 h-14 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center shadow-inner";
            icon.setAttribute('data-lucide', 'user-check');
        }

        lucide.createIcons();
        document.getElementById('statusModal').classList.remove('hidden');
        requestAnimationFrame(() => document.getElementById('statusCard').classList.remove('scale-95'));
    }


    function openDeleteModal(url, title, description) {
        const modal = document.getElementById('deleteModal');
        const card = document.getElementById('modalCard');
        const confirmBtn = document.getElementById('confirmDeleteBtn');

        document.getElementById('modalTitle').innerText = title;
        document.getElementById('modalDesc').innerText = description;
        
        // Reset the click behavior to default link navigation
        confirmBtn.onclick = null; 
        confirmBtn.href = url;

        modal.classList.remove('hidden');
        requestAnimationFrame(() => card.classList.remove('scale-95'));
    }

    function closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        const card = document.getElementById('modalCard');
        card.classList.add('scale-95');
        setTimeout(() => modal.classList.add('hidden'), 200);
    }
</script>