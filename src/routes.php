<?php
// Routes

// Front page.
$app->get('/', function($request, $response, $args) {
  /** @var Twig_Environment $twig */
  $twig = $this->renderer;
  return $twig->render('index.html.twig', $args);
});

// Opened issues.
$app->get('/issues/open', function($request, $response, $args) {
  /** @var Twig_Environment $twig */
  $twig = $this->renderer;
  return $twig->render('issues/open.html.twig', $args);
});

// Closed issues.
$app->get('/issues/open', function($request, $response, $args) {
  /** @var Twig_Environment $twig */
  $twig = $this->renderer;
  return $twig->render('issues/closed.html.twig', $args);
});
