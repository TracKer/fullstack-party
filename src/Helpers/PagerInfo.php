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

  /**
   * PagerInfo constructor.
   *
   * @param int $pagesCount
   *   Pages count.
   * @param int $issuesCount
   *   Issues count.
   */
  public function __construct($pagesCount, $issuesCount) {
    $this->pages = $pagesCount;
    $this->issues = $issuesCount;
  }
}
