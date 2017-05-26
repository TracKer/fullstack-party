<?php

namespace Exception;

use Throwable;

class ApiFail extends \Exception {
  public function __construct($message = "", $code = 0, Throwable $previous = null) {
    parent::__construct("API request failed!\n" . $message, $code, $previous);
  }
}
