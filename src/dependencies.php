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

  return $twig;
};

$container['guzzle'] = function ($c) {
  return new GuzzleHttp\Client($c->get('settings')['guzzle']);
};


$container['github'] = function ($c) {
  $provider = new \League\OAuth2\Client\Provider\Github($c->get('settings')['github']);
  return $provider;
};
