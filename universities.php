<?php
require_once __DIR__ . '/includes/init.php';

$universities = db()->query("SELECT * FROM universities WHERE status='active' ORDER BY name")->fetchAll();

$uniDomains = [
    'Massachusetts Institute of Technology' => 'mit.edu',
    'University of Oxford' => 'ox.ac.uk',
    'National University of Singapore' => 'nus.edu.sg',
    'University of Toronto' => 'utoronto.ca',
    'Technical University of Munich' => 'tum.de',
    'University of Sydney' => 'sydney.edu.au',
    'Sciences Po Paris' => 'sciencespo.fr',
    'University of Tokyo' => 'u-tokyo.ac.jp',
    'University of Amsterdam' => 'uva.nl',
    'ETH Zurich' => 'ethz.ch',
    'Seoul National University' => 'snu.ac.kr',
    'University of Sao Paulo' => 'usp.br',
    'Complutense University of Madrid' => 'ucm.es',
    'Bocconi University' => 'unibocconi.it',
    'Stockholm University' => 'su.se',
    'Asia University' => 'asia.edu.tw',
    'Asia university' => 'asia.edu.tw',
    'Presidency University' => 'presiuniv.ac.in'
];

$pageTitle = 'Partner Universities — ' . APP_NAME;
require __DIR__ . '/includes/landing-header.php';
?>
<section class="min-h-screen pt-28 pb-20 bg-gradient-to-br from-slate-50 via-white to-slate-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="text-center max-w-2xl mx-auto mb-12">
            <a href="<?= url('/') ?>" class="inline-flex items-center gap-1 text-xs text-slate-400 hover:text-indigo-600 transition-colors mb-4">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16l-4-4m0 0l4-4m-4 4h18"/></svg>
                Back to Home
            </a>
            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 text-indigo-700 text-xs font-semibold mb-4">Our Network</span>
            <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900 tracking-tight">All Partner Universities</h1>
            <p class="mt-3 text-slate-500 text-lg"><?= count($universities) ?> institutions worldwide</p>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($universities as $u): ?>
            <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6 hover:border-indigo-200/60 transition-all hover:shadow-md">
                <div class="flex items-start gap-4">
                    <div class="w-14 h-14 shrink-0 flex items-center justify-center bg-white rounded-xl border border-slate-100 p-1 relative overflow-hidden">
                        <?php 
                        $domain = $uniDomains[$u['name']] ?? '';
                        if ($domain): 
                        ?>
                        <img src="https://www.google.com/s2/favicons?domain=<?= urlencode($domain) ?>&sz=128" alt="<?= e($u['name']) ?> Logo" class="w-full h-full object-contain rounded-xl" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';" />
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-violet-50 flex items-center justify-center hidden">
                            <span class="text-lg font-bold text-indigo-600"><?= e(substr($u['name'], 0, 2)) ?></span>
                        </div>
                        <?php else: ?>
                        <div class="absolute inset-0 bg-gradient-to-br from-indigo-50 to-violet-50 flex items-center justify-center">
                            <span class="text-lg font-bold text-indigo-600"><?= e(substr($u['name'], 0, 2)) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="font-semibold text-slate-900 text-sm leading-snug"><?= e($u['name'])?></h3>
                        <p class="text-xs text-slate-500 mt-1"><?= e($u['city'])?>, <?= e($u['country'])?></p>
                        <div class="flex items-center gap-2 mt-2">
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded-full"><?= e($u['code'])?></span>
                            <?php if ($u['status'] === 'active'): ?>
                            <span class="inline-flex items-center gap-1 text-[11px] font-medium text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Active</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php if (!$universities): ?>
        <div class="text-center py-12 text-slate-500 text-sm">No universities available at this time.</div>
        <?php endif; ?>
    </div>
</section>
<?php require __DIR__ . '/includes/landing-footer.php'; ?>
