<?php

use League\OAuth2\Client\Provider\Github;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Routes.

// GitHub authorization.
$app->get('/auth', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
  /** @var Github $provider */
  $provider = $this->github;

  $query = $request->getQueryParams();

  if (!isset($query['code'])) {
    $authUrl = $provider->getAuthorizationUrl();
    $_SESSION['last_state'] = $provider->getState();
    return $response->withStatus(302)->withHeader('Location', $authUrl);
  }
  elseif ((!isset($query['state'])) || ($query['state'] !== $_SESSION['last_state'])) {
    unset($_SESSION['last_state']);
    throw new Exception('Invalid state!');
  }
  else {
    // Try to get an access token (using the authorization code grant).
    $token = $provider->getAccessToken('authorization_code', ['code' => $query['code']]);
    $_SESSION['token'] = $token;

    return $response->withStatus(302)->withHeader('Location', '/issues/open');
  }
});

// Front page.
$app->get('/', function($request, $response, $args) {
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
