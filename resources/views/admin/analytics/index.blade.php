<x-app-layout>
    <x-slot name="title">Analytics — MiraiStudy Admin</x-slot>

    <div class="px-4 pb-10" x-data="analyticsPage()">
        <div class="max-w-6xl mx-auto">

            {{-- AJAX fetch error banner --}}
            <div x-show="error"
                 x-transition.opacity
                 class="mb-4 rounded-lg bg-red-100 border border-red-300 px-4 py-3 text-sm font-bold text-red-700
                        dark:bg-red-900/30 dark:border-red-800 dark:text-red-400">
                <span x-text="error"></span>
            </div>

            {{-- Page header --}}
            <header class="mb-8">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <h1 class="text-2xl font-bold tracking-tight text-content">Analytics</h1>
                    <a href="{{ route('admin.dashboard') }}"
                       class="inline-flex shrink-0 items-center gap-1.5 rounded-xl border border-line bg-surface
                              px-4 py-2 text-sm font-bold text-content transition-colors hover:bg-surface-muted">
                        <i data-lucide="arrow-left" class="h-4 w-4"></i>
                        Dashboard
                    </a>
                </div>
                <p class="text-sm text-muted">Platform growth and performance trends.</p>
            </header>

            {{-- Time-range controls --}}
            <div class="mb-8">
                <div class="flex items-center gap-1 rounded-xl bg-surface-muted p-1 border border-line">
                    <button @click="setRange('7d')"
                            :class="range === '7d' ? 'bg-surface text-accent font-bold shadow-sm rounded-lg' : 'text-muted hover:text-content'"
                            class="rounded-lg px-4 py-2 text-sm transition-all">
                        7 days
                    </button>
                    <button @click="setRange('30d')"
                            :class="range === '30d' ? 'bg-surface text-accent font-bold shadow-sm rounded-lg' : 'text-muted hover:text-content'"
                            class="rounded-lg px-4 py-2 text-sm transition-all">
                        30 days
                    </button>
                    <button @click="setRange('90d')"
                            :class="range === '90d' ? 'bg-surface text-accent font-bold shadow-sm rounded-lg' : 'text-muted hover:text-content'"
                            class="rounded-lg px-4 py-2 text-sm transition-all">
                        90 days
                    </button>
                    <span class="mx-1 h-5 w-px bg-line"></span>
                    <button @click="showCustom = !showCustom"
                            :class="range === 'custom' ? 'bg-surface text-accent font-bold shadow-sm rounded-lg' : 'text-muted hover:text-content'"
                            class="rounded-lg px-4 py-2 text-sm transition-all">
                        Custom
                    </button>
                </div>

                {{-- Custom date picker --}}
                <div x-show="showCustom" x-transition class="mt-3 flex items-center gap-3 flex-wrap">
                    <label class="text-xs text-muted">From</label>
                    <input type="date" x-model="customFrom"
                           class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-content
                                  focus:border-accent focus:ring-1 focus:ring-accent/30" />
                    <label class="text-xs text-muted">To</label>
                    <input type="date" x-model="customTo"
                           class="h-10 rounded-xl border border-line bg-surface px-3 text-sm text-content
                                  focus:border-accent focus:ring-1 focus:ring-accent/30" />
                    <button @click="applyCustomRange()"
                            class="h-10 rounded-xl bg-accent px-4 text-sm font-bold text-white hover:opacity-90 transition-colors disabled:opacity-50"
                            :disabled="!customFrom || !customTo || loading">
                        Apply Range
                    </button>
                    <span x-show="dateError" x-text="dateError" class="text-xs text-red-600"></span>
                </div>
            </div>

            {{-- KPI summary row --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">

                {{-- Users --}}
                <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted">Users</span>
                        <i data-lucide="users" class="h-4 w-4 text-muted"></i>
                    </div>
                    <div class="text-2xl font-bold text-content" x-text="kpis.totalUsers"></div>
                    <div class="mt-2 text-xs text-muted">+<span x-text="kpis.newUsersThisPeriod"></span> this period</div>
                </div>

                {{-- Exam Papers --}}
                <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted">Exam Papers</span>
                        <i data-lucide="file-text" class="h-4 w-4 text-muted"></i>
                    </div>
                    <div class="text-2xl font-bold text-content" x-text="kpis.totalPapers"></div>
                    <div class="mt-2 text-xs text-muted">+<span x-text="kpis.newPapersThisPeriod"></span> this period</div>
                </div>

                {{-- Quiz Attempts --}}
                <div class="rounded-2xl border border-line bg-surface p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-xs font-bold uppercase tracking-wider text-muted">Quiz Attempts</span>
                        <i data-lucide="bar-chart-2" class="h-4 w-4 text-muted"></i>
                    </div>
                    <div class="text-2xl font-bold text-content" x-text="kpis.quizAttempts"></div>
                    <div class="mt-2 text-xs text-muted"><span x-text="kpis.passRate"></span>% pass rate</div>
                </div>

            </div>

            {{-- Chart A: User Registrations (full width) --}}
            <div class="mb-8">
                <div class="rounded-2xl border border-line bg-surface shadow-sm p-5 relative"
                     :class="loading ? 'opacity-50 pointer-events-none' : ''">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center z-10">
                        <i data-lucide="loader-2" class="h-6 w-6 text-accent animate-spin"></i>
                    </div>
                    <h2 class="text-sm font-bold text-content mb-4">User Registrations</h2>
                    <div x-show="!charts.registrations" class="flex flex-col items-center gap-2 px-5 py-12 text-center text-sm text-muted">
                        <i data-lucide="bar-chart-2" class="h-6 w-6 text-muted"></i>
                        <p class="font-bold text-content">No data for this period</p>
                        <p>Try a wider date range or check back once users are active.</p>
                    </div>
                    <div class="h-64 md:h-80">
                        <canvas id="chart-registrations" class="w-full"></canvas>
                    </div>
                </div>
            </div>

            {{-- Chart row B: side-by-side --}}
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">

                {{-- Chart B: Exam Content (left) --}}
                <div class="rounded-2xl border border-line bg-surface shadow-sm p-5 relative"
                     :class="loading ? 'opacity-50 pointer-events-none' : ''">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center z-10">
                        <i data-lucide="loader-2" class="h-6 w-6 text-accent animate-spin"></i>
                    </div>
                    <div class="flex items-center gap-4 mb-4">
                        <h2 class="text-sm font-bold text-content">Exam Content</h2>
                        <div class="flex items-center gap-3 text-xs text-muted">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block h-3 w-3 rounded-full" style="background:rgba(var(--accent),0.85)"></span>
                                Papers
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block h-3 w-3 rounded-full" style="background:rgba(var(--accent),0.4)"></span>
                                Questions
                            </span>
                        </div>
                    </div>
                    <div x-show="!charts.examContent" class="flex flex-col items-center gap-2 px-5 py-12 text-center text-sm text-muted">
                        <i data-lucide="bar-chart-2" class="h-6 w-6 text-muted"></i>
                        <p class="font-bold text-content">No data for this period</p>
                        <p>Try a wider date range or check back once users are active.</p>
                    </div>
                    <div class="h-64">
                        <canvas id="chart-exam-content" class="w-full"></canvas>
                    </div>
                </div>

                {{-- Chart C: Quiz Performance (right) --}}
                <div class="rounded-2xl border border-line bg-surface shadow-sm p-5 relative"
                     :class="loading ? 'opacity-50 pointer-events-none' : ''">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center z-10">
                        <i data-lucide="loader-2" class="h-6 w-6 text-accent animate-spin"></i>
                    </div>
                    <div class="flex items-center gap-4 mb-4">
                        <h2 class="text-sm font-bold text-content">Quiz Performance</h2>
                        <div class="flex items-center gap-3 text-xs text-muted">
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block h-3 w-3 rounded-full" style="background:rgb(var(--accent))"></span>
                                Attempts
                            </span>
                            <span class="flex items-center gap-1.5">
                                <span class="inline-block h-3 w-3 rounded-full border-2 border-dashed" style="border-color:rgb(var(--muted))"></span>
                                Pass Rate
                            </span>
                        </div>
                    </div>
                    <div x-show="!charts.quizPerformance" class="flex flex-col items-center gap-2 px-5 py-12 text-center text-sm text-muted">
                        <i data-lucide="bar-chart-2" class="h-6 w-6 text-muted"></i>
                        <p class="font-bold text-content">No data for this period</p>
                        <p>Try a wider date range or check back once users are active.</p>
                    </div>
                    <div class="h-64">
                        <canvas id="chart-quiz-performance" class="w-full"></canvas>
                    </div>
                </div>

            </div>

            {{-- Performance detail table --}}
            <section class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden mb-8">
                <div class="flex items-center justify-between px-5 py-4 border-b border-line">
                    <h2 class="text-sm font-bold text-content">Performance by Category &amp; Level</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b border-line bg-surface-muted">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Category</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Level</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Attempts</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Pass Rate</th>
                                <th class="px-5 py-3 text-left text-xs font-bold uppercase tracking-wider text-muted">Avg Score</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line">
                            @forelse($initialData['performanceTable'] as $row)
                                <tr class="hover:bg-surface-muted transition-colors">
                                    <td class="px-5 py-3 text-sm font-bold text-content">{{ $row['category'] }}</td>
                                    <td class="px-5 py-3">
                                        <span class="rounded-full bg-surface-muted px-2 py-0.5 text-xs text-muted">{{ $row['level'] }}</span>
                                    </td>
                                    <td class="px-5 py-3 text-sm text-content">{{ $row['attempts'] }}</td>
                                    <td class="px-5 py-3 text-sm {{ $row['passRate'] === null ? 'text-muted' : ($row['passRate'] >= 60 ? 'text-green-600' : 'text-red-600') }}">
                                        {{ $row['passRate'] === null ? '—' : $row['passRate'] . '%' }}
                                    </td>
                                    <td class="px-5 py-3 text-sm text-muted">{{ $row['avgScore'] ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-5 py-8 text-center text-sm text-muted">No quiz attempts recorded for this period.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>

    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
    function analyticsPage() {
        return {
            // --- State ---
            range: '30d',
            showCustom: false,
            customFrom: '',
            customTo: '',
            loading: false,
            error: null,
            dateError: null,
            kpis: @json($initialData['kpis']),
            charts: { registrations: null, examContent: null, quizPerformance: null },

            // --- CSS variable helper ---
            getCSSColor(varName) {
                return `rgb(${getComputedStyle(document.documentElement).getPropertyValue(varName).trim()})`;
            },

            // --- Init: build charts from Blade-seeded data ---
            init() {
                const seed = @json($initialData);
                this.kpis = seed.kpis;
                this.initCharts(seed);
            },

            // --- Chart initialization (called once) ---
            initCharts(data) {
                const accent      = this.getCSSColor('--accent');
                const muted       = this.getCSSColor('--muted');
                const line        = this.getCSSColor('--line');
                const surface     = this.getCSSColor('--surface');
                const content     = this.getCSSColor('--content');

                // Chart A: User Registrations (line)
                this.charts.registrations = new Chart(
                    document.getElementById('chart-registrations'),
                    {
                        type: 'line',
                        data: {
                            labels: data.labels,
                            datasets: [{
                                label: 'New Users',
                                data: data.registrations,
                                borderColor: accent,
                                backgroundColor: accent.replace('rgb(', 'rgba(').replace(')', ', 0.08)'),
                                fill: true,
                                tension: 0.4,
                                pointRadius: 3,
                                pointHoverRadius: 5,
                            }]
                        },
                        options: {
                            animation: false,
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: surface,
                                    borderColor: line,
                                    borderWidth: 1,
                                    titleColor: content,
                                    bodyColor: content,
                                    callbacks: {
                                        label: ctx => `${ctx.parsed.y} new users`
                                    }
                                }
                            },
                            scales: {
                                x: { ticks: { color: muted, font: { size: 11 } }, grid: { color: line } },
                                y: { beginAtZero: true, ticks: { color: muted, font: { size: 11 }, precision: 0 }, grid: { color: line } }
                            }
                        }
                    }
                );

                // Chart B: Exam Content (bar, 2 series)
                this.charts.examContent = new Chart(
                    document.getElementById('chart-exam-content'),
                    {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Papers',
                                    data: data.papers,
                                    backgroundColor: accent.replace('rgb(', 'rgba(').replace(')', ', 0.85)'),
                                    barPercentage: 0.8,
                                    categoryPercentage: 0.7,
                                },
                                {
                                    label: 'Questions',
                                    data: data.questions,
                                    backgroundColor: accent.replace('rgb(', 'rgba(').replace(')', ', 0.4)'),
                                    barPercentage: 0.8,
                                    categoryPercentage: 0.7,
                                }
                            ]
                        },
                        options: {
                            animation: false,
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: surface,
                                    borderColor: line,
                                    borderWidth: 1,
                                    titleColor: content,
                                    bodyColor: content,
                                }
                            },
                            scales: {
                                x: { ticks: { color: muted, font: { size: 11 } }, grid: { color: line } },
                                y: { beginAtZero: true, ticks: { color: muted, font: { size: 11 }, precision: 0 }, grid: { color: line } }
                            }
                        }
                    }
                );

                // Chart C: Quiz Performance (mixed bar + line, dual Y axis)
                this.charts.quizPerformance = new Chart(
                    document.getElementById('chart-quiz-performance'),
                    {
                        type: 'bar',
                        data: {
                            labels: data.labels,
                            datasets: [
                                {
                                    label: 'Attempts',
                                    type: 'bar',
                                    data: data.quizAttempts,
                                    backgroundColor: accent,
                                    yAxisID: 'yAttempts',
                                },
                                {
                                    label: 'Pass Rate',
                                    type: 'line',
                                    data: data.passRates,
                                    borderColor: muted,
                                    borderDash: [4, 4],
                                    pointRadius: 3,
                                    fill: false,
                                    yAxisID: 'yPassRate',
                                }
                            ]
                        },
                        options: {
                            animation: false,
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: {
                                legend: { display: false },
                                tooltip: {
                                    backgroundColor: surface,
                                    borderColor: line,
                                    borderWidth: 1,
                                    titleColor: content,
                                    bodyColor: content,
                                }
                            },
                            scales: {
                                x: { ticks: { color: muted, font: { size: 11 } }, grid: { color: line } },
                                yAttempts: {
                                    beginAtZero: true,
                                    position: 'left',
                                    ticks: { color: muted, font: { size: 11 }, precision: 0 },
                                    grid: { color: line }
                                },
                                yPassRate: {
                                    beginAtZero: true,
                                    max: 100,
                                    position: 'right',
                                    ticks: { color: muted, font: { size: 11 }, callback: v => v + '%' },
                                    grid: { drawOnChartArea: false }
                                }
                            }
                        }
                    }
                );
            },

            // --- Update all charts in-place via Chart.js .update() ---
            updateCharts(data) {
                const c = this.charts;
                if (c.registrations) {
                    c.registrations.data.labels = data.labels;
                    c.registrations.data.datasets[0].data = data.registrations;
                    c.registrations.update();
                }
                if (c.examContent) {
                    c.examContent.data.labels = data.labels;
                    c.examContent.data.datasets[0].data = data.papers;
                    c.examContent.data.datasets[1].data = data.questions;
                    c.examContent.update();
                }
                if (c.quizPerformance) {
                    c.quizPerformance.data.labels = data.labels;
                    c.quizPerformance.data.datasets[0].data = data.quizAttempts;
                    c.quizPerformance.data.datasets[1].data = data.passRates;
                    c.quizPerformance.update();
                }
            },

            // --- AJAX fetch ---
            async fetchData() {
                this.loading = true;
                this.error = null;
                const params = this.range === 'custom'
                    ? `from=${this.customFrom}&to=${this.customTo}`
                    : `range=${this.range}`;
                try {
                    const res = await fetch(`/admin/analytics/data?${params}`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                    });
                    if (!res.ok) throw new Error(`Server error ${res.status}`);
                    const data = await res.json();
                    this.kpis = data.kpis;
                    this.updateCharts(data);
                    history.pushState(null, '', `/admin/analytics?${params}`);
                } catch (err) {
                    this.error = 'Could not load analytics data. Please try again.';
                    setTimeout(() => { this.error = null; }, 5000);
                } finally {
                    this.loading = false;
                }
            },

            // --- Tab switching ---
            setRange(r) {
                this.range = r;
                this.showCustom = (r === 'custom');
                this.dateError = null;
                if (r !== 'custom') this.fetchData();
            },

            // --- Custom range validation + fetch ---
            applyCustomRange() {
                this.dateError = null;
                if (!this.customFrom || !this.customTo) return;
                const from = new Date(this.customFrom);
                const to   = new Date(this.customTo);
                if (to < from) {
                    this.dateError = 'End date must be after start date.';
                    return;
                }
                const diffDays = Math.round((to - from) / (1000 * 60 * 60 * 24));
                if (diffDays > 365) {
                    this.dateError = 'Custom range cannot exceed 365 days.';
                    return;
                }
                this.range = 'custom';
                this.fetchData();
            },
        };
    }
    </script>
    @endpush
</x-app-layout>
