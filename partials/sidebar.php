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
            'id' => 'reports',
            'label' => 'Reports',
            'icon' => 'bar-chart-2',
            'roles' => ['Admin', 'Clerk', 'Doctor', 'Nurse', 'Lab Technician', 'Pharmacist', 'Radiologist']
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
        class="w-[270px] bg-gradient-to-b from-[#0F766E] to-[#115e59] text-white flex-col hidden md:flex h-screen sticky top-0 z-50 transition-all duration-300 shadow-xl shadow-primary-900/20">

        <!-- BRAND SECTION -->
        <div class="px-6 pt-7 pb-6 mb-2">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/15 rounded-xl flex items-center justify-center backdrop-blur-md border border-white/10 shadow-inner">
                    <i data-lucide="heart-pulse" class="text-white w-5 h-5"></i>
                </div>
                <div>
                    <h1 class="text-[15px] font-bold tracking-tight leading-none">TBMIS</h1>
                    <p class="text-[10px] font-medium text-white/60 mt-0.5">Mettu Karl Referral Hospital</p>
                </div>
            </div>
        </div>

        <!-- NAVIGATION LINKS -->
        <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto custom-scrollbar">
            <?php foreach ($navItems as $item): ?>
                <?php
                // Only display if the user's role is in the allowed list for this item
                if (in_array($userRole, $item['roles'])):
                    $isActive = ($page == $item['id']);
                    ?>
                    <a href="index.php?page=<?= $item['id'] ?>"
                        class="flex items-center justify-between group px-4 py-3 rounded-xl transition-all duration-200 
                    <?= $isActive ? 'bg-white text-primary-800 shadow-lg shadow-primary-900/10' : 'hover:bg-white/10 text-white/80 hover:text-white' ?>">

                        <div class="flex items-center gap-3">
                            <i data-lucide="<?= $item['icon'] ?>"
                                class="w-[18px] h-[18px] transition-transform duration-200 <?= $isActive ? 'text-primary-700' : 'group-hover:scale-110' ?>"></i>
                            <span class="text-[13px] font-semibold">
                                <?= $item['label'] ?>
                            </span>
                        </div>

                        <?php if ($isActive): ?>
                            <div class="w-1.5 h-1.5 bg-primary-500 rounded-full pulse-glow"></div>
                        <?php endif; ?>
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </nav>

        <!-- FOOTER / LOGOUT -->
        <div class="p-3 border-t border-white/10">
            <a href="logout.php"
                class="flex items-center gap-3 px-4 py-3 text-white/60 hover:text-white hover:bg-red-500/20 rounded-xl transition-all duration-200 group">
                <i data-lucide="log-out" class="w-[18px] h-[18px] group-hover:translate-x-0.5 transition-transform"></i>
                <span class="font-medium text-sm">Logout</span>
            </a>
        </div>
        <!-- Mobile Close Button (Hidden on Desktop) -->
        <button onclick="toggleSidebar()" class="md:hidden absolute top-5 right-5 text-white/50 hover:text-white p-1 hover:bg-white/10 rounded-lg transition-all">
            <i data-lucide="x" class="w-5 h-5"></i>
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