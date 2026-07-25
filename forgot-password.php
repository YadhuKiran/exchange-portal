<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('/');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim($_POST['email'] ?? '');

    if ($email) {
        $stmt = db()->prepare('SELECT id, first_name FROM users WHERE email = ? AND status = ?');
        $stmt->execute([$email, 'active']);
        $user = $stmt->fetch();

        if ($user) {
            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO password_resets (email, token) VALUES (?, ?)')
                ->execute([$email, $token]);
            
            $resetLink = url('/reset-password.php?token=' . $token);
            
            if (function_exists('log_activity')) {
                log_activity('auth.password.reset_requested', 'Password reset requested', 'user', (int)$user['id']);
            }
            
            // For demo purposes, we show the link in the success message!
            $success = "Password reset instructions have been sent! <br><br><span class='text-xs text-emerald-800'>[DEMO MODE] Click here to reset: <a href='$resetLink' class='font-bold underline'>$resetLink</a></span>";
        } else {
            // Generic message to prevent email enumeration
            $success = "If an active account exists for that email, we have sent password reset instructions.";
        }
    } else {
        $error = 'Please enter your email address.';
    }
}

$hideNav = true;
$pageTitle = 'Forgot Password — ' . APP_NAME;
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
    <a href="<?= url('/login.php') ?>" class="ml-auto flex items-center gap-1 text-xs text-slate-500 hover:text-indigo-600 transition-colors font-medium">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
        Back to Login
    </a>
</div>
<section class="min-h-screen flex w-full">
    <!-- Left: Hero Image (hidden on mobile) -->
    <div class="hidden lg:flex w-1/2 bg-indigo-900 relative items-center justify-center overflow-hidden">
        <div class="absolute inset-0 bg-indigo-900/40 z-10 mix-blend-multiply"></div>
        <img src="<?= url('/assets/img/hero.png') ?>" alt="Campus" class="absolute inset-0 w-full h-full object-cover z-0 opacity-80 animate-pulse-soft" />
        <div class="z-20 text-center px-12 animate-fade-in-up">
            <h1 class="text-4xl font-extrabold text-white mb-4 drop-shadow-md">Account Recovery</h1>
            <p class="text-indigo-50 text-lg drop-shadow">We'll help you get back into your account securely.</p>
        </div>
    </div>
    <!-- Right: Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-gradient-to-br from-slate-50 via-white to-slate-100 pt-20 pb-12 px-4 relative">
        <div class="w-full max-w-md animate-fade-in-up">
        <div class="text-center mb-8">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center mx-auto mb-5 shadow-xl shadow-indigo-500/20">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">Forgot your password?</h2>
            <p class="text-slate-500 text-sm mt-1.5">No worries! Enter your email and we will send you reset instructions.</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-8">
            <?php if ($error): ?>
            <div class="mb-5 rounded-xl bg-red-50 border border-red-200/60 text-red-700 px-4 py-3 text-sm flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200/60 text-emerald-700 px-4 py-3 text-sm flex items-start gap-2.5">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span><?= $success ?></span>
            </div>
            <?php else: ?>
            <form method="post" class="space-y-5">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email address</label>
                    <input type="email" name="email" required autocomplete="email"
                           class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all placeholder:text-slate-400"
                           placeholder="you@university.edu">
                </div>
                <button type="submit" class="btn-hover w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm">
                    Send Reset Link
                </button>
            </form>
            <?php endif; ?>

            <p class="mt-6 text-center text-sm text-slate-500">
                Remembered your password? <a href="<?= url('/login.php') ?>" class="text-indigo-600 font-medium hover:text-indigo-700">Sign in</a>
            </p>
        </div>
        </div>
    </div>
</section>
<?php require __DIR__ . '/includes/landing-footer.php'; ?>
