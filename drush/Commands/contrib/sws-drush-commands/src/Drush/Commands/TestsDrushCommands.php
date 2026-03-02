<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Drush\Boot\DrupalBootLevels;
use Drush\Exceptions\CommandFailedException;
use Symfony\Component\Console\Input\InputOption;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Symfony\Component\Filesystem\Path;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class TestsDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  /**
   * Test a PHPUnit test report file for expected coverage.
   */
  #[CLI\Command(name: 'sws:tests:phpunit-coverage-check')]
  #[CLI\Argument(name: 'xml_directory', description: 'Path to xml coverage directory when using --coverage-xml=[coverage/directory].')]
  #[CLI\Option(name: 'min-coverage', description: 'Minimum coverage percent.')]
  #[CLI\Option(name: 'clover-file', description: 'Path to clover.xml file using --coverage-clover=[clover/file.xml]')]
  public function testPhpUnitCoverage(string $xml_directory, array $options = [
    'min-coverage' => 90,
    'clover-file' => InputOption::VALUE_OPTIONAL,
  ]
  ) {
    $report = "$xml_directory/index.xml";
    if (!file_exists($report)) {
      throw new CommandFailedException('Coverage report not found at ' . $report);
    }

    libxml_use_internal_errors(TRUE);
    $dom = new \DOMDocument();
    $dom->loadHtml(file_get_contents($report));
    $xpath = new \DOMXPath($dom);

    $coverage_percent = $xpath->query("//directory[@name='/']/totals/lines/@percent");
    $percent = (float) $coverage_percent->item(0)->nodeValue;

    $min = $options['min-coverage'];
    if ($min > $percent) {
      throw new CommandFailedException("Test coverage is only at $percent%. $min% is required.");
    }
    $this->yell(sprintf('Coverage at %s%%. %s%% required.', $percent, $min));
  }

  /**
   * Scaffold and run phpunit tests.
   */
  #[CLI\Command(name: 'sws:source:tests:phpunit', aliases: ['phpunit'])]
  #[CLI\Option(name: 'db-user', description: 'Database user name')]
  #[CLI\Option(name: 'db-pass', description: 'Database password')]
  #[CLI\Option(name: 'db-host', description: 'Database host')]
  #[CLI\Option(name: 'db-name', description: 'Database name')]
  #[CLI\Option(name: 'with-coverage', description: 'Run test with coverage report.')]
  public function phpUnit(array $options = [
    'db-user' => 'root',
    'db-pass' => 'password',
    'db-host' => 'localhost',
    'db-name' => 'drupal',
    'with-coverage' => FALSE,
  ]
  ) {
    $this->phpUnitConfig($options);

    $testCommand = '../vendor/bin/phpunit --configuration=core --filter="/(Unit|Kernel)/" --testsuite=stanford';
    if ($options['with-coverage']) {
      $testCommand .= ' --log-junit=../artifacts/phpunit/results.xml --coverage-html=../artifacts/phpunit/coverage/html --coverage-xml=../artifacts/phpunit/coverage/xml --coverage-clover=../artifacts/phpunit/coverage/clover.xml';
    }
    $this->say($testCommand);
    $result = $this->localMachineHelper()
      ->executeFromCmd($testCommand, NULL, $this->getDir() . '/docroot/');
    if (!$result->isSuccessful()) {
      throw new CommandFailedException('PHPUnit tests failed', $result->getExitCode());
    }
  }

  /**
   * Scaffold phpunit tests.
   */
  #[CLI\Command(name: 'sws:source:tests:phpunit:config', aliases: ['phpunit:config'])]
  #[CLI\Option(name: 'db-user', description: 'Database user name')]
  #[CLI\Option(name: 'db-pass', description: 'Database password')]
  #[CLI\Option(name: 'db-host', description: 'Database host')]
  #[CLI\Option(name: 'db-name', description: 'Database name')]
  public function phpUnitConfig(array $options = [
    'db-user' => 'root',
    'db-pass' => 'password',
    'db-host' => 'localhost',
    'db-name' => 'drupal',
    'with-coverage' => FALSE,
  ]
  ) {
    $dbUser = $this->input()->getOption('db-user');
    $dbPass = $this->input()->getOption('db-pass');
    $dbHost = $this->input()->getOption('db-host');
    $dbName = $this->input()->getOption('db-name');

    $fileSystem = $this->localMachineHelper()->getFilesystem();
    if ($fileSystem->exists($this->getDir() . '/tests/phpunit/example.phpunit.xml')) {
      $fileSystem->copy($this->getDir() . '/tests/phpunit/example.phpunit.xml', $this->getDir() . '/docroot/core/phpunit.xml');
    }
    else {
      $fileSystem->copy(__DIR__ . '/../../../settings/example.phpunit.xml', $this->getDir() . '/docroot/core/phpunit.xml');
    }
    $contents = file_get_contents($this->getDir() . '/docroot/core/phpunit.xml');
    $contents = str_replace('${drupal.db.username}', $dbUser, $contents);
    $contents = str_replace('${drupal.db.password}', $dbPass, $contents);
    $contents = str_replace('${drupal.db.host}', $dbHost, $contents);
    $contents = str_replace('${drupal.db.database}', $dbName, $contents);

    file_put_contents($this->getDir() . '/docroot/core/phpunit.xml', $contents);
  }

  /**
   * Prep and run codeception tests.
   */
  #[CLI\Command(name: 'sws:codeception', aliases: ['codeception'])]
  #[CLI\Option(name: 'site-domain', description: 'Local site domain for testing.')]
  #[CLI\Option(name: 'protocol', description: 'Domain protocol: http or https.')]
  #[CLI\Option(name: 'test-dir', description: 'A path to codeception tests if not in the `tests` directory.')]
  #[CLI\Option(name: 'suite', description: 'Codeception suite to run.')]
  #[CLI\Option(name: 'group', description: 'Codeception group to run.')]
  #[CLI\Option(name: 'shard', description: 'Execute subset of tests to run tests on different machine. To split tests on 3 machines to run with shards: 1/3, 2/3, 3/3.')]
  #[CLI\Option(name: 'filter', description: 'Filter tests by name.')]
  #[CLI\Option(name: 'skip-group', description: 'Skip selected groups.')]
  #[CLI\Option(name: 'fail-fast', description: 'Stop after nth failure.')]
  public function codeception(array $options = [
    'site-domain' => 'localhost',
    'protocol' => 'http',
    'test-dir' => NULL,
    'suite' => 'acceptance',
    'group' => NULL,
    'shard' => NULL,
    'filter' => NULL,
    'skip-group' => NULL,
    'fail-fast' => NULL,
  ]
  ) {
    $fileSystem = $this->localMachineHelper()->getFilesystem();
    $symLinkDir = NULL;
    if ($options['test-dir']) {
      $testDir = Path::join($this->getDir(), $options['test-dir'], $options['suite']);
      if (!$fileSystem->exists($testDir)) {
        throw new CommandFailedException('Test directory does not exist: ' . $testDir);
      }
      $symLinkDir = Path::join($this->getDir(), 'tests', 'codeception', $options['suite'], 'temp');
      $fileSystem->symlink($testDir, $symLinkDir);
    }

    $domain = $this->input()->getOption('site-domain');
    $protocol = $this->input()->getOption('protocol');

    if ($fileSystem->exists($this->getDir() . '/tests/codeception.dist.yml')) {
      $fileSystem->copy($this->getDir() . '/tests/codeception.dist.yml', $this->getDir() . '/tests/codeception.yml');
    }
    $contents = file_get_contents($this->getDir() . '/tests/codeception.yml');
    $contents = str_replace('${docroot}', $this->getDir() . '/docroot', $contents);
    $contents = str_replace('${project.local.hostname}', $domain, $contents);
    $contents = str_replace('${repo.root}', $this->getDir(), $contents);
    $contents = str_replace('${project.local.protocol}', $protocol, $contents);
    file_put_contents($this->getDir() . '/tests/codeception.yml', $contents);

    $command = "vendor/bin/codecept run {$options['suite']} --steps --config=tests --html";
    $codeceptOptions = ['group', 'shard', 'filter', 'skip-group', 'fail-fast'];
    foreach ($codeceptOptions as $opt) {
      if ($options[$opt]) {
        $command .= " --$opt=$options[$opt]";
      }
    }

    if (getenv('CI')) {
      $command .= ' --env=ci';
    }

    $result = $this->localMachineHelper()
      ->executeFromCmd($command, NULL, $this->getDir());

    if (!file_exists('artifacts/report.html')) {
      throw new CommandFailedException('Codeception failed', $result->getExitCode());
    }

    if (!$result->isSuccessful() && getenv('CI')) {
      $command = "vendor/bin/codecept run {$options['suite']} --steps --config=tests --html --group=failed --env=ci";
      $result = $this->localMachineHelper()
        ->executeFromCmd($command, NULL, $this->getDir());
    }

    if ($symLinkDir) {
      $fileSystem->remove($symLinkDir);
    }
    if (!$result->isSuccessful() || !file_exists('artifacts/report.html')) {
      throw new CommandFailedException('Codeception failed', $result->getExitCode());
    }
  }

}
