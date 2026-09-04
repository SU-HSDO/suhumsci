# AGENTS.md

Guidance for AI coding agents working in this repository. This file contains only AI-specific instructions and pointers; Read the specific docs below when the task calls for them. `CLAUDE.md` points here.

## Project Context

- HSDP (Humanities and Sciences Drupal Platform): a Drupal 11 multisite platform running 130+ sites on Acquia Cloud.
- Default development branch: the current `<major>.x` branch (e.g. `12.x`). Production branch: `main`. See [docs/BranchingStrategy.md](docs/BranchingStrategy.md).
- Drush toolchain is SWSDC (SWS Drush Commands), prefixed `drush sws:`. Custom commands are also namespaced `humsci:` and `drupal:`. Do not add BLT references.
- This is a public repository. Never commit credentials or internal URLs.

## Where Code Lives
See [Repository Layout](README.md#repository-layout) in README.md for the full map, how changes are made to each location, and module ownership by domain.

## Read Before Task

Read the relevant documentation before starting work; do not rely on assumptions:

- Any code change: [Repository Layout](README.md#repository-layout) in README.md, to find where the code lives, how changes are made there, and which module owns the topic.
- Config work or update/deploy hooks: [docs/Config.md](docs/Config.md). The config stack is not stock Drupal.
- Patching Drupal core, contrib, or `stanford_*` modules: [docs/Patching.md](docs/Patching.md).
- Frontend work (JavaScript, SCSS, Twig): [docs/FrontendPatterns.md](docs/FrontendPatterns.md).
- Writing or running tests: [docs/Testing.md](docs/Testing.md).
- Any PHP change: check against the Review Patterns in [docs/CodingStandards.md](docs/CodingStandards.md) before opening a PR.
- Evaluating or adopting a contributed module: [docs/ModuleEvaluation.md](docs/ModuleEvaluation.md).
- Creating or updating documentation (`README.md` or anything in `docs/`): [docs/DocumentationStandards.md](docs/DocumentationStandards.md).
- Operational tasks (site launch, provisioning, copying, decommissioning, code deployment, core upgrades, dependency update review, module uninstall, Codespaces): step-by-step guides in `docs/`, linked from [README.md](README.md#documentation).

## Core Rules

- Extend the existing module that owns the topic rather than creating a new one. Do not create new modules or themes unless told to.
- Prefer hooks (`hook_form_alter`, `hook_preprocess_HOOK`) or services over custom systems. Do not invent services or hooks.
- Keep changes minimal and localized. Match the style of the surrounding code exactly. Do not reformat entire files or restructure unrelated code. Preserve existing comments.
- Code comments should describe why the code exists, not what it does.
- Never use `drush config-import --partial`; it is deprecated and destructive here.
- Follow the [PR template](./.github/pull_request_template.md) when opening a Pull Request.
- Ask for clarification instead of guessing. Look for an existing example in the target module and follow it.

## Before You Finish (Verify)

- Hook names match the module's machine name. Services are registered in `services.yml`.
- `vendor/bin/phpcs` and `vendor/bin/phpstan` pass on changed files.
- Config changes import cleanly with `drush ci -y && drush ci -y`; any deletion has a matching update hook.
- A `drush cr` (cache rebuild) would succeed.
- Any new `docs/` file is linked from `README.md`.

## Do Not

- Do not edit `docs/architecture/decisions/` (ADRs are immutable human-written records).
- Do not commit `drush/local.drush.yml` or any credentials.
- Do not edit anything in `docroot/modules/custom/` directly; it is Composer-installed and gitignored (see [Repository Layout](README.md#repository-layout)).
