<?php
// DIC configuration
$container = $app->getContainer();
// view renderer
$container['renderer'] = function ($c) {
  $paths = $c->get('settings')['renderer']['template_paths'];
  $loader = new \Twig_Loader_Filesystem($paths);

  $twig = new Twig_Environment($loader, array(
    'cache' => new Twig_Cache_Filesystem('/tmp/twig'),
    'autoescape' => FALSE,
    'auto_reload' => TRUE,
    'debug' => TRUE,
  ));

//  $this->twig->addExtension(new Extension());
//  $this->twig->addExtension(new FormsExtension());
//
//  if ($this->twig->isDebug()) {
//    $this->twig->addExtension(new DebugPanelExtension());
//  }



  return $twig;
};
// monolog
//$container['logger'] = function ($c) {
//  $settings = $c->get('settings')['logger'];
//  $logger = new Monolog\Logger($settings['name']);
//  $logger->pushProcessor(new Monolog\Processor\UidProcessor());
//  $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));
//  return $logger;
//};
