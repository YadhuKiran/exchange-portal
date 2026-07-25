<?php

namespace App;

class Paginator
{
    private string $baseUrl;
    private int $total;
    private int $perPage;
    private int $currentPage;
    private int $totalPages;

    public function __construct(int $total, int $currentPage, int $perPage = 10, string $baseUrl = '')
    {
        $this->total = $total;
        $this->perPage = max(1, $perPage);
        $this->currentPage = max(1, $currentPage);
        $this->totalPages = (int) ceil($this->total / $this->perPage);
        $this->baseUrl = $baseUrl ?: $_SERVER['REQUEST_URI'] ?? '';
    }

    public static function fromQuery(?\PDOStatement $countStmt = null, int $perPage = 10, string $baseUrl = ''): self
    {
        $total = $countStmt ? (int) $countStmt->fetchColumn() : 0;
        $currentPage = max(1, (int) ($_GET['page'] ?? 1));
        return new self($total, $currentPage, $perPage, $baseUrl);
    }

    public function offset(): int
    {
        return ($this->currentPage - 1) * $this->perPage;
    }

    public function limit(): int
    {
        return $this->perPage;
    }

    public function totalPages(): int
    {
        return $this->totalPages;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function hasPages(): bool
    {
        return $this->totalPages > 1;
    }

    public function hasPrevious(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->totalPages;
    }

    public function previousUrl(): string
    {
        return $this->buildUrl($this->currentPage - 1);
    }

    public function nextUrl(): string
    {
        return $this->buildUrl($this->currentPage + 1);
    }

    public function pages(): array
    {
        $pages = [];
        $start = max(1, $this->currentPage - 2);
        $end = min($this->totalPages, $this->currentPage + 2);

        if ($start > 1) {
            $pages[] = ['num' => 1, 'url' => $this->buildUrl(1), 'active' => false];
            if ($start > 2) {
                $pages[] = ['num' => null, 'url' => null, 'active' => false];
            }
        }
        for ($i = $start; $i <= $end; $i++) {
            $pages[] = ['num' => $i, 'url' => $this->buildUrl($i), 'active' => $i === $this->currentPage];
        }
        if ($end < $this->totalPages) {
            if ($end < $this->totalPages - 1) {
                $pages[] = ['num' => null, 'url' => null, 'active' => false];
            }
            $pages[] = ['num' => $this->totalPages, 'url' => $this->buildUrl($this->totalPages), 'active' => false];
        }
        return $pages;
    }

    public function render(): string
    {
        if (!$this->hasPages()) {
            return '';
        }

        $html = '<nav class="flex items-center justify-between border-t border-slate-200 px-4 py-3 sm:px-6" aria-label="Pagination">';
        $html .= '<div class="hidden sm:block text-sm text-slate-600">Showing page ' . $this->currentPage . ' of ' . $this->totalPages . ' (' . $this->total . ' total)</div>';
        $html .= '<div class="flex flex-1 justify-between sm:justify-end gap-2">';

        if ($this->hasPrevious()) {
            $html .= '<a href="' . e($this->previousUrl()) . '" class="relative inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Previous</a>';
        } else {
            $html .= '<span class="relative inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-400 cursor-not-allowed">Previous</span>';
        }

        foreach ($this->pages() as $p) {
            if ($p['num'] === null) {
                $html .= '<span class="px-2 py-2 text-sm text-slate-400">...</span>';
            } elseif ($p['active']) {
                $html .= '<a href="' . e($p['url']) . '" class="relative inline-flex items-center rounded-lg bg-indigo-600 px-3 py-2 text-sm font-medium text-white">' . $p['num'] . '</a>';
            } else {
                $html .= '<a href="' . e($p['url']) . '" class="relative inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">' . $p['num'] . '</a>';
            }
        }

        if ($this->hasNext()) {
            $html .= '<a href="' . e($this->nextUrl()) . '" class="relative inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">Next</a>';
        } else {
            $html .= '<span class="relative inline-flex items-center rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-medium text-slate-400 cursor-not-allowed">Next</span>';
        }

        $html .= '</div></nav>';
        return $html;
    }

    public function info(): string
    {
        $start = $this->offset() + 1;
        $end = min($this->offset() + $this->perPage, $this->total);
        return "Showing {$start}–{$end} of {$this->total}";
    }

    private function buildUrl(int $page): string
    {
        $parsed = parse_url($this->baseUrl);
        $query = [];
        if (isset($parsed['query'])) {
            parse_str($parsed['query'], $query);
        }
        unset($query['page']);
        if ($page > 1) {
            $query['page'] = $page;
        }
        $path = $parsed['path'] ?? '/';
        $qs = $query ? '?' . http_build_query($query) : '';
        return $path . $qs;
    }
}
