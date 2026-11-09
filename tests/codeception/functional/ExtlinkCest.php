<?php

/**
 * Checks external link behavior with the platform's shipped settings.
 *
 * @group install
 * @group extlink
 */
class ExtlinkCest {

  /**
   * Existing link targets, titles, and rich-text exclusions stay intact.
   */
  public function testPlatformLinkSettings(FunctionalTester $I): void {
    $I->amOnPage('/');
    $I->waitForJS('return typeof window.Drupal?.extlink?.attach === "function";', 10);
    $settings = $I->executeJS('return drupalSettings.data.extlink;');
    $I->assertFalse($settings['extTarget']);
    $I->assertTrue($settings['extTitleNoOverride']);
    $I->assertTrue($settings['extUseFontAwesome']);
    $I->assertFalse($settings['extUseIcon']);
    $I->assertFalse($settings['extPreventOrphan']);
    $I->assertSame('0', $settings['extClass']);

    $this->attachFixture($I);
    $I->seeElement('#extlink-check-external.ext[data-extlink]');
    $I->assertNull($I->grabAttributeFrom('#extlink-check-external', 'target'));
    $I->assertSame('author', $I->grabAttributeFrom('#extlink-check-external', 'rel'));
    $I->assertSame('Keep this title', $I->grabAttributeFrom('#extlink-check-existing', 'title'));
    $I->assertSame('_self', $I->grabAttributeFrom('#extlink-check-existing', 'target'));
    $I->dontSeeElement('#extlink-check-internal[data-extlink]');
    $I->dontSeeElement('#extlink-check-excluded[data-extlink]');
    $I->dontSeeElement('#extlink-check-fixture svg, #extlink-check-fixture span.extlink');
  }

  /**
   * Enabling title changes must not announce a new window when targets are off.
   */
  public function testNewWindowTitleRequiresTarget(FunctionalTester $I): void {
    $I->amOnPage('/');
    $I->waitForJS('return typeof window.Drupal?.extlink?.attach === "function";', 10);
    $settings = $I->executeJS('return drupalSettings.data.extlink;');
    $I->assertFalse($settings['extTarget']);
    $I->assertTrue($settings['extTargetAppendNewWindowDisplay']);

    $this->attachFixture($I, ['extTitleNoOverride' => FALSE]);
    $I->assertNull($I->grabAttributeFrom('#extlink-check-external', 'title'));
    $I->assertSame('Keep this title', $I->grabAttributeFrom('#extlink-check-existing', 'title'));
  }

  /**
   * New-window titles still work when opening links in a new window is enabled.
   */
  public function testNewWindowTitleWithTarget(FunctionalTester $I): void {
    $I->amOnPage('/');
    $I->waitForJS('return typeof window.Drupal?.extlink?.attach === "function";', 10);
    $settings = $I->executeJS('return drupalSettings.data.extlink;');

    $this->attachFixture($I, [
      'extTitleNoOverride' => FALSE,
      'extTarget' => TRUE,
    ]);
    $I->assertSame('_blank', $I->grabAttributeFrom('#extlink-check-external', 'target'));
    $I->assertSame($settings['extTargetAppendNewWindowLabel'], $I->grabAttributeFrom('#extlink-check-external', 'title'));
    $I->assertSame('Keep this title ' . $settings['extTargetAppendNewWindowLabel'], $I->grabAttributeFrom('#extlink-check-existing', 'title'));
  }

  /**
   * Text-only orphan prevention leaves complex card markup intact.
   */
  public function testTextOnlyOrphanPrevention(FunctionalTester $I): void {
    $I->amOnPage('/');
    $I->waitForJS('return typeof window.Drupal?.extlink?.attach === "function";', 10);

    $this->attachFixture($I, [
      'extClass' => 'ext',
      'extUseFontAwesome' => FALSE,
      'extIconPlacement' => 'append',
      'extPreventOrphan' => TRUE,
      'extPreventOrphanTextLike' => TRUE,
    ]);
    $I->seeElement('#extlink-check-external > .extlink-nobreak > svg.ext');
    $I->seeElement('#extlink-check-card > svg.ext');
    $I->dontSeeElement('#extlink-check-card .extlink-nobreak');
    $I->assertSame('Card title', $I->grabTextFrom('#extlink-check-card .card-title'));
  }

  /**
   * Disabling the text-only restriction retains the earlier wrapping behavior.
   */
  public function testLegacyOrphanPrevention(FunctionalTester $I): void {
    $I->amOnPage('/');
    $I->waitForJS('return typeof window.Drupal?.extlink?.attach === "function";', 10);

    $this->attachFixture($I, [
      'extClass' => 'ext',
      'extUseFontAwesome' => FALSE,
      'extIconPlacement' => 'append',
      'extPreventOrphan' => TRUE,
      'extPreventOrphanTextLike' => FALSE,
    ]);
    $I->seeElement('#extlink-check-card .extlink-nobreak > svg.ext');
  }

  /**
   * Exercise browser behavior without creating content or following links.
   */
  private function attachFixture(FunctionalTester $I, array $overrides = []): void {
    $I->executeJS(
      <<<'JS'
const fixture = document.createElement('div');
fixture.id = 'extlink-check-fixture';
fixture.innerHTML = `
  <a id="extlink-check-external" href="https://external.example.com/" rel="author">External</a>
  <a id="extlink-check-existing" href="https://external.example.com/" title="Keep this title" target="_self">Existing attributes</a>
  <a id="extlink-check-internal" href="/">Internal</a>
  <div class="text-long"><a id="extlink-check-excluded" href="https://external.example.com/">Rich text</a></div>
  <a id="extlink-check-card" href="https://external.example.com/"><div class="card"><div class="card-title">Card title</div></div></a>
  <a href="mailto:test@example.com">Email</a>
  <a href="tel:+16505550100">Telephone</a>`;
document.body.appendChild(fixture);
const original = drupalSettings.data.extlink;
try {
  drupalSettings.data.extlink = {...original, ...arguments[0]};
  Drupal.extlink.attach(fixture, drupalSettings);
} finally {
  drupalSettings.data.extlink = original;
}
JS,
      [$overrides],
    );
  }

}
