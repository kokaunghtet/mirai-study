<x-app-layout>
    <x-slot name="title">Analytics — MiraiStudy Admin</x-slot>

    <div class="px-4 pb-10" x-data="analyticsPage()">
        <div class="max-w-7xl mx-auto">

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
                            class="h-10 rounded-xl bg-gradient-to-tr from-accent-from to-accent-to px-4 text-sm font-bold text-white hover:opacity-90 transition-colors disabled:opacity-50"
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

            {{-- Charts: 3-column compact row --}}
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-sm font-bold text-content">Charts</h2>
                <button @click="cycleChartType()"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-line bg-surface-muted px-3 py-1.5 text-xs font-medium text-muted hover:text-content hover:border-accent/40 transition-colors">
                    <i :data-lucide="chartType === 'line' ? 'line-chart' : chartType === 'bar' ? 'bar-chart-3' : 'pie-chart'"
                       class="h-3.5 w-3.5"></i>
                    <span x-text="chartType.charAt(0).toUpperCase() + chartType.slice(1)"></span>
                </button>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">

                {{-- Chart A: User Registrations --}}
                <div class="rounded-xl border border-line bg-surface shadow-sm p-3 relative"
                     :class="loading ? 'opacity-50 pointer-events-none' : ''">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center z-10">
                        <i data-lucide="loader-circle" class="h-5 w-5 text-accent animate-spin"></i>
                    </div>
                    <div class="flex items-center justify-between mb-3">
                        <h2 class="text-xs font-bold text-content">User Registrations</h2>
                    </div>
                    <div x-show="!chartsReady" class="flex flex-col items-center gap-1.5 px-3 py-8 text-center text-xs text-muted">
                        <i data-lucide="bar-chart-2" class="h-5 w-5 text-muted"></i>
                        <p class="font-bold text-content">No data</p>
                    </div>
                    <div class="h-48">
                        <canvas id="chart-registrations" class="w-full"></canvas>
                    </div>
                </div>

                {{-- Chart B: Exam Content --}}
                <div class="rounded-xl border border-line bg-surface shadow-sm p-3 relative"
                     :class="loading ? 'opacity-50 pointer-events-none' : ''">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center z-10">
                        <i data-lucide="loader-circle" class="h-5 w-5 text-accent animate-spin"></i>
                    </div>
                    <div class="flex items-center gap-3 mb-3">
                        <h2 class="text-xs font-bold text-content">Exam Content</h2>
                        <div class="flex items-center gap-2 text-[10px] text-muted ml-auto">
                            <span class="flex items-center gap-1">
                                <span class="inline-block h-2 w-2 rounded-full" style="background:rgb(var(--accent) / 0.85)"></span>
                                Papers
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="inline-block h-2 w-2 rounded-full" style="background:rgb(var(--accent) / 0.4)"></span>
                                Qs
                            </span>
                        </div>
                    </div>
                    <div x-show="!chartsReady" class="flex flex-col items-center gap-1.5 px-3 py-8 text-center text-xs text-muted">
                        <i data-lucide="bar-chart-2" class="h-5 w-5 text-muted"></i>
                        <p class="font-bold text-content">No data</p>
                    </div>
                    <div class="h-48">
                        <canvas id="chart-exam-content" class="w-full"></canvas>
                    </div>
                </div>

                {{-- Chart C: Quiz Performance --}}
                <div class="rounded-xl border border-line bg-surface shadow-sm p-3 relative"
                     :class="loading ? 'opacity-50 pointer-events-none' : ''">
                    <div x-show="loading" class="absolute inset-0 flex items-center justify-center z-10">
                        <i data-lucide="loader-circle" class="h-5 w-5 text-accent animate-spin"></i>
                    </div>
                    <div class="flex items-center gap-3 mb-3">
                        <h2 class="text-xs font-bold text-content">Quiz Performance</h2>
                        <div class="flex items-center gap-2 text-[10px] text-muted ml-auto">
                            <span class="flex items-center gap-1">
                                <span class="inline-block h-2 w-2 rounded-full" style="background:rgb(var(--accent))"></span>
                                Attempts
                            </span>
                            <span class="flex items-center gap-1">
                                <span class="inline-block h-2 w-2 rounded-full border border-dashed" style="border-color:rgb(var(--muted))"></span>
                                Rate
                            </span>
                        </div>
                    </div>
                    <div x-show="!chartsReady" class="flex flex-col items-center gap-1.5 px-3 py-8 text-center text-xs text-muted">
                        <i data-lucide="bar-chart-2" class="h-5 w-5 text-muted"></i>
                        <p class="font-bold text-content">No data</p>
                    </div>
                    <div class="h-48">
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
        // Kept outside Alpine reactive data — Chart.js instances have circular getters
        // that cause Alpine's toRaw() to recurse infinitely if stored as reactive state.
        const _charts = { registrations: null, examContent: null, quizPerformance: null };
        const _lastData = {};

        return {
            // --- State ---
            range: '30d',
            showCustom: false,
            customFrom: '',
            customTo: '',
            loading: false,
            dateError: null,
            kpis: @json($initialData['kpis']),
            chartsReady: false,
            chartType: 'bar',

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
                Object.assign(_lastData, data);
                const accent  = this.getCSSColor('--accent');
                const muted   = this.getCSSColor('--muted');
                const line    = this.getCSSColor('--line');
                const surface = this.getCSSColor('--surface');
                const content = this.getCSSColor('--content');
                const isPie = this.chartType === 'pie';
                const barColor = (a) => accent.replace(')', ` / ${a})`);
                const pieSlice = (len) => (_, i) => barColor(0.3 + (i / len) * 0.6);
                const baseOpts = {
                    animation: false, responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: isPie, position: 'bottom', labels: { boxWidth: 8, font: { size: 9 }, padding: 4 } },
                        tooltip: { backgroundColor: surface, borderColor: line, borderWidth: 1, titleColor: content, bodyColor: content }
                    }
                };

                _charts.registrations = new Chart(document.getElementById('chart-registrations'), {
                    type: isPie ? 'pie' : this.chartType,
                    data: {
                        labels: data.labels,
                        datasets: [isPie
                            ? { data: data.registrations, backgroundColor: data.labels.map(pieSlice(data.labels.length)), borderWidth: 0 }
                            : { label: 'New Users', data: data.registrations, borderColor: accent, backgroundColor: this.chartType === 'bar' ? barColor(0.7) : barColor(0.08), fill: this.chartType === 'line', tension: 0.4, pointRadius: 2, pointHoverRadius: 4 }
                        ]
                    },
                    options: { ...baseOpts,
                        plugins: { ...baseOpts.plugins,
                            tooltip: { ...baseOpts.plugins.tooltip,
                                callbacks: { label: ctx => isPie ? `${ctx.label}: ${ctx.parsed} users` : `${ctx.parsed.y} new users` }
                            }
                        },
                        scales: isPie ? {} : {
                            x: { ticks: { color: muted, font: { size: 10 } }, grid: { color: line } },
                            y: { beginAtZero: true, ticks: { color: muted, font: { size: 10 }, precision: 0 }, grid: { color: line } }
                        }
                    }
                });

                _charts.examContent = new Chart(document.getElementById('chart-exam-content'), {
                    type: isPie ? 'pie' : this.chartType,
                    data: {
                        labels: data.labels,
                        datasets: isPie
                            ? [{ data: data.papers, backgroundColor: data.labels.map(pieSlice(data.labels.length)), borderWidth: 0 }]
                            : [
                                { label: 'Papers', data: data.papers, backgroundColor: barColor(0.85), barPercentage: 0.75, categoryPercentage: 0.65 },
                                { label: 'Questions', data: data.questions, backgroundColor: barColor(0.4), barPercentage: 0.75, categoryPercentage: 0.65 }
                            ]
                    },
                    options: { ...baseOpts,
                        scales: isPie ? {} : {
                            x: { ticks: { color: muted, font: { size: 10 } }, grid: { color: line } },
                            y: { beginAtZero: true, ticks: { color: muted, font: { size: 10 }, precision: 0 }, grid: { color: line } }
                        }
                    }
                });

                _charts.quizPerformance = new Chart(document.getElementById('chart-quiz-performance'), {
                    type: isPie ? 'pie' : this.chartType,
                    data: {
                        labels: data.labels,
                        datasets: isPie
                            ? [{ data: data.quizAttempts, backgroundColor: data.labels.map(pieSlice(data.labels.length)), borderWidth: 0 }]
                            : [
                                { label: 'Attempts', type: this.chartType === 'line' ? 'line' : 'bar', data: data.quizAttempts, borderColor: accent, backgroundColor: this.chartType === 'line' ? barColor(0.08) : accent, fill: this.chartType === 'line', tension: 0.4, pointRadius: 2, yAxisID: 'yAttempts' },
                                { label: 'Pass Rate', type: 'line', data: data.passRates, borderColor: muted, borderDash: [4, 4], pointRadius: 2, fill: false, yAxisID: 'yPassRate' }
                            ]
                    },
                    options: { ...baseOpts,
                        scales: isPie ? {} : {
                            x: { ticks: { color: muted, font: { size: 10 } }, grid: { color: line } },
                            yAttempts: { beginAtZero: true, position: 'left', ticks: { color: muted, font: { size: 10 }, precision: 0 }, grid: { color: line } },
                            yPassRate: { beginAtZero: true, max: 100, position: 'right', ticks: { color: muted, font: { size: 10 }, callback: v => v + '%' }, grid: { drawOnChartArea: false } }
                        }
                    }
                });
                this.chartsReady = true;
            },

            // --- Update all charts in-place via Chart.js .update() ---
            updateCharts(data) {
                Object.assign(_lastData, data);
                const c = _charts;
                if (c.registrations) {
                    c.registrations.data.labels = data.labels;
                    c.registrations.data.datasets[0].data = data.registrations;
                    c.registrations.update();
                }
                if (c.examContent) {
                    c.examContent.data.labels = data.labels;
                    c.examContent.data.datasets[0].data = data.papers;
                    if (c.examContent.data.datasets[1]) c.examContent.data.datasets[1].data = data.questions;
                    c.examContent.update();
                }
                if (c.quizPerformance) {
                    c.quizPerformance.data.labels = data.labels;
                    c.quizPerformance.data.datasets[0].data = data.quizAttempts;
                    if (c.quizPerformance.data.datasets[1]) c.quizPerformance.data.datasets[1].data = data.passRates;
                    c.quizPerformance.update();
                }
            },

            // --- Chart type toggle (all 3 at once) ---
            cycleChartType() {
                const order = ['line', 'bar', 'pie'];
                const next = order[(order.indexOf(this.chartType) + 1) % order.length];
                this.chartType = next;

                const isPie = next === 'pie';
                const accent  = this.getCSSColor('--accent');
                const muted   = this.getCSSColor('--muted');
                const line    = this.getCSSColor('--line');
                const surface = this.getCSSColor('--surface');
                const content = this.getCSSColor('--content');
                const d = _lastData;
                const barColor = (a) => accent.replace(')', ` / ${a})`);
                const pieSlice = (len) => (_, i) => barColor(0.3 + (i / len) * 0.6);
                const baseOpts = {
                    animation: false, responsive: true, maintainAspectRatio: false,
                    plugins: {
                        legend: { display: isPie, position: 'bottom', labels: { boxWidth: 8, font: { size: 9 }, padding: 4 } },
                        tooltip: { backgroundColor: surface, borderColor: line, borderWidth: 1, titleColor: content, bodyColor: content }
                    }
                };

                // Destroy all
                Object.keys(_charts).forEach(k => { if (_charts[k]) { _charts[k].destroy(); _charts[k] = null; } });

                // Registrations
                _charts.registrations = new Chart(document.getElementById('chart-registrations'), {
                    type: isPie ? 'pie' : next,
                    data: {
                        labels: d.labels,
                        datasets: [isPie
                            ? { data: d.registrations, backgroundColor: d.labels.map(pieSlice(d.labels.length)), borderWidth: 0 }
                            : { label: 'New Users', data: d.registrations, borderColor: accent, backgroundColor: next === 'bar' ? barColor(0.7) : barColor(0.08), fill: next === 'line', tension: 0.4, pointRadius: 2, pointHoverRadius: 4 }
                        ]
                    },
                    options: { ...baseOpts,
                        plugins: { ...baseOpts.plugins,
                            tooltip: { ...baseOpts.plugins.tooltip,
                                callbacks: { label: ctx => isPie ? `${ctx.label}: ${ctx.parsed} users` : `${ctx.parsed.y} new users` }
                            }
                        },
                        scales: isPie ? {} : {
                            x: { ticks: { color: muted, font: { size: 10 } }, grid: { color: line } },
                            y: { beginAtZero: true, ticks: { color: muted, font: { size: 10 }, precision: 0 }, grid: { color: line } }
                        }
                    }
                });

                // Exam Content
                _charts.examContent = new Chart(document.getElementById('chart-exam-content'), {
                    type: isPie ? 'pie' : next,
                    data: {
                        labels: d.labels,
                        datasets: isPie
                            ? [{ data: d.papers, backgroundColor: d.labels.map(pieSlice(d.labels.length)), borderWidth: 0 }]
                            : [
                                { label: 'Papers', data: d.papers, backgroundColor: barColor(0.85), barPercentage: 0.75, categoryPercentage: 0.65 },
                                { label: 'Questions', data: d.questions, backgroundColor: barColor(0.4), barPercentage: 0.75, categoryPercentage: 0.65 }
                            ]
                    },
                    options: { ...baseOpts,
                        scales: isPie ? {} : {
                            x: { ticks: { color: muted, font: { size: 10 } }, grid: { color: line } },
                            y: { beginAtZero: true, ticks: { color: muted, font: { size: 10 }, precision: 0 }, grid: { color: line } }
                        }
                    }
                });

                // Quiz Performance
                _charts.quizPerformance = new Chart(document.getElementById('chart-quiz-performance'), {
                    type: isPie ? 'pie' : next,
                    data: {
                        labels: d.labels,
                        datasets: isPie
                            ? [{ data: d.quizAttempts, backgroundColor: d.labels.map(pieSlice(d.labels.length)), borderWidth: 0 }]
                            : [
                                { label: 'Attempts', type: next === 'line' ? 'line' : 'bar', data: d.quizAttempts, borderColor: accent, backgroundColor: next === 'line' ? barColor(0.08) : accent, fill: next === 'line', tension: 0.4, pointRadius: 2, yAxisID: 'yAttempts' },
                                { label: 'Pass Rate', type: 'line', data: d.passRates, borderColor: muted, borderDash: [4, 4], pointRadius: 2, fill: false, yAxisID: 'yPassRate' }
                            ]
                    },
                    options: { ...baseOpts,
                        scales: isPie ? {} : {
                            x: { ticks: { color: muted, font: { size: 10 } }, grid: { color: line } },
                            yAttempts: { beginAtZero: true, position: 'left', ticks: { color: muted, font: { size: 10 }, precision: 0 }, grid: { color: line } },
                            yPassRate: { beginAtZero: true, max: 100, position: 'right', ticks: { color: muted, font: { size: 10 }, callback: v => v + '%' }, grid: { drawOnChartArea: false } }
                        }
                    }
                });

                this.$nextTick(() => window.renderIcons());
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
                    console.error('[analytics fetchData]', err);
                    window.dispatchEvent(new CustomEvent('show-snackbar', {
                        detail: { message: 'Could not load analytics data. Please try again.', type: 'error', duration: 5000 }
                    }));
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
