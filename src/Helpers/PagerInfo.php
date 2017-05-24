<?php

namespace Helpers;

class PagerInfo {
  /**
   * Pages count.
   * @var int
   */
  public $pages;

  /**
   * Issues count.
   * @var int
   */
  public $issues;

  public function __construct($pagesCount, $issuesCount) {
    $this->pages = $pagesCount;
    $this->issues = $issuesCount;
  }
}
