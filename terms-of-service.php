<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Terms of Service';
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
            <h2>1. Acceptance of Terms</h2>
            <p>By accessing or using the GlobalExchange platform, you agree to be bound by these Terms of Service. If you disagree with any part of the terms, then you may not access the service.</p>

            <h2>2. User Responsibilities</h2>
            <p>Users are responsible for maintaining the confidentiality of their account credentials and for all activities that occur under their account. You must immediately notify us of any unauthorized use of your account.</p>

            <h2>3. Academic Integrity</h2>
            <p>All documents and information submitted through the portal must be authentic, accurate, and up-to-date. Fraudulent submissions may result in immediate account termination and notification to respective universities.</p>

            <h2>4. Limitation of Liability</h2>
            <p>In no event shall GlobalExchange, nor its directors, employees, partners, agents, suppliers, or affiliates, be liable for any indirect, incidental, special, consequential or punitive damages, including without limitation, loss of profits, data, use, goodwill, or other intangible losses, resulting from your access to or use of or inability to access or use the Service.</p>
        </main>
    </div>

    <footer class="bg-slate-900 text-slate-400 py-6 text-center">
        <div class="max-w-4xl mx-auto px-6">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
