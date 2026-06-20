# External API & Library Eccentricities

Gotchas in the third-party services and libraries Borges depends on, with the
evidence and the workaround. Documented so they aren't rediagnosed from scratch.

## citeproc-php: localized `form="text"` dates need locale date blocks we don't ship

**Symptom:** bibliography entries silently dropped the publication year.

**This is NOT a citeproc-php bug** — it behaves exactly as the CSL spec
requires. `<date variable="issued" form="text"/>` (no `<date-part>` children) is
a *localized* date: the renderer pulls the parts to emit from the **active
locale's `<date form="text">` block**
(`Date::prepareDatePartsChildren` → `getDatePartsFromLocales` in
`seboettg/citeproc-php`). This plugin ships a **deliberately minimal locale**
(`packages/citation-style-language-locales/locales-en-US.xml`, ~19 lines) that
defines a few terms and **no `<date>` format blocks at all** — a standard CSL
`locales-en-US.xml` is ~300 lines and does define them. With no locale date
definition and no explicit children, citeproc-php's fallback iterates an empty
date-part list and returns an empty string. Correct per spec; the mismatch was
ours (a locale-dependent style construct against a stripped locale).

**This is also NOT an `intl` problem.** `intl` was the first suspect (the
formatter needs it, and the Playground blueprint requires `features.intl`).
Ruled out by direct repro: on a host **with** `intl` loaded, `form="text"`
still rendered no year, while `<date-part name="year"/>` rendered `1963` for the
same CSL. `intl` is necessary for the formatter in general, but it is not the
cause of the dropped year.

**Rule / fix:** for bundled styles, use a *non-localized* date — explicit
`<date-part name="year"/>`, which renders independently of the locale and yields
exactly the year-only output a bibliography wants:

```xml
<date variable="issued"><date-part name="year"/></date>
```

(The spec-pure alternative — shipping the full standard locales — is far heavier
and would drag in month/day formatting we don't want, so the non-localized
date-part is the better choice here, not merely a workaround.)

Guarded by `tests/phpunit/StyleYearRenderingTest.php` (static check + render
check across every bundled style). Three styles had regressed to the localized
form and dropped the year: `chicago-notes-bibliography`, `oscola`,
`modern-language-association`.

## Build: the formatter renders from `vendor/`, not `packages/`

The PHP formatter (`bibliography_builder_format_csl_items`) loads style XML from
`vendor/citation-style-language/styles/`, **not** from the
`packages/citation-style-language-styles/` source. The `packages/` dir is a
Composer *path* package with `"symlink": false`, so `composer install`
**copies** it into `vendor/`. Consequences that have bitten us:

- Editing a style under `packages/` has **no effect** until `composer install`
  re-copies it to `vendor/` (or you copy it by hand). A stale `vendor/` will keep
  rendering the old style even though the source looks fixed.
- A test that globs only `packages/` gives false confidence — it passes while the
  rendered `vendor/` copy is still broken. `StyleYearRenderingTest` therefore
  globs **both** directories.
- The release zip is safe: `scripts/package-release.sh` runs `composer install`
  into a clean staging dir, so it always copies the current `packages/` source.
- The formatter also caches rendered HTML in a `bbb_*` transient (1 h TTL) keyed
  by items + style *key* — **not** by style file content. After changing a style
  file, clear those transients (or wait out the TTL) or you'll see stale output.

## CrossRef / PubMed: author names returned in ALL CAPS

Some CrossRef and PubMed records return author family (and sometimes given)
names in **all uppercase** — e.g. `TURING` from CrossRef for
`10.1093/mind/LIX.236.433`, and the Watson/Crick authors from the PubMed
resolver. The block stores and renders CSL names verbatim, so these surface as
`TURING, A. M.` in every style (it is *data*, not a style-`text-case` setting —
confirmed by rendering the same record through styles with no uppercase rule).
Normalizing all-caps names to title case is a separate, deliberately deferred
enhancement (it must avoid mangling genuine acronyms, particles like "van der",
and initials). Tracked in `.planning/todos/`.

## CrossRef: non-standard CSL `type` values

**Symptom:** some DOIs (e.g. university-press books like
`10.7208/chicago/9780226458144.001.0001`) failed to import entirely.

**Cause:** CrossRef's CSL-JSON transform emits `type` values that are **not**
valid CSL types — e.g. `monograph` for a book, `edited-book`,
`reference-entry`, `report-component`, `posted-content`. The block's
`validateAndSanitizeCsl` rejects unknown types and aborts the whole import.

**Workaround:** `normalizeCrossRefCsl` (`src/lib/parser.js`) maps the common
CrossRef types to valid CSL types (`monograph`/`edited-book`/`reference-book`
→ `book`, etc.) and falls any remaining unknown type back to the generic
`document`, so an unusual type label can never make a valid DOI unimportable.
The metadata (year, volume, etc.) is otherwise intact in CrossRef's response.

## CrossRef: DOIs with multiple slashes

The lookup URL is built with `encodeURIComponent(doi)` (`src/lib/parser.js`),
which percent-encodes the DOI's slashes (`/` → `%2F`). CrossRef's
`/works/{doi}/transform/...` endpoint **accepts** the encoded form, including
for multi-slash DOIs like `10.7208/chicago/9780226458144.001.0001` (verified:
HTTP 200 for both encoded and raw). No special handling needed — recorded here
because it looks suspect but is fine.

## WordPress: symlinked-plugin `viewScript` "alias" notice (dev only)

When the plugin is loaded into a site via a **symlink pointing outside**
`wp-content/plugins` (a common local-dev setup), WordPress can't compute a
script `src` URL for `viewScript`, registers the handle as a src-less alias,
and logs: *"Cannot supply a strategy `defer` … because it is an alias (it lacks
a `src` value)."* This is an artifact of the symlinked dev install — it does
**not** occur for a normally-installed (copied) plugin or on WordPress.org.
