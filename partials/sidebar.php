    <?php
    /**
     * Sidebar Navigation with Role-Based Access Control (RBAC)
     * Roles: Admin, Doctor, Nurse, Lab Technician, Pharmacist, Clerk
     */

    $userRole = $_SESSION['role'] ?? '';

    // Define sidebar items with associated allowed roles
    $navItems = [
        [
            'id' => 'dashboard',
            'label' => 'Dashboard',
            'icon' => 'layout-dashboard',
            'roles' => ['Admin', 'Doctor', 'Nurse', 'Lab Technician', 'Pharmacist', 'Clerk', 'Radiologist']
        ],
        [
            'id' => 'registration',
            'label' => 'TB Registration Desk',
            'icon' => 'monitor',
            'roles' => ['Admin', 'Clerk']
        ],
        [
            'id' => 'records',
            'label' => 'TB Case Records',
            'icon' => 'clipboard-list',
            'roles' => ['Admin']
        ],
        [
         'id' => 'visit', 
         'label' => 'Active Worklist', 
         'icon' => 'activity',  
         'roles' => ['Doctor', 'Nurse']
         ], 
        [
            'id' => 'laboratory',
            'label' => 'TB Laboratory',
            'icon' => 'test-tube',
            'roles' => ['Admin', 'Lab Technician']
        ],
        [
            'id' => 'pharmacy',
            'label' => 'TB Drug Unit',
            'icon' => 'pill',
            'roles' => ['Admin', 'Pharmacist']
        ],
        [
            'id' => 'admin',
            'label' => 'TBMIS Administration',
            'icon' => 'settings',
            'roles' => ['Admin']
        ],
        [
            'id'=>"radiology",
            'label'=>"Radiology",
            'icon'=>"scan",
            'roles'=>['Admin', 'Radiologist']
        ],
        [
            "id"=>"visit-history",
            "label"=>"TB Follow-up History",
            "icon"=>"history",
            "roles"=>['Admin', 'Doctor', 'Nurse', 'Clerk']
        ]
    ];
    ?>

  
    <aside id="main-sidebar"
        class="w-72 bg-emerald-900 text-white flex-col hidden md:flex h-screen sticky top-0 z-50 transition-all duration-300 shadow-2xl">

        <!-- BRAND SECTION -->
        <div class="p-8 mb-4">
            <div class="flex items-center gap-3">
                <div
                    class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center backdrop-blur-md border border-white/10 shadow-lg">
                    <i data-lucide="activity" class="text-white w-6 h-6"></i>
                </div>
                <div>
                    <h1 class="text-lg font-black tracking-tighter leading-none">TBMIS</h1>
                    <p class="text-[10px] font-bold text-emerald-200 uppercase tracking-widest">Mettu Karl Referral Hospital</p>
                </div>
            </div>
        </div>

        <!-- NAVIGATION LINKS -->
        <nav class="flex-1 px-4 space-y-1.5 overflow-y-auto custom-scrollbar">
            <?php foreach ($navItems as $item): ?>
                <?php
                // Only display if the user's role is in the allowed list for this item
                if (in_array($userRole, $item['roles'])):
                    $isActive = ($page == $item['id']);
                    ?>
                    <a href="index.php?page=<?= $item['id'] ?>"
                        class="flex items-center justify-between group px-4 py-3.5 rounded-2xl transition-all duration-300 
                    <?= $isActive ? 'bg-white text-emerald-800 shadow-xl shadow-emerald-900/20 translate-x-2' : 'hover:bg-emerald-800/70 text-emerald-100 hover:text-white' ?>">

                        <div class="flex items-center gap-3.5">
                            <i data-lucide="<?= $item['icon'] ?>"
                                class="w-5 h-5 transition-transform duration-300 <?= $isActive ? 'text-emerald-700' : 'group-hover:scale-110' ?>"></i>
                            <span class="text-sm font-black first-letter:uppercase tracking-wider">
                                <?= $item['label'] ?>
                            </span>
                        </div>

                        <?php if ($isActive): ?>
                            <div class="w-1.5 h-1.5 bg-emerald-600 rounded-full shadow-[0_0_8px_rgba(5,150,105,0.8)]"></div>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- FOOTER / LOGOUT -->
    <div class="p-2">
            <a href="logout.php"
                class="flex items-center gap-3 p-3 text-red-300 hover:text-white hover:bg-red-600 rounded-xl transition-all group">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span class="font-bold">Logout</span>
            </a>
        </div>
        <!-- Mobile Close Button (Hidden on Desktop) -->
        <button onclick="toggleSidebar()" class="md:hidden absolute top-6 right-6 text-white/50 hover:text-white">
            <i data-lucide="x" class="w-6 h-6"></i>
        </button>
    </aside>

    <style>
        /* Custom thin scrollbar for a professional look */
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: transparent;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.2);
        }
    </style>