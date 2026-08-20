<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) {
    redirect('/');
}


$universities = db()->query("SELECT id, name, code FROM universities WHERE status='active' ORDER BY name")->fetchAll();
$error = '';
$tab = $_GET['type'] ?? 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $tab = $_POST['reg_type'] ?? 'student';

    if ($tab === 'student') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $first = trim($_POST['first_name'] ?? '');
        $last = trim($_POST['last_name'] ?? '');
        $uniIdInput = $_POST['university_id'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $otherUni = trim($_POST['other_university'] ?? '');

        if (strlen($password) < 8) {
            $error = 'Password must be at least 8 characters.';
        } elseif (!$first || !$last || !$uniIdInput) {
            $error = 'Please fill in all required fields.';
        } elseif ($uniIdInput === 'other' && !$otherUni) {
            $error = 'Please enter your university name.';
        } else {
            $check = db()->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$email]);
            if ($check->fetch()) {
                $error = 'An account with this email already exists.';
            } else {
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    $uniId = (int) $uniIdInput;
                    if ($uniIdInput === 'other') {
                        $checkUni = $pdo->prepare("SELECT id FROM universities WHERE LOWER(name) = LOWER(?)");
                        $checkUni->execute([$otherUni]);
                        $existingUni = $checkUni->fetch();
                        if ($existingUni) {
                            $uniId = (int) $existingUni['id'];
                        } else {
                            $tempCode = 'TBD-' . strtoupper(substr(uniqid(), -4));
                            $pdo->prepare("INSERT INTO universities (name, code, country, city, status) VALUES (?, ?, ?, ?, 'inactive')")
                                ->execute([$otherUni, $tempCode, 'Unknown', 'Unknown']);
                            $uniId = (int) $pdo->lastInsertId();
                        }
                    }

                    $hash = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare('INSERT INTO users (role, email, password_hash, first_name, last_name) VALUES (?,?,?,?,?)');
                    $stmt->execute(['student', $email, $hash, $first, $last]);
                    $userId = (int) $pdo->lastInsertId();
                    $num = 'STU-' . date('Y') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
                    $stmt = $pdo->prepare('INSERT INTO students (user_id, student_number, university_id, phone) VALUES (?,?,?,?)');
                    $stmt->execute([$userId, $num, $uniId, $phone ?: null]);
                    notify($userId, 'Welcome!', 'Your student account has been created. Start your exchange application today.');
                    if (function_exists('log_activity')) {
                        log_activity('user.registered', 'New student registered: ' . $first . ' ' . $last, 'user', $userId);
                    }
                    $pdo->commit();
                    flash('success', 'Account created! Please sign in.');
                    redirect('/login.php');
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Registration failed. Please try again.';
                }
            }
        }
    } elseif ($tab === 'university') {
        $uniName = trim($_POST['uni_name'] ?? '');
        $uniCode = trim($_POST['uni_code'] ?? '');
        $country = trim($_POST['country'] ?? '');
        $city = trim($_POST['city'] ?? '');
        $coordFirst = trim($_POST['coord_first_name'] ?? '');
        $coordLast = trim($_POST['coord_last_name'] ?? '');
        $coordEmail = trim($_POST['coord_email'] ?? '');
        $coordPw = $_POST['coord_password'] ?? '';
        $coordDept = trim($_POST['coord_department'] ?? 'International Programs');

        if (!$uniName || !$uniCode || !$country || !$city || !$coordFirst || !$coordLast || !$coordEmail) {
            $error = 'Please fill in all required fields.';
        } elseif (strlen($coordPw) < 8) {
            $error = 'Coordinator password must be at least 8 characters.';
        } else {
            $check = db()->prepare('SELECT id FROM users WHERE email = ?');
            $check->execute([$coordEmail]);
            if ($check->fetch()) {
                $error = 'A user with this email already exists.';
            } else {
                $checkCode = db()->prepare('SELECT id FROM universities WHERE code = ?');
                $checkCode->execute([$uniCode]);
                if ($checkCode->fetch()) {
                    $error = 'A university with this code already exists.';
                } else {
                    $pdo = db();
                    $pdo->beginTransaction();
                    try {
                        $pdo->prepare('INSERT INTO universities (name, code, country, city, status) VALUES (?,?,?,?,?)')
                            ->execute([$uniName, strtoupper($uniCode), $country, $city, 'inactive']);
                        $uniId = (int) $pdo->lastInsertId();

                        $hash = password_hash($coordPw, PASSWORD_DEFAULT);
                        $pdo->prepare('INSERT INTO users (role, email, password_hash, first_name, last_name, status) VALUES (?,?,?,?,?,?)')
                            ->execute(['coordinator', $coordEmail, $hash, $coordFirst, $coordLast, 'active']);
                        $coordUserId = (int) $pdo->lastInsertId();

                        $pdo->prepare('INSERT INTO coordinators (user_id, university_id, department) VALUES (?,?,?)')
                            ->execute([$coordUserId, $uniId, $coordDept]);

                        notify($coordUserId, 'University Registered', 'Your university has been registered. An administrator will activate your account shortly.');
                        $pdo->commit();
                        flash('success', 'University registered! An admin will activate your account. You can sign in once approved.');
                        redirect('/login.php?role=coordinator');
                    } catch (Exception $e) {
                        $pdo->rollBack();
                        $error = 'Registration failed. Please try again.';
                    }
                }
            }
        }
    }
}

$hideNav = true;
$pageTitle = 'Register — ' . APP_NAME;
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
            <h1 class="text-4xl font-extrabold text-white mb-4 drop-shadow-md">Join Our Global Network</h1>
            <p class="text-indigo-50 text-lg drop-shadow">Start your international adventure or register your institution today.</p>
        </div>
    </div>
    <!-- Right: Registration Form -->
    <div class="w-full lg:w-1/2 flex items-center justify-center bg-gradient-to-br from-slate-50 via-white to-slate-100 pt-20 pb-12 px-4 relative">
        <div class="w-full max-w-lg animate-fade-in-up">
        <div class="text-center mb-8 reveal">
            <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 text-white flex items-center justify-center mx-auto mb-5 shadow-xl shadow-indigo-500/20">
                <svg viewBox="0 0 36 36" class="w-8 h-8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="18" cy="18" r="14"/><ellipse cx="18" cy="18" rx="6" ry="14"/>
                    <line x1="4" y1="18" x2="32" y2="18"/>
                    <path d="M10 14l8-5 8 5v4"/><path d="M10 18v4l8 4 8-4v-4"/>
                </svg>
            </div>
            <h2 class="text-2xl font-bold text-slate-900">Join Global Exchange</h2>
            <p class="text-slate-500 text-sm mt-1.5">Create your account to start your international journey</p>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-2 mb-4">
            <div class="grid grid-cols-2 gap-2">
                <a href="<?= url('/register.php?type=student') ?>" class="flex flex-col items-center gap-1 px-4 py-3 rounded-xl text-center transition-all <?= $tab === 'student' ? 'bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 border border-transparent' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                    <span class="text-xs font-semibold">Student</span>
                    <span class="text-[10px] <?= $tab === 'student' ? 'text-indigo-500' : 'text-slate-400' ?>">I want to study abroad</span>
                </a>
                <a href="<?= url('/register.php?type=university') ?>" class="flex flex-col items-center gap-1 px-4 py-3 rounded-xl text-center transition-all <?= $tab === 'university' ? 'bg-indigo-50 text-indigo-700 shadow-sm border border-indigo-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50 border border-transparent' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="text-xs font-semibold">University</span>
                    <span class="text-[10px] <?= $tab === 'university' ? 'text-indigo-500' : 'text-slate-400' ?>">Register my institution</span>
                </a>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-xl shadow-slate-200/50 border border-slate-200/60 p-8">
            <?php if ($error): ?>
            <div class="mb-5 rounded-xl bg-red-50 border border-red-200/60 text-red-700 px-4 py-3 text-sm flex items-center gap-2.5">
                <svg class="w-4 h-4 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span><?= e($error) ?></span>
            </div>
            <?php endif; ?>

            <?php if ($tab === 'student'): ?>
            <!-- STUDENT REGISTRATION -->
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="reg_type" value="student">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First name</label>
                        <input type="text" name="first_name" required value="<?= e($_POST['first_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Alex">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last name</label>
                        <input type="text" name="last_name" required value="<?= e($_POST['last_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Johnson">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Email</label>
                    <input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="you@university.edu">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="password" required minlength="8" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Minimum 8 characters">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Home university</label>
                    <select name="university_id" id="university_select" required class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" onchange="document.getElementById('other_uni_wrapper').style.display = this.value === 'other' ? 'block' : 'none'">
                        <option value="">Select your university</option>
                        <?php foreach ($universities as $u): ?>
                        <option value="<?= $u['id'] ?>" <?= (($_POST['university_id'] ?? '') == $u['id']) ? 'selected' : '' ?>><?= e($u['name']) ?> (<?= e($u['code']) ?>)</option>
                        <?php endforeach; ?>
                        <option value="other" <?= (($_POST['university_id'] ?? '') === 'other') ? 'selected' : '' ?>>Other (Type below)</option>
                    </select>
                </div>
                <div id="other_uni_wrapper" style="display: <?= (($_POST['university_id'] ?? '') === 'other') ? 'block' : 'none' ?>;">
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Enter your university name</label>
                    <input type="text" name="other_university" value="<?= e($_POST['other_university'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Harvard University">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Phone <span class="text-slate-400 font-normal">(optional)</span></label>
                    <input type="text" name="phone" value="<?= e($_POST['phone'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                </div>
                <button type="submit" class="btn-hover w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm mt-2">
                    Create Student Account
                </button>
            </form>
            <?php endif; ?>

            <?php if ($tab === 'university'): ?>
            <!-- UNIVERSITY REGISTRATION -->
            <form method="post" class="space-y-4">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <input type="hidden" name="reg_type" value="university">

                <div class="flex items-center gap-2 pb-2 border-b border-slate-100 mb-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    <span class="text-sm font-semibold text-slate-700">University Information</span>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">University name</label>
                    <input type="text" name="uni_name" required value="<?= e($_POST['uni_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Stanford University">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Code</label>
                        <input type="text" name="uni_code" required value="<?= e($_POST['uni_code'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm font-mono uppercase focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. STN">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Department</label>
                        <input type="text" name="coord_department" value="<?= e($_POST['coord_department'] ?? 'International Programs') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Country</label>
                        <input type="text" name="country" required value="<?= e($_POST['country'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. United States">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">City</label>
                        <input type="text" name="city" required value="<?= e($_POST['city'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="e.g. Stanford">
                    </div>
                </div>

                <div class="flex items-center gap-2 pt-2 pb-2 border-b border-slate-100 mt-4 mb-2">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="text-sm font-semibold text-slate-700">Coordinator Information</span>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">First name</label>
                        <input type="text" name="coord_first_name" required value="<?= e($_POST['coord_first_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1.5">Last name</label>
                        <input type="text" name="coord_last_name" required value="<?= e($_POST['coord_last_name'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Coordinator email</label>
                    <input type="email" name="coord_email" required value="<?= e($_POST['coord_email'] ?? '') ?>" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="coordinator@university.edu">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Password</label>
                    <input type="password" name="coord_password" required minlength="8" class="w-full rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all" placeholder="Minimum 8 characters">
                </div>
                <button type="submit" class="btn-hover w-full bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white font-semibold py-2.5 rounded-xl shadow-md shadow-indigo-500/20 text-sm mt-2">
                    Register University
                </button>
            </form>
            <?php endif; ?>

            <p class="mt-5 text-center text-sm text-slate-500">
                Already have an account? <a href="<?= url('/login.php') ?>" class="text-indigo-600 font-medium hover:text-indigo-700">Sign in</a>
            </p>
        </div>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/landing-footer.php'; ?>
