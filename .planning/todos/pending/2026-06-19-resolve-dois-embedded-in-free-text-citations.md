---
created: 2026-06-19T00:00:00Z
title: Resolve DOIs embedded in pasted free-text citations
area: parsing
files:
  - src/lib/parser.js
  - src/lib/free-text-parser.js
---

## Problem

DOI detection in `src/lib/parser.js` uses `DOI_ONLY_REGEX`, anchored `^…$`, so
the **entire pasted chunk** must be a DOI. A lone DOI (`10.1038/171737a0`) or a
lone `doi.org` URL works, but a DOI embedded **inside** a formatted citation —
e.g. `Watson, J. D., Crick, F. H. C. Molecular Structure of Nucleic Acids…
Nature. https://doi.org/10.1038/171737a0.` — is classified as `freetext`. The
free-text parser then fails to parse that author-initials / journal / DOI-URL
shape, so the entry doesn't import.

Separately: bare PMID numbers are not detected (`PMID_REGEX` requires the
`PMID:` keyword — `PMID:20051345` works, `20051345` does not). That is
intentional (a bare number is ambiguous), but a relaxed `PMID 20051345`
(keyword + space, no colon) could reasonably be accepted.

Found during Phase 04 browser verification (2026-06-19).

## Solution

TBD. Primary: when a free-text line contains a recognizable DOI (a `doi.org/10.x`
URL or a bare `10.x/...` token), **extract it and resolve via the DOI / CrossRef
path** rather than the fragile free-text parser — yields clean CSL metadata.
Validate the extracted DOI before any outbound request (fixed host, no
arbitrary-URL fetch) per the resolver-layer model in SPEC.md / the roadmap's
identifier backlog.

Secondary (optional): relax PMID detection to accept `PMID 12345` (keyword +
space). Tests: DOI embedded in a citation string, lone-DOI-URL regression,
`PMID 12345` with a space.
