{{-- Single paper row. Rendered inside an Alpine <template x-for="p in …">,
     so `p` is the per-iteration paper object (id, title, year, session,
     part, doc_type, view_url, download_url). Used by exams/index.blade.php. --}}
<div class="paper">
    <i data-lucide="file-text" class="ico h-5 w-5"></i>
    <span class="pmeta">
        <span class="title">
            <span class="truncate" x-text="p.title"></span>
            <span class="badge" x-show="p.doc_type === 'answer'">Answer key</span>
            <span class="badge" x-show="p.doc_type === 'combined'">Questions + answers</span>
        </span>
        <span class="sub" x-text="[p.year, p.session, p.part].filter(Boolean).join(' · ')"></span>
    </span>
    <span class="actions">
        <a class="btn" :href="p.view_url" target="_blank" rel="noopener">
            <i data-lucide="eye" class="h-4 w-4"></i>
            <span class="hidden sm:inline">View</span>
        </a>
        <a class="btn primary" :href="p.download_url">
            <i data-lucide="download" class="h-4 w-4"></i>
            <span class="hidden sm:inline">Download</span>
        </a>
    </span>
</div>
