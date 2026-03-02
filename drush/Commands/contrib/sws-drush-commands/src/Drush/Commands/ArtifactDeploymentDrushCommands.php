<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Drush\Attributes as CLI;
use Drupal\SwsDrush\Output\Checklist;
use Drush\Boot\DrupalBootLevels;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;

/**
 * A Drush command file.
 */
#[CLI\Bootstrap(level: DrupalBootLevels::NONE)]
final class ArtifactDeploymentDrushCommands extends DrushCommands {

  use SwsCommandsTrait;

  protected array $vendorDirs;

  protected array $scaffoldFiles;

  private string $composerJsonPath;

  private string $docrootPath;

  private string $destinationGitRef;

  private ?string $destinationTag;

  protected Checklist $checklist;

  /**
   * Build and push an artifact based on the current drupal installation.
   *
   * Replaces `blt deploy`
   */
  #[CLI\Command(name: 'sws:artifact:deploy')]
  #[CLI\Option(name: 'drupal-core-folder', description: 'Drupal install folder e.g. docroot or web')]
  #[CLI\Option(name: 'git-url', description: 'Destination git repo url. Use multiple options for multiple urls. --git-url=foo --git-url=bar')]
  #[CLI\Option(name: 'branch', description: 'Destination branch name')]
  #[CLI\Option(name: 'tag', description: 'Destination Tag name')]
  #[CLI\Option(name: 'commit-msg', description: 'Commit message string')]
  #[CLI\Option(name: 'no-sanitize', description: 'Do not sanitize the build artifact')]
  #[CLI\Option(name: 'no-push', description: 'Do not push changes to VCS repository')]
  #[CLI\Option(name: 'post-build-script', description: 'Shell script to run after the build')]
  #[CLI\Option(name: 'artifact-dir', description: 'Directory to build the artifact')]
  #[CLI\Usage(name: 'artifact:deploy -n', description: 'Deploy code to the branch name using all default settings')]
  #[CLI\Help(description: 'The options can be configured in a drush.yml file and committed to the repository. See the example file for more information.')]
  public function buildCommand(
    $options = [
      'drupal-core-folder' => 'docroot',
      'git-url' => [InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED],
      'branch' => InputOption::VALUE_OPTIONAL,
      'tag' => InputOption::VALUE_OPTIONAL,
      'commit-msg' => NULL,
      'no-sanitize' => FALSE,
      'no-push' => FALSE,
      'post-build-script' => NULL,
      'artifact-dir' => NULL,
    ]
  ): void {
    $this->ensureOption('git-url', fn() => $this->io()
      ->askRequired('Remote Git URL'), TRUE);

    $this->ensureOption('branch', fn() => $this->io()
      ->ask('Target branch', $this->getLocalGitBranch()));

    $this->ensureOption('tag', fn() => $this->io()
      ->ask('Provide a tag name'));

    $artifactDir = Path::join(sys_get_temp_dir(), 'drupal-artifact-build');
    if ($options['artifact-dir']) {
      $artifactDir = str_starts_with($options['artifact-dir'], '/') ? $options['artifact-dir'] : Path::join($this->getDir(), $options['artifact-dir']);
    }

    $this->composerJsonPath = Path::join($this->getDir(), 'composer.json');
    $this->docrootPath = Path::join($this->getDir(), $options['drupal-core-folder']);
    $this->validateSourceCode();

    $isDirty = $this->isLocalGitRepoDirty();
    $commitHash = $this->getLocalGitCommitHash();

    if ($isDirty) {
      throw new \RuntimeException(
        'Pushing code was aborted because your local Git repository has uncommitted changes. Either commit, reset, or stash your changes via git.'
      );
    }
    $this->checklist = new Checklist($this->output());
    $outputCallback = $this->getOutputCallback($this->output(), $this->checklist);

    $destinationGitUrls = $this->input->getOption('git-url');

    $this->destinationGitRef = $this->input->getOption('branch') ?: $this->getLocalGitBranch() . '-build';
    $this->destinationTag = $this->input->getOption('tag');
    $destinationGitUrlsString = implode(',', $destinationGitUrls);

    $refType = $this->input->getOption('tag') ? 'tag' : 'branch';

    $this->io()->note([
      'The command will:',
      "- git clone $this->destinationGitRef from $destinationGitUrls[0]",
      "- Compile the contents of {$this->getDir()} into an artifact in a temporary directory",
      "- Copy the artifact files into the checked out copy of $this->destinationGitRef",
      "- Run provided post-build {$options['post-build-script']} script if specified",
      "- Commit changes and push the $this->destinationGitRef $refType to the following git remote(s):",
      "  $destinationGitUrlsString",
    ]);

    $this->checklist->addItem('Preparing artifact directory');
    $this->cloneSourceBranch($outputCallback, $artifactDir, $destinationGitUrls[0], $this->destinationGitRef);
    $this->checklist->completePreviousItem();

    $this->checklist->addItem('Generating build artifact');
    $this->buildArtifact($outputCallback, $artifactDir);
    $this->checklist->completePreviousItem();

    if (!$options['no-sanitize']) {
      $this->checklist->addItem('Sanitizing build artifact');
      $this->sanitizeArtifact($outputCallback, $artifactDir);
      $this->checklist->completePreviousItem();
    }

    if ($options['post-build-script']) {
      $this->checklist->addItem('Running post-build script');
      $process = $this->localmachineHelper()
        ->executeFromCmd($options['post-build-script'], $outputCallback, $artifactDir);
      if (!$process->isSuccessful()) {
        $this->io()->error($process->getCommandLine());
        $this->io()->error($process->getOutput());
        throw new \RuntimeException('Failed to run post build script');
      }
      $this->checklist->completePreviousItem();
    }

    $this->checklist->addItem("Committing changes (commit hash: $commitHash)");
    $this->commit($outputCallback, $artifactDir, $this->destinationTag);
    $this->checklist->completePreviousItem();

    if (!$options['no-push']) {
      $dest = $refType == 'branch'? $this->destinationGitRef: $this->destinationTag;
      $this->checklist->addItem("Pushing changes to <options=bold>$dest</> $refType.");
      $this->pushArtifact($outputCallback, $artifactDir, $destinationGitUrls, $this->destinationGitRef . ':' . $this->destinationGitRef, $refType);
      $this->checklist->completePreviousItem();
      $this->logger()->success(dt('Artifact successfully built and pushed.'));
      return;
    }

    $this->logger()->success(dt('Artifact successfully built but not pushed.'));
  }

  private function validateSourceCode(): void {
    $requiredPaths = [
      $this->composerJsonPath,
      $this->docrootPath,
    ];
    foreach ($requiredPaths as $requiredPath) {
      if (!file_exists($requiredPath)) {
        throw new \RuntimeException("Your current directory does not look like a valid Drupal application. $requiredPath is missing.");
      }
    }
  }

  protected function isLocalGitRepoDirty(): bool {
    $this->localmachineHelper()->checkRequiredBinariesExist(['git']);
    $process = $this->localmachineHelper()->executeFromCmd(
    // Problem with this is that it stages changes for the user. They may
    // not want that.
      'git add . && git diff-index --cached --quiet HEAD',
      NULL,
      $this->getDir(),
      FALSE
    );

    return !$process->isSuccessful();
  }

  protected function getLocalGitBranch(): string {
    $this->localmachineHelper()->checkRequiredBinariesExist(['git']);
    $process = $this->localmachineHelper()->execute([
      'git',
      'rev-parse',
      '--abbrev-ref',
      'HEAD',
    ], NULL, $this->getDir(), FALSE);

    if (!$process->isSuccessful()) {
      throw new \RuntimeException('Unable to determine Git commit hash.');
    }

    return trim($process->getOutput());
  }

  protected function getLocalGitCommitHash(): string {
    $this->localmachineHelper()->checkRequiredBinariesExist(['git']);
    $process = $this->localmachineHelper()->execute([
      'git',
      'rev-parse',
      'HEAD',
    ], NULL, $this->getDir(), FALSE);

    if (!$process->isSuccessful()) {
      throw new \RuntimeException('Unable to determine Git commit hash.');
    }

    return trim($process->getOutput());
  }

  /**
   * Prepare a directory to build the artifact.
   */
  private function cloneSourceBranch(
    \Closure $outputCallback,
    string $artifactDir,
    string $vcsUrl,
    string $vcsPath
  ): void {
    $fs = $this->localmachineHelper()->getFilesystem();

    $outputCallback('out', "Removing $artifactDir if it exists");
    $fs->remove($artifactDir);

    $outputCallback('out', "Initializing Git in $artifactDir");
    $this->localmachineHelper()->checkRequiredBinariesExist(['git']);
    $process = $this->localmachineHelper()->execute([
      'git',
      'clone',
      $vcsUrl,
      $artifactDir,
    ],
      $outputCallback,
      NULL,
      ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    if (!$process->isSuccessful()) {
      throw new \RuntimeException(sprintf('Failed to clone repository from the Cloud Platform: %s', $process->getErrorOutput()));
    }
    $process = $this->localmachineHelper()->execute([
      'git',
      'fetch',
      $vcsUrl,
      $vcsPath . ':' . $vcsPath,
    ],
      $outputCallback,
      $artifactDir,
      ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    if (!$process->isSuccessful()) {
      // Remote branch does not exist. Just create it locally. This will create
      // the new branch off of the current commit.
      $process = $this->localmachineHelper()->execute([
        'git',
        'checkout',
        '-b',
        $vcsPath,
      ],
        $outputCallback,
        $artifactDir,
        ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    }
    else {
      $process = $this->localmachineHelper()->execute([
        'git',
        'checkout',
        $vcsPath,
      ],
        $outputCallback,
        $artifactDir,
        ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    }
    if (!$process->isSuccessful()) {
      throw new \RuntimeException(
        sprintf('Could not checkout %s branch locally: %s%s', $vcsPath, $process->getErrorOutput(), $process->getOutput())
      );
    }

    $outputCallback('out', 'Global .gitignore file is temporarily disabled during artifact builds.');
    $this->localmachineHelper()->execute([
      'git',
      'config',
      '--local',
      'core.excludesFile',
      'false',
    ],
      $outputCallback,
      $artifactDir,
      ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    $this->localmachineHelper()->execute([
      'git',
      'config',
      '--local',
      'core.fileMode',
      'true',
    ],
      $outputCallback,
      $artifactDir,
      ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));

    // Vendor directories can be "corrupt" (i.e. missing scaffold files due to earlier sanitization) in ways that break composer install.
    $outputCallback('out', 'Removing vendor directories');
    foreach ($this->vendorDirs() as $vendorDirectory) {
      $fs->remove(Path::join($artifactDir, $vendorDirectory));
    }
  }

  private function vendorDirs($relativeDrupalDir = ''): array {
    if (!empty($this->vendorDirs) && empty($relativeDrupalDir)) {
      return $this->vendorDirs;
    }

    $this->vendorDirs = [
      $relativeDrupalDir . 'vendor',
    ];
    if (file_exists($this->composerJsonPath)) {
      $composerJson = json_decode($this->localmachineHelper()
        ->readFile($this->composerJsonPath), TRUE, 512, JSON_THROW_ON_ERROR);

      foreach ($composerJson['extra']['installer-paths'] as $path => $type) {
        $path = str_replace('/{$name}', '', $path);
        $this->vendorDirs[] = $relativeDrupalDir . str_replace('/{$name}', '', $path);
      }
      return $this->vendorDirs;
    }
    return [];
  }

  /**
   * Build the artifact.
   */
  private function buildArtifact(\Closure $outputCallback, string $artifactDir): void {
    $outputCallback('out', "Mirroring source files from {$this->getDir()} to $artifactDir");
    $originFinder = $this->localmachineHelper()->getFinder();
    $originFinder->in($this->getDir())
      // Include dot files like .htaccess.
      ->ignoreDotFiles(FALSE)
      // Ignore VCS ignored files (e.g. vendor) to speed up the mirror (Composer will restore them later).
      ->ignoreVCSIgnored(TRUE);
    $targetFinder = $this->localmachineHelper()->getFinder();
    $targetFinder->in($artifactDir)->ignoreDotFiles(FALSE);
    $this->localmachineHelper()->getFilesystem()->remove($targetFinder);
    $this->localmachineHelper()->getFilesystem()
      ->mirror($this->getDir(), $artifactDir, $originFinder, ['override' => TRUE]);

    $this->localmachineHelper()->checkRequiredBinariesExist(['composer']);
    $outputCallback('out', 'Installing Composer production dependencies');
    $process = $this->localmachineHelper()->execute([
      'composer',
      'install',
      '--no-dev',
      '--no-interaction',
      '--optimize-autoloader',
    ],
      $outputCallback,
      $artifactDir,
      ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    if (!$process->isSuccessful()) {
      throw new \RuntimeException(
        sprintf('Unable to install composer dependencies: %s%s', $process->getOutput(), $process->getErrorOutput())
      );
    }
  }

  /**
   * Sanitize the artifact.
   */
  private function sanitizeArtifact(\Closure $outputCallback, string $artifactDir): void {
    $outputCallback('out', 'Finding Drupal core text files');
    $sanitizeFinder = $this->localmachineHelper()->getFinder()
      ->files()
      ->name('*.txt')
      ->notName('LICENSE.txt')
      ->in("$artifactDir/docroot/core");

    $outputCallback('out', 'Finding VCS directories');
    $vcsFinder = $this->localmachineHelper()->getFinder()
      ->ignoreDotFiles(FALSE)
      ->ignoreVCS(FALSE)
      ->directories()
      ->in([
        "$artifactDir/docroot",
        "$artifactDir/vendor",
      ])
      ->name('.git');
    $drushDir = "$artifactDir/drush";
    if (file_exists($drushDir)) {
      $vcsFinder->in($drushDir);
    }
    if ($vcsFinder->hasResults()) {
      $sanitizeFinder->append($vcsFinder);
    }

    $outputCallback('out', 'Finding INSTALL database text files');
    $dbInstallFinder = $this->localmachineHelper()->getFinder()
      ->files()
      ->in([$artifactDir])
      ->name('/INSTALL\.[a-z]+\.(md|txt)$/');
    if ($dbInstallFinder->hasResults()) {
      $sanitizeFinder->append($dbInstallFinder);
    }

    $outputCallback('out', 'Finding other common text files');
    $filenames = [
      'AUTHORS',
      'CHANGELOG',
      'CONDUCT',
      'CONTRIBUTING',
      'INSTALL',
      'MAINTAINERS',
      'PATCHES',
      'TESTING',
      'UPDATE',
    ];
    $textFileFinder = $this->localmachineHelper()->getFinder()
      ->files()
      ->in(["$artifactDir/docroot"])
      ->name('/(' . implode('|', $filenames) . ')\.(md|txt)$/');
    if ($textFileFinder->hasResults()) {
      $sanitizeFinder->append($textFileFinder);
    }

    $outputCallback('out', 'Removing sensitive files from build');
    $this->localmachineHelper()->getFilesystem()->remove($sanitizeFinder);
  }

  /**
   * Commit the artifact.
   */
  private function commit(\Closure $outputCallback, string $artifactDir, ?string $tag = NULL): void {
    $outputCallback('out', 'Adding and committing changed files');
    $this->localmachineHelper()->checkRequiredBinariesExist(['git']);
    $process = $this->localmachineHelper()->execute(['git', 'add', '-A'],
      $outputCallback,
      $artifactDir,
      ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    if (!$process->isSuccessful()) {
      throw new \RuntimeException(
        sprintf('Could not add files to artifact via git: %s%s', $process->getErrorOutput(), $process->getOutput())
      );
    }
    foreach (array_merge($this->vendorDirs(), $this->scaffoldFiles($artifactDir)) as $file) {
      $this->logger->debug("Forcibly adding $file");
      $this->localmachineHelper()->execute([
        'git',
        'add',
        '-f',
        $file,
      ], NULL, $artifactDir, FALSE);
      if (!$process->isSuccessful()) {
        // This will fatally error if the file doesn't exist. Suppress error output.
        $this->io->warning("Unable to forcibly add $file to new branch");
      }
    }
    $commitMessage = $this->generateCommitMessage() ?: $tag;
    $process = $this->localmachineHelper()->execute([
      'git',
      'commit',
      '-m',
      $commitMessage,
    ],
      $outputCallback,
      $artifactDir,
      ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));
    if (!$process->isSuccessful()) {
      throw new \RuntimeException(
        sprintf('Could not commit via git: %s%s', $process->getErrorOutput(), $process->getOutput())
      );
    }

    if ($tag) {
      $process = $this->localmachineHelper()->execute([
        'git',
        'tag',
        '-a',
        $tag,
        '-m',
        $tag,
      ],
        $outputCallback,
        $artifactDir,
        ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL));

      if (!$process->isSuccessful()) {
        throw new \RuntimeException(
          sprintf('Could not create git tag via git: %s%s', $process->getErrorOutput(), $process->getOutput())
        );
      }
    }
  }

  private function generateCommitMessage(): string {
    if ($message = $this->input()->getOption('commit-msg')) {
      return $message;
    }
    $message = $this->localMachineHelper()
      ->executeFromCmd('git log -1 --pretty=%B', NULL, $this->getDir())
      ->getOutput();
    return str_replace("\n", '', $message);
  }

  /**
   * Get a list of scaffold files from Drupal core's composer.json.
   */
  private function scaffoldFiles(string $artifactDir): array {
    if (!empty($this->scaffoldFiles)) {
      return $this->scaffoldFiles;
    }

    $this->scaffoldFiles = [];
    $composerJson = json_decode(
      $this->localmachineHelper()
        ->readFile(Path::join($artifactDir, 'docroot', 'core', 'composer.json')),
      TRUE,
      512,
      JSON_THROW_ON_ERROR
    );
    foreach ($composerJson['extra']['drupal-scaffold']['file-mapping'] as $file => $assetPath) {
      if (str_starts_with($file, '[web-root]')) {
        $this->scaffoldFiles[] = str_replace('[web-root]', 'docroot', $file);
      }
    }
    $this->scaffoldFiles[] = 'docroot/autoload.php';

    return $this->scaffoldFiles;
  }

  /**
   * Push the artifact.
   */
  private function pushArtifact(\Closure $outputCallback, string $artifactDir, array $vcsUrls, string $destGit, string $destType = 'branch'): void {
    $this->localmachineHelper()->checkRequiredBinariesExist(['git']);
    $this->localmachineHelper()->execute(['git', 'config', '--global', 'http.postBuffer', 268435456]);

    foreach ($vcsUrls as $vcsUrl) {
      $outputCallback('out', "Pushing changes to Git ($vcsUrl)");
      $args = $destType == 'branch' ? [
        'git',
        'push',
        $vcsUrl,
        $destGit,
      ] : [
        'git',
        'push',
        $vcsUrl,
        'tag',
        $this->destinationTag,
      ];
      $process = $this->localmachineHelper()->execute(
        $args,
        $outputCallback,
        $artifactDir,
        ($this->output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL)
      );
      if (!$process->isSuccessful()) {
        throw new \RuntimeException(
          sprintf(
            'Unable to push artifact to remote repository: %s %s',
            $process->getOutput(),
            $process->getErrorOutput()
          )
        );
      }
    }
  }

}
