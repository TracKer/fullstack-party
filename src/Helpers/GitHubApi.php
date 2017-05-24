<?php

namespace Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class GitHubApi {
  private $client;
  private $cache;

  public function __construct(Client $client, ResponseCache $cache) {
    $this->client = $client;
    $this->cache = $cache;
  }

  /**
   * Executes API request with caching.
   *
   * @param string $uri
   *   Uri.
   * @param string|null $token
   *   Access token.
   * @return Response
   *   Data.
   */
  public function get(string $uri, string $token = null): Response {
    $cacheKey = $uri;

    $response = $this->cache->get($cacheKey);
    if ($response !== null) {
      return $response;
    }

    if (isset($token)) {
      $uri = self::addTokenToUri($uri, $token);
    }

    /** @var Response $response */
    $response = $this->client->get($uri);
    $this->cache->set($cacheKey, $response);
    return $response;
  }

  /**
   * Appends token to the Uri.
   *
   * @param string $uri
   *   Uri.
   * @param string $token
   *   Token.
   * @return string
   *   Uri with token.
   */
  private static function addTokenToUri(string $uri, string $token): string {
    if (strpos($uri, '?') !== false) {
      return "{$uri}&access_token={$token}";
    }

    return "{$uri}?access_token={$token}";
  }

}
