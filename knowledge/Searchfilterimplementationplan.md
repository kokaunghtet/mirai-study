# Add Search and Filters to Feed

We'll add a live search box, a tag filter, and a sorting option to the feed. The feed
updates automatically via AJAX when the user types or changes the filters, seamlessly
integrating with the existing infinite scroll in `resources/views/feed/index.blade.php`.

## Proposed Changes

### Controller

#### [MODIFY] app/Http/Controllers/PostController.php
- **Read filters**: Pull `search`, `tag`, and `sort` from the request query string.
- **Search**: Match `title`, `content`, or the author's `name`. The three OR conditions
  MUST be wrapped in a single nested `where(function ($q) { ... })` group, otherwise the
  `orWhere` leaks past the tag `whereHas` and a combined tag + search returns wrong rows.
  Escape LIKE wildcards (`%`, `_`, `\`) in the user term with `addcslashes` so they're
  treated literally. (DB is MySQL → case-insensitive LIKE, `\` is the default escape char.)
- **Tag filter**: `whereHas('tags', ...)` on the given tag id.
- **Sort**: `popular` orders by `likes_count` then `comments_count` (both already provided
  by `withCount`), falling back to `latest()`; default is `latest()`.
- **Query String**: `->withQueryString()` on the paginator so `next_page_url` carries the
  active search/filter params for infinite scroll.
- **Pass Tags**: `Tag::all()` passed to `feed.index` to populate the Tag dropdown (only
  needed on the full-page render, not the AJAX branch).

### Views

#### [MODIFY] resources/views/feed/index.blade.php
- **Search UI**: Above the posts list, add a search input, a Tag `<select>`, and a Sort
  `<select>` (Latest / Popular), styled to match the existing form controls
  (`rounded-xl bg-gray-50 border-gray-200 focus:border-green-400`).
- **JavaScript** — all added INSIDE the existing infinite-scroll IIFE so it shares the
  `currentPage` / `hasMore` / `observer` / `sentinel` closure variables:
  - Single shared `buildUrl(page)` that appends the current `search` + `tag` + `sort`
    values; both infinite scroll and filtering use it (replaces the hardcoded
    `?page=${page}`).
  - Debounced `input` listener on the search box; `change` listeners on the selects.
  - On any filter change run an `applyFilters()` that: resets `currentPage = 1` and
    `hasMore = true`, clears `posts-container`, fetches page 1, and renders results.
  - **Re-observe the sentinel.** The existing code calls `sentinel.remove()` +
    `observer.disconnect()` when a feed ends. Change it to HIDE the sentinel instead of
    removing it, and on filter reset re-show it and `observer.observe(sentinel)` so the
    new result set can paginate. Without this, "scroll to end → change filter" loads page
    1 and never loads page 2.
  - **Empty-results state.** AJAX only renders `feed/_posts.blade.php`, which has no empty
    state (the "No posts yet" block lives in `index.blade.php`). When `applyFilters()` gets
    zero results, render a "No results" message into the container.
  - **Out-of-order responses.** Keep an `AbortController`; abort the previous in-flight
    request before each new `applyFilters()` so a slow earlier response can't overwrite a
    newer one. Ignore `AbortError` in the catch.
  - Update browser URL with `history.replaceState` so refresh/back keeps the filters.

## Verification Plan

### Manual Verification
1. Navigate to the feed.
2. Type in the search box; posts filter by title, content, or author name without reload.
3. Select a tag; only posts with that tag are shown. Combine with search — results stay
   correct (validates the grouped-OR fix).
4. Switch Sort to "Popular"; posts reorder by likes then comments.
5. Scroll to the very bottom (feed ends, sentinel hides), then change a filter; confirm
   the new results still infinite-scroll past page 1 (validates the sentinel re-observe).
6. Search a term with no matches; confirm a "No results" message shows, not a blank gap.
7. Type quickly then clear; confirm results match the final input (validates AbortController).
