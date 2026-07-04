<div class="animate-pulse">
    {{-- Filter bar --}}
    <div class="mb-5 flex flex-wrap items-center gap-2">
        <div class="h-8 w-56 rounded-xl bg-surface-muted"></div>
        <div class="h-6 w-12 rounded-full bg-surface-muted"></div>
        <div class="h-6 w-9 rounded-full bg-surface-muted"></div>
        <div class="h-6 w-12 rounded-full bg-surface-muted"></div>
        <div class="h-6 w-14 rounded-full bg-surface-muted"></div>
        <div class="h-6 w-20 rounded-full bg-surface-muted"></div>
        <div class="h-6 w-14 rounded-full bg-surface-muted"></div>
    </div>

    {{-- Table --}}
    <div class="rounded-2xl border border-line bg-surface shadow-sm overflow-hidden">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b border-line bg-surface-muted">
                    <th class="px-4 py-3 text-left rounded-tl-2xl"><div class="h-3 w-8 rounded bg-line"></div></th>
                    <th class="px-4 py-3 text-left hidden sm:table-cell"><div class="h-3 w-10 rounded bg-line"></div></th>
                    <th class="px-4 py-3 text-left"><div class="h-3 w-8 rounded bg-line"></div></th>
                    <th class="px-4 py-3 text-left"><div class="h-3 w-10 rounded bg-line"></div></th>
                    <th class="px-4 py-3 text-left hidden md:table-cell"><div class="h-3 w-10 rounded bg-line"></div></th>
                    <th class="px-4 py-3 text-right rounded-tr-2xl"><div class="h-3 w-12 rounded bg-line ml-auto"></div></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-line">
                @for ($i = 0; $i < 7; $i++)
                <tr>
                    <td class="px-4 py-3">
                        <div class="flex items-center gap-2.5">
                            <div class="h-8 w-8 shrink-0 rounded-full bg-surface-muted"></div>
                            <div class="space-y-1.5">
                                <div class="h-3 w-24 rounded bg-surface-muted"></div>
                                <div class="h-2.5 w-16 rounded bg-surface-muted"></div>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-3 hidden sm:table-cell"><div class="h-3 w-36 rounded bg-surface-muted"></div></td>
                    <td class="px-4 py-3"><div class="h-5 w-14 rounded-full bg-surface-muted"></div></td>
                    <td class="px-4 py-3"><div class="h-5 w-14 rounded-full bg-surface-muted"></div></td>
                    <td class="px-4 py-3 hidden md:table-cell"><div class="h-3 w-20 rounded bg-surface-muted"></div></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-2">
                            <div class="h-7 w-14 rounded-lg bg-surface-muted"></div>
                            <div class="h-7 w-20 rounded-lg bg-surface-muted"></div>
                        </div>
                    </td>
                </tr>
                @endfor
            </tbody>
        </table>
    </div>
</div>
