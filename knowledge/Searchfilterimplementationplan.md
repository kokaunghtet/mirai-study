# Add Search and Filters to Feed

We'll add a live search box, a tag filter, and a sorting option to the feed. The feed will update automatically via AJAX when the user types or changes the filters, seamlessly integrating with the existing infinite scroll functionality.

## Proposed Changes

### Controller

#### [MODIFY] app/Http/Controllers/PostController.php
- **Query Updates**: Modify the `index` method to apply search criteria (title, content, author's name), filter by tag ID, and sort by either `latest` (default) or `popular` (likes count & comments count).
- **Pass Tags**: Fetch all tags (`Tag::all()`) and pass them to the `feed.index` view so we can populate the Tag dropdown.
- **Query String**: Append the current request's query string to the paginator (`->withQueryString()`) so the `next_page_url` includes the search/filter parameters for infinite scroll.

### Views

#### [MODIFY] resources/views/feed/index.blade.php
- **Search UI**: Add a search bar, a Tag dropdown filter, and a Sort dropdown (Latest / Popular) at the top of the Main Feed section.
- **Javascript Updates**:
  - Add event listeners to the search input (`input` event with debounce) and select dropdowns (`change` event).
  - When filters change, reset `currentPage = 1`, `hasMore = true`, clear the `posts-container`, and fetch the first page with the new query parameters.
  - Update the `fetchPage` URL builder to include the current values of the search input, tag filter, and sort filter.

## Verification Plan

### Manual Verification
1. Navigate to the feed.
2. Type in the search box; verify that posts filter down to matching titles, content, or author names without a page reload.
3. Select a specific tag; verify only posts with that tag are shown.
4. Change sorting to "Popular"; verify posts are sorted by likes and comments.
5. Scroll down; verify infinite scroll still works and loads subsequent pages using the currently active search and filter parameters.
