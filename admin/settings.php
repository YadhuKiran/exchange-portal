<?php
require_once __DIR__ . '/../includes/init.php';
require_role(['admin']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $user = current_user();
    foreach ($_POST['settings'] ?? [] as $key => $value) {
        set_setting($key, $value, (int) $user['id']);
    }
    log_activity('settings.updated', 'System settings were updated', 'settings', null);
    flash('success', 'Settings saved.');
    redirect('/admin/settings.php');
}

$pageTitle = 'System Settings';
$activeNav = 'settings';
$settings = all_settings();
require __DIR__ . '/../includes/layout.php';
?>
<div class="max-w-3xl">
    <div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-8 card-hover">
        <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-100">
            <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </div>
            <div>
                <h2 class="text-sm font-semibold text-slate-900">System Configuration</h2>
                <p class="text-xs text-slate-500">Manage global settings for the exchange portal</p>
            </div>
        </div>
        <form method="post" class="space-y-6">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <?php
            $group = '';
            foreach ($settings as $s):
                if ($s['setting_group'] !== $group):
                    $group = $s['setting_group'];
                    echo '<div class="pt-4"><h3 class="text-xs font-semibold uppercase tracking-widest text-slate-500">' . e(ucfirst($group)) . '</h3><div class="mt-3 space-y-4">';
                endif;
            ?>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1.5"><?= e($s['label']) ?></label>
                <?php if ($s['data_type'] === 'boolean'): ?>
                <select name="settings[<?= e($s['setting_key']) ?>]" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                    <option value="1" <?= $s['setting_value'] === '1' ? 'selected' : '' ?>>Enabled</option>
                    <option value="0" <?= $s['setting_value'] === '0' ? 'selected' : '' ?>>Disabled</option>
                </select>
                <?php else: ?>
                <input type="<?= $s['data_type'] === 'integer' ? 'number' : 'text' ?>" name="settings[<?= e($s['setting_key']) ?>]"
                       value="<?= e($s['setting_value']) ?>" class="w-full rounded-xl border border-slate-200 px-4 py-2.5 text-sm focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
                <?php endif; ?>
            </div>
            <?php
                if (next($settings) === false || $settings[array_search($s, $settings, true) + 1]['setting_group'] !== $group) {
                    echo '</div></div>';
                }
            endforeach;
            ?>
            <?php if (!$settings): ?>
            <div class="p-4 bg-amber-50 border border-amber-200 rounded-xl text-sm text-amber-700">Run <code class="bg-amber-100 px-1.5 py-0.5 rounded">database/migrations/001_enterprise.sql</code> to enable settings.</div>
            <?php endif; ?>
            <div class="pt-4 border-t border-slate-100">
                <button type="submit" class="btn-hover bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white px-6 py-2.5 rounded-xl text-sm font-semibold shadow-md shadow-indigo-500/20">Save Settings</button>
            </div>
        </form>
    </div>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
