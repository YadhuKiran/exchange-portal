<?php
function stat_card(string $label, $value, string $color = 'brand', ?string $sub = null): string
{
    $colors = [
        'brand'   => ['from-indigo-500 to-violet-600', 'shadow-indigo-500/20'],
        'emerald' => ['from-emerald-500 to-teal-600', 'shadow-emerald-500/20'],
        'amber'   => ['from-amber-500 to-orange-600', 'shadow-amber-500/20'],
        'rose'    => ['from-rose-500 to-pink-600', 'shadow-rose-500/20'],
        'slate'   => ['from-slate-600 to-slate-700', 'shadow-slate-500/20'],
        'cyan'    => ['from-cyan-500 to-blue-600', 'shadow-cyan-500/20'],
        'violet'  => ['from-violet-500 to-purple-600', 'shadow-violet-500/20'],
    ];
    $c = $colors[$color] ?? $colors['brand'];
    $subHtml = $sub ? '<p class="text-xs text-white/70 mt-1.5">' . e($sub) . '</p>' : '';
    return '<div class="stat-card rounded-xl bg-gradient-to-br ' . $c[0] . ' p-5 text-white shadow-lg ' . $c[1] . ' cursor-default">
        <p class="text-sm font-medium text-white/80">' . e($label) . '</p>
        <p class="text-3xl font-bold mt-1 tracking-tight">' . e((string) $value) . '</p>' . $subHtml . '</div>';
}

function page_actions(string $addUrl, string $addLabel = 'Add New'): void
{
    echo '<div class="mb-6 flex flex-wrap items-center justify-between gap-3">
        <div></div>
        <a href="' . e(url($addUrl)) . '" class="btn-hover inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 hover:from-indigo-500 hover:to-violet-500 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md shadow-indigo-500/20">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
            ' . e($addLabel) . '
        </a></div>';
}

function empty_state(string $message, ?string $actionUrl = null, ?string $actionLabel = null): void
{
    echo '<div class="flex flex-col items-center justify-center py-16 px-6 bg-white rounded-2xl border border-slate-200 shadow-sm">
        <svg class="w-16 h-16 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <p class="text-slate-500 text-sm font-medium">' . e($message) . '</p>';
    if ($actionUrl) {
        echo '<a href="' . e(url($actionUrl)) . '" class="btn-hover mt-5 inline-flex items-center gap-2 bg-gradient-to-r from-indigo-600 to-violet-600 text-white text-sm font-semibold px-5 py-2.5 rounded-xl shadow-md shadow-indigo-500/20">' . e($actionLabel ?? 'Get started') . '</a>';
    }
    echo '</div>';
}

function premium_card(string $title, ?string $subtitle = null, string $size = 'full'): string
{
    $w = $size === 'full' ? '' : ' max-w-' . $size;
    $sub = $subtitle ? '<p class="text-sm text-slate-500 mt-0.5">' . e($subtitle) . '</p>' : '';
    return '<div class="bg-white rounded-2xl border border-slate-200/60 shadow-sm p-6' . $w . '">
        <h2 class="text-sm font-semibold text-slate-900">' . e($title) . '</h2>' . $sub;
}

function status_badge_enhanced(string $status): string
{
    $map = [
        'draft'        => ['bg-slate-100 text-slate-700', 'Draft'],
        'submitted'    => ['bg-blue-100 text-blue-800', 'Submitted'],
        'under_review' => ['bg-amber-100 text-amber-800', 'Under Review'],
        'approved'     => ['bg-emerald-100 text-emerald-800', 'Approved'],
        'rejected'     => ['bg-red-100 text-red-800', 'Rejected'],
        'pending'      => ['bg-amber-100 text-amber-800', 'Pending'],
        'open'         => ['bg-emerald-100 text-emerald-800', 'Open'],
        'closed'       => ['bg-slate-100 text-slate-600', 'Closed'],
        'active'       => ['bg-emerald-100 text-emerald-800', 'Active'],
        'inactive'     => ['bg-slate-100 text-slate-600', 'Inactive'],
        'verified'     => ['bg-emerald-100 text-emerald-800', 'Verified'],
        'expired'      => ['bg-red-100 text-red-800', 'Expired'],
        'dropped'      => ['bg-slate-100 text-slate-600', 'Dropped'],
        'completed'    => ['bg-blue-100 text-blue-800', 'Completed'],
    ];
    $c = $map[$status] ?? ['bg-slate-100 text-slate-700', ucwords(str_replace('_', ' ', $status))];
    $dot = match($status) {
        'approved','verified','active','open','completed' => 'bg-emerald-500',
        'rejected','expired' => 'bg-red-500',
        'draft','inactive','closed','dropped' => 'bg-slate-400',
        default => 'bg-amber-500',
    };
    return '<span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-medium ' . $c[0] . '"><span class="w-1.5 h-1.5 rounded-full ' . $dot . '"></span>' . e($c[1]) . '</span>';
}

function table_header(array $columns, bool $hasSerial = false): string
{
    $html = '<thead class="bg-slate-50/80 text-slate-500 text-xs uppercase tracking-wider font-semibold"><tr>';
    if ($hasSerial) {
        $html .= '<th class="px-6 py-3.5 text-left w-12">#</th>';
    }
    foreach ($columns as $col) {
        $align = ($col['align'] ?? 'left') === 'right' ? 'text-right' : 'text-left';
        $html .= '<th class="px-6 py-3.5 ' . $align . '">' . e($col['label']) . '</th>';
    }
    $html .= '</tr></thead>';
    return $html;
}

function section_title(string $title, ?string $subtitle = null): string
{
    $sub = $subtitle ? '<p class="text-sm text-slate-500 mt-0.5">' . e($subtitle) . '</p>' : '';
    return '<div class="mb-6"><h2 class="text-lg font-bold text-slate-900">' . e($title) . '</h2>' . $sub . '</div>';
}

function search_box(string $placeholder = 'Search...', string $id = 'tableSearch'): string
{
    return '<div class="relative">
        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
        <input type="text" id="' . e($id) . '" placeholder="' . e($placeholder) . '" class="w-full sm:w-64 pl-9 pr-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700 placeholder-slate-400 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
    </div>';
}

function filter_dropdown(string $label, array $options, string $name, ?string $selected = null): string
{
    $html = '<select name="' . e($name) . '" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-brand-500/20 focus:border-brand-500 outline-none transition-all">
        <option value="">' . e($label) . '</option>';
    foreach ($options as $value => $display) {
        $sel = $selected === $value ? ' selected' : '';
        $html .= '<option value="' . e($value) . '"' . $sel . '>' . e($display) . '</option>';
    }
    $html .= '</select>';
    return $html;
}

function avatar_icon(string $name, string $size = 'md', ?string $imagePath = null): string
{
    $sizes = ['xs' => 'w-6 h-6 text-[10px]', 'sm' => 'w-8 h-8 text-[11px]', 'md' => 'w-10 h-10 text-sm', 'lg' => 'w-14 h-14 text-lg'];
    $s = $sizes[$size] ?? $sizes['md'];
    
    if ($imagePath) {
        return '<img src="' . e(url('/uploads/' . $imagePath)) . '" alt="' . e($name) . '" class="' . $s . ' rounded-full object-cover shadow-md shrink-0 border border-slate-200">';
    }
    
    $parts = explode(' ', $name);
    $initials = strtoupper(substr($parts[0] ?? '', 0, 1) . substr($parts[1] ?? $parts[0] ?? '', 0, 1));
    return '<div class="' . $s . ' rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 flex items-center justify-center font-bold text-white shadow-md shrink-0">' . e($initials) . '</div>';
}
