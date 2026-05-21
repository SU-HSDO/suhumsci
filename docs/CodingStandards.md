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
- The PHPStan package is included and configured for Drupal code. There is currently no CI runner.
