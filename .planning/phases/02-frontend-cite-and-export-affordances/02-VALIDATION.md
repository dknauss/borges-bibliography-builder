---
phase: 2
slug: frontend-cite-and-export-affordances
status: draft
nyquist_compliant: true
wave_0_complete: true
created: 2026-06-04
---

# Phase 2 — Validation Strategy

> Per-phase validation contract for feedback sampling during execution.

---

## Test Infrastructure

| Property | Value |
|----------|-------|
| **Framework** | Jest 29.x |
| **Config file** | `jest.config.js` |
| **Quick run command** | `npm test -- --testPathPattern="export-single|view|save"` |
| **Full suite command** | `npm test` |
| **Estimated runtime** | ~30 seconds |

---

## Sampling Rate

- **After every task commit:** Run `npm test -- --testPathPattern="export-single|view|save"`
- **After every plan wave:** Run `npm test`
- **Before `/gsd:verify-work`:** Full suite must be green
- **Max feedback latency:** 30 seconds

---

## Per-Task Verification Map

| Task ID | Plan | Wave | Requirement | Test Type | Automated Command | File Exists | Status |
|---------|------|------|-------------|-----------|-------------------|-------------|--------|
| 02-01-T1 | 02-01 | 1 | REQ-FE-02: per-entry export helpers | unit | `npm test -- --testPathPattern="export-single" --passWithNoTests=false` | ❌ W0 (TDD RED step creates it) | ⬜ pending |
| 02-02-T1 | 02-02 | 1 | REQ-FE-03: data-csl embedding in save markup | unit | `npm test -- --testPathPattern="src/save.test" --passWithNoTests=false` | ✅ | ⬜ pending |
| 02-02-T2 | 02-02 | 1 | REQ-FE-01/REQ-FE-04: block.json viewScript wiring | syntax | `node -e "const b=require('./block.json'); if(!b.viewScript) process.exit(1)" && node -e "require('./webpack.config.js')" && echo ok` | ✅ | ⬜ pending |
| 02-03-T1 | 02-03 | 2 | REQ-FE-01: view.js progressive enhancement | unit | `npm test -- --testPathPattern="src/view.test" --passWithNoTests=false` | ❌ W0 (TDD RED step creates it) | ⬜ pending |
| 02-03-T2 | 02-03 | 2 | REQ-FE-04/REQ-FE-05: frontend CSS no-JS baseline | build | `npm run build && echo "Build clean"` | ✅ | ⬜ pending |

*Status: ⬜ pending · ✅ green · ❌ red · ⚠️ flaky*

---

## Wave 0 Requirements

Wave 0 is satisfied by the TDD RED steps in Plans 02-01 and 02-03. Each TDD plan's first task writes and commits the failing test file before implementation begins, fulfilling the Nyquist requirement that `<automated>` verify commands reference existing test files.

- [x] `src/lib/export-single.test.js` — created in Plan 02-01 Task 1 RED commit before implementation
- [x] `src/view.test.js` — created in Plan 02-03 Task 1 RED commit before implementation

*Existing `src/save.test.js` covers Plan 02-02 Task 1 changes — no Wave 0 stub needed.*

---

## Manual-Only Verifications

| Behavior | Requirement | Why Manual | Test Instructions |
|----------|-------------|------------|-------------------|
| No-JS bibliography readable | REQ-FE-04: no-JS bibliography remains fully readable | Requires browser with JS disabled | Load post in browser, disable JS, verify bibliography text is present and cite controls are absent |
| Plugin-deactivation resilience | REQ-FE-05: preserves plugin-deactivation resilience | Requires live WP environment | Deactivate plugin, reload page, verify bibliography renders and no JS errors appear |
| Mendeley/Zotero import | REQ-FE-06: Mendeley/Zotero manual-import acceptance target | Requires manual tool testing | Download BibTeX from per-entry control; import into Zotero/Mendeley; verify metadata round-trips |
| iOS download behavior | REQ-FE-02: BibTeX, RIS, and CSL-JSON download/copy per entry | iOS does not trigger file downloads via Blob URLs | Test copy fallback on iOS Safari |

---

## Validation Sign-Off

- [x] All tasks have `<automated>` verify or Wave 0 dependencies
- [x] Sampling continuity: no 3 consecutive tasks without automated verify
- [x] Wave 0 covers all MISSING references (TDD RED steps in Plans 02-01 and 02-03)
- [x] No watch-mode flags
- [x] Feedback latency < 30s
- [x] `nyquist_compliant: true` set in frontmatter

**Approval:** pending
