# Appearance Settings — Layout Design Specification

---

## 1. Overall Page Structure

The page is divided into three structural zones:

```
┌──────────┬──────────────────────────────────────────────────────┐
│          │  Page Header                                         │
│ Sidebar  ├───────────────────────────┬──────────────────────────┤
│  (rail)  │                           │                          │
│          │   Left Column             │   Right Column           │
│          │   (Controls ~60%)         │   (Preview ~40%)         │
│          │                           │                          │
└──────────┴───────────────────────────┴──────────────────────────┘
```

---

## 2. Sidebar (Existing — Icon Rail)

- Narrow collapsed rail on the far left edge.
- Contains icon-only navigation items stacked vertically.
- A small arrow/chevron toggle sits at the very top to expand/collapse.
- Profile avatar sits pinned at the bottom.
- No labels visible in this collapsed state.

---

## 3. Page Header

Located at the top of the main content area, full width above both columns.

- **Title:** Large bold heading — "Appearance"
- **Subtitle:** A single line of muted descriptive text below the title.
- No buttons, actions, or dividers in the header area.

---

## 4. Two-Column Layout

Sits below the page header. Columns are side-by-side with a gap between them.

| Column        | Width   | Content          |
|---------------|---------|------------------|
| Left Column   | ~60%    | Configuration controls |
| Right Column  | ~40%    | Live interface preview |

---

## 5. Left Column — Controls

Contains two stacked sections separated by vertical spacing.

---

### 5.1 Section: Theme

- **Section Header:** Medium-weight label — "Theme"
- **Component:** Segmented toggle — a single pill-shaped container holding three equal-width options in one row.

```
┌──────────────────────────────────────────────────────┐
│  [ ☀ Light ]     [  ☽ Dark  ]     [ ⚙ System (auto)] │
└──────────────────────────────────────────────────────┘
```

- Each option has an icon above the label text.
- The selected option has a filled background; unselected options have no background.
- The outer container has a subtle background and rounded pill shape.
- All three options share equal width and height.

---

### 5.2 Section: Primary Color

- **Section Header:** Medium-weight label — "Primary Color"
- **Component:** A 2-row × 3-column grid of color selection buttons.

```
┌────────────┐  ┌────────────┐  ┌────────────┐
│ ✓  Venom   │  │   Aurora   │  │  Sangria   │
└────────────┘  └────────────┘  └────────────┘
┌────────────┐  ┌────────────┐  ┌────────────┐
│  Twilight  │  │  Inferno   │  │   Ocean    │
└────────────┘  └────────────┘  └────────────┘
```

- Each button is a wide pill/rounded-rectangle shape.
- Each button is filled with its representative color and displays the color name as white text centered inside.
- The selected button shows a checkmark icon to the left of the name.
- All buttons are equal in size. Grid gap is consistent between rows and columns.

---

## 6. Right Column — Live Preview Panel

A single card that spans the full height of the right column.

### 6.1 Preview Card Container

- Rounded card with a white background and a light border.
- A small all-caps label at the top — "LIVE INTERFACE PREVIEW" — acts as the card header.
- The card content below the label is a non-interactive miniaturized app mockup.

---

### 6.2 Mini App Mockup (Inside Preview Card)

The mockup is a scaled-down simulation of the actual app. It is split into two side-by-side panels.

```
┌─────────────────────────────────────────────────┐
│  LIVE INTERFACE PREVIEW                         │
│  ┌───────────────────┬───────────────────────┐  │
│  │  Mini Sidebar     │  Mini Main Content    │  │
│  │                   │                       │  │
│  │  MiraiStudy       │  Available Exam       │  │
│  │                   │  Papers               │  │
│  │  ▶ Feed (active)  │  ┌───────────────┐    │  │
│  │    Exams          │  │ JLPT N2 Mock  │    │  │
│  │    Quiz           │  │ 140Q · 110min │[▶] │  │
│  │    Focus          │  └───────────────┘    │  │
│  │    Notifications  │                       │  │
│  └───────────────────┴───────────────────────┘  │
└─────────────────────────────────────────────────┘
```

**Mini Sidebar (left panel of mockup):**
- Displays the app brand name at the top — "MiraiStudy".
- Stacked navigation items below the brand:
  - Feed (active — highlighted row with icon)
  - Exams (with icon)
  - Quiz (no icon visible)
  - Focus (with icon)
  - Notifications (with icon)
- The active item has a highlighted background matching the selected Primary Color.

**Mini Main Content (right panel of mockup):**
- Section heading — "Available Exam Papers"
- One exam item card below the heading containing:
  - Exam title — "JLPT N2 Mock Exam"
  - Meta info — "140 Questions · 110 mins"
  - A small action button — "Start" — aligned to the right of the row, filled with the selected Primary Color.

---

## 7. Spacing & Sizing Summary

| Element                        | Notes                              |
|--------------------------------|------------------------------------|
| Page content padding           | Consistent padding on all sides    |
| Gap between left/right columns | Medium gap                         |
| Gap between sections (left)    | Vertical spacing between Theme and Primary Color sections |
| Color button grid gap          | Consistent gap between all 6 buttons (both row and column) |
| Preview card padding           | Padding inside the card before the mockup |
| Mini mockup internal gap       | Small gap between mini sidebar and mini main content |

---

## 8. Interactive Behavior Notes

- Selecting a theme option immediately reflects in the preview card (light/dark background).
- Selecting a color button immediately updates:
  - The active nav item highlight in the mini sidebar.
  - The "Start" button color in the mini main content.
  - The checkmark moves to the newly selected color button.
- Only one theme option and one color can be active at a time (mutually exclusive selection).
- The preview panel is non-interactive — it only responds to changes made in the left column.
