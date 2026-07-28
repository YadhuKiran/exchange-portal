<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Cookie Policy';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?= e($pageTitle . ' - ' . APP_NAME) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 text-slate-900 antialiased font-sans flex flex-col min-h-screen">
    <div class="flex-grow">
        <header class="bg-white border-b border-slate-200">
            <div class="max-w-4xl mx-auto px-6 py-8">
                <a href="<?= url('/') ?>" class="inline-flex items-center gap-2 text-indigo-600 font-bold hover:text-indigo-700 transition">
                    &larr; Back to Home
                </a>
                <h1 class="text-3xl font-bold mt-4"><?= e($pageTitle) ?></h1>
                <p class="text-slate-500 mt-2">Last updated: June 2026</p>
            </div>
        </header>

        <main class="max-w-4xl mx-auto px-6 py-12 prose prose-slate">
            <h2>1. What are Cookies?</h2>
            <p>Cookies are small pieces of text sent by your web browser by a website you visit. A cookie file is stored in your web browser and allows the Service or a third-party to recognize you and make your next visit easier and the Service more useful to you.</p>

            <h2>2. How We Use Cookies</h2>
            <p>When you use and access the Service, we may place a number of cookie files in your web browser. We use cookies to enable certain functions of the Service, specifically to manage user authentication and session management (keeping you logged in securely).</p>

            <h2>3. Types of Cookies We Use</h2>
            <p><strong>Essential Cookies:</strong> We use essential cookies to authenticate users and prevent fraudulent use of user accounts. The platform requires these cookies to operate properly.</p>

            <h2>4. Your Choices Regarding Cookies</h2>
            <p>If you'd like to delete cookies or instruct your web browser to delete or refuse cookies, please visit the help pages of your web browser. Please note, however, that if you delete cookies or refuse to accept them, you might not be able to use all of the features we offer, and some of our pages might not display properly.</p>
        </main>
    </div>

    

    <footer class="bg-slate-900 text-slate-400 py-6 text-center">
        <div class="max-w-4xl mx-auto px-6">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
