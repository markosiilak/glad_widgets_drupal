<?php

namespace Drupal\glad_widgets;

use Drupal\Core\Config\ConfigFactoryInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Log\LoggerInterface;

/**
 * Talks to the Glad API to discover which modules an institution can use.
 */
class GladApiClient {

  /**
   * Feature code => [sub-page, label].
   */
  protected const FEATURE_MAP = [
    'CATERING' => ['catering', 'Toitlustamine / menüü'],
    'DIARY' => ['diary', 'Päevik'],
    'STUDENTS' => ['students', 'Õpilased'],
    'TIMETABLE' => ['timetable', 'Tunniplaan'],
    'SUBJECTS' => ['subjects', 'Õppeained'],
    'GROUPS' => ['groups', 'Rühmad'],
    'CLASSES' => ['classes', 'Klassid'],
    'GRADING' => ['grades', 'Hindamine'],
    'EXAMS' => ['exams', 'Kontrolltööd'],
  ];

  public function __construct(
    protected ClientInterface $httpClient,
    protected ConfigFactoryInterface $configFactory,
    protected LoggerInterface $logger,
  ) {}

  /**
   * Returns available modules for the configured institution.
   *
   * @return array
   *   Array keyed by sub-page machine name => label. Empty on failure.
   */
  public function getAvailableModules(): array {
    $config = $this->configFactory->get('glad_widgets.settings');
    $uuid = $config->get('institution_uuid');
    $token = $config->get('api_token');
    $baseUrl = rtrim((string) $config->get('api_base_url'), '/');

    if (empty($uuid) || empty($baseUrl)) {
      return [];
    }

    try {
      $features = $this->fetchFeaturesByInstitution($baseUrl, $uuid, $token);
    }
    catch (GuzzleException $e) {
      $this->logger->error('Glad API request failed: @message', ['@message' => $e->getMessage()]);
      return [];
    }

    $modules = [];
    foreach (self::FEATURE_MAP as $code => [$subPage, $label]) {
      if (!empty($features[$code])) {
        $modules[$subPage] = $label;
      }
    }

    return $modules;
  }

  /**
   * Fetches the features-by-institution response.
   */
  protected function fetchFeaturesByInstitution(string $baseUrl, string $uuid, ?string $token): array {
    $headers = ['Accept' => 'application/json'];
    if (!empty($token)) {
      $headers['Authorization'] = 'Bearer ' . $token;
    }

    $response = $this->httpClient->request('GET', "{$baseUrl}/api/admin/institutions/{$uuid}/features-by-institution", [
      'headers' => $headers,
      'timeout' => 10,
    ]);

    $body = (string) $response->getBody();
    $data = json_decode($body, TRUE);

    return $data['features'] ?? [];
  }

  /**
   * Returns the human label for a module (sub-page) machine name.
   */
  public function getModuleLabel(string $subPage): string {
    foreach (self::FEATURE_MAP as [$mappedSubPage, $label]) {
      if ($mappedSubPage === $subPage) {
        return $label;
      }
    }
    return $subPage;
  }

}
