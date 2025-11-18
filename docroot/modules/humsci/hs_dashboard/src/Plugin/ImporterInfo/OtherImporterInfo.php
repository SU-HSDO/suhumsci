<?php

namespace Drupal\hs_dashboard\Plugin\ImporterInfo;

use Drupal\Core\StringTranslation\TranslatableMarkup;
use Drupal\hs_dashboard\Plugin\ImporterInfoBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides information about other importers.
 *
 * @ImporterInfo(
 *   id = "other_importer_info",
 *   label = @Translation("Other Importers"),
 *   description = @Translation("Displays a message if other importers are active for this site."),
 *   weight = 100,
 * )
 */
class OtherImporterInfo extends ImporterInfoBase {

  /**
   * A list of migration IDs already represented in other ImporterInfo blocks.
   */
  const SKIP_IMPORTERS = [
    'hs_courses',
    'hs_localist_scheduled',
    'hs_capx',
  ];

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('keyvalue'),
      $container->get('date.formatter'),
      $container->get('plugin.manager.migration')
    );
  }

  /**
   * {@inheritDoc}
   */
  public function getNoDataCaption(): TranslatableMarkup {
    // Unlike other ImporterInfo blocks, this plugin never displays data.
    // Instead, this no-results message is repurposed to communicate that this
    // website has other active migrations not shown on the dashboard.
    return $this->t('<em>Other importers are running that cannot be shown on the dashboard.</em>');
  }

  /**
   * {@inheritdoc}
   */
  public function showImporter(): bool {
    return $this->hasActiveMigration();
  }

  /**
   * Check if this site has at least one relevant active migration.
   *
   * This method first identifies all enabled migrations, then removes any
   * migrations listed in the SKIP_IMPORTERS as these are handled in other parts
   * of the dashboard. Finally, it excludes any migrations that have not run in
   * the last 30 days.
   *
   * @return bool
   *   TRUE if this site has at least one 'other' migration; otherwise, FALSE.
   */
  protected function hasActiveMigration(): bool {
    $all_migrations = array_keys($this->migrationManager->getDefinitions());
    $other_migrations = array_diff($all_migrations, self::SKIP_IMPORTERS);
    foreach ($other_migrations as $id) {
      $last_imported = (int) $this->lastImportedStore->get($id, FALSE);
      if ($last_imported / 1000 >= time() - (30 * 24 * 60 * 60)) {
        return TRUE;
      }
    }
    return FALSE;
  }

}
