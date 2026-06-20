# Code Review + QA Remediation Plan — 2026-06-20

## Context

A comprehensive code review and QA analysis was performed for the Borges Bibliography Builder WordPress block plugin after the 1.4.0 codebase state. The review checked the authoritative `SPEC.md`, WordPress/plugin architecture, static save output, parser/formatter/security paths, test coverage, CI workflows, release packaging, and local automated QA commands.

## Automated QA baseline

Commands that passed locally:

-   `npm run lint:js`
-   `npm run lint:css`
-   `composer validate --strict`
-   `composer lint:php`
-   `composer test:php`
-   `composer analyze:php`
-   `composer audit`
-   `npm audit --omit=dev --omit=optional`
-   `npm audit --omit=optional --audit-level=moderate` was run after safe audit fixes; it still reports dev-tool vulnerabilities that require breaking WordPress package upgrades or have no direct fix.
-   `npm run test -- --runInBand`
-   `npm run test:js:coverage`
-   `npm run test:coordination`
-   `npm run lint:i18n`
-   `npm run build`
-   `npm run package:release`

Known caveats:

-   PHPUnit passed but reported vendor deprecations from `seboettg/citeproc-php` under local PHP 8.5.
-   Full `npm audit --audit-level=moderate --omit=optional` failed due to dev/transitive tooling vulnerabilities. Production/runtime audit was clean.
-   Browser/Playwright suites were not run in the non-browser Codex session. Run them in a browser-capable session before release.

## Priority remediation backlog

### P1 — Add server-side CSL validation before PHP formatting

**Finding:** The editor-only `/bibliography/v1/format` endpoint validates payload size and item count, but accepts arbitrary authenticated `cslItems` and passes them to citeproc after JSON encoding/decoding. Client-side parsing validates CSL shape in `src/lib/csl-sanitize.js`, but the PHP formatter endpoint should not rely on client-side validation.

**Primary files:**

-   `bibliography-builder.php`
-   `tests/phpunit/RestEdgeCasesTest.php` or a new PHPUnit test file
-   optionally `tests/phpunit/UtilityFunctionsTest.php`

**Recommended action:**

-   Implement a PHP mirror of the CSL validation policy used in `src/lib/csl-sanitize.js`:
    -   known CSL type safelist
    -   max recursive depth
    -   reject or strip `__proto__`, `constructor`, and `prototype`
    -   validate string fields (`title`, `container-title`, `publisher`, `page`, `volume`, `issue`, `DOI`, `URL`, `language`, etc.)
    -   validate string-or-string-array identifier fields (`ISBN`, `ISSN`)
    -   validate name lists (`author`, `editor`, `reviewed-author`)
    -   validate `issued` and `accessed` date-parts
    -   strip HTML tags from string fields
-   Call this sanitizer in `bibliography_builder_rest_format_citations()` before `bibliography_builder_format_csl_items()`.
-   Return `400` for invalid CSL payloads with a stable error code.

**Acceptance criteria:**

-   Malformed CSL sent to `/bibliography/v1/format` is rejected before citeproc runs.
-   Existing valid formatter requests still pass.
-   PHPUnit covers invalid type, invalid author shape, invalid date-parts, deeply nested payload, HTML-bearing string field, and prototype-ish keys.
-   `composer lint:php`, `composer test:php`, and `composer analyze:php` pass.

---

### P2 — Complete source-level i18n coverage

**Finding:** `npm run lint:i18n` validates translation artifact consistency, but many user-facing strings in `src/` remain raw English literals. This leaves notices, aria labels, button labels, and dynamic messages outside translation workflows.

**Primary files:**

-   `src/hooks/use-citation-editor-state.js`
-   `src/hooks/use-bibliography-export-actions.js`
-   `src/hooks/use-manual-citation-actions.js`
-   `src/hooks/use-citation-import-actions.js`
-   `src/components/citation-entry-body.js`
-   `src/components/citation-reorder-controls.js`
-   `src/lib/citation-limits.js`
-   `src/save-markup.js`
-   `scripts/check-i18n.js` or a new source-string scanner
-   `languages/borges-bibliography-builder.pot` and seed PO/MO files if updated

**Recommended action:**

-   Wrap all user-facing source strings with WordPress i18n helpers.
-   Use `_n()` for plural forms and `sprintf()` for dynamic values.
-   Add a source-level check that flags likely raw UI strings in `src/`, not only stale PO/POT content.
-   Regenerate POT/PO/MO artifacts after wrapping strings.

**Acceptance criteria:**

-   No obvious user-facing raw strings remain in editor notices, aria labels, button labels, inspector text, or export messages.
-   `npm run lint:i18n`, `npm run lint:js`, and `npm run test -- --runInBand` pass.
-   Updated POT/seed files are deterministic.

---

### P2 — Make JS coverage reporting file-inclusive and actionable

**Finding:** `npm run test:js:coverage` reports high coverage, but the coverage artifact only included a small subset of `src/` files. The percentage is therefore not a reliable project-level signal.

**Primary files:**

-   `package.json` Jest config
-   possibly a dedicated Jest config file
-   `.github/workflows/ci.yml`

**Recommended action:**

-   Add explicit `collectCoverageFrom` patterns for `src/**/*.js`.
-   Exclude tests, fixtures, benchmarks, and generated or non-runtime utility files deliberately.
-   Add threshold gates for high-risk layers such as parser/security serializers and formatter helpers.
-   Consider making Codecov upload non-blocking but local coverage generation strict.

**Acceptance criteria:**

-   Coverage artifact includes all intended runtime source files.
-   Coverage output clearly identifies low-coverage files.
-   CI fails or warns intentionally when coverage drops below agreed thresholds.

---

### P2 — Triage dev dependency audit warnings

**Finding:** Production dependency audit is clean, but full npm audit reports dev/transitive vulnerabilities through tooling such as `@wordpress/scripts`, webpack dev server, jsdom, Lighthouse, and related packages.

**Primary files:**

-   `package.json`
-   `package-lock.json`
-   `.github/workflows/ci.yml`
-   optional: `docs/release-readiness-checklist.md`

**Recommended action:**

-   Create a dependency-maintenance branch.
-   Test the suggested `@wordpress/scripts` upgrade separately because npm reports it as a breaking-change path.
-   Add an explicit audit policy:
    -   fail CI on production/runtime vulnerabilities
    -   report dev-only audit warnings on a scheduled workflow or as non-blocking CI
    -   document accepted dev-only exceptions with expiry dates

**Acceptance criteria:**

-   `npm audit --omit=dev --omit=optional` remains clean.
-   Dev audit warnings are either remediated or documented in a tracked exception list.
-   Build, lint, Jest, and Playwright smoke tests pass after dependency updates.

---

### P3 — Track PHP 8.5 vendor deprecations

**Finding:** PHPUnit passes, but local PHP 8.5 reports deprecations from `seboettg/citeproc-php` due to implicitly nullable parameters.

**Recommended action:**

-   Track upstream citeproc-php PHP 8.5 compatibility.
-   Add a PHP 8.5 allowed-failure CI lane when practical.
-   Avoid suppressing deprecations globally unless they create noisy release blockers.

**Acceptance criteria:**

-   A tracking issue exists for PHP 8.5 compatibility.
-   Current supported PHP range remains green.

---

### P3 — Clean release package runtime metadata

**Finding:** Release packaging is compact and excludes source/tests/node_modules. It still includes runtime `composer.json` and vendor dotfiles such as `.gitignore`.

**Primary files:**

-   `scripts/package-release.sh`
-   `.distignore`

**Recommended action:**

-   Remove or replace `composer.json` in the release staging directory after `composer install --no-dev`.
-   Delete vendor dotfiles after install.
-   Add package assertions for forbidden files.

**Acceptance criteria:**

-   Release ZIP contains only runtime files and notices required for licensing/compliance.
-   `npm run package:release` fails if forbidden dev/source files are staged.

---

### P3 — Browser/manual QA release gate

**Finding:** Browser-only workflows were not run in the non-browser review session.

**Recommended action:**

Run in a browser-capable session before release:

-   `npm run test:e2e:playground`
-   `npm run test:a11y`
-   `npm run test:e2e:lifecycle`
-   `npm run test:runtime:local`
-   Manual QA from `docs/qa-matrix-checklist.md`
-   Manual checklist from `docs/manual-test-checklist.md`

**Acceptance criteria:**

-   Playwright and accessibility gates pass.
-   Manual QA notes are captured for any failures or release exceptions.

## Recommended implementation order

1. **Server-side CSL validation** — highest security hardening value and well-scoped.
2. **Source-level i18n pass** — high product-quality value; may touch many files, so do it separately.
3. **Coverage config correction** — improves confidence and may reveal real test gaps.
4. **Dependency audit triage** — safest as its own branch because `@wordpress/scripts` upgrades may cascade.
5. **Package cleanup** — low risk, small change.
6. **Browser/manual QA gate** — run after code changes stabilize.

## Suggested branch sequence

-   `codex/server-csl-validation`
-   `codex/i18n-source-strings`
-   `codex/coverage-config`
-   `codex/dev-dependency-audit`
-   `codex/package-cleanup`

Keep changes small and separately reviewable. The server validation branch should ship first if time is limited.

## Execution update — 2026-06-20

Implemented on branch `codex/review-qa-remediation`:

-   Added PHP server-side CSL validation/sanitization before formatter execution, including CSL type safelist, forbidden prototype-like key removal, max-depth rejection, string/date/name validation, and HTML stripping for known string fields.
-   Added PHPUnit edge coverage for invalid CSL type, invalid author shape, invalid date-parts, excessive nesting, HTML stripping, and forbidden key removal.
-   Completed a source i18n pass for editor notices, dynamic aria/button labels, limit messages, manual entry messages, parser errors, export notices, and default save aria label.
-   Extended `scripts/check-i18n.js` with a source-string regression scanner and regenerated POT/PO/MO artifacts.
-   Made JS coverage file-inclusive with explicit `collectCoverageFrom` patterns and global coverage thresholds.
-   Applied non-forcing `npm audit fix --omit=optional`, kept production dependency audits clean, and added production dependency audit gates to CI plus a weekly dependency audit workflow with non-blocking dev-tool audit monitoring.
-   Documented the dependency audit policy and updated release readiness gates.
-   Hardened release packaging to remove root/vendor Composer metadata and vendor dotfiles, with assertions that fail packaging if forbidden metadata is staged.

Local verification completed after implementation:

-   `npm run lint:js`
-   `npm run lint:css`
-   `npm run lint:i18n`
-   `npm test -- --runInBand`
-   `npm run test:js:coverage` — 94.68% statements, 87.39% branches, 96.8% functions, 94.69% lines
-   `npm run test:coordination`
-   `npm run build`
-   `npm run package:release`
-   `composer validate --strict`
-   `composer lint:php`
-   `composer test:php` — passed with 4 existing/vendor deprecations under local PHP 8.5
-   `composer analyze:php`
-   `composer audit`
-   `npm audit --omit=dev --omit=optional`
-   `npm audit --omit=optional --audit-level=moderate` was run after safe audit fixes; it still reports dev-tool vulnerabilities that require breaking WordPress package upgrades or have no direct fix.

Still requires browser-capable QA before release:

-   `npm run test:e2e:playground`
-   `npm run test:a11y`
-   `npm run test:e2e:lifecycle`
-   `npm run test:runtime:local`
-   Manual QA from `docs/qa-matrix-checklist.md` and `docs/manual-test-checklist.md`
