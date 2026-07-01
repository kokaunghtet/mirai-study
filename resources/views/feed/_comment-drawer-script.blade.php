{{--
    Alpine factory for the comment drawer. Loads the comments partial via AJAX
    and intercepts comment/reply/delete submits so they post in place. Include
    once on any page that renders the drawer box (feed._comment-drawer), inside
    an x-data="commentDrawer()" root.
--}}
<script>
    function commentDrawer() {
        return {
            isOpen: false,
            loading: false,
            title: 'Comments',
            url: null,
            postId: null,

            async open({ url, title, id }) {
                this.url     = url;
                this.title   = title || 'Comments';
                this.postId  = id ?? null;
                this.isOpen  = true;

                this.loading = true;
                await this.load();
            },

            close() {
                this.isOpen = false;
            },

            // Read the comment count baked into the partial and push it into
            // the shared Alpine store so the matching post card badge updates.
            syncCount() {
                const root = this.$refs.content.querySelector('[data-comments-root]');
                if (!root || this.postId === null) return;
                Alpine.store('commentCounts')[this.postId] = Number(root.dataset.count);
            },

            async load() {
                this.loading = true;
                try {
                    const res = await fetch(this.url, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    if (!res.ok) throw new Error('HTTP ' + res.status);
                    this.$refs.content.innerHTML = await res.text();
                    window.renderIcons(this.$refs.content);
                    this.syncCount();
                } catch (err) {
                    console.error('Failed to load comments:', err);
                    this.$refs.content.innerHTML =
                        '<p class="py-10 text-center text-sm text-muted">Could not load comments. Please try again.</p>';
                } finally {
                    this.loading = false;
                }
            },

            // Intercept comment / reply / delete form submits inside the
            // drawer so they post via AJAX and refresh in place.
            init() {
                const token = document.querySelector('meta[name=csrf-token]')?.content;

                this.$refs.content.addEventListener('submit', async (e) => {
                    const form = e.target.closest('form');
                    if (!form) return;

                    // A delete form's inline confirm() may have already
                    // cancelled the submit — respect that and bail.
                    if (e.defaultPrevented) return;

                    e.preventDefault();

                    const submitBtn = form.querySelector('[type=submit]');
                    window.showButtonLoading(submitBtn);

                    // Remove any previous validation error for this form
                    form.querySelectorAll('.js-reply-error').forEach(el => el.remove());

                    try {
                        const res = await fetch(form.action, {
                            method: 'POST', // _method spoofing handles DELETE
                            body: new FormData(form),
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': token,
                                'Accept': 'text/html',
                            }
                        });

                        if (res.status === 422) {
                            const data = await res.json();
                            const msg = data.errors
                                ? Object.values(data.errors).flat()[0]
                                : data.message || 'Validation failed.';
                            const errEl = document.createElement('p');
                            errEl.className = 'js-reply-error text-red-500 text-xs mt-1';
                            errEl.textContent = msg;
                            form.appendChild(errEl);
                            window.resetButtonLoading(submitBtn);
                            return;
                        }

                        if (!res.ok) throw new Error('HTTP ' + res.status);
                        this.$refs.content.innerHTML = await res.text();
                        window.renderIcons(this.$refs.content);
                        this.syncCount();
                    } catch (err) {
                        console.error('Comment action failed:', err);
                        window.resetButtonLoading(submitBtn);
                    }
                });
            },
        };
    }
</script>
