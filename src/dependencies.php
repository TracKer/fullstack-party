<?php
// Dependency Injection Container configuration.
$container = $app->getContainer();

// Twig service.
$container['twig'] = function ($c) {
  $paths = $c->get('settings')['twig']['template_paths'];
  $loader = new \Twig_Loader_Filesystem($paths);

  $twig = new Twig_Environment($loader, array(
    'cache' => new Twig_Cache_Filesystem('/tmp/twig'),
    'autoescape' => FALSE,
    'auto_reload' => TRUE,
    'debug' => TRUE,
  ));

//  $twig->addExtension(new Twig_Extension_Debug());
  $twig->addExtension(new Twig_Extensions_Extension_Date());

  return $twig;
};

// Guzzle Client service.
$container['guzzle'] = function ($c) {
  return new GuzzleHttp\Client($c->get('settings')['guzzle']);
};

// GitHub API service.
$container['githubApi'] = function ($c) {
  return new \Helpers\GitHubApi(
    ['issues_per_page' => $c->get('settings')['github']['issues_per_page']],
    $c->get('guzzle'),
    new \Helpers\ResponseCache()
  );
};

// Error handler.
$container['errorHandler'] = function ($container) {
  return new \Helpers\ErrorHandler($container);
};

$container['phpErrorHandler'] = function ($container) {
  return new \Helpers\ErrorHandler($container);
};
