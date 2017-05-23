<?php
return [
  'settings' => [
    'displayErrorDetails' => true, // set to false in production
    'addContentLengthHeader' => false, // Allow the web server to send the content-length header

    // Twig settings
    'twig' => [
      'template_paths' => [
        SITE_ROOT . '/templates/'
      ],
    ],
  ],
];
