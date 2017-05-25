<?php

namespace Helpers;

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\Response;

class GitHubApi {
  private $client;
  private $cache;
  private $issuesPerPage;

  /**
   * GitHubApi constructor.
   * @param array $config
   *   Config.
   * @param Client $client
   *   Guzzle object.
   * @param ResponseCache $cache
   */
  public function __construct(array $config, Client $client, ResponseCache $cache) {
    $this->client = $client;
    $this->cache = $cache;
    $this->issuesPerPage = isset($config['issues_per_page']) ? $config['issues_per_page'] : 30;
  }

  /**
   * Executes API request with caching.
   *
   * @param string $uri
   *   Uri.
   * @param string[] $query
   *   Query parameters.
   * @param string|null $token
   *   Access token.
   * @return Response
   *   Data.
   */
  public function get(string $uri, array $query = [], string $token = null): Response {
    ksort($query);
    $queryString = http_build_query($query);
    $cacheKey = "{$uri}?$queryString";

    $response = $this->cache->get($cacheKey);
    if ($response !== null) {
      return $response;
    }

    if (isset($token)) {
      $query['access_token'] = $token;
    }

    /** @var Response $response */
    $response = $this->client->get($uri, ['query' => $query]);
    $this->cache->set($cacheKey, $response);
    return $response;
  }

  /**
   * Gets pager information.
   *
   * @param string $repo
   *   Repository.
   * @param string $state
   *   Issue state 'open' or 'closed'.
   * @param string|null $token
   *   Access token.
   * @return PagerInfo
   *   Object with information about issues count and pages count.
   */
  public function getPagerInfo(string $repo, string $state, string $token = null) {
    // Get first page.
    $response = $this->get("repos/{$repo}/issues", [
      'state' => $state,
      'per_page' => $this->issuesPerPage,
      'page' => 1,
    ], $token);
    $data = json_decode($response->getBody()->getContents(), true);

    // If data is empty of json parsing failed.
    if ((!isset($data)) || empty($data)) {
      return new PagerInfo(0, 0);
    }

    // *IPP = $this->issuesPerPage
    // If issues count on page less then IPP, then it's amount of all items, and there is only one page.
    // Or, if there is no Link header, the issues amount is the amount of issues on first page.
    if ((count($data) < $this->issuesPerPage) || (!$response->hasHeader('Link'))) {
      return new PagerInfo(1,count($data));
    }

    preg_match('/<[^>]+[?&]page=(\d+)[^>]*>;\s*rel="last"/', reset($response->getHeader('Link')), $matches);
    if (!isset($matches[1])) {
      // Error.
      return new PagerInfo(0, 0);
    }

    $pagesCount = $matches[1];

    // Get last page.
    $response = $this->get("repos/{$repo}/issues", [
      'state' => $state,
      'per_page' => $this->issuesPerPage,
      'page' => $pagesCount,
    ], $token);
    $data = json_decode($response->getBody()->getContents(), true);

    if ((!isset($data)) || empty($data)) {
      // Error.
      return new PagerInfo(0, 0);
    }

    $issuesCount = (($pagesCount - 1) * $this->issuesPerPage) + count($data);
    return new PagerInfo($pagesCount, $issuesCount);
  }

  /**
   * Gets issues list.
   *
   * @param string $repo
   *   Repository.
   * @param string $state
   *   Issue state 'open' or 'closed'.
   * @param int $page
   *   Page number.
   * @param string|null $token
   *   Access token.
   * @return array
   *   List of issues.
   */
  public function getIssuesList(string $repo, string $state, int $page = 1, string $token = null) {
    $response = $this->get("repos/{$repo}/issues", [
      'state' => $state,
      'per_page' => $this->issuesPerPage,
      'page' => $page,
    ], $token);
    $data = json_decode($response->getBody()->getContents(), true);

    // If data is empty of json parsing failed.
    if ((!isset($data)) || empty($data)) {
      return [];
    }

    foreach ($data as &$issue) {
      $issue['created_at'] = strtotime($issue['created_at']);
      $issue['updated_at'] = strtotime($issue['updated_at']);
      if (isset($issue['closed_at'])) {
        $issue['closed_at'] = strtotime($issue['closed_at']);
      }

      foreach ($issue['labels'] as &$label) {
        $label['text_color'] = self::getContrastColor(ltrim($label['color'], '#'));
      }
    }

    return $data;
  }

  /**
   * Calculates which text color is looking better on specific background color.
   *
   * @param $hexColor
   *   Color.
   * @return string
   *   Calculated color.
   */
  private static function getContrastColor($hexColor) {
    if (strlen($hexColor) == 3) {
      $hexColor = $hexColor[0].$hexColor[0].$hexColor[1].$hexColor[1].$hexColor[2].$hexColor[2];
    }

    //////////// hexColor RGB
    $R1 = hexdec(substr($hexColor, 0, 2));
    $G1 = hexdec(substr($hexColor, 2, 2));
    $B1 = hexdec(substr($hexColor, 4, 2));

    //////////// Black RGB
    $blackColor = "#000000";
    $R2BlackColor = hexdec(substr($blackColor, 0, 2));
    $G2BlackColor = hexdec(substr($blackColor, 2, 2));
    $B2BlackColor = hexdec(substr($blackColor, 4, 2));

    //////////// Calc contrast ratio
    $L1 = 0.2126 * pow($R1 / 255, 2.2) +
      0.7152 * pow($G1 / 255, 2.2) +
      0.0722 * pow($B1 / 255, 2.2);

    $L2 = 0.2126 * pow($R2BlackColor / 255, 2.2) +
      0.7152 * pow($G2BlackColor / 255, 2.2) +
      0.0722 * pow($B2BlackColor / 255, 2.2);

    $contrastRatio = 0;
    if ($L1 > $L2) {
      $contrastRatio = (int)(($L1 + 0.05) / ($L2 + 0.05));
    } else {
      $contrastRatio = (int)(($L2 + 0.05) / ($L1 + 0.05));
    }

    //////////// If contrast is more than 5, return black color
    if ($contrastRatio > 5) {
      return '000000';
    } else { //////////// if not, return white color.
      return 'ffffff';
    }
  }

}
