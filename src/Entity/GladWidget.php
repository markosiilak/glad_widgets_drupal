<?php

namespace Drupal\glad_widgets\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;
use Drupal\glad_widgets\GladWidgetInterface;

/**
 * Defines the Glad Widget config entity.
 *
 * @ConfigEntityType(
 *   id = "glad_widget",
 *   label = @Translation("Glad Widget"),
 *   handlers = {
 *     "list_builder" = "Drupal\glad_widgets\GladWidgetListBuilder",
 *     "form" = {
 *       "add" = "Drupal\glad_widgets\Form\GladWidgetForm",
 *       "edit" = "Drupal\glad_widgets\Form\GladWidgetForm",
 *       "delete" = "Drupal\glad_widgets\Form\GladWidgetDeleteForm",
 *     },
 *   },
 *   config_prefix = "glad_widget",
 *   admin_permission = "administer glad widgets",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "status" = "status",
 *     "weight" = "weight",
 *   },
 *   links = {
 *     "edit-form" = "/admin/config/services/glad-widgets/widgets/{glad_widget}/edit",
 *     "delete-form" = "/admin/config/services/glad-widgets/widgets/{glad_widget}/delete",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "module",
 *     "height",
 *     "status",
 *     "weight",
 *   }
 * )
 */
class GladWidget extends ConfigEntityBase implements GladWidgetInterface {

  /**
   * Machine name.
   *
   * @var string
   */
  protected $id;

  /**
   * Human readable label.
   *
   * @var string
   */
  protected $label;

  /**
   * The feature/sub-page machine name, e.g. "catering".
   *
   * @var string
   */
  protected $module;

  /**
   * Iframe height in pixels.
   *
   * @var int
   */
  protected $height = 600;

  /**
   * Weight for ordering.
   *
   * @var int
   */
  protected $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function getModule(): string {
    return $this->module ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getHeight(): int {
    return (int) $this->height;
  }

  /**
   * {@inheritdoc}
   */
  public function getWeight(): int {
    return (int) $this->weight;
  }

}
