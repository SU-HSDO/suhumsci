<?php

namespace Drupal\hs_entities;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface defining a h&amp;s entities entity type.
 */
interface HsEntitiesInterface extends ContentEntityInterface, EntityOwnerInterface, EntityChangedInterface {

}
