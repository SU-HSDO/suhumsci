<?php

namespace Drupal\hs_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a humsci entity entity type.
 */
interface HsEntityInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
