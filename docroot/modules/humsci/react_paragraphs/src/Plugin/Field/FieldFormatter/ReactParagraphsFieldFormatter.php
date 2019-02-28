<?php

namespace Drupal\react_paragraphs\Plugin\Field\FieldFormatter;

use Drupal\entity_reference_revisions\Plugin\Field\FieldFormatter\EntityReferenceRevisionsEntityFormatter;

/**
 * Plugin implementation of the 'react_paragraphs' formatter.
 *
 * @FieldFormatter(
 *   id = "react_paragraphs",
 *   label = @Translation("React Paragraphs"),
 *   field_types = {
 *     "react_paragraphs"
 *   }
 * )
 */
class ReactParagraphsFieldFormatter extends EntityReferenceRevisionsEntityFormatter {

  // TODO render in rows.
}
