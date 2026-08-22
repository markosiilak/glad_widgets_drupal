<?php

namespace Drupal\glad_widgets\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

/**
 * AJAX endpoints for the Glad Widgets admin UI.
 */
class GladWidgetsController extends ControllerBase {

  /**
   * Returns available modules as JSON, based on current config.
   */
  public function availableModules(Request $request): JsonResponse {
    $modules = \Drupal::service('glad_widgets.api_client')->getAvailableModules();
    return new JsonResponse(['modules' => $modules]);
  }

}
