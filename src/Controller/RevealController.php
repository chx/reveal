<?php

namespace Drupal\reveal\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Drupal\reveal\Form\RevealOverviewForm;

class RevealController extends ControllerBase {

  public function overview(NodeInterface $node) {
    return $this->formBuilder()->getForm(RevealOverviewForm::class, $node);
  }
}
