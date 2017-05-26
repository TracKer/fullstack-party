<?php
return [
  'settings' => [
    'displayErrorDetails' => true, // set to false in production
    'addContentLengthHeader' => false, // Allow the web server to send the content-length header

    // Twig settings.
    'twig' => [
      'template_paths' => [
        SITE_ROOT . '/templates/'
      ],
    ],

    // Guzzle settings.
    'guzzle' => [
      'base_uri' => 'https://api.github.com/',
    ],

    // GitHub settings.
    'github' => [
      'clientId' => '587b512a6831b5acae95',
      'clientSecret' => '170da7bfe3a52a13d249c956fc0f9e921ea4d147',
      'redirectUri' => 'http://tesonet-test.local/auth',
      'issues_per_page' => 30,
    ],
  ],
];
