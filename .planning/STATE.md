# Project State

## Current Focus

1. **v1.3.0 is live on WordPress.org.** Phase 3 (release prep) is complete.
   The GitHub Release was published manually, triggering `wp-deploy.yml` SVN
   deploy. WordPress.org plugin page now shows version 1.3.0.
2. Keep whole-bibliography reformat optimization conservative: numeric deletion
   is the only confirmed safe no-reformat path; author-date/notes paths remain
   full-list formatted.
3. Track refreshed build footprint from the benchmark report while maintaining
   frontend zero-JS for saved output.
4. Next: plan and execute Phase 4 (Frontend Cite/Export affordances) or the
   large-bibliography benchmark spike, per roadmap priority order.

## Current Priority Order

1. Performance and stability remediation — **Phase 2 committed (`3d5d3de`, `539b6b3`)**
    - The former top risk, the hidden 51-entry formatter cliff, is now addressed
      by an explicit 50-total-citation policy and editor guards.
    - Completed: conservative 50-total-citation policy, fallback cache
      correctness, manual-entry single-format path, async stale guards,
      benchmark hardening, release pruning, PMID response caching, duplicate DOI
      resolution reduction, persistent-object-cache-only formatter caching,
      memory-bounded JS formatter cache keys, supported-size mutation
      benchmarks, numeric deletion no-reformat coverage, refreshed build
      footprint reporting, and the final maintainability slices for
      paste/import, manual entry, clipboard/export, PMID resolver/caching, and
      free-text author parsing.
    - Optional deeper splits (delete/list-mutation, formatter/REST helpers,
      free-text-parser by citation type) remain follow-up only.
2. CI and runtime compatibility hygiene
    - keep `main`, release assets, and WordPress.org SVN output aligned
    - monitor the Apache/Nginx, PHP 7.4-8.4/8.5-adjacent, WordPress 6.4-latest,
      SQLite, and Multisite runtime lanes
    - track citeproc-php PHP-version deprecations before they become a PHP 9
      stability risk
3. Interoperability and automation backlog — paused behind Phase 2 hardening
    - frontend Cite/Export affordances remain valuable but should not outrank
      the 51-entry cliff
    - BibLaTeX export and PMID input/proxy are already shipped; remaining work
      is BibLaTeX import and future biomedical identifiers such as PMCID/NBIB if
      demand appears
    - writable bibliography REST routes and WordPress Abilities integration
      still require a design memo before implementation
4. Translation and language-pack expansion backlog
    - current official WordPress.org language packs: Russian (`ru_RU`); English
      (US) is the source language and is not counted as a translated locale
    - bundled PO/MO files remain seed/import material for translator review, not
      official WordPress.org language-pack availability
    - first-wave official language-pack candidates remain French, German,
      Spanish, Brazilian Portuguese, and Japanese after hardening priorities
      settle
5. Low-priority UX/documentation polish
    - full accessibility audit and optional Block Accessibility Checks
      follow-ups remain useful, but should not displace Phase 2 P0 reliability
      work
    - localized WordPress.org banner variants remain demand/translation-gated

## Last activity

Recent work completed or in final verification includes:

-   Phase 2 stabilization pass 4 completed the intended narrow maintainability
    slice: manual-entry state/add/clear behavior now lives in
    `useManualCitationActions()`, while the manual-entry UI markup and shared
    latest-operation guard ownership remain in `edit.js`. The editor shell is
    now ~845 lines.
-   Phase 2 stabilization pass 3 extracted paste/import and clipboard/export
    behavior into focused hooks, moved PMID cache/permission/resolver behavior
    into `includes/pmid.php`, and split free-text author parsing into
    `src/lib/free-text-authors.js`.
-   Phase 2 execution pass 3 added measured mutation/bundle reporting and
    reaffirmed the style-context-safe optimization boundary: numeric deletion
    can skip formatter work, while author-date/notes mutation paths keep
    full-list reformatting. Latest local build footprint is 261.54 KB raw /
    80.15 KB gzip across build assets.
-   Phase 2 execution pass 2 extended the hardening work: duplicate DOI input
    now avoids avoidable `citation-js` calls, successful DOI metadata has
    bounded in-session reuse, formatter responses cache only in persistent
    object cache with a short TTL, and the JS formatter cache uses bounded hash
    keys plus approximate byte-budget eviction.
-   Phase 2 execution pass 1 landed: 50 total citations is now the explicit 1.x
    block limit, fallback formatter output no longer poisons the successful
    cache, manual add formats once, async editor mutation flows have shared
    stale-result guards, benchmarks detect fallback, release packaging prunes
    dead vendor weight, and PMID success/404 responses are cached.
-   Recent performance/stability planning is now committed: `b0527ed` added the
    remediation plan and `809b9d5` revised it after the deeper review.
-   GSD Phase 2 now mirrors that remediation plan, with the hidden 51-entry
    cliff as the first execution item.
-   PMID input/resolution has moved from planned interoperability work to
    shipped current state through the WordPress REST proxy (`a99b7e3`,
    `6c05c63`, `77f77a9`); remaining PMID work is cache/retry hardening.
-   Release workflow dispatch follow-up landed in `3a5ae89`.
-   The latest dependency-only commit `c577c5c` updated `fast-uri` in
    development dependencies.
-   `main` CI was restored to green after the branding/changelog cleanup.
-   repository-facing URLs, badges, Plugin URI, security reporting URL,
    Playground blueprint, and language bug-report headers were normalized around
    `dknauss/borges-bibliography-builder` and the approved
    `borges-bibliography-builder` slug.
-   the release package name is standardized on
    `borges-bibliography-builder.zip`; no transition zip is needed because this
    is the first public WordPress.org release.
-   a new Apache/PHP 8.3/latest-WordPress Multisite runtime smoke lane was added
    and validated locally with network activation.
-   PHP utility-function coverage was expanded, including plain-text/export
    helper behavior and ABNT normalization edge cases.
-   Claude bug-sweep follow-up landed: `outputJsonLd` REST defaults now honor
    the block attribute default when the key is absent, structured-edit
    cancellation now guards stale post-format commits, thesis COinS and
    chapter/review JSON-LD mappings were corrected, and 25 additional regression
    tests now cover those paths plus focus helper behavior.
-   docs/spec/checklist sync across `README.md`, `SPEC.md`, and QA worksheets.
-   reduced persisted citation payload for new entries by dropping `inputRaw`,
    `parsedAt`, and `parseConfidence`.
-   bounded-concurrency DOI parsing with stable result ordering.
-   tighter CSL validation for additional structured fields.
-   JSON-LD corporate-author typing improvements (`Organization` for
    literal-only institutional authors).
-   metadata output controls verified live on the front end.
-   additional spec-strength tests for sorting, `lang`, and review DOI fixtures.
-   spec clarification that focus-management behavior is already implemented and
    tested.
-   manual-entry v1 shipped with a second add mode, curated 6-type selector, and
    the current 8 structured fields plus Publication Type.
-   Harvard, Vancouver, IEEE, MLA 9, OSCOLA, and ABNT are now enabled selectable
    styles, with formatter and save-path coverage.
-   manual-entry formatting moved behind async loading so the main editor
    entrypoint stays small.
-   Xdebug trace/profile capture confirms PHP runtime cost is mostly
    WordPress/theme bootstrap; plugin-specific PHP cost is concentrated in
    `build/index.asset.php`.
-   editor-side CSL formatting now batches duplicate work, paste/import defers
    formatting until the editor actually needs it, and a repeatable local
    benchmark harness now records cold-cache timings.
-   `parsePastedInput()` is now parse-first by default; callers must opt in
    explicitly if they want parser-owned formatting, and
    lint/tests/benchmarks/build all passed after the change.
-   native notification follow-up landed: Gutenberg `Notice`/`Snackbar`
    primitives now handle block-local feedback with success-only snackbars and
    inline mixed-result validation notices.
-   submission-readiness packaging landed: `npm run package:release` creates a
    clean artifact and Plugin Check passed against the staged release directory
    with no errors.
-   CSL-JSON, BibTeX, and RIS exports now ship in the editor as practical
    downloadable bibliography-data formats.
-   a per-entry copy-citation action now ships in the editor for reusing visible
    citation text.
-   a Copy bibliography action now ships in the editor for copying the current
    bibliography as plain text.
-   read-only REST endpoints now expose bibliography block data at
    `/wp-json/bibliography/v1/posts/<post_id>/bibliographies` and
    `/wp-json/bibliography/v1/posts/<post_id>/bibliographies/<index>`.
-   GitHub Actions runtime coverage now spans additional
    Apache/Nginx/PHP/WordPress combinations and includes SQLite and Multisite
    smoke lanes.
-   translation docs now distinguish official WordPress.org language packs from
    bundled seed PO/MO files; Russian (`ru_RU`) is the current official
    generated language pack, while the bundled seed files require
    translate.wordpress.org review before they should be advertised as public
    language availability.
-   post-launch cleanup aligned GitHub/WordPress.org links, Playground demo
    copy, REST API docs, screenshot ordering, SPEC runtime coverage, WordPress
    7.0 compatibility wording, manual wp.org deploy triggering, and small
    code-quality refactors.

## Active Concerns

-   **v1.3.0 is live** — WordPress.org plugin page shows 1.3.0. GitHub Release
    published manually; `wp-deploy.yml` SVN deploy triggered and confirmed.
    Release baseline is now `v1.3.0`.
-   **Performance plan vs. implementation** — the remediation plan and GSD
    Phase 2 are now consistent with what is in the committed tree;
    correctness, cache hardening, measured optimization, and the scoped
    maintainability items are landed.
-   **Release baseline vs. head** — `v1.3.0` is the current release baseline.
    Avoid retagging unless a real release blocker or WordPress.org deployment
    correction requires it.
-   **GitHub repository rename fallout** — after the rename to
    `dknauss/borges-bibliography-builder`, confirm badges, release links,
    Codecov, Playground, and GitHub Actions redirects resolve from the canonical
    path.
-   **Dependabot alerts #31/#32** — leave open for upstream tracking unless the
    warning noise becomes unacceptable. Both are transitive npm development
    dependencies only, not bundled in the WordPress.org release zip or static
    plugin build output, and the plugin does not directly import or execute the
    vulnerable code paths. `showdown` currently has no patched release and is
    pulled through `@wordpress/blocks`; `uuid@14` would require upstream
    WordPress/webpack dependency support because current `@wordpress/*`,
    `sockjs`, and `webpack-dev-server` dependency ranges still resolve to
    `uuid@8/9`. If dismissing later, use “vulnerable code is not actually used”
    with this rationale.
-   **Codecov threshold** — current coverage around the high-70s is acceptable
    for a first WordPress.org submission because it covers the
    security-sensitive parser/output/export paths, but the v1.1 sweep should
    keep raising coverage through targeted tests rather than chasing a vanity
    number.
-   Export-format groundwork is now in place, and copy citation, Copy
    bibliography, plus the read-only bibliography REST endpoints now provide
    practical next-layer interoperability. Follow-up todos track optional
    frontend Cite / Export affordances modeled on Google Scholar's visible
    citation/export workflow. Mendeley testing showed DOI-only auto-detection is
    likely for non-page-head metadata, so the frontend export path should make
    RIS/BibTeX/CSL-JSON available to readers without depending on hidden COinS
    discovery. Format-expansion priority remains: BibLaTeX first, PMID before
    NBIB, EndNote XML only if testing proves RIS/BibTeX insufficient, CIW
    demand-gated, and ENL out of scope.
-   Build remains healthy, with `citation-citeproc.js` now the only oversized
    deferred asset after the style-template reduction pass.
-   The citeproc investigation confirmed that `citation-citeproc.js` is
    effectively `citeproc_commonjs.js`; narrower load responsibility has now
    landed, so the remaining question is whether future work should target load
    strategy only or leave citeproc architecture alone.

## Pending Todos

-   8 pending todos in `.planning/todos/pending`
    -   WordPress.org launch monitoring and hotfix readiness
    -   Add frontend Cite and Export affordances — paused behind Phase 2 P0
        hardening
    -   Prioritize remaining BibLaTeX/import and biomedical interoperability —
        PMID input/proxy is shipped; cache hardening is Phase 2
    -   Investigate writable bibliography REST API and Abilities integration —
        design memo before implementation
    -   Prepare official language-pack expansion and refreshed i18n artifacts
    -   Add optional Block Accessibility Checks compatibility
    -   Full accessibility audit
    -   Add localized WordPress.org banner assets

## Roadmap Alignment

**Post-launch GSD Phase 2 (performance/stability remediation) is committed.**
The hidden formatter cliff, cache correctness, manual-entry double formatting,
async stale-result exposure, benchmark ambiguity, release-package dead weight,
PMID/DOI network fragility, conservative formatter caching, and the scoped
maintainability splits all landed in `3d5d3de` and `539b6b3`.

Current next-task sequence:

1. ~~**Cut release `1.3.0`**~~ — **Done.** WordPress.org shows 1.3.0.
2. **Optional deeper splits only if justified** — delete/list mutation,
   formatter/REST route helpers, and citation-type parser modules are follow-up
   candidates, not Phase 2 blockers.
3. **Resume feature backlog** — frontend Cite/Export affordances (Phase 4),
   writable REST/Abilities design, and translation expansion.
4. **Keep operational hygiene active** — CI/runtime matrix, release package,
   WordPress.org assets, and citeproc-php compatibility monitoring.

Run `/gsd:progress` to choose the next feature or operational track.

## Accumulated Context

### Roadmap Evolution

-   Post-launch GSD Phase 1 added: post-launch cleanup and documentation polish
    (completed)
-   Post-launch GSD Phase 2 added: performance and stability remediation, led
    by the hidden 51-entry formatter cliff (completed, committed in `3d5d3de`
    and `539b6b3`)
-   Post-launch GSD Phase 3 added: 1.3.0 release prep (completed — v1.3.0
    live on WordPress.org as of 2026-05-10)
