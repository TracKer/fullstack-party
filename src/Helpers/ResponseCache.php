<?php

namespace Helpers;

use GuzzleHttp\Psr7\Response;

class ResponseCache {
  private $cache;

  public function __construct() {
    if (!isset($_SESSION['response_cache'])) {
      $_SESSION['response_cache'] = [];
    }
    $this->cache = &$_SESSION['response_cache'];
    $this->cleanExpired();
  }

  /**
   * Tries to get Response object from cache.
   *
   * @param string $key
   *   Cache key.
   * @return Response|null
   *   Returns Response object if cached, or null otherwise.
   */
  public function get(string $key) {
    if (!isset($this->cache[$key])) {
      return null;
    }

    $data = $this->cache[$key];
    $response = new Response(
      $data['data']['status'],
      $data['data']['header'],
      $data['data']['body'],
      $data['data']['version'],
      $data['data']['reason']
    );

    return $response;
  }

  /**
   * Saves Response object to cache.
   *
   * @param string $key
   *   Cache key.
   * @param Response $response
   *   Response object.
   */
  public function set(string $key, Response &$response) {
    $data = [
      'expire' => time() + 60*5,
      'data' => [
        'status' => $response->getStatusCode(),
        'header' => $response->getHeaders(),
        'body' => $response->getBody()->getContents(),
        'version' => $response->getProtocolVersion(),
        'reason' => $response->getReasonPhrase(),
      ],
    ];

    $this->cache[$key] = $data;
    $response = $this->get($key);
  }

  /**
   * Removes expired objects from cache.
   */
  public function cleanExpired() {
    $now = time();
    foreach ($this->cache as $key => $data) {
      if ($now > $data['expire']) {
        unset($this->cache[$key]);
      }
    }
  }
}
