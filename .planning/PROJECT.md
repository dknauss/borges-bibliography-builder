# Project

## Name

Borges Bibliography Builder

## Summary

WordPress block plugin that converts DOI, PubMed/PMID, BibTeX, and progressively
enhanced scholarly citation inputs into a semantically rich, static bibliography
block backed by CSL-JSON.

## Source of truth

-   `SPEC.md`

## Current focus

-   Current public release baseline is `v1.3.3`; `main` is 11 commits ahead
    with post-release E2E, docs, planning, sorting-test, hygiene, and
    dev-dependency lockfile work
-   Next feature track is the post-Phase 2 backlog, starting with frontend
    Cite/Export affordances
-   The hidden 51-entry formatter cliff is now replaced by an explicit, tested
    50-total-citation policy for 1.x
-   Maintain CSL-JSON as the canonical citation model
-   Preserve static saved output / frontend-zero-JS bibliography rendering
