<?php

namespace Drupal\Tests\hs_admin\Unit;

use Drupal\Tests\UnitTestCase;
use Drush\Commands\HsReportsDrushCommands;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * Ensures remote audits cannot silently expand to every configured site.
 */
#[Group('hs_admin')]
class VideoEmbedFieldReportTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp(): void {
    parent::setUp();
    require_once __DIR__ . '/../../../../../../../vendor/drush/drush/includes/output.inc';
    require_once __DIR__ . '/../../../../../../../drush/Commands/HsReportsDrushCommands.php';
  }

  /**
   * Reject an unspecified remote target before accessing Drush services.
   */
  #[DataProvider('remoteSitesProvider')]
  public function testRemoteSitesMustBeExplicit(string $env, string $sites): void {
    $this->expectException(\InvalidArgumentException::class);
    $this->expectExceptionMessage('Specify the remote sites to audit with --sites.');
    (new HsReportsDrushCommands())->videoEmbedField([
      'env' => $env,
      'sites' => $sites,
    ]);
  }

  /**
   * Remote environments with an empty or whitespace-only site list.
   */
  public static function remoteSitesProvider(): array {
    return [
      ['dev', ''],
      ['dev', '   '],
      ['stage', ''],
      ['stage', '   '],
      ['prod', ''],
      ['prod', '   '],
    ];
  }

}
