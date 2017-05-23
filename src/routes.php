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

    try {
      /** @var GithubResourceOwner $user */
      $user = $provider->getResourceOwner($token);
      $_SESSION['user'] = $user;
    } catch (Exception $e) {
      throw new Exception('Failed to get user details!');
    }

    return $response->withStatus(302)->withHeader('Location', '/issues/open');
  }
});

// Front page.
$app->get('/', function($request, $response, $args) {
  /** @var Twig_Environment $twig */
  $twig = $this->twig;
  return $twig->render('index.html.twig', $args);
});

// Opened issues.
$app->get('/issues/open', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
  if (!isset($_SESSION['token'])) {
    return $response->withStatus(302)->withHeader('Location', '/');
  }

  /** @var GithubResourceOwner $user */
  $user = $_SESSION['user'];

  /** @var Twig_Environment $twig */
  $twig = $this->twig;
  return $twig->render('issues/open.html.twig', [
    'user' => $user,
  ]);
});

// Closed issues.
$app->get('/issues/closed', function(ServerRequestInterface $request, ResponseInterface $response, $args) {
  if (!isset($_SESSION['token'])) {
    return $response->withStatus(302)->withHeader('Location', '/');
  }

  /** @var GithubResourceOwner $user */
  $user = $_SESSION['user'];

  /** @var Twig_Environment $twig */
  $twig = $this->twig;
  return $twig->render('issues/closed.html.twig', [
    'user' => $user,
  ]);
});
