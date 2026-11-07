# Architecture and product decisions

| ADR                                   | Title                                  | Status                              |
| ------------------------------------- | -------------------------------------- | ----------------------------------- |
| [0001](0001-mvp.md)                   | MVP product decisions                  | Accepted (amended 2026-11-06)       |
| [0002](0002-data-platform.md)         | MySQL 8.4 primary, SQLite legacy       | Accepted                            |
| [0003](0003-production-operations.md) | Production operations and launch gates | Proposed — no deployment authorized |
| [0004](0004-open-decisions.md)        | Open product and operational decisions | Open                                |

## When to add an ADR

Record a decision when it changes architecture, data, security, billing,
deployment or a product constraint that future work must respect. Prefer a small
ADR over leaving the decision implicit in code or chat.

Do not rewrite accepted ADRs to hide history: amend them with a dated note or
create a new ADR that supersedes them.
