<?php

namespace Drupal\reveal\Form;

use Drupal\Component\Utility\Xss;
use Drupal\Core\Datetime\DateFormatter;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Url;
use Drupal\diff\DiffEntityComparison;
use Drupal\node\NodeInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class RevealOverviewForm extends FormBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The date service.
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $date;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The diff entity comparison service.
   *
   * @var \Drupal\diff\DiffEntityComparison
   */
  protected $entityComparison;

  /**
   * Constructs a RevisionOverviewForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Datetime\DateFormatter $date
   *   The date service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\diff\DiffEntityComparison $entityComparison
   *   The diff entity comparison service.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, DateFormatter $date, RendererInterface $renderer, DiffEntityComparison $entityComparison) {
    $this->entityTypeManager = $entityTypeManager;
    $this->date = $date;
    $this->renderer = $renderer;
    $this->entityComparison = $entityComparison;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('date.formatter'),
      $container->get('renderer'),
      $container->get('diff.entity_comparison')
    );
  }

  /**
   * Returns a unique string identifying the form.
   *
   * @return string
   *   The unique string identifying the form.
   */
  public function getFormId() {
    return 'reveal_overview_form';
  }

  /**
   * Form constructor.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   *
   * @return array
   *   The form structure.
   */
  public function buildForm(array $form, FormStateInterface $form_state, $node = NULL) {
    /** @var NodeInterface $node */
    $header = [''];
    $row = [''];
    $langcodes = array_keys($node->getTranslationLanguages());
    foreach ($langcodes as $langcode) {
      $header[] = ['data' => $langcode, 'colspan' => 4, 'style' => 'text-align: center;'];
      $row[] = [''];
      $row[] = [
        'colspan' => 2,
        'class' => 'diff-link',
        'data-langcode' => $langcode,
        'data-text' => $this->t('Diff'),
        'style' => 'text-align: center;'
      ];
      $row[] = $this->t('Pick');
    }
    $build['node_revisions_table'] = array(
      '#type' => 'table',
      '#header' => $header,
      '#rows' => [['data' => $row, 'class' => ['reveal-top']]],
    );
    $nodeStorage = $this->entityTypeManager->getStorage('node');
    $query = $nodeStorage->getQuery()
      ->condition($node->getEntityType()->getKey('id'), $node->id())
      ->pager(50)
      ->allRevisions()
      ->sort($node->getEntityType()->getKey('revision'), 'DESC')
      ->execute();
    $vids = array_keys($query);
    /** @var NodeInterface[] $revisions */
    $revisions = array_map([$nodeStorage, 'loadRevision'], $vids);
    foreach ($vids as $key => $vid) {
      $revision = $revisions[$key];
      $previous_revision = isset($vids[$key + 1]) ? $revisions[$key + 1] : NULL;
      $row = ['revision' => $this->buildRevisionText($revision, $previous_revision)];
      foreach ($langcodes as $langcode) {
        $row[$langcode . '_view'] = ['#markup' => 'view'];
        $row['select_column_one_' . $langcode] = $this->buildRadio('radios_left', $langcode, $vid, FALSE);
        $row['select_column_two_' . $langcode] = $this->buildRadio('radios_right', $langcode, $vid, FALSE);
        $row['pick_' . $langcode] = $this->buildRadio('pick', $langcode, $langcode, FALSE);
      }
      $build['node_revisions_table'][] = $row;
    }
    $build['#attached']['library'][] = 'node/drupal.node.admin';
    $build['#attached']['library'][] = 'reveal/reveal.overview';
    $build['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $build;
  }

  protected function buildRadio($name, $langcode, $return_value, $default_value) {
    return [
      '#type' => 'radio',
      '#title_display' => 'invisible',
      '#name' => $name . '_' . $langcode,
      '#return_value' => $return_value,
      '#default_value' => $default_value,
    ];

  }

  protected function buildRevisionText( NodeInterface $revision, NodeInterface $previous_revision = NULL) {
    $username = ['#theme' => 'username', '#account' => $revision->getRevisionUser()];
    return [
      '#type' => 'inline_template',
      '#template' => '{% trans %}{{ date }} by {{ username }}{% endtrans %}{% if message %}<p class="revision-log">{{ message }}</p>{% endif %}',
      '#context' => [
        'date' => $this->date->format($revision->getRevisionCreationTime(), 'short'),
        'username' => $this->renderer->renderPlain($username),
        'message' => [
          '#markup' => $this->entityComparison->getRevisionDescription($revision, $previous_revision),
          '#allowed_tags' => Xss::getAdminTagList(),
        ],
      ],
    ];
  }

  /**
   * Form submission handler.
   *
   * @param array $form
   *   An associative array containing the structure of the form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   The current state of the form.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // TODO: Implement submitForm() method.
  }
}
