<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('/');
}

$error = '';
$selectedRole = $_POST['role'] ?? $_GET['role'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = $_POST['role'] ?? 'student';

    if (class_exists('App\Auth')) {
        $check = App\Auth::checkBruteForce($email);
        if ($check['locked']) {
            $error = $check['message'];
        } else {
            $dbRole = $selectedRole === 'university' ? 'coordinator' : $selectedRole;
            $stmt = db()->prepare('SELECT u.* FROM users u LEFT JOIN students s ON s.user_id = u.id WHERE (u.email = ? OR s.student_number = ?) AND u.status = ? AND u.role = ?');
            $stmt->execute([$email, $email, 'active', $dbRole]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_role'] = $user['role'];
                $_SESSION['login_time'] = time();
                if (function_exists('log_activity')) {
                    log_activity('auth.login', $user['first_name'] . ' ' . $user['last_name'] . ' signed in', 'user', (int) $user['id']);
                }
                if ($selectedRole === 'university') {
                    redirect('/university/index.php');
                }
                redirect('/');
            }

            App\Auth::logFailedAttempt($email, $_SERVER['REMOTE_ADDR'] ?? '');
            $error = 'Invalid email or password for selected role.';
            if (!empty($check['message'])) {
                $error .= ' ' . $check['message'];
            }
        }
    } else {
        $dbRole = $selectedRole === 'university' ? 'coordinator' : $selectedRole;
        $stmt = db()->prepare('SELECT u.* FROM users u LEFT JOIN students s ON s.user_id = u.id WHERE (u.email = ? OR s.student_number = ?) AND u.status = ? AND u.role = ?');
        $stmt->execute([$email, $email, 'active', $dbRole]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['login_time'] = time();
            redirect('/');
        }
        $error = 'Invalid email or password for selected role.';
    }
}

$hideNav = true;
$pageTitle = 'Sign In — ' . APP_NAME;
require __DIR__ . '/includes/landing-header.php';
?>
<div class="fixed top-0 inset-x-0 z-50 bg-white/90 nav-blur border-b border-slate-200/40 h-14 flex items-center px-4 sm:px-6">
    <a href="<?= url('/') ?>" class="flex items-center gap-2 group">
        <div class="w-7 h-7 rounded-lg bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center shadow-sm">
            <svg viewBox="0 0 36 36" class="w-4 h-4 text-white" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="18" cy="18" r="14"/><ellipse cx="18" cy="18" rx="6" ry="14"/>
                <line x1="4" y1="18" x2="32" y2="18"/>
                <path d="M10 14l8-5 8 5v4"/><path d="M10 18v4l8 4 8-4v-4"/>
            </svg>
        </div>
        <span class="font-bold text-sm text-slate-900">Global<span class="text-indigo-600">Exchange</span></span>
    </a>
    <a href="<?= url('/') ?>" class="ml-auto flex items-center gap-1 text-xs text-slate-500 hover:text-indigo-600 transition-colors font-medium">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
        Back to Home
    </a>
</div>
<section class="min-h-screen flex w-full">
    <!-- Left: Hero Image (hidden on mobile) -->
    <div class="hidden lg:flex w-1/2 bg-indigo-900 relative items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-indigo-900/40 z-10 mix-blend-multiply"></div>
        <img src="<?= url('/assets/img/hero.png') ?>" alt="Campus" class="absolute inset-0 w-full h-full object-cover z-0 opacity-80 animate-pulse-soft" />
        <div class="z-20 text-center px-12 animate-fade-in-up">
            <h1 class="text-4xl font-extrabold text-white mb-4 drop-shadow-md">Empower Your Global Journey</h1>
            <p class="text-indigo-50 text-lg drop-shadow">Connect with top universities and manage your exchange program effortlessly.</p>
        </div>
    </div>
    <!-- Right: Login Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-gradient-to-br from-slate-50 via-white to-slate-100 pt-20 pb-12 px-4 relative">
        <div class="w-full max-w-lg animate-fade-in-up">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center mx-auto mb-5 shadow-xl shadow-indigo-500/20">
                <svg viewBox="0 0 36 36" class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="18" r="14"/><ellipse cx="18" cy="18" rx="6" ry="14"/>
                    <line x1="4" y1="18" x2="32" y2="18"/>
                    <path d="M10 14l8-5 8 5v4"/><path d="M10 18v4l8 4 8-4v-4"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">Welcome back</h2>
            <p class="text-slate-500 text-sm mt-1.5">Sign in to your Global Exchange account</p>
        </div>

        <!-- Role Tabs -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-2 mb-4">
            <div class="grid grid-cols-3 gap-2">
                <?php
$roles = [
    'student'     => ['label' => 'Student', 'icon' => 'M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253', 'desc' => 'Exchange Student'],
    'coordinator' => ['label' => 'Coordinator', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z', 'desc' => 'University Staff'],
    'admin'       => ['label' => 'Admin', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z', 'desc' => 'System Admin'],
];
                foreach ($roles as $key => $r):
                    $active = $selectedRole === $key;
                ?>
                <button type="button" data-role="<?= $key ?>" class="role-tab flex flex-col items-center gap-1.5 px-3 py-3 rounded-xl text-center transition-all <?= $active ? 'bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 border border-transparent' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $r['icon'] ?>"/></svg>
                    <span class="text-xs font-semibold"><?= $r['label'] ?></span>
                    <span class="text-[10px] <?= $active ? 'text-indigo-500' : 'text-slate-400' ?>"><?= $r['desc'] ?></span>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Login Form -->
        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-8">
            <?php if ($error): ?>
            <div class="mb-5 rounded-xl bg-red-50 border border-red-200/60 text-red-700 px-4 py-3 text-sm flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <form method="post" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="role" id="roleInput" value="<?= e($selectedRole) ?>">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address or Student ID</label>
                    <input type="text" name="email" required value="<?= e($_POST['email'] ?? '') ?>" autocomplete="email"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-slate-400"
                           placeholder="you@university.edu or MIT-2024-1042">
                </div>
                <div>
                    <div class="flex items-center justify-between mb-1.5">
                        <label class="block text-sm font-medium text-slate-700">Password</label>
                        <a href="<?= url('/forgot-password.php') ?>" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">Forgot password?</a>
                    </div>
                    <input type="password" name="password" required autocomplete="current-password"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-slate-400"
                           placeholder="••••••••">
                </div>
                <button type="submit" class="btn-hover w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">
                    Sign in as <span id="roleLabel"><?= ucfirst($selectedRole) ?></span>
                </button>
            </form>

            <p class="mt-6 text-center text-sm text-slate-500">
                New student? <a href="<?= url('/register.php') ?>" class="text-indigo-600 font-medium hover:text-indigo-700">Create an account</a>
            </p>
            <p class="mt-2 text-center text-sm text-slate-500">
                Partner university? <a href="<?= url('/register.php?type=university') ?>" class="text-indigo-600 font-medium hover:text-indigo-700">Register your institution</a>
            </p>

            <div class="mt-6 p-4 rounded-xl bg-slate-50 border border-slate-100 text-xs text-slate-600 space-y-3">
                <p class="font-semibold text-slate-700 text-sm flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                    One-Click Demo Access
                </p>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="document.querySelector('[data-role=\'admin\']').click(); document.querySelector('input[name=\'email\']').value='admin@exchangeportal.com'; document.querySelector('input[name=\'password\']').value='password123'; setTimeout(() => document.forms[0].submit(), 100);" class="flex flex-col items-center justify-center p-2 rounded-lg bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-sm hover:text-indigo-600 transition-all font-medium">
                        Admin
                    </button>
                    <button type="button" onclick="document.querySelector('[data-role=\'coordinator\']').click(); document.querySelector('input[name=\'email\']').value='mia.harris@coord1.edu'; document.querySelector('input[name=\'password\']').value='password123'; setTimeout(() => document.forms[0].submit(), 100);" class="flex flex-col items-center justify-center p-2 rounded-lg bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-sm hover:text-indigo-600 transition-all font-medium">
                        Coordinator
                    </button>
                    <button type="button" onclick="document.querySelector('[data-role=\'student\']').click(); document.querySelector('input[name=\'email\']').value='student01@demo.exchangeportal.com'; document.querySelector('input[name=\'password\']').value='password123'; setTimeout(() => document.forms[0].submit(), 100);" class="flex flex-col items-center justify-center p-2 rounded-lg bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-sm hover:text-indigo-600 transition-all font-medium">
                        Student
                    </button>
                </div>
            </div>
        </div>
        </div>
    </div>
</section>

<script>
document.querySelectorAll('.role-tab').forEach(function(tab) {
    tab.addEventListener('click', function() {
        var role = this.getAttribute('data-role');
        document.getElementById('roleInput').value = role;
        document.getElementById('roleLabel').textContent = role.charAt(0).toUpperCase() + role.slice(1);
        document.querySelectorAll('.role-tab').forEach(function(t) {
            t.classList.remove('bg-indigo-50', 'text-indigo-700', 'shadow-sm', 'border-indigo-200');
            t.classList.add('text-slate-500', 'hover:text-slate-700', 'hover:bg-slate-50', 'border-transparent');
            var span = t.querySelector('span:last-child');
            if (span) span.classList.remove('text-indigo-500');
        });
        this.classList.remove('text-slate-500', 'hover:text-slate-700', 'hover:bg-slate-50', 'border-transparent');
        this.classList.add('bg-indigo-50', 'text-indigo-700', 'shadow-sm', 'border-indigo-200');
        var desc = this.querySelector('span:last-child');
        if (desc) desc.classList.add('text-indigo-500');
    });
});
</script>

<?php require __DIR__ . '/includes/landing-footer.php'; ?>
