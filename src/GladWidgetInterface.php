<?php

namespace Drupal\glad_widgets;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Provides an interface for Glad Widget config entities.
 */
interface GladWidgetInterface extends ConfigEntityInterface {

  /**
   * Gets the feature/sub-page machine name, e.g. "catering".
   */
  public function getModule(): string;

  /**
   * Gets the iframe height in pixels.
   */
  public function getHeight(): int;

  /**
   * Gets the sort weight.
   */
  public function getWeight(): int;

}
