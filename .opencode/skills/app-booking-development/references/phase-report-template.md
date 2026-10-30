# Phase report template

Copy this template to `docs/development/phases/NN-name.md` at the end of each phase.

```markdown
# Phase NN — Name

**Status: PASS | PARTIAL | BLOCKED | FAIL**
**Date:**
**Branch / commit:**

## Scope

What was built in this phase.

## Decisions

Any product or architectural decision made, with reason.

## Packages and versions

| Package | Version | Why |
|---|---|---|
| ... | ... | ... |

## Database changes

Migrations added, tables/columns/indexes and whether they were applied locally.

## Testing

| Command | Result | Tests / assertions |
|---|---|---|
| `php artisan test` | PASS/FAIL | ... |
| `npm run build` | PASS/FAIL | ... |
| `vendor/bin/pint --test` | PASS/FAIL | ... |

## Authorization and isolation checks

Specific scenarios verified and their evidence.

## No Bootstrap / jQuery check

Command and result.

## Manual verification

URLs or flows tested locally. Do not include passwords or secrets.

## Residual risks

Known limitations, unfinished items and why they do not block this phase.

## Next phase

The next phase file and any decision that must be resolved first.
```
