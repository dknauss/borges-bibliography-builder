---
created: 2026-06-17T22:04:43.084Z
title: Research benefits of a leaner root plugin file
area: architecture
files:
  - bibliography-builder.php
  - includes/pmid.php
---

## Problem

`bibliography-builder.php` (the main plugin file / bootstrap) is a monolith: it
holds the plugin header *and* substantial inline logic (REST routes, the PMID
proxy + cache helpers, the formatter endpoint, sanitization, etc.). A well-formed
main plugin file is normally thin — header + bootstrap + `require`s — delegating
logic to `includes/`, classes, or autoloaded modules.

A half-finished extract-to-`includes/` refactor is already in the tree: the PMID
functions were copied into `includes/pmid.php` but the inline originals were
never removed and the new file was never `require`d (confirmed dead duplicate,
2026-06-17 — see separate cleanup todo/PR). That near-miss is itself a reason to
evaluate the refactor properly rather than piecemeal.

Before committing to (or rejecting) a leaner-root-file refactor, we want a
grounded read on whether it's actually worth it.

## Solution

TBD — produce a short research memo covering:

1. **Performance** — does a leaner, conditionally-loaded structure reduce
   per-request parse/compile cost vs the always-parsed monolith? Consider opcode
   cache (OPcache) behavior, loading REST/PMID code only on `rest_api_init`,
   admin-only code only in admin, front-end-only paths, etc. Quantify if
   possible (e.g. file size parsed per request, micro-benchmarks).
2. **Security** — narrower attack surface, clearer capability/nonce boundaries
   per module, easier auditing, and reduced risk of accidental
   function-redeclare fatals (cf. the `includes/pmid.php` duplicate).
3. **Maintainability / risk** — testability, how to finish the in-progress
   extract safely, deprecation/back-compat considerations (function names are
   public-ish; moving them must not break callers or tests).

Deliverable: a recommendation (do it / not worth it) and, if recommended, a
phased plan. Prerequisite cleanup: remove the dead `includes/pmid.php`
(tracked separately).
