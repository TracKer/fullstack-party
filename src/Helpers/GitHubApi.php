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

  public function getPagerInfo(string $repo, string $state, string $token = null) {
    // Get first page.
    $response = $this->get("repos/{$repo}/issues?state={$state}&per_page=100&page=1", $token);
    $data = json_decode($response->getBody()->getContents(), true);

    // If data is empty of json parsing failed.
    if ((!isset($data)) || empty($data)) {
      return new PagerInfo(0, 0);
    }

    // If issues count on page less then 100, then it's amount of all items, and there is only one page.
    // Or, if there is no Link header, the issues amount is the amount of issues on first page.
    if ((count($data) < 100) || (!$response->hasHeader('Link'))) {
      return new PagerInfo(1,count($data));
    }

    preg_match('/<[^>]+[?&]page=(\d+)[^>]*>;\s*rel="last"/', reset($response->getHeader('Link')), $matches);
    if (!isset($matches[1])) {
      // Error.
      return new PagerInfo(0, 0);
    }

    $pagesCount = $matches[1];

    // Get last page.
    $response = $this->get("repos/{$repo}/issues?state={$state}&per_page=100&page={$pagesCount}");
    $data = json_decode($response->getBody()->getContents(), true);

    if ((!isset($data)) || empty($data)) {
      // Error.
      return new PagerInfo(0, 0);
    }

    $issuesCount = (($pagesCount - 1) * 100) + count($data);
    return new PagerInfo($pagesCount, $issuesCount);
  }

  public function getIssuesList(string $repo, string $state, int $page = 1, string $token = null) {
    $response = $this->get("repos/{$repo}/issues?state={$state}&per_page=100&page={$page}", $token);
    $data = json_decode($response->getBody()->getContents(), true);

    // If data is empty of json parsing failed.
    if ((!isset($data)) || empty($data)) {
      return [];
    }

    return $data;
  }

}
