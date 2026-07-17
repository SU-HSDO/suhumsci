<?php

declare(strict_types=1);

namespace Drush\Commands;

use Drupal\SwsDrush\Drush\Commands\SwsCommandsTrait;
use Drush\Boot\DrupalBootLevels;
use Drush\Attributes as CLI;
use Symfony\Component\Console\Input\InputOption;

/**
 * Drush command to run a read-only SQL query across all multisites.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class HsSqlQueryDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Allowed remote environments (keys defined in drush/sites/*.site.yml).
   */
  const ALLOWED_ENVS = ['local', 'dev', 'stage', 'prod'];

  /**
   * SQL keywords that are unconditionally allowed as the first statement verb.
   */
  const ALLOWED_FIRST_KEYWORDS = ['SELECT', 'SHOW', 'DESCRIBE', 'DESC', 'EXPLAIN'];

  /**
   * SQL keywords that must never appear anywhere in the query.
   */
  const DENIED_KEYWORDS = [
    'INSERT',
    'UPDATE',
    'DELETE',
    'DROP',
    'TRUNCATE',
    'ALTER',
    'CREATE',
    'REPLACE',
    'RENAME',
    'GRANT',
    'REVOKE',
    'LOAD',
    'CALL',
    'LOCK',
    'UNLOCK',
    'HANDLER',
  ];

  /**
   * Run a read-only SQL query against every multisite on a given environment.
   *
   * @param string $query
   *   A read-only SQL query (SELECT, SHOW, DESCRIBE, EXPLAIN).
   * @param string $env
   *   The target environment: local, dev, stage, or prod.
   * @param array $options
   *   An associative array of options; see below.
   */
  #[CLI\Command(name: 'humsci:sql:query', aliases: ['hs:sqlq'])]
  #[CLI\Argument(name: 'query', description: 'A read-only SQL query to run (SELECT, SHOW, DESCRIBE, EXPLAIN).')]
  #[CLI\Argument(name: 'env', description: 'Target environment: local, dev, stage, or prod.')]
  #[CLI\Option(name: 'multisites', description: 'Comma-separated list of site machine names to query. Defaults to all sites in drush.yml.')]
  #[CLI\Option(name: 'format', description: 'Output format: table, raw, json, or sites-only.')]
  #[CLI\Option(name: 'hide-empty', description: 'Omit sites that returned no rows from the report.')]
  #[CLI\Usage(name: 'drush humsci:sql:query "SELECT COUNT(*) FROM users" prod', description: 'Count users on every site in prod.')]
  #[CLI\Usage(name: 'drush hs:sqlq "SHOW TABLES" stage --multisites=aaai,biology', description: 'List tables for two sites on stage.')]
  #[CLI\Usage(name: 'drush hs:sqlq "SELECT name FROM users LIMIT 5" dev --format=json', description: 'Return results as JSON.')]
  #[CLI\Usage(name: 'drush hs:sqlq "SELECT nid FROM node WHERE type=\'page\'" prod --format=sites-only --hide-empty', description: 'List only sites that have page nodes.')]
  public function sqlQuery(
    string $query,
    string $env,
    array $options = [
      'multisites' => InputOption::VALUE_OPTIONAL,
      'format' => 'table',
      'hide-empty' => FALSE,
    ],
  ): void {
    // Validate environment.
    if (!in_array($env, self::ALLOWED_ENVS, TRUE)) {
      throw new \InvalidArgumentException(sprintf(
        'Invalid environment "%s". Allowed values: %s.',
        $env,
        implode(', ', self::ALLOWED_ENVS)
      ));
    }

    // Validate format.
    $format = $options['format'] ?? 'table';
    if (!in_array($format, ['table', 'raw', 'json', 'sites-only'], TRUE)) {
      throw new \InvalidArgumentException(sprintf(
        'Invalid format "%s". Allowed values: table, raw, json, sites-only.',
        $format
      ));
    }

    // Validate the query is read-only.
    $this->assertReadOnlyQuery($query);

    // Resolve multisite list.
    $multisites = $this->resolveMultisites($options['multisites'] ?? NULL);

    if (empty($multisites)) {
      $this->io()->warning('No sites found. Aborting.');
      return;
    }

    // Confirm before running.
    $this->io()->text([
      sprintf('  Query : <comment>%s</comment>', $query),
      sprintf('  Env   : <comment>%s</comment>', $env),
      sprintf('  Sites : <comment>%d</comment>', count($multisites)),
    ]);

    if (!$this->io()->confirm('Run this query on all listed sites?', TRUE)) {
      return;
    }

    // Execute query on each site.
    $results = [];
    foreach ($multisites as $site) {
      $alias = sprintf('@%s.%s', $site, $env);
      $this->output()->writeln(sprintf('  Querying <comment>%s</comment>...', $alias));

      $result = $this->localMachineHelper()->execute(
        ['drush', $alias, 'sql:query', $query],
        NULL,
        $this->getDir(),
        FALSE
      );

      $results[$site] = [
        'site' => $site,
        'env' => $env,
        'success' => $result->isSuccessful(),
        'output' => trim($result->getOutput()),
        'error' => trim($result->getErrorOutput()),
      ];
    }

    // Report results.
    $this->output()->writeln('');
    $this->renderResults($results, $format, !empty($options['hide-empty']));
  }

  /**
   * Assert that a query is read-only.
   *
   * Checks that:
   *  1. There is exactly one SQL statement (no stacked queries via semicolons).
   *  2. The first keyword is in the allow-list.
   *  3. No denied keywords appear anywhere in the query.
   *
   * @param string $query
   *   The raw SQL string to validate.
   *
   * @throws \InvalidArgumentException
   *   When the query is not read-only or is otherwise unsafe.
   */
  protected function assertReadOnlyQuery(string $query): void {
    // Strip single-line comments (-- ...) and multi-line comments (/* ... */).
    $stripped = preg_replace('/--[^\n]*/', '', $query);
    $stripped = preg_replace('/\/\*.*?\*\//s', '', $stripped);
    $stripped = trim($stripped ?? '');

    if ($stripped === '') {
      throw new \InvalidArgumentException('The SQL query is empty.');
    }

    // Reject stacked queries by counting semicolons that appear outside of
    // single-quoted string literals. A semicolon inside 'foo;bar' is part of a
    // value and must not trigger the check.
    if ($this->countUnquotedSemicolons($stripped) > 0) {
      throw new \InvalidArgumentException(
        'Only a single SQL statement is allowed. Multiple statements separated by ";" are not permitted.'
      );
    }

    // Extract the first keyword.
    if (!preg_match('/^\s*(\w+)/i', $stripped, $matches)) {
      throw new \InvalidArgumentException('Could not parse the SQL query.');
    }
    $firstKeyword = strtoupper($matches[1]);

    if (!in_array($firstKeyword, self::ALLOWED_FIRST_KEYWORDS, TRUE)) {
      throw new \InvalidArgumentException(sprintf(
        'Query must begin with one of: %s. Got: "%s".',
        implode(', ', self::ALLOWED_FIRST_KEYWORDS),
        $firstKeyword
      ));
    }

    // Scan the full (stripped) query for denied keywords.
    foreach (self::DENIED_KEYWORDS as $keyword) {
      if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $stripped)) {
        throw new \InvalidArgumentException(sprintf(
          'Query contains a forbidden keyword: "%s". Only read-only queries are permitted.',
          $keyword
        ));
      }
    }

    // Extra check: INTO OUTFILE / INTO DUMPFILE patterns.
    if (preg_match('/\bINTO\s+(OUTFILE|DUMPFILE)\b/i', $stripped)) {
      throw new \InvalidArgumentException(
        'Query contains a forbidden pattern: INTO OUTFILE / INTO DUMPFILE.'
      );
    }
  }

  /**
   * Count semicolons that fall outside single-quoted string literals.
   *
   * Walks the string character by character, tracking whether the current
   * position is inside a single-quoted string (respecting escaped quotes via
   * '' and \').  Only semicolons found outside quotes are counted.
   *
   * @param string $sql
   *   The SQL string to inspect.
   *
   * @return int
   *   Number of unquoted semicolons found.
   */
  protected function countUnquotedSemicolons(string $sql): int {
    $count = 0;
    $inString = FALSE;
    $len = strlen($sql);

    for ($i = 0; $i < $len; $i++) {
      $char = $sql[$i];

      if ($inString) {
        // An escaped quote written as \' — skip next char.
        if ($char === '\\' && $i + 1 < $len && $sql[$i + 1] === "'") {
          $i++;
          continue;
        }
        // A doubled single-quote '' is an escaped literal — skip both.
        if ($char === "'" && $i + 1 < $len && $sql[$i + 1] === "'") {
          $i++;
          continue;
        }
        // Closing single-quote ends the string.
        if ($char === "'") {
          $inString = FALSE;
        }
      }
      else {
        if ($char === "'") {
          $inString = TRUE;
        }
        elseif ($char === ';') {
          $count++;
        }
      }
    }

    return $count;
  }

  /**
   * Resolve the multisite list from the option or drush.yml config.
   *
   * @param string|null $multisitesOption
   *   Comma-separated site names provided via --multisites, or NULL.
   *
   * @return string[]
   *   Resolved list of site machine names.
   */
  protected function resolveMultisites(?string $multisitesOption): array {
    if ($multisitesOption !== NULL && $multisitesOption !== '') {
      return array_filter(array_map('trim', explode(',', $multisitesOption)));
    }
    return $this->getConfig()->get('command.sws.options.multisites') ?? ['default'];
  }

  /**
   * Render query results in the requested format.
   *
   * @param array $results
   *   Results array, each entry: [site, env, success, output, error].
   * @param string $format
   *   One of 'table', 'raw', 'json', or 'sites-only'.
   * @param bool $hideEmpty
   *   When TRUE, sites that succeeded but returned no output are omitted.
   */
  protected function renderResults(array $results, string $format, bool $hideEmpty = FALSE): void {
    if ($hideEmpty) {
      $results = array_filter(
        $results,
        fn(array $r) => !$r['success'] || $r['output'] !== ''
      );
    }

    if (empty($results)) {
      $this->io()->note('No results to display' . ($hideEmpty ? ' (all sites returned empty output)' : '') . '.');
      return;
    }

    switch ($format) {
      case 'sites-only':
        foreach ($results as $data) {
          $this->output()->writeln($data['site']);
        }
        return;

      case 'json':
        $this->output()->writeln(json_encode(array_values($results), JSON_PRETTY_PRINT));
        return;

      case 'raw':
        foreach ($results as $data) {
          $this->output()->writeln(sprintf(
            '=== %s @ %s ===',
            $data['site'],
            $data['env']
          ));
          if ($data['success']) {
            $this->output()->writeln($data['output'] !== '' ? $data['output'] : '(no output)');
          }
          else {
            $this->output()->writeln('<error>' . ($data['error'] ?: '(no error output)') . '</error>');
          }
          $this->output()->writeln('');
        }
        $this->renderSummary($results);
        return;

      case 'table':
      default:
        // Overview table: Site | Env | Status | Preview.
        $rows = [];
        foreach ($results as $data) {
          $status = $data['success']
            ? '<info>OK</info>'
            : '<error>FAIL</error>';

          $preview = $data['success']
            ? $this->truncate($data['output'], 60)
            : $this->truncate($data['error'], 60);

          $rows[] = [$data['site'], $data['env'], $status, $preview];
        }
        $this->io()->table(['Site', 'Env', 'Status', 'Result (truncated)'], $rows);

        // Detailed per-site output.
        $this->output()->writeln('<comment>Detailed output.</comment>');
        foreach ($results as $data) {
          $this->output()->writeln(sprintf(
            '<comment>%s @ %s</comment>',
            $data['site'],
            $data['env']
          ));
          if ($data['success']) {
            $this->output()->writeln($data['output'] !== '' ? $data['output'] : '(no output)');
          }
          else {
            $this->output()->writeln('<error>' . ($data['error'] ?: '(no error output)') . '</error>');
          }
          $this->output()->writeln('');
        }

        $this->renderSummary($results);
    }
  }

  /**
   * Print a summary line after the report.
   *
   * @param array $results
   *   The results array.
   */
  protected function renderSummary(array $results): void {
    $total = count($results);
    $succeeded = count(array_filter($results, fn(array $r) => $r['success']));
    $failed = $total - $succeeded;

    $this->output()->writeln(sprintf(
      'Ran query on <comment>%d</comment> site(s): <info>%d succeeded</info>, %s.',
      $total,
      $succeeded,
      $failed > 0
        ? sprintf('<error>%d failed</error>', $failed)
        : '<info>0 failed</info>'
    ));
  }

  /**
   * Truncate a string to a maximum length, appending ellipsis if needed.
   *
   * @param string $text
   *   The input text.
   * @param int $maxLength
   *   Maximum number of characters.
   *
   * @return string
   *   The (possibly truncated) text.
   */
  protected function truncate(string $text, int $maxLength): string {
    // Collapse newlines for table cell display.
    $text = preg_replace('/\s+/', ' ', $text) ?? $text;
    $text = trim($text);
    if (mb_strlen($text) <= $maxLength) {
      return $text;
    }
    return mb_substr($text, 0, $maxLength - 3) . '...';
  }

}
