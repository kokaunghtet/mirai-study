<x-app-layout>
    <x-slot name="title">Exams — MiraiStudy</x-slot>

    {{-- Scoped styles. Colors reference the global theme tokens (rgb(var(--accent))…)
         so the section follows light/dark + accent theme automatically. --}}
    <style>
        .exam-ui [x-cloak]{display:none}
        .exam-ui .folder-grid{display:grid;gap:16px;grid-template-columns:repeat(2,1fr)}
        @media(max-width:560px){.exam-ui .folder-grid{grid-template-columns:1fr}}
        .exam-ui .exam-card{display:flex;flex-direction:column;text-align:left;cursor:pointer;background:rgb(var(--surface));border:1px solid rgb(var(--line));border-radius:18px;overflow:hidden;transition:transform .15s,border-color .2s,box-shadow .2s}
        .exam-ui .exam-card:hover{transform:scale(1.02);border-color:rgb(var(--accent)/.6);box-shadow:0 14px 32px rgba(0,0,0,.45)}
        .exam-ui .exam-card:active{transform:scale(.98)}
        .exam-ui .exam-card.open{border-color:rgb(var(--accent));box-shadow:0 0 0 2px rgb(var(--accent)/.4)}
        .exam-ui .card-art{position:relative;height:168px;overflow:hidden;display:flex;align-items:flex-end;padding:14px}
        .exam-ui .card-art .motif{position:absolute;right:-8px;top:-16px;font-size:150px;line-height:1;opacity:.2;transform:rotate(-8deg);pointer-events:none}
        .exam-ui .logo-badge{position:relative;z-index:1;width:64px;height:64px;border-radius:15px;background:#fff;color:#0b1220;display:grid;place-items:center;font-weight:800;font-size:14px;letter-spacing:.5px;box-shadow:0 6px 16px rgba(0,0,0,.35)}
        .exam-ui .card-body{padding:16px;display:flex;flex-direction:column;gap:12px}
        .exam-ui .card-body h3{font-size:22px;font-weight:700;color:rgb(var(--content))}
        .exam-ui .meta-list{display:flex;flex-direction:column;gap:9px}
        .exam-ui .meta-row{display:flex;align-items:center;gap:10px;font-size:14px;color:rgb(var(--muted))}
        .exam-ui .meta-row .mi{flex-shrink:0;width:6px;height:6px;border-radius:50%;background:rgb(var(--accent));margin-left:4px}
        .exam-ui .continue{margin-top:4px;width:100%;background:rgb(var(--accent));color:#fff;border:none;border-radius:12px;padding:12px;font-size:14px;font-weight:700;letter-spacing:.3px;cursor:pointer;transition:background .15s,box-shadow .2s}
        .exam-ui .continue:hover{background:rgb(var(--accent-strong))}
        .exam-ui .exam-card:hover .continue{box-shadow:0 0 20px rgb(var(--accent)/.5)}
        .exam-ui .level-popout{margin-top:16px}
        .exam-ui .level-popout-head{font-size:12px;text-transform:uppercase;letter-spacing:.5px;color:rgb(var(--muted));margin-bottom:10px;padding-left:2px}
        .exam-ui .level-list{display:flex;flex-direction:column;gap:10px}
        .exam-ui .level-card{display:flex;align-items:center;gap:14px;cursor:pointer;padding:14px 16px;background:rgb(var(--surface));border:1px solid rgb(var(--line));border-left:3px solid rgb(var(--accent));border-radius:12px;color:rgb(var(--content));opacity:0;transform:translateY(8px);animation:examCascade .3s ease forwards;transition:background .15s,transform .15s,border-color .15s}
        .exam-ui .level-card:hover{background:rgb(var(--surface-muted));transform:translateX(3px)}
        @keyframes examCascade{to{opacity:1;transform:translateY(0)}}
        .exam-ui .level-badge{flex-shrink:0;display:grid;place-items:center;min-width:46px;height:34px;padding:0 10px;border-radius:9px;background:rgb(var(--accent)/.15);color:rgb(var(--accent));font-weight:700;font-size:14px}
        .exam-ui .lc-body{flex:1;min-width:0;display:flex;flex-direction:column}
        .exam-ui .lc-name{font-weight:600;font-size:14px}
        .exam-ui .lc-sub{margin-top:1px;font-size:12px;color:rgb(var(--muted))}
        .exam-ui .lc-chev{color:rgb(var(--muted))}
        .exam-ui .crumbs{display:flex;align-items:center;gap:8px;font-size:14px;margin:4px 0 22px}
        .exam-ui .crumbs .crumb{background:none;border:none;padding:0;cursor:pointer;font:inherit;color:rgb(var(--muted));display:inline-flex;align-items:center;gap:6px}
        .exam-ui .crumbs .crumb:hover{color:rgb(var(--content))}
        .exam-ui .crumbs .sep{color:rgb(var(--muted));opacity:.5}
        .exam-ui .crumbs .current{font-weight:700;color:rgb(var(--content))}
        .exam-ui .level-tabs{display:flex;flex-wrap:wrap;gap:8px;border-bottom:1px solid rgb(var(--line));padding-bottom:14px;margin-bottom:18px}
        .exam-ui .level-tab{border:1px solid rgb(var(--line));background:rgb(var(--surface));color:rgb(var(--muted));border-radius:11px;padding:7px 15px;font-size:14px;font-weight:600;cursor:pointer;transition:all .15s}
        .exam-ui .level-tab:hover{color:rgb(var(--content));border-color:rgb(var(--accent)/.5)}
        .exam-ui .level-tab.active{background:rgb(var(--accent));color:#fff;border-color:transparent}
        .exam-ui .papers-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap}
        .exam-ui .filter-chips{display:flex;gap:6px}
        .exam-ui .chip{border:1px solid rgb(var(--line));background:rgb(var(--surface));color:rgb(var(--muted));border-radius:999px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
        .exam-ui .chip:hover{color:rgb(var(--content));border-color:rgb(var(--accent)/.5)}
        .exam-ui .chip.active{background:rgb(var(--accent));color:#fff;border-color:transparent}
        .exam-ui .sort-btn{display:inline-flex;align-items:center;gap:6px;border:1px solid rgb(var(--line));background:rgb(var(--surface));color:rgb(var(--content));border-radius:10px;padding:6px 14px;font-size:13px;font-weight:600;cursor:pointer;transition:all .15s}
        .exam-ui .sort-btn:hover{border-color:rgb(var(--accent)/.5)}
        .exam-ui .sort-btn .sort-arrow{color:rgb(var(--accent));font-size:12px}
        .exam-ui .paper-group-head{display:flex;align-items:center;gap:8px;font-size:13px;font-weight:700;color:rgb(var(--content));margin:0 0 10px;padding-left:2px}
        .exam-ui .paper-group-head span{font-size:11px;font-weight:700;color:rgb(var(--accent));background:rgb(var(--accent)/.12);border-radius:999px;padding:1px 8px}
        .exam-ui .papers{border:1px solid rgb(var(--line));border-radius:16px;background:rgb(var(--surface));overflow:hidden}
        .exam-ui .paper{display:flex;align-items:center;gap:14px;padding:14px 16px;border-bottom:1px solid rgb(var(--line))}
        .exam-ui .paper:last-child{border-bottom:none}
        .exam-ui .paper .ico{color:rgb(var(--accent));flex-shrink:0}
        .exam-ui .paper .pmeta{flex:1;min-width:0;display:flex;flex-direction:column}
        .exam-ui .paper .title{font-weight:600;font-size:14px;display:flex;align-items:center;gap:8px}
        .exam-ui .paper .sub{margin-top:2px;font-size:12px;color:rgb(var(--muted))}
        .exam-ui .badge{font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.5px;color:rgb(var(--accent));background:rgb(var(--accent)/.12);border-radius:6px;padding:2px 6px}
        .exam-ui .paper .actions{display:flex;gap:8px;flex-shrink:0}
        .exam-ui .btn{display:inline-flex;align-items:center;gap:6px;border-radius:10px;padding:7px 12px;font-size:13px;font-weight:600;cursor:pointer;text-decoration:none;border:1px solid rgb(var(--line));background:rgb(var(--surface));color:rgb(var(--content))}
        .exam-ui .btn:hover{background:rgb(var(--surface-muted))}
        .exam-ui .btn.primary{background:rgb(var(--accent));color:#fff;border-color:transparent}
        .exam-ui .btn.primary:hover{background:rgb(var(--accent-strong))}
        .exam-ui .empty{padding:48px 16px;text-align:center;color:rgb(var(--muted));font-size:14px}
    </style>

    <div class="exam-ui px-4" x-data="examBrowser(@js(['categories' => $categories]))" x-cloak>
        <div class="mx-auto max-w-4xl">

            <header class="mb-6">
                <h1 class="text-2xl font-bold tracking-tight text-content">Exams</h1>
                <p class="mt-1 text-sm text-muted">Pick a folder to reveal its levels, then open one to browse papers.</p>
            </header>

            @if ($categories->isEmpty())
                <div class="rounded-2xl border border-dashed border-line bg-surface px-4 py-12 text-center">
                    <i data-lucide="folder-open" class="mx-auto h-8 w-8 text-muted"></i>
                    <p class="mt-3 text-sm font-medium text-content">No exam folders yet</p>
                </div>
            @else

            {{-- ─────────────── FOLDERS VIEW ─────────────── --}}
            <section x-show="view === 'folders'">
                <div class="folder-grid">
                    <template x-for="cat in categories" :key="cat.id">
                        <div class="exam-card" :class="openId === cat.id && 'open'" @click="toggleFolder(cat.id)">
                            <div class="card-art" :style="'background:' + art(cat).grad">
                                <span class="motif" x-text="art(cat).motif"></span>
                                <span class="logo-badge" x-text="cat.name"></span>
                            </div>
                            <div class="card-body">
                                <h3 x-text="cat.name"></h3>
                                <div class="meta-list">
                                    <div class="meta-row"><span class="mi"></span>
                                        <span x-text="cat.levels.length + (cat.levels.length === 1 ? ' level' : ' levels')"></span></div>
                                    <div class="meta-row"><span class="mi"></span>
                                        <span x-text="cat.papers_count + (cat.papers_count === 1 ? ' past paper' : ' past papers')"></span></div>
                                    <div class="meta-row"><span class="mi"></span>
                                        <span x-text="art(cat).tag"></span></div>
                                </div>
                                <button type="button" class="continue"
                                        x-text="openId === cat.id ? 'Hide levels' : 'Continue'"></button>
                            </div>
                        </div>
                    </template>
                </div>

                {{-- level pop-out (vertical cascade) --}}
                <div class="level-popout" x-show="openCat" x-cloak>
                    <template x-if="openCat">
                        <div>
                            <div class="level-popout-head"><span x-text="openCat.name"></span> — choose a level</div>
                            <div class="level-list">
                                <template x-for="(lvl, i) in openCat.levels" :key="lvl.id">
                                    <div class="level-card" :style="`animation-delay:${i * 55}ms`"
                                         @click="openLevel(openCat, lvl)">
                                        <span class="level-badge" x-text="lvl.code"></span>
                                        <span class="lc-body">
                                            <span class="lc-name" x-text="lvl.name"></span>
                                            <span class="lc-sub"
                                                  x-text="lvl.papers_count + (lvl.papers_count === 1 ? ' paper' : ' papers')"></span>
                                        </span>
                                        <span class="lc-chev">›</span>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </section>

            {{-- ─────────────── DETAIL VIEW ─────────────── --}}
            <section x-show="view === 'detail'" x-cloak>
                <template x-if="curCat && curLevel">
                    <div>
                        <nav class="crumbs">
                            <button class="crumb" @click="backToFolders()">
                                <i data-lucide="folder" class="h-4 w-4"></i>
                                <span x-text="curCat.name"></span>
                            </button>
                            <span class="sep">/</span>
                            <span class="current" x-text="curLevel.code"></span>
                        </nav>

                        <div class="level-tabs">
                            <template x-for="lvl in curCat.levels" :key="lvl.id">
                                <button class="level-tab" :class="curLevel.id === lvl.id && 'active'"
                                        @click="selectLevel(lvl)" x-text="lvl.code"></button>
                            </template>
                        </div>

                        {{-- toolbar: AM/PM filter (when parts exist) + year sort --}}
                        <div class="papers-toolbar" x-show="!loading && papers.length">
                            <div class="filter-chips" x-show="hasParts">
                                <button class="chip" :class="filterPart === 'all' && 'active'" @click="filterPart = 'all'">All</button>
                                <button class="chip" :class="filterPart === 'AM' && 'active'" @click="filterPart = 'AM'">AM</button>
                                <button class="chip" :class="filterPart === 'PM' && 'active'" @click="filterPart = 'PM'">PM</button>
                            </div>
                            <span x-show="!hasParts"></span>
                            <button class="sort-btn" @click="toggleSort()" title="Toggle year order">
                                Year <span class="sort-arrow" x-text="sortDir === 'asc' ? '▲' : '▼'"></span>
                            </button>
                        </div>

                        {{-- papers (icons re-rendered after each reactive change) --}}
                        <div x-effect="questions; answers; combined; $nextTick(() => window.renderIcons($el))">
                            <div x-show="loading" class="papers"><div class="empty">Loading…</div></div>

                            <template x-if="!loading">
                                <div>
                                    <div x-show="!papers.length" class="papers"><div class="empty">No papers here yet.</div></div>
                                    <div x-show="papers.length && !questions.length && !answers.length && !combined.length"
                                         class="papers"><div class="empty">No papers match this filter.</div></div>

                                    {{-- grouped: questions / combined / answers (e.g. ITPEC, JLPT combined) --}}
                                    <template x-if="papers.length && isGrouped">
                                        <div>
                                            <div x-show="questions.length">
                                                <div class="paper-group-head">Question papers <span x-text="questions.length"></span></div>
                                                <div class="papers">
                                                    <template x-for="p in questions" :key="p.id">
                                                        @include('exams._paper-row')
                                                    </template>
                                                </div>
                                            </div>
                                            <div x-show="combined.length" style="margin-top:22px">
                                                <div class="paper-group-head">Questions + answer key <span x-text="combined.length"></span></div>
                                                <div class="papers">
                                                    <template x-for="p in combined" :key="p.id">
                                                        @include('exams._paper-row')
                                                    </template>
                                                </div>
                                            </div>
                                            <div x-show="answers.length" style="margin-top:22px">
                                                <div class="paper-group-head">Answer keys <span x-text="answers.length"></span></div>
                                                <div class="papers">
                                                    <template x-for="p in answers" :key="p.id">
                                                        @include('exams._paper-row')
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- flat list (plain question papers only, e.g. JLPT) --}}
                                    <template x-if="papers.length && !isGrouped">
                                        <div class="papers">
                                            <template x-for="p in questions" :key="p.id">
                                                @include('exams._paper-row')
                                            </template>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </section>
            @endif

        </div>
    </div>
</x-app-layout>
