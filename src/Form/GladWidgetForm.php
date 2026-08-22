<?php

namespace Drupal\glad_widgets\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Add/edit form for a Glad Widget entity.
 */
class GladWidgetForm extends EntityForm {

  public function __construct(
    protected \Drupal\glad_widgets\GladApiClient $apiClient,
  ) {}

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('glad_widgets.api_client'));
  }

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {
    $form = parent::form($form, $form_state);
    /** @var \Drupal\glad_widgets\GladWidgetInterface $widget */
    $widget = $this->entity;

    $form['#attached']['library'][] = 'glad_widgets/preview';

    $form['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Nimetus'),
      '#default_value' => $widget->label(),
      '#required' => TRUE,
    ];

    $form['id'] = [
      '#type' => 'machine_name',
      '#default_value' => $widget->id(),
      '#machine_name' => [
        'exists' => ['\Drupal\glad_widgets\Entity\GladWidget', 'load'],
      ],
      '#disabled' => !$widget->isNew(),
    ];

    $modules = $this->apiClient->getAvailableModules();
    if (empty($modules)) {
      $form['module_warning'] = [
        '#markup' => '<div class="messages messages--warning">' . $this->t('Ühtegi moodulit ei leitud. Kontrolli asutuse seadeid.') . '</div>',
      ];
    }

    $form['module'] = [
      '#type' => 'select',
      '#title' => $this->t('Moodul'),
      '#options' => $modules,
      '#default_value' => $widget->getModule(),
      '#required' => TRUE,
      '#empty_option' => $this->t('- Vali moodul -'),
      '#attributes' => ['class' => ['glad-widgets-preview-trigger'], 'data-preview-field' => 'sub_page'],
    ];

    $form['height'] = [
      '#type' => 'number',
      '#title' => $this->t('Kõrgus (px)'),
      '#default_value' => $widget->getHeight() ?: 600,
      '#min' => 100,
      '#max' => 3000,
      '#required' => TRUE,
      '#attributes' => ['class' => ['glad-widgets-preview-trigger'], 'data-preview-field' => 'height'],
    ];

    $form['weight'] = [
      '#type' => 'number',
      '#title' => $this->t('Järjekord (weight)'),
      '#default_value' => $widget->getWeight(),
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Aktiivne'),
      '#default_value' => $widget->status(),
    ];

    $config = \Drupal::config('glad_widgets.settings');
    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Live eelvaade'),
      '#open' => TRUE,
    ];
    $form['preview']['frame_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'glad-widgets-preview-wrapper'],
    ];
    $form['preview']['frame_wrapper']['iframe'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe id="glad-widgets-preview-iframe" src="{{ src }}" width="100%" height="{{ height }}" frameborder="0" loading="lazy" data-base-url="{{ base_url }}" data-uuid="{{ uuid }}" data-token="{{ token }}"></iframe>',
      '#context' => [
        'src' => $this->buildPreviewUrl($config->get('widget_base_url'), $config->get('institution_uuid'), $widget->getModule() ?: 'catering', $config->get('api_token')),
        'height' => $widget->getHeight() ?: 600,
        'base_url' => $config->get('widget_base_url'),
        'uuid' => $config->get('institution_uuid'),
        'token' => $config->get('api_token'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $widget = $this->entity;
    $status = $widget->save();

    $message = $status === SAVED_NEW
      ? $this->t('Widget %label loodud.', ['%label' => $widget->label()])
      : $this->t('Widget %label salvestatud.', ['%label' => $widget->label()]);
    $this->messenger()->addStatus($message);

    $form_state->setRedirect('entity.glad_widget.collection');
    return $status;
  }

  /**
   * Builds a widget preview URL.
   */
  protected function buildPreviewUrl(?string $baseUrl, ?string $uuid, string $subPage, ?string $token): string {
    if (empty($baseUrl) || empty($uuid)) {
      return 'about:blank';
    }
    $url = rtrim($baseUrl, '/') . "/widget/institutions/{$uuid}/{$subPage}";
    if (!empty($token)) {
      $url .= '?token=' . rawurlencode($token);
    }
    return $url;
  }

}
