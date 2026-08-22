<?php

namespace Drupal\glad_widgets\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configuration form for institution UUID, API token and preview.
 */
class GladWidgetsSettingsForm extends ConfigFormBase {

  const UUID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i';

  public function __construct(
    $configFactory,
    protected \Drupal\glad_widgets\GladApiClient $apiClient,
  ) {
    parent::__construct($configFactory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('glad_widgets.api_client'),
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['glad_widgets.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'glad_widgets_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('glad_widgets.settings');

    $form['#attached']['library'][] = 'glad_widgets/preview';

    $form['institution_uuid'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Asutuse UUID'),
      '#default_value' => $config->get('institution_uuid'),
      '#required' => TRUE,
      '#description' => $this->t('Nt. 5ea30cbc-0b37-4d9f-8912-0ba305040000'),
      '#attributes' => ['class' => ['glad-widgets-preview-trigger'], 'data-preview-field' => 'uuid'],
    ];

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API token'),
      '#default_value' => $config->get('api_token'),
      '#description' => $this->t('Valikuline. Ilma tokenita on nähtavad ainult avalikud andmed.'),
      '#attributes' => ['class' => ['glad-widgets-preview-trigger'], 'data-preview-field' => 'token'],
    ];

    $form['default_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Vaikimisi kõrgus (px)'),
      '#default_value' => $config->get('default_height') ?: 600,
      '#min' => 100,
      '#max' => 3000,
      '#required' => TRUE,
    ];

    $form['api_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('API baas-URL'),
      '#default_value' => $config->get('api_base_url'),
      '#required' => TRUE,
    ];

    $form['widget_base_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Widgeti baas-URL'),
      '#default_value' => $config->get('widget_base_url'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['glad-widgets-preview-trigger'], 'data-preview-field' => 'base_url'],
    ];

    $modules = $this->apiClient->getAvailableModules();
    $previewSubPage = array_key_first($modules) ?: 'catering';

    $form['preview'] = [
      '#type' => 'details',
      '#title' => $this->t('Live eelvaade'),
      '#open' => TRUE,
    ];

    $form['preview']['info'] = [
      '#markup' => $modules
        ? $this->t('Asutusele on saadaval @count moodulit: @list', [
          '@count' => count($modules),
          '@list' => implode(', ', $modules),
        ])
        : $this->t('Mooduleid ei leitud. Kontrolli UUID-d ja tokenit, seejärel salvesta.'),
      '#prefix' => '<p>',
      '#suffix' => '</p>',
    ];

    $form['preview']['preview_module'] = [
      '#type' => 'select',
      '#title' => $this->t('Vaade'),
      '#options' => $modules,
      '#default_value' => $previewSubPage,
      '#access' => !empty($modules),
      '#attributes' => ['class' => ['glad-widgets-preview-trigger'], 'data-preview-field' => 'sub_page'],
    ];

    $form['preview']['frame_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'glad-widgets-preview-wrapper'],
    ];

    $form['preview']['frame_wrapper']['iframe'] = [
      '#type' => 'inline_template',
      '#template' => '<iframe id="glad-widgets-preview-iframe" src="{{ src }}" width="100%" height="{{ height }}" frameborder="0" loading="lazy" data-base-url="{{ base_url }}" data-sub-page="{{ sub_page }}"></iframe>',
      '#context' => [
        'src' => $this->buildPreviewUrl(
          $config->get('widget_base_url'),
          $config->get('institution_uuid'),
          $previewSubPage,
          $config->get('api_token')
        ),
        'height' => $config->get('default_height') ?: 600,
        'base_url' => $config->get('widget_base_url'),
        'sub_page' => $previewSubPage,
      ],
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    $uuid = $form_state->getValue('institution_uuid');
    if (!preg_match(self::UUID_PATTERN, $uuid)) {
      $form_state->setErrorByName('institution_uuid', $this->t('Asutuse UUID ei ole korrektses formaadis.'));
    }

    $token = $form_state->getValue('api_token');
    if (!empty($token) && strlen($token) < 20) {
      $form_state->setErrorByName('api_token', $this->t('API token peab olema vähemalt 20 tähemärki.'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('glad_widgets.settings')
      ->set('institution_uuid', $form_state->getValue('institution_uuid'))
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('default_height', $form_state->getValue('default_height'))
      ->set('api_base_url', rtrim($form_state->getValue('api_base_url'), '/'))
      ->set('widget_base_url', rtrim($form_state->getValue('widget_base_url'), '/'))
      ->save();

    parent::submitForm($form, $form_state);
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
