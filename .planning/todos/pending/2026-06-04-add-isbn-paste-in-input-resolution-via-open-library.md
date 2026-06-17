---
created: 2026-06-04T14:45:24.669Z
title: Add ISBN paste-in input resolution via Open Library
area: interoperability
files:
  - src/lib/parser.js
  - src/lib/input-support.js
---

## Problem

Users cannot paste an ISBN (e.g. `978-0-226-81990-9` or `ISBN: 9780226819909`) and get metadata back. ISBNs are already handled as a stored CSL-JSON field (COinS `rft.isbn`, JSON-LD `isbn`, BibTeX export) but parser.js has no ISBN detection or resolution backend.

## Solution

Follow the DOI/PMID pattern in parser.js:
1. Add `ISBN_REGEX` to detectFormat() — match bare 10/13-digit ISBNs and `ISBN:` prefix variants
2. Add `isbn` backend to `PARSER_BACKENDS` using `@citation-js/plugin-isbn` (wraps Open Library API) as primary; fallback to direct Open Library fetch if the plugin is unavailable
3. Add `normalizeIsbnInput` (strip `ISBN:` prefix, hyphens, spaces)
4. Update `SUPPORTED_INPUT_MESSAGE` in input-support.js to include ISBN
5. Update `looksLikeStandaloneCitationLine` and `allLinesAreIdentifiers` to cover `isbn` format
6. Update `formatBackendParseError` with isbn case

Open Library is preferred over Crossref (fuzzy multi-result) and Google Books (requires API key). Check whether `@citation-js/plugin-isbn` is already a transitive dep before adding it explicitly.

Phase 3 item — do not bundle into Phase 2 (frontend Cite/Export).
