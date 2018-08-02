<?php

/**
 * @file
 * mrc_paragraphs_slideshow.post_update.php
 */

/**
 * Make slick slideshow loop.
 */
function mrc_paragraphs_slideshow_post_update_8_0_6() {
  $config_factory = \Drupal::configFactory();
  $config = $config_factory->getEditable('slick.optionset.mrc_slideshow');
  $config->set('options.settings.infinite', TRUE);
  $config->save(TRUE);
}
