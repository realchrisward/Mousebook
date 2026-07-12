# Issue #24 — add_animals.php whitescreen generating FOUNDER animals

**Patch:** `issue24_add_animals_no_allele.patch` (1 file, `php/add_animals.php`; +23 / −8)
**Base:** current `phase3_revisions` HEAD `9b2e1aa` (issue22 followup); `git apply --check` clean; `php -l` clean.
**Deploy:** PHP-only, no schema/migration.

## Root cause (reproduced on MySQL 8.0.46 + PHP 8.3.6)
The whitescreen isn't really about FOUNDER — it's about **a line with no configured
allelegroups**. In the `generate_animals` handler, `$genelist` is only ever built
inside the allele-fetch loop, so when a line has no rows in `key_allelebyline`,
`$genelist` stays uninitialised and `$genecount = count($genelist)` fatals
(`count(): …null given`), blanking the whole generate step.

Why it looked FOUNDER-specific: founder/external animals start a **brand-new line**
that has no alleles configured yet — and such a line also has no mating cages, so
FOUNDER is the only available source. Adding from an existing mating cage always
targets an established line that already has alleles, so it never hit the crash.
Confirmed the differential: FOUNDER + new line "EXT1" (no alleles) → fatal at the
count(); FOUNDER + line "B6" (has alleles) → fine.

A second latent crash on the same no-allele path: with `genecount = 0`, the
`foreach (range(0, $genecount - 1, 1))` loops become `range(0, -1)` (which PHP
returns as `[0, -1]`), and in the confirm handler `$xgenelist` would be left null
for the later `foreach ($xgenelist as $gene)`.

## Fix
- Initialise `$genelist`/`$aglist` before the allele fetch (so `count()` is 0, not
  a fatal), and guard both allele-query fetches with `instanceof mysqli_result`.
- Guard the three `range(0, genecount-1)` loops (two in generate, one in confirm)
  so they iterate an empty array when there are no allelegroups.
- Initialise `$xgenelist`/`$genotypes` in the confirm handler so the no-allele
  genotype loop is a clean no-op.

## Validation (curl + cookie jars, full generate → confirm → insert)
- Reporter case — FOUNDER + new no-allele line: generate no longer fatals, confirm
  succeeds, **3 founder animals created**, 0 genotype rows (correct). ✓
- FOUNDER + established line (has alleles): 3 animals + 3 genotype rows. ✓
- Mating cage + established line: 3 litter animals. ✓
SQL-level check: table_cages has UNIQUE/PRIMARY on `cageid` only (no cageno
constraint), table_animals has no FKs; FOUNDER cage/animal INSERTs are valid.

## Noted, NOT fixed here (out of #24 scope)
1. **Silent multi_query in confirm.** The insert uses `multi_query(...)` then
   `while (mysqli_next_result())`, so a failure in the 2nd+ statement is swallowed
   and the page still reports "successful." Not triggered in the validated runs,
   but worth hardening (report per-statement errors).
2. **Other unguarded `mysqli_fetch_array($results)` fetches** in this file
   (max-autono lookups, the mating-cage/contents display queries). Same
   unguarded-result family as #23/#22; latent on missing tables / dead sessions.
   Candidates for a general add_animals hardening pass.
