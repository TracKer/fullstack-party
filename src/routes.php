<?php
// Routes
$app->get('/[{name}]', function ($request, $response, $args) {
  /** @var Twig_Environment $twig */
  $twig = $this->renderer;
  return $twig->render('base.html.twig', $args);
});
