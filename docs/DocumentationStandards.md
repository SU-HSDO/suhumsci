# Documentation Standards

This document defines the standards for creating and updating documentation in this repository. It applies to `README.md` and everything in `docs/` (except `docs/Codeception.md` unless specifically requested). It is a living document — update it whenever conventions change.

## Project Context

- The primary audience for operational documentation is experienced Drupal developers on the H&S team
- The primary operational stakeholders are the **H&S web team** — use this term when documentation refers to site owners, editors, or operational contacts (e.g., "Notify the H&S web team and/or site owner")
- `DocumentationStandards.md` also serves as AI context for Claude Code in this repository — keep it accurate and up to date

## Language and Tone

- Use clear, direct language targeted at experienced Drupal developers
- Address the reader as "you" — avoid "we" in instructional content
- Use active voice
- Use active voice especially in warnings — "Take a database backup before proceeding" not "Database backups are required"
- Avoid filler words ("simply", "just", "easily", "very good", etc.)
- Do not use em dashes (—). Rewrite the sentence to avoid them.
- Avoid meta-notes written to the author (e.g., "update this before publishing") — those belong in PR descriptions or commit messages, not in committed docs

## Document Types

Documentation in this repo falls into two categories. Structure each accordingly:

- **Conceptual / reference docs** (`BranchingStrategy.md`, `CodingStandards.md`, `Config.md`, `DevelopmentRequirements.md`) — meant to be read once for understanding. Prose is appropriate. Organize by concept.
- **Process guides / operational checklists** (`CodeDeploy.md`, `Launch.md`, `NewSite.md`, `DeleteSite.md`, `DrupalCoreUpgrades.md`, `Patching.md`) — developers return to these during active work. Headers must be scannable. Steps must be discrete. Minimize prose between steps.

## Formatting Rules

- **No emoji** in any documentation file
- **No screenshots or image embeds** — they go stale and are a maintenance burden
- Heading hierarchy must not skip levels: H1 → H2 → H3 → H4; never jump from H2 to H4
- Every document must begin with an H1 (`#`) title
- **No step numbers in headers** — "### Preparation" not "### 1. Preparation". Step numbers in headers must be maintained manually and break when steps are added or removed
- Use fenced code blocks with a language hint (` ```bash `, ` ```php `, ` ```yaml `, ` ```json `, etc.)
- **No `$` prompt prefix in bash code blocks** — it prevents copying commands directly
- Use `1.` for every item in a numbered list — Markdown auto-increments, so manual tracking is unnecessary and error-prone
- No trailing commas in enumerations: `(foo, bar, etc.)` not `(foo, bar, etc.,)`

## Callout Style

Use blockquote callouts for notes, warnings, and tips. Choose the appropriate type:

- `> **Note:**` — background context or supplementary information
- `> **Important:**` — something easy to miss that affects correctness
- `> **Warning:**` — risk of data loss, security issue, or hard-to-reverse action
- `> **Tip:**` — optional guidance that improves efficiency

Do not use emoji-prefixed bullets (`:warning:`, `:bulb:`, etc.) as callouts.

## Header Verb Style

- **Structural sections** use noun phrases: "Overview", "Requirements", "Troubleshooting", "Best Practices"
- **Procedural steps** use imperative verbs: "Create a Release Branch", "Merge the Pull Request", "Deploy to Production"

## Placeholder Syntax

Use `<PLACEHOLDER>` (angle brackets, all caps) for all substitution values — in commands, file paths, naming patterns, and format descriptions.

Always follow a placeholder example with a realistic example to show what a real value looks like:

```bash
git checkout -b <RELEASE_BRANCH>

# Example:
git checkout -b 12.1.1-release
```

This applies consistently: `<SITENAME>`, `<ISSUE_NUMBER>`, `<YYYYMMDD>`, `<MAJOR>`, etc.

## What Not to Include

- **No Slack channel references** of any kind — not client channels, not vendor channels, not internal team channels
- **No dated inline notes** — notes like `> **Note (2024-04-15):**` go stale. If context is needed, strip the date and keep the content; if it's truly historical, it belongs in a commit message or ADR
- **No ticket, PR, or issue number references** — they rot and become meaningless. Reference the concept or the relevant doc instead
- **No internal Confluence, Atlassian, or Jira URLs** — this is a public repository
- **No vendor-specific internal identifiers** (internal channel names, internal project keys, etc.)
- **No credentials, tokens, API keys, or secrets** — always use `<PLACEHOLDER>` syntax in examples

## Security and Public Repo

This repository is public. Before committing documentation:
- Confirm any external URLs are public and appropriate
- Any time a doc instructs creating a credential-bearing file, include a note to verify it is listed in `.gitignore`
- Acquia application UUIDs already present in the codebase may remain — do not add new ones unnecessarily
- Internal server paths on Acquia infrastructure are acceptable where operationally necessary

## Cross-References and Links

- Link to other docs using relative paths: `[Title](filename.md)` or `[Title](../path/to/file.md)`
- Always use descriptive link text — never bare URLs in prose
- Do not link to the `develop` branch (retired); use `main` or the current `<major>.x` branch
- When linking to a specific file in the GitHub repo, use the `main` branch unless the content is version-specific
- Review links for accuracy when updating documentation — external links go stale between major upgrades
- Every file added under `docs/` must be linked from `README.md` in the appropriate Documentation section before the PR is merged

## Branch and Version References

- Current development branch: the current `<major>.x` branch (e.g., `12.x`)
- Production branch: `main`
- The `develop` branch is retired — do not reference it as active
- PR base branch for feature and maintenance work is the current `<major>.x` branch
- Use `<major>.x` as a placeholder in examples; pair with a realistic example (e.g., `12.x`) where helpful

## Acquia Environment Naming

Acquia's internal name for the staging environment is "test". In all documentation and communication, use "staging" — only use "test" in actual ACLI commands and drush aliases where it is technically required.

| Context | Use |
|---|---|
| Documentation prose | "staging environment" |
| ACLI commands | `humscigryphon.test` |
| Drush aliases | `@<SITENAME>.test` |
| URLs | `<SITENAME>-stage.stanford.edu` |

## Command References

- SWSDC (SWS Drush Commands) is the current toolchain — do not add new BLT references
- Use `drush drupal:sync` for site syncing (alias for `drush sws:site:sync`; standardize to `drush drupal:sync`)
- PHPStan binary: `vendor/bin/phpstan` (no `.phar` extension)
- PHPCS binary: `vendor/bin/phpcs`
- PHPCBF binary: `vendor/bin/phpcbf`

## Architecture Decision Records (ADRs)

ADRs in `docs/architecture/decisions/` are **immutable historical records**:
- Do not edit the body of an existing ADR
- ADRs should be written by humans, not generated by AI tools
- If an ADR has been superseded, add a `> **Note:**` callout immediately after the Status section referencing the superseding ADR and linking to it — do not alter the original content
- Number new ADRs sequentially following the existing pattern (`NNNN-short-title.md`)
- Include the standard sections: Status, Context, Decision, Consequences

## Checklist When Updating Documentation

1. Verify commands against the current codebase — check actual drush commands, file paths, and branch names before documenting them
1. Keep process guides consistent with `docs/BranchingStrategy.md` for any branch references
1. If a new development tool is required by the project, add it to `docs/DevelopmentRequirements.md`
1. If a step is unclear or potentially missing, note it explicitly with a `> **Note:**` rather than guessing
1. Review links for accuracy — external links go stale
1. If a new file was added directly under `docs/` (not in a subdirectory), confirm it is linked from `README.md` in the appropriate Documentation section
1. Update this file whenever new documentation conventions are established
