# Feed Ranking Algorithm — "For You"

How MiraiStudy orders the social feed. Replaces the old chronological / "popular"
sort with a single algorithmic score, the way Facebook/Instagram rank a home feed.

- **Where it lives:** `Post::scopeForYouRanked()` in `app/Models/Post.php`
- **Where it's called:** `PostController::index()` in `app/Http/Controllers/PostController.php`
- **Tests:** `tests/Feature/FeedRankingTest.php`

---

## The core idea

Every post gets a **single numeric score**, and the feed is sorted by that score,
highest first. The score blends four ingredients:

```
score = ENGAGEMENT + RECENCY + FOLLOW_BOOST + JITTER
```

The trick is putting all four on a **comparable scale**. We use **"15-day units"** as
the common unit — every term answers "how many 15-day periods of freshness is this worth?"

---

## Term 1 — RECENCY (how new is it)

```
RECENCY = (created_at_epoch − 1,735,689,600) / 1,296,000
```

`created_at` becomes a Unix timestamp (seconds since 1970), then we subtract
`1,735,689,600` (2025-01-01 00:00:00 UTC — the app's reference epoch) and divide by
`1,296,000` (seconds in **15 days**). Anchoring to the app epoch keeps values small
(~12–25) instead of huge (~6 759), so engagement can actually compete.

1 recency point = 15 days of age: a post made today scores ~0.067 higher than
yesterday's, ~0.33 higher than one from 5 days ago. Newer = higher, but age now
decays gently so engagement and follows can compete instead of being steamrolled by
raw recency.

---

## Term 2 — ENGAGEMENT (how much people interacted)

```
weighted    = likes + 2×comments + 1.5×bookmarks
ENGAGEMENT  = log10(1 + weighted)
```

First we combine the three signals with weights — a comment counts as 2 likes
(more intent/effort than a like), a bookmark as 1.5. Then we take **log10**, which
is the important part:

| weighted engagement | log10 value |
| ------------------- | ----------- |
| 0                   | 0           |
| 10                  | ~1.0        |
| 100                 | ~2.0        |
| 1000                | ~3.0        |

The log **compresses** large numbers: 0→10 adds ~1 point, but so does 100→1000.
This stops one viral post from sitting at the top forever.

**How the two main terms interact** — each `10×` of engagement adds ~1.0 log point,
and (after the 15-day decay) each day of age subtracts only ~0.067:

> **10× more engagement ≈ worth 15 days of freshness.**

A ~15-day-old post needs only ~10× the engagement to beat a brand-new one. The gentle
15-day decay is what lets an older-but-loved post climb above a fresh-but-dead one.

---

## Term 3 — FOLLOW_BOOST (personalization)

```
FOLLOW_BOOST = 1.5 if you follow the author, else 0
```

`PostController::index()` fetches the IDs you follow (accepted follows only) and
passes them in; in SQL it becomes `CASE WHEN posts.user_id IN (...) THEN 1.5 ELSE 0`.
At the 15-day decay, 1.5 points ≈ **22.5 days fresher** — a strong, deliberate lift so
a followed author clearly outranks a stranger's brand-new post. Very old followed
content (>22.5 days) still yields to fresher posts, so it's a lift, not a hard pin.

Works for guests and brand-new users too: with no follows this term is just 0 for
everyone, and the feed gracefully falls back to "trending + recent."

---

## Term 4 — JITTER (the "feels alive" wiggle)

```
JITTER = (((post_id × 2654435761 + seed) % 100000) / 100000) × 0.25
```

A small pseudo-random value between 0 and 0.25, **deterministic** for a given
`(post, seed)` pair. It only reshuffles posts whose scores are nearly tied, so the
feed isn't byte-identical every visit. The `seed` rotates daily, so today's order
differs slightly from tomorrow's. Capped at 0.25 (≈ 3.75 days of freshness at the
15-day decay) so it swaps near-ties without ever letting a dead post leapfrog a
genuinely popular one.

---

## The constraint that shaped everything: pagination stability

The feed loads 10 posts per page via infinite scroll — **separate HTTP requests
fired seconds apart** as you scroll. If the order changed between page 1 and page 2,
you'd see the same post twice or miss posts entirely. Three design choices prevent
that:

1. **No `NOW()` in the formula.** A naive "age = now − created_at" decay shifts
   every clock tick. We use the post's *fixed* `created_at` epoch instead — two
   posts compared at any instant give the same relative order regardless of when
   the query runs.
2. **The jitter seed is fixed per session** (derived from session ID + date), not
   random per request — stable while you scroll, changes day to day.
3. **A final `ORDER BY posts.id DESC`** breaks exact ties, guaranteeing a total,
   deterministic order.

---

## Worked example

Three posts, you follow nobody:

| Post | Age        | Engagement  | RECENCY | ENGAGEMENT       | Score\* |
| ---- | ---------- | ----------- | ------- | ---------------- | ------- |
| A    | today      | 0 likes     | 12.40   | log10(1) = 0     | 12.40   |
| B    | 5 days ago | 30 likes    | 12.07   | log10(31) ≈ 1.49 | 13.56   |
| C    | 2 days ago | 200 likes   | 12.27   | log10(201) ≈ 2.30 | 14.57  |

\*ignoring tiny jitter. RECENCY = (epoch − 1 735 689 600) / 1 296 000.

Order: **C → B → A**. Post C wins — not the newest, but heavy engagement (≈2.3 log
points = ~34 days of freshness) pushes its 2-day-old self clearly past today's empty
post A. Post B's 1.49 points (~22 days) easily offsets its 5-day age, landing it
well above A — engagement now meaningfully outweighs modest recency differences.

---

## Cross-database note

Production runs **MySQL**; tests run **SQLite** in-memory. The scope is driver-aware:

- **MySQL:** `LOG10(...)` and `(UNIX_TIMESTAMP(created_at) − 1735689600) / 1296000`.
- **SQLite:** `(strftime('%s', created_at) − 1735689600) / 1296000.0` for recency,
  and a **linear** engagement term (no `LOG10`) since the bundled SQLite build may
  lack math functions. Feed order isn't asserted on SQLite — that path only needs to
  run without error.

The `likes_count` / `comments_count` / `bookmarks_count` values come from
`withCount(['likes', 'comments', 'bookmarks'])` in the controller, and are referenced
by alias inside `ORDER BY` (valid on both engines).

---

## Tunable knobs

All constants in `app/Models/Post.php`:

| Constant                 | Default        | Effect                                                                 |
| ------------------------ | -------------- | ---------------------------------------------------------------------- |
| `RECENCY_DECAY`          | `1296000`      | Raise → age matters *less* (popular old posts surface harder). 15 days/point. |
| `RECENCY_EPOCH`          | `1735689600`   | 2025-01-01 UTC reference point. Keeps recency values small (~12–25).   |
| `ENGAGE_COMMENT_WEIGHT`  | `2`            | Worth of a comment relative to a like.                                 |
| `ENGAGE_BOOKMARK_WEIGHT` | `1.5`          | Worth of a bookmark relative to a like.                                |
| `FOLLOW_BOOST`           | `1.5`          | Crank up → more "following-centric" feed. ≈ 22.5 days at 15-day decay. |
| `JITTER_SCALE`           | `0.25`         | Bigger → more shuffle/variety; smaller → more stable.                  |
| `JITTER_MULT`            | `2654435761`   | Knuth multiplicative hash constant; don't need to change.              |

---

## Not included (deferred)

- **View/impression tracking** (engagement-rate ranking) — needs a new `post_views`
  table + a write on every render.
- **Author diversity** ("don't show 5 posts from the same person in a row") — hard
  to express in pure SQL with offset pagination.
- Profile post lists stay chronological — this scope is feed-only.
