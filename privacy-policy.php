<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Privacy Policy';
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
            <h2>1. Information We Collect</h2>
            <p>At GlobalExchange, we collect information to provide better services to our users. This includes personal information provided during registration such as name, email address, university affiliation, and phone numbers. We may also collect documentation related to exchange programs, such as passports, transcripts, and visa documents.</p>

            <h2>2. How We Use Information</h2>
            <p>We use the information we collect to operate, maintain, and provide the features and functionality of the Service, to communicate with you, and to process applications for international mobility programs.</p>

            <h2>3. Information Sharing</h2>
            <p>We do not share personal information with companies, organizations, or individuals outside of GlobalExchange except with your consent or as necessary to process your exchange applications with partner universities.</p>

            <h2>4. Data Security</h2>
            <p>We implement appropriate technical and organizational measures to protect your personal data against unauthorized or unlawful processing, accidental loss, destruction, or damage.</p>
        </main>
    </div>

    <footer class="bg-slate-900 text-slate-400 py-6 text-center">
        <div class="max-w-4xl mx-auto px-6">
            <p>&copy; <?= date('Y') ?> <?= e(APP_NAME) ?>. All rights reserved.</p>
        </div>
    </footer>
</body>
</html>
