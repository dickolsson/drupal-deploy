<?php

/**
 * @file
 * JSON REST client for clearing caches.
 */

module_load_include('inc', 'deploy', 'includes/DeployServiceRest');

/**
 * Class for calling Rest Service.
 */
class DeployRemoteCcServiceRestJSON extends \DeployServiceRest {

  /**
   * {@inheritdoc}
   */
  public function __construct(array $config = array()) {
    parent::__construct($config);
    // Clearing caches takes time overriding the default value.
    $this->config['timeout'] = 120.0;
  }

  /**
   * {@inheritdoc}
   */
  public function deploy(\Traversable $entities) {
    foreach ($entities->entities as $entity) {
      $this->config['headers']['Content-Type'] = 'application/json';

      // We only want the scheme and host part of any saved URL.
      $url = parse_url($this->config['url']);
      $url = $url['scheme'] . "://" . $url['host'] . "/";
      $url .= $entities->buildPath(array($entity));

      $this->httpRequest($url, 'PUT');
    }
  }

  /**
   * Provides access to the context to be able to use it elsewhere.
   *
   * The fetchContext() function is protected however it would be useful to be
   * able to use it outside of the class.
   *
   * @return resource
   *   The stream context resource.
   */
  public function getContext() {
    return parent::fetchContext();
  }

  /**
   * Make a HTTP request.
   *
   * @param string $url
   *   The URL to use for the request.
   * @param string $method
   *   The method of request, 'GET', 'POST' or 'PUT'.
   * @param null|array $data
   *   The data to send with the request.
   *
   * @return mixed
   *   The data retrieved from the request.
   *
   * @throws \DeployServiceException
   *   Will throw a DeployServiceException if there are any issues with the
   *   http request.
   */
  public function httpRequest($url, $method, $data = NULL) {
    $this->config['headers']['Content-Type'] = 'application/json';

    $options = array(
      'method' => $method,
      'headers' => $this->config['headers'],
      'data' => $data,
      'context' => $this->config['context'],
      'timeout' => (float) $this->config['timeout'],
    );

    if ($this->config['debug']) {
      watchdog('deploy', 'Service request: %url <pre>@options</pre>', array(
        '%url' => $url,
        '@options' => print_r($options, TRUE),
      ), WATCHDOG_DEBUG
      );
    }

    $response = drupal_http_request($url, $options);

    if ($this->config['debug']) {
      watchdog('deploy', 'Service response: <pre>@response</pre>', array('@response' => print_r($response, TRUE)), WATCHDOG_DEBUG);
    }
    if (isset($response->error) || !in_array($response->code, array(200, 304))) {
      throw new \DeployServiceException(t('Service error: @code @error', array('@code' => $response->code, '@error' => $response->error)));
    }

    return drupal_json_decode($response->data);
  }

}
