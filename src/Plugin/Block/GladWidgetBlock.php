<?php

namespace Drupal\glad_widgets\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Glad Widget block.
 *
 * @Block(
 *   id = "glad_widget_block",
 *   admin_label = @Translation("Glad Widget"),
 *   category = @Translation("Glad")
 * )
 */
class GladWidgetBlock extends BlockBase implements ContainerFactoryPluginInterface {

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeManagerInterface $entityTypeManager,
    protected ConfigFactoryInterface $configFactory,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('config.factory'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return ['widget_id' => ''] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $storage = $this->entityTypeManager->getStorage('glad_widget');
    $options = [];
    foreach ($storage->loadMultiple() as $widget) {
      /** @var \Drupal\glad_widgets\GladWidgetInterface $widget */
      $options[$widget->id()] = $widget->label();
    }

    $form['widget_id'] = [
      '#type' => 'select',
      '#title' => $this->t('Widget'),
      '#options' => $options,
      '#default_value' => $this->configuration['widget_id'],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Vali widget -'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['widget_id'] = $form_state->getValue('widget_id');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    if (empty($this->configuration['widget_id'])) {
      return [];
    }

    /** @var \Drupal\glad_widgets\GladWidgetInterface|null $widget */
    $widget = $this->entityTypeManager->getStorage('glad_widget')->load($this->configuration['widget_id']);
    if (!$widget || !$widget->status()) {
      return [];
    }

    $config = $this->configFactory->get('glad_widgets.settings');
    $uuid = $config->get('institution_uuid');
    $baseUrl = rtrim((string) $config->get('widget_base_url'), '/');
    $token = $config->get('api_token');

    if (empty($uuid) || empty($baseUrl)) {
      return [];
    }

    $url = "{$baseUrl}/widget/institutions/{$uuid}/{$widget->getModule()}";
    if (!empty($token)) {
      $url .= '?token=' . rawurlencode($token);
    }

    return [
      '#theme' => 'glad_widget',
      '#widget_url' => $url,
      '#widget_height' => $widget->getHeight(),
      '#widget_name' => $widget->label(),
      '#cache' => [
        'tags' => $widget->getCacheTags(),
        'contexts' => ['url'],
      ],
    ];
  }

}
