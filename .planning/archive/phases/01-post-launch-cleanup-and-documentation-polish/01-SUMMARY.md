# Phase 1 Summary: Post-launch cleanup and documentation polish

## Outcome

Completed the post-launch cleanup slice after the public WordPress.org `1.0.0` deployment.

## Completed

- Updated GitHub README with WordPress.org install link, concise compatibility wording, expanded read-only REST API documentation, and matching screenshot order, plus a linked WordPress.org SVN deploy checklist in the repo docs.
- Updated WordPress.org `readme.txt` with WordPress 7.0 compatibility wording, a concise WordPress Playground demo link, GitHub documentation wording, and matching screenshot captions.
- Reordered `.wordpress-org/screenshot-*.png` so the front-end output screenshot is first and editor-with-citations is second.
- Updated `SPEC.md` to remove stale Multisite/SQLite testing-gap language and document current runtime smoke coverage.
- Added `workflow_dispatch` to the WordPress.org deploy workflow for manual redeploys.
- Consolidated `getPrimaryIdentifierValue` into `src/lib/csl-utils.js` with shared tests.
- Upgraded the formatter cache from FIFO to simple LRU semantics and added regression coverage.
- Renamed/documented year helper semantics in export vs. sort paths.
- Rebuilt the release zip with updated `readme.txt` and plugin header metadata.
- Added `/docs/wporg-svn-checklist.md` as a short repo deploy checklist grounded in the WordPress.org SVN handbook pages.

## Verification

- `git diff --check` passed.
- `npm run lint:js` passed.
- `npm run test -- --runInBand` passed: 24 suites passed, 1 skipped; 384 tests passed, 1 skipped.
- `npm run build` passed.
- `npm run package:release` passed and recreated `output/release/borges-bibliography-builder.zip`.
- `composer test:php` passed with existing deprecation notices.
- `composer analyze:php` passed with no errors.
- `.github/workflows/wp-deploy.yml` parsed successfully and `gh workflow view wp-deploy.yml` resolved the workflow.

## Follow-up

- Deploy the updated WordPress.org assets/readme when ready, using the manual workflow trigger or the next release deploy.
- Keep launch monitoring/hotfix readiness open as an operational todo.
- Defer writable REST/Abilities API work to a design memo.
