# Editor Notices

How the block surfaces in-editor notices, and why their **appearance varies
between sites** (a common "did this change?" question — it didn't).

## How notices are rendered

- Notices go through the Gutenberg **`core/notices`** store, but are kept
  **block-local** — shown inside the block, next to the add-citations form —
  via a dedicated context key, rather than WordPress's global bottom-left
  snackbar region. Rationale (see `src/hooks/use-block-notices.js`): a message
  is more meaningful next to the active form than in a global snackbar.
- Rendering lives in `src/components/editor-canvas-notices.js` and uses two
  **native `@wordpress/components`** components:
  - `<Snackbar>` — transient success messages (`type: 'snackbar'`).
  - `<Notice>` — errors, warnings, and instructional text.

## Why the look varies (and why it's not our bug)

Because these are **native components**, their visual style (border radius,
spacing, colors) is inherited from whatever `@wordpress/components` version the
site is running — not from this plugin:

- With the **Gutenberg plugin active**, notices render with Gutenberg's current
  design — e.g. **rounded corners**.
- On **stock WordPress core** (Gutenberg plugin deactivated), the same
  components render with core's bundled (often squarer/older) styling. This was
  confirmed by toggling the Gutenberg plugin off.

These differ again from the **classic `wp-admin` PHP notices** (the flat
banners with a colored left border, e.g. plugin-activation messages) — those
are a separate, older notice layer entirely and are unrelated to the block.

Our own editor styles (`src/editor.scss`) only set the notice **wrapper margin,
a focus ring, and 13px notice text** — they never set a `border-radius`. So the
rounded/square difference is purely upstream component styling.

## Takeaway for QA / reviewers

Notice appearance differing between environments (or from memory) is **expected
and not a regression**. If verifying visual consistency, note which
`@wordpress/components` source is active (Gutenberg plugin vs core WordPress)
before flagging a difference. The notice code has been stable since `v1.3.4`.
