<?php

namespace Drupal\Tests\hs_page_reports\Functional\EventSubscriber;

use Drupal\Tests\BrowserTestBase;

class PageReportsEventSubscriberTest extends BrowserTestBase {

  protected $defaultTheme = 'stark';

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = [
    'system',
    'hs_page_reports',
  ];

  /**
   * Test a 404 page is reported and displayed appropriately.
   */
  public function test404Report() {
    $this->drupalGet('/does_not_exist');
    $this->assertSession()->statusCodeEquals(404);

    $account = $this->createUser(['view 404 reports']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/reports/page-not-found');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('/does_not_exist');
  }

  /**
   * Test a access denied page is recorded correctly.
   */
  public function test403Report() {
    $this->drupalGet('/admin/reports/page-not-found');
    $this->assertSession()->statusCodeEquals(403);
    $this->drupalGet('/admin/reports/access-denied');
    $this->assertSession()->statusCodeEquals(403);

    $account = $this->createUser(['view 403 reports']);
    $this->drupalLogin($account);
    $this->drupalGet('/admin/reports/access-denied');
    $this->assertSession()->statusCodeEquals(200);
    $this->assertSession()->pageTextContains('/admin/reports/page-not-found');
    $this->assertSession()->pageTextContains('/admin/reports/access-denied');
  }

}
