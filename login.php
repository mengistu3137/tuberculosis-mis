<?php
include_once 'config/Database.php';
include_once 'includes/classes/User.php';

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $res = $user->login($_POST['email'], $_POST['password']);

    if ($res === true) {
        header("Location: index.php");
        exit;
    } elseif ($res === "account_disabled") {
        $error = "This account has been disabled. Please contact the IT Department.";
    } else {
        $error = "Invalid email or password.";
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['do_reset'])) {
    $email = $_POST['email'];
    $current = $_POST['current_password'];
    $new_p = $_POST['new_password'];
    $con_p = $_POST['confirm_password'];

    if ($new_p !== $con_p) {
        header("Location: login.php?error=password_mismatch");
        exit();
    }

    $result = $user->changePassword($email, $current, $new_p);

    if ($result === true) {
        header("Location: login.php?reset=success");
    } else {
        header("Location: login.php?error=" . urlencode($result));
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Login | PRMIS Mattu Karl</title>
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
                    },
                },
            },
        }
    </script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&display=swap');
        body { font-family: 'Sora', sans-serif; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fade-in { animation: fadeInUp 0.6s ease-out forwards; }
        @keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
        .animate-float { animation: float 6s ease-in-out infinite; }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6 bg-[#F8FAFC] relative overflow-hidden">
    <!-- Background decoration -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 bg-primary-100 rounded-full opacity-40 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 bg-primary-200 rounded-full opacity-30 blur-3xl"></div>
        <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-primary-50 rounded-full opacity-50 blur-3xl"></div>
    </div>

    <div class="max-w-md w-full animate-fade-in relative z-10">
        <!-- Card -->
        <div class="bg-white/80 backdrop-blur-xl p-10 rounded-3xl shadow-xl shadow-primary-900/5 border border-white/60">
            <div class="text-center mb-10">
                <div class="w-20 h-20 bg-gradient-to-br from-primary-600 to-primary-800 text-white rounded-2xl flex items-center justify-center mx-auto mb-5 shadow-lg shadow-primary-200/60 animate-float">
                    <i data-lucide="heart-pulse" class="w-10 h-10"></i>
                </div>
                <h2 class="text-xl font-bold text-gray-800 tracking-tight">Welcome Back</h2>
                <p class="text-xs text-gray-400 font-medium mt-1.5 tracking-wide">Mattu Karl Specialized Hospital</p>
            </div>

       

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 text-red-600 text-xs font-semibold rounded-xl border border-red-100 flex items-center gap-2.5">
                <i data-lucide="alert-circle" class="w-4 h-4 shrink-0"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-5">
            <div>
                <label class="text-[11px] font-semibold text-gray-500 tracking-wide ml-1 mb-1.5 block">Hospital Email</label>
                <input type="email" name="email" required placeholder="name@mattu.edu"
                    class="w-full px-5 py-3.5 bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none font-medium text-gray-700 text-sm transition-all duration-200 hover:border-gray-300">
            </div>

            <div class="relative">
                <label class="text-[11px] font-semibold text-gray-500 tracking-wide ml-1 mb-1.5 block">Password</label>
                <div class="relative">
                    <input type="password" id="password" name="password" required placeholder="••••••••"
                        class="w-full px-5 py-3.5 bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 outline-none font-medium text-gray-700 text-sm transition-all duration-200 pr-14 hover:border-gray-300">
                    <button type="button" onclick="togglePassword()"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600 transition-colors">
                        <i id="eye-icon" data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login"
                class="w-full py-4 bg-gradient-to-r from-primary-700 to-primary-600 text-white rounded-xl font-bold shadow-lg shadow-primary-200/50 hover:shadow-xl hover:shadow-primary-300/50 hover:from-primary-800 hover:to-primary-700 transition-all duration-300 active:scale-[0.98] text-sm tracking-wide">
                Sign In
            </button>
        </form>

        <div class="mt-8 pt-6 border-t border-gray-100 text-center">
            <p class="text-sm text-gray-500 font-medium">
                <button onclick="openChangePasswordModal()"
                    class="text-primary-700 font-semibold hover:text-primary-800 hover:underline underline-offset-4 transition-colors">Change Password</button>
            </p>
        </div>
    </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-gray-900/40 backdrop-blur-md transition-all duration-300">
        <div id="changePasswordCard"
            class="bg-white rounded-2xl w-full max-w-md p-8 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300">

            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-11 h-11 bg-gradient-to-br from-primary-600 to-primary-800 text-white rounded-xl flex items-center justify-center shadow-lg shadow-primary-100">
                        <i data-lucide="key" class="w-5 h-5"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 leading-none">Change Password</h3>
                        <p class="text-[11px] text-gray-400 font-medium mt-0.5">Update your login credentials</p>
                    </div>
                </div>
                <button onclick="closeChangePasswordModal()" class="text-gray-400 hover:text-red-500 transition-colors p-1 hover:bg-gray-100 rounded-lg">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>

            <form method="POST" action="" class="space-y-4">
                <!-- Email Field -->
                <div>
                    <label class="text-[11px] font-semibold text-gray-500 tracking-wide ml-1 mb-1.5 block">Hospital Email</label>
                    <input type="email" name="email" required placeholder="Verify your account email"
                        class="w-full px-4 py-3 bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 font-medium text-sm transition-all duration-200 hover:border-gray-300">
                </div>

                <!-- CURRENT PASSWORD -->
                <div>
                    <label class="text-[11px] font-semibold text-gray-500 tracking-wide ml-1 mb-1.5 block">Current Password</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="modal_current_password" required placeholder="••••••••"
                            class="w-full px-4 py-3 bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 font-medium text-sm transition-all duration-200 pr-12 hover:border-gray-300">
                        <button type="button" onclick="toggleModalPwd('modal_current_password', 'eye-icon-current')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600 focus:outline-none transition-colors">
                            <i id="eye-icon-current" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- NEW PASSWORD -->
                <div>
                    <label class="text-[11px] font-semibold text-gray-500 tracking-wide ml-1 mb-1.5 block">New Password</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="modal_new_password" required placeholder="••••••••"
                            class="w-full px-4 py-3 bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 font-medium text-sm transition-all duration-200 pr-12 hover:border-gray-300">
                        <button type="button" onclick="toggleModalPwd('modal_new_password', 'eye-icon-new')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600 focus:outline-none transition-colors">
                            <i id="eye-icon-new" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- CONFIRM PASSWORD -->
                <div>
                    <label class="text-[11px] font-semibold text-gray-500 tracking-wide ml-1 mb-1.5 block">Confirm New Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="modal_confirm_password" required placeholder="••••••••"
                            class="w-full px-4 py-3 bg-gray-50/80 border border-gray-200 rounded-xl focus:ring-2 focus:ring-primary-500/30 focus:border-primary-500 font-medium text-sm transition-all duration-200 pr-12 hover:border-gray-300">
                        <button type="button" onclick="toggleModalPwd('modal_confirm_password', 'eye-icon-conf')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-primary-600 focus:outline-none transition-colors">
                            <i id="eye-icon-conf" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-100">
                    <button type="button" onclick="closeChangePasswordModal()"
                        class="px-5 py-2.5 text-gray-500 rounded-xl font-medium text-sm hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" name="do_reset"
                        class="px-6 py-2.5 bg-gradient-to-r from-primary-700 to-primary-600 text-white rounded-xl font-semibold text-sm shadow-lg shadow-primary-200/40 hover:shadow-xl transition-all active:scale-[0.98]">
                        Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons on Load
        lucide.createIcons();

        // Toggle for Modal Fields
        function toggleModalPwd(inputId, iconId) {
            const input = document.getElementById(inputId);
            const icon = document.getElementById(iconId);

            if (input.type === 'password') {
                input.type = 'text';
                icon.setAttribute('data-lucide', 'eye-off');
            } else {
                input.type = 'password';
                icon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        // Toggle for Main Login Field
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordInput.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            lucide.createIcons();
        }

        function openChangePasswordModal() {
            const modal = document.getElementById('changePasswordModal');
            const card = document.getElementById('changePasswordCard');
            modal.classList.remove('hidden');
            setTimeout(() => {
                card.classList.remove('scale-95');
                card.classList.add('scale-100');
            }, 10);
        }

        function closeChangePasswordModal() {
            const modal = document.getElementById('changePasswordModal');
            const card = document.getElementById('changePasswordCard');
            card.classList.remove('scale-100');
            card.classList.add('scale-95');
            setTimeout(() => {
                modal.classList.add('hidden');
            }, 200);
        }

        // Close modal when clicking outside
        document.getElementById('changePasswordModal')?.addEventListener('click', function (e) {
            if (e.target === this) {
                closeChangePasswordModal();
            }
        });
    </script>
</body>

</html>