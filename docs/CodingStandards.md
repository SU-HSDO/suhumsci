# Coding Standards

## Drupal Coding Standards
This repo folows [Drupal Coding Standards](https://www.drupal.org/docs/develop/standards/php/php-coding-standards).

- Where relevant `snake_case` should be used for PHP functions and variables, not `camelCase`.
- Code should be adequately commented.
- Code should avoid [code smells](https://blog.codinghorror.com/code-smells/).

## Code Security
- User input should be [properly sanitized when rendered](https://www.drupal.org/docs/8/security/drupal-8-sanitizing-output).
- Code should follow [Drupal Security Best Practices](https://www.drupal.org/docs/8/security).

## Automation and Tools
- The PHPCS scanner for Drupal is included and can be ran with `vendor/bin/phpcs`. There is also a PHPCS CI runner on every pull request.
- The PHPStan package is included and configured for Drupal code. There is a PHPStan CI runner on every pull request.

## PHPStan

### Current Configuration
- The default PHPStan configuration is in `phpstan.neon`.
- The current default analysis level is `5`.
- The current configuration includes `phpstan-baseline.neon`.
- The current analysis scope is limited to:
	- `docroot/modules/humsci`
	- `docroot/profiles/humsci`

### Basic Local Usage
- Run PHPStan with the repo default configuration:
	- `vendor/bin/phpstan`
- Run PHPStan against a specific file or directory:
	- `vendor/bin/phpstan analyse docroot/modules/humsci/hs_layouts/hs_layouts.module --no-progress`
	- `vendor/bin/phpstan analyse docroot/modules/humsci/hs_layouts --no-progress`

### Running Different Levels
- Run using the level configured in `phpstan.neon`:
	- `vendor/bin/phpstan`
- Override the level from the command line for testing higher or lower strictness:
	- `vendor/bin/phpstan --level=5`
	- `vendor/bin/phpstan --level=6`
- This is useful when testing a stricter level without changing the default CI level in `phpstan.neon`.

### Running Without the Baseline
- The repo currently includes `phpstan-baseline.neon` from `phpstan.neon`.
- To test the real unresolved issues at a given level without baseline suppression, temporarily comment out the `includes:` entry in `phpstan.neon`, or run with a temporary config that does not include the baseline.
- A common local workflow is:
	- Run with baseline included to confirm the current enforced state passes.
	- Run again at a target level without the baseline to see the remaining work.

### Generating a Baseline
- Generate a baseline at the current configured level:
	- `vendor/bin/phpstan --generate-baseline`
- Generate a baseline for a specific level:
	- `vendor/bin/phpstan --level=6 --generate-baseline`
- If no errors are found and you still want an empty baseline file generated:
	- `vendor/bin/phpstan --generate-baseline --allow-empty-baseline`
- After generating a baseline, ensure it is included from `phpstan.neon`:

```neon
includes:
	- phpstan-baseline.neon
```

### Current Baseline Usage
- `phpstan-baseline.neon` is currently included by default.
- The baseline should be treated as a temporary compatibility layer, not as a substitute for fixing issues.
- When fixing PHPStan issues, prefer removing entries from the baseline by resolving the underlying code issue rather than regenerating the entire baseline unnecessarily.

### CI Workflow
- PHPStan runs in GitHub Actions on pull requests via `.github/workflows/phpstan.yml`.
- CI currently uses the default repo configuration in `phpstan.neon`.

### Recommended Workflow
- Keep CI at the highest level that the scoped codebase passes consistently.
- Use higher levels locally to identify future cleanup work before raising the enforced CI level.
- Avoid changing code only to satisfy PHPStan if the change introduces functional risk. In those cases, preserve behavior first and satisfy PHPStan with narrower typing, guards, assertions, or refactors.

## Pull Requests
- Pull requests should be scoped to the problem they are solving. Multiple smaller Pull Requests are generally preferred.
