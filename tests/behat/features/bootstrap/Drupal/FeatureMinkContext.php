<?php

namespace Drupal;

use Behat\Mink\Exception\ExpectationException;
use Behat\MinkExtension\Context\RawMinkContext;

/**
 * Javascript related tests.
 */
class FeatureMinkContext extends RawMinkContext {

  protected $files = [];

  /**
   * @Then I maximize the window
   */
  public function maximizeWindow() {
    $this->getSession()->getDriver()->maximizeWindow();
  }

  /**
   * @Then I set window dimensions :width x :height
   */
  public function iSetWindowDimensions($width, $height) {
    $this->getSession()->resizeWindow((int) $width, (int) $height, 'current');
  }

  /**
   * @Then I wait :seconds seconds
   */
  public function iWaitSeconds($seconds) {
    $this->getSession()->wait(1000 * $seconds);
  }

  /**
   * @Then I drop :path file into dropzone
   */
  public function iDropAFileIntoDropzone($path) {
    if ($this->getMinkParameter('files_path')) {
      $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $path;
      if (is_file($fullPath)) {
        $path = $fullPath;
      }
    }
    $file_name = basename($path);

    $type = mime_content_type($path);
    $data = file_get_contents($path);
    $base64 = "data:$type;base64," . base64_encode($data);

    $javascript = "
    function dataURLtoFile(dataurl, filename) {
        var arr = dataurl.split(','), mime = arr[0].match(/:(.*?);/)[1],
            bstr = atob(arr[1]), n = bstr.length, u8arr = new Uint8Array(n);
        while(n--){
            u8arr[n] = bstr.charCodeAt(n);
        }
        return new File([u8arr], filename, {type:mime});
    }
    var newfile = dataURLtoFile('$base64', '$file_name');
    Dropzone.instances[0].addFile(newfile);";
    $this->getSession()->executeScript($javascript);
    $this->getSession()->wait(1000 * 2);
  }

  /**
   * @Then I fill in wysiwyg :locator with :value
   */
  public function iFillInWysiwygOnFieldWith($locator, $value) {
    $element = $this->getSession()->getPage()->findField($locator);

    if (empty($element)) {
      throw new ExpectationException('Could not find WYSIWYG with locator: ' . $locator, $this->getSession());
    }

    $fieldId = $element->getAttribute('id');

    if (empty($fieldId)) {
      throw new \Exception('Could not find an id for field with locator: ' . $locator);
    }

    $this->getSession()
      ->executeScript("CKEDITOR.instances[\"$fieldId\"].setData(\"$value\");");
  }

  /**
   * @Given I click the :selector element
   */
  public function iClickTheElement($selector) {
    $page = $this->getSession()->getPage();
    $element = $page->find('css', $selector);

    if (empty($element)) {
      throw new \Exception("No html element found for the selector ('$selector')");
    }

    $element->click();
  }

  /**
   * @Then I switch to :selector iframe
   */
  public function iSwitchToiFrame($selector) {
    $this->getSession()->switchToIFrame($selector);
  }

  /**
   * @Then I exit iframe
   */
  public function iExitiFrame() {
    $this->getSession()->switchToWindow();
  }

  /**
   * Returns fixed step argument (with \\" replaced back to ")
   *
   * @param string $argument
   *
   * @return string
   */
  protected function fixStepArgument($argument) {
    return str_replace('\\"', '"', $argument);
  }

}
