<?php

namespace Helpers;

use Pimple\Container;

class ErrorHandler {
  /**
   * Service container.
   * @var Container
   */
  private $container;

  public function __construct(Container $container) {
    $this->container = $container;
  }

  public function __invoke($request, $response, \Exception $exception) {
    if ($exception instanceof \GuzzleHttp\Exception\RequestException) {
      if (!$exception->hasResponse()) {
        $errorText = nl2br($exception->getMessage());
      } else {
        $data = json_decode($exception->getResponse()->getBody()->getContents(), true);
        if (!isset($data['message'])) {
          $errorText = nl2br($exception->getMessage());
        } else {
          $errorText = nl2br($data['message']);
          if (isset($data['documentation_url'])) {
            $uri = $data['documentation_url'];
            $errorText .= "<br>Please see more info here: <a href='{$uri}'>{$uri}</a>.";
          }
        }
      }
    }
    else {
      $errorText = nl2br($exception->getMessage());
    }

    /** @var \Twig_Environment $twig */
    $twig = $this->container['twig'];
    $renderedContent = $twig->render('error.html.twig', [
      'error_text' => $errorText,
      'logged_in' => isset($_SESSION['token']),
    ]);

    return $this->container['response']
      ->withStatus(500)
      ->withHeader('Content-Type', 'text/html')
      ->write($renderedContent);
  }
}