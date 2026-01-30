<?php

declare(strict_types=1);

namespace Drupal\SwsDrush\Drush\Commands;

use Drupal\SwsDrush\Helpers\AcquiaApi;
use Drupal\SwsDrush\Helpers\LocalMachineHelper;
use Drupal\SwsDrush\Output\Checklist;
use Drush\Drush;
use Symfony\Component\Console\Output\OutputInterface;

trait SwsCommandsTrait {

  protected string $dir;

  protected LocalMachineHelper $localMachineHelper;

  protected function getOutputCallback(
    OutputInterface $output,
    Checklist $checklist
  ): \Closure {
    return static function(mixed $type, mixed $buffer) use ($checklist, $output): void {
      if (!$output->isVerbose() && $checklist->getItems()) {
        $checklist->updateProgressBar($buffer);
      }
      $output->writeln($buffer, OutputInterface::VERBOSITY_VERY_VERBOSE);
    };
  }

  public function getDir(): string {
    if (isset($this->dir)) {
      return $this->dir;
    }
    /** @var \Drush\Boot\BootstrapManager $bootstrap */
    $bootstrap = Drush::bootstrapManager();
    $this->dir = $bootstrap->getComposerRoot();
    return $this->dir;
  }

  public function localMachineHelper(): LocalMachineHelper {
    if (isset($this->localMachineHelper)) {
      return $this->localMachineHelper;
    }
    $this->getDir();

    $this->localMachineHelper = new LocalMachineHelper();
    $this->localMachineHelper->setLogger($this->logger());
    $this->localMachineHelper->setInput($this->input());
    $this->localMachineHelper->setOutput($this->output());
    return $this->localMachineHelper;
  }

  protected function ensureOption(string $name, callable $asker, bool $required = FALSE): void {
    $value = $this->input->getOption($name);

    if ($value === NULL && $this->input->isInteractive()) {
      $value = $asker();
    }

    if ($required && !$value) {
      throw new \InvalidArgumentException(dt('The !optionName option is required.', [
        '!optionName' => $name,
      ]));
    }

    $this->input->setOption($name, $value);
  }

  /**
   * Get a API connection service object.
   *
   * @return \Drupal\SwsDrush\Helpers\AcquiaApi
   *   API connection.
   */
  protected function getAcquiaApi(): AcquiaApi {
    $this->ensureOption('app-id', fn() => $this->io()
      ->ask('Acquia Application ID'), TRUE);
    $this->ensureOption('app-key', fn() => $this->io()
      ->ask('Acquia Application Key'), TRUE);
    $this->ensureOption('app-secret', fn() => $this->io()
      ->password('Acquia Application Secret'), TRUE);

    $appId = $this->input()->getOption('app-id');
    $appKey = $this->input()->getOption('app-key');
    $appSecret = $this->input()->getOption('app-secret');

    return new AcquiaApi($appId, $appKey, $appSecret);
  }

}
