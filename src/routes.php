<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Routes.

// GitHub authorization.
$app->get('/auth', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
  if (isset($_SESSION['token'])) {
    return $response->withStatus(302)->withHeader('Location', '/issues/open');
  }
  $query = $request->getQueryParams();

  if (!isset($query['code'])) {
    // Redirect user to authorization page.
    $_SESSION['last_state'] = md5(time() . 31337);
    $requestQuery = [
      'state' => $_SESSION['last_state'],
      'redirect_uri' => 'http://tesonet-test.local/auth',
      'client_id' => $this->settings['github']['clientId'],
    ];
    $uri = 'https://github.com/login/oauth/authorize?' . http_build_query($requestQuery);
    return $response->withStatus(302)->withHeader('Location', $uri);
  }
  elseif (!isset($query['state']) || ($query['state'] !== $_SESSION['last_state'])) {
    // If state is not the same as was sent to GitHub, the request must be aborted.
    unset($_SESSION['last_state']);
    throw new \Exception\AuthorizationFail('Invalid state received!');
  }
  else {
    // Try to get an access token using the temporary code.
    $guzzle = new \GuzzleHttp\Client(['base_uri' => 'https://github.com/']);
    $response = $guzzle->post('login/oauth/access_token', [
      'headers' => [
        'Accept' => 'application/json',
        'Content-Type' => 'application/json'
      ],
      'body' => json_encode([
        'client_id' => $this->settings['github']['clientId'],
        'client_secret' => $this->settings['github']['clientSecret'],
        'code' => $query['code'],
        'state' => $_SESSION['last_state'],
      ])
    ]);
    $data = json_decode($response->getBody()->getContents(), true);

    // If data is empty or json parsing failed.
    if (!isset($data['access_token'])) {
      unset($_SESSION['last_state']);
      throw new \Exception\AuthorizationFail('Can not get access token from GitHub response!');
    }

    unset($_SESSION['last_state']);
    $_SESSION['token'] = $data['access_token'];

    return $response->withStatus(302)->withHeader('Location', '/issues/open');
  }
});

// Front page.
$app->get('/', function($request, $response, $args) {
  if (isset($_SESSION['token'])) {
    return $response->withStatus(302)->withHeader('Location', '/issues/open');
  }

  /** @var Twig_Environment $twig */
  $twig = $this->twig;
  return $twig->render('index.html.twig', $args);
});

// Issues list.
$app->get('/issues/{state}[/{page:[0-9]+}]', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
  if (!isset($_SESSION['token'])) {
    return $response->withStatus(302)->withHeader('Location', '/');
  }

  $state = $args['state'];
  $page = isset($args['page']) ? $args['page'] : 1;

  /** @var \Helpers\GitHubApi $githubApi */
  $githubApi = $this->githubApi;
  $openPagerInfo = $githubApi->getPagerInfo('symfony/symfony', 'open', $_SESSION['token']);
  $closedPagerInfo = $githubApi->getPagerInfo('symfony/symfony', 'closed', $_SESSION['token']);
  $issues = $githubApi->getIssuesList('symfony/symfony', $state, $page, $_SESSION['token']);

  /** @var Twig_Environment $twig */
  $twig = $this->twig;
  return $twig->render('issues/list.html.twig', [
    'state' => $state,
    'issues' => $issues,
    'currentPage' => $page,
    'lastPage' => $state == 'open' ? $openPagerInfo->pages : $closedPagerInfo->pages,
    'openIssuesCount' => $openPagerInfo->issues,
    'closedIssuesCount' => $closedPagerInfo->issues,
  ]);
});

// Issue.
$app->get('/issue/{id}', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
  if (!isset($_SESSION['token'])) {
    return $response->withStatus(302)->withHeader('Location', '/');
  }

  /** @var \Helpers\GitHubApi $githubApi */
  $githubApi = $this->githubApi;
  $issue = $githubApi->getIssue('symfony/symfony', $args['id'], $_SESSION['token']);

  if ($request->hasHeader('HTTP_REFERER')) {
    $referer = reset($request->getHeader('HTTP_REFERER'));
  } else {
    $referer = '/issues/' . $issue['state'];
  }

  /** @var Twig_Environment $twig */
  $twig = $this->twig;
  return $twig->render('issue.html.twig', [
    'issue' => $issue,
    'referer' => $referer,
  ]);
});

$app->get('/logout', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
  unset($_SESSION['token']);
  return $response->withStatus(302)->withHeader('Location', '/');
});
