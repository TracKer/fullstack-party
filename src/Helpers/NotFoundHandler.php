<?php

namespace Helpers;

use GuzzleHttp\Exception\RequestException;
use Pimple\Container;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NotFoundHandler {
  /**
   * Service container.
   * @var Container
   */
  private $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

  public function __invoke(ServerRequestInterface $request, ResponseInterface $response) {
    /** @var \Twig_Environment $twig */
    $twig = $this->container['twig'];
    $renderedContent = $twig->render('404.html.twig', [
      'logged_in' => isset($_SESSION['token']),
    ]);

    return $this->container['response']
      ->withStatus(404)
      ->withHeader('Content-Type', 'text/html')
      ->write($renderedContent);
  }
}
