<?php

namespace Exception;

use Throwable;

class AuthorizationFail extends \Exception {
  public function __construct($message = "", $code = 0, Throwable $previous = null) {
    parent::__construct("Authorization failed!\n" . $message, $code, $previous);
  }
}
