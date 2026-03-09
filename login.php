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
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;800&display=swap');

        body {
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>

<body class="bg-slate-50 flex items-center justify-center min-h-screen p-6">
    <div class="max-w-md w-full bg-white p-10 rounded-[3rem] shadow-2xl border border-blue-50">
        <div class="text-center mb-10">
            <div
                class="w-20 h-20 bg-blue-600 text-white rounded-[2rem] flex items-center justify-center mx-auto mb-4 shadow-lg shadow-blue-200">
                <i data-lucide="hospital" class="w-10 h-10"></i>
            </div>
            <p class="text-xs text-gray-400 font-bold first-letter:uppercase tracking-widest mt-2">Mattu Karl Specialized Hospital
            </p>
        </div>

       

        <?php if ($error): ?>
            <div
                class="mb-6 p-4 bg-red-50 text-red-600 text-xs font-bold rounded-2xl border border-red-100 flex items-center gap-2 animate-bounce">
                <i data-lucide="alert-circle" class="w-4 h-4"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="text-[10px] font-black text-blue-600/60 first-letter:uppercase tracking-[0.2em] ml-4">Hospital
                    Email</label>
                <input type="email" name="email" required placeholder="name@mattu.edu"
                    class="w-full mt-1 px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-gray-700 shadow-sm transition-all">
            </div>

            <div class="relative">
                <label class="text-[10px] font-black text-blue-600/60 first-letter:uppercase tracking-[0.2em] ml-4">Security
                    Password</label>
                <div class="relative mt-1">
                    <input type="password" id="password" name="password" required placeholder="••••••••"
                        class="w-full px-6 py-4 bg-gray-50 border-none rounded-2xl focus:ring-2 focus:ring-blue-500 outline-none font-bold text-gray-700 shadow-sm transition-all pr-14">
                    <button type="button" onclick="togglePassword()"
                        class="absolute right-5 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600">
                        <i id="eye-icon" data-lucide="eye" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>

            <button type="submit" name="login"
                class="w-full py-5 bg-blue-600 text-white rounded-2xl font-black shadow-xl shadow-blue-200 hover:bg-blue-700 transition-all scale-100 active:scale-95 text-xs tracking-widest first-letter:uppercase">
                Authorize Access
            </button>
        </form>

        <div class="mt-8 pt-8 border-t border-gray-50 text-center">
            <p class="text-sm text-gray-500 font-medium">

                <button onclick="openChangePasswordModal()"
                    class="text-blue-600 font-black hover:underline underline-offset-4 ml-1">Change Password</button>
            </p>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="changePasswordModal"
        class="hidden fixed inset-0 z-[100] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-md transition-all duration-300">
        <div id="changePasswordCard"
            class="bg-white rounded-[2.5rem] w-full max-w-md p-8 shadow-2xl border border-gray-100 transform scale-95 transition-all duration-300">

            <!-- Header -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center gap-3">
                    <div
                        class="w-12 h-12 bg-blue-600 text-white rounded-xl flex items-center justify-center shadow-lg shadow-blue-100">
                        <i data-lucide="key" class="w-6 h-6"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-black text-gray-800 first-letter:uppercase tracking-tighter leading-none">Credential
                            Reset</h3>
                        <p class="text-[8px] text-gray-400 font-bold tracking-widest first-letter:uppercase mt-1">Update your login
                            security</p>
                    </div>
                </div>
                <button onclick="closeChangePasswordModal()" class="text-gray-400 hover:text-red-500 transition-colors">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>

            <form method="POST" action="" class="space-y-4">
                <!-- Email Field -->
                <div>
                    <label class="text-[9px] font-black text-blue-600/60 first-letter:uppercase tracking-widest ml-4">Hospital
                        Email</label>
                    <input type="email" name="email" required placeholder="Verify your account email"
                        class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-sm shadow-inner transition-all">
                </div>

                <!-- CURRENT PASSWORD -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-gray-400 first-letter:uppercase tracking-widest ml-4">Current
                        Password</label>
                    <div class="relative">
                        <input type="password" name="current_password" id="modal_current_password" required
                            placeholder="••••••••"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-sm shadow-inner transition-all pr-12">
                        <button type="button" onclick="toggleModalPwd('modal_current_password', 'eye-icon-current')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 focus:outline-none">
                            <i id="eye-icon-current" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- NEW PASSWORD -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-gray-400 first-letter:uppercase tracking-widest ml-4">New
                        Password</label>
                    <div class="relative">
                        <input type="password" name="new_password" id="modal_new_password" required
                            placeholder="••••••••"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-sm shadow-inner transition-all pr-12">
                        <button type="button" onclick="toggleModalPwd('modal_new_password', 'eye-icon-new')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 focus:outline-none">
                            <i id="eye-icon-new" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <!-- CONFIRM PASSWORD -->
                <div class="space-y-1">
                    <label class="text-[9px] font-black text-gray-400 first-letter:uppercase tracking-widest ml-4">Confirm New
                        Password</label>
                    <div class="relative">
                        <input type="password" name="confirm_password" id="modal_confirm_password" required
                            placeholder="••••••••"
                            class="w-full px-5 py-3.5 bg-gray-50 border-none rounded-xl focus:ring-2 focus:ring-blue-500 font-bold text-sm shadow-inner transition-all pr-12">
                        <button type="button" onclick="toggleModalPwd('modal_confirm_password', 'eye-icon-conf')"
                            class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-blue-600 focus:outline-none">
                            <i id="eye-icon-conf" data-lucide="eye" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t border-gray-50">
                    <button type="button" onclick="closeChangePasswordModal()"
                        class="px-6 py-3 text-gray-400 rounded-xl font-bold text-[10px] first-letter:uppercase tracking-widest hover:bg-gray-50 transition-all">
                        Cancel
                    </button>
                    <button type="submit" name="do_reset"
                        class="px-8 py-3 bg-gray-900 text-white rounded-xl font-black text-[10px] first-letter:uppercase tracking-[0.3em] shadow-xl hover:bg-black transition-all active:scale-95">
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