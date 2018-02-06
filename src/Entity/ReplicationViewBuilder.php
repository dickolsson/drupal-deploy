<?php

namespace Drupal\deploy\Entity;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * ReplicationViewBuilder class.
 */
class ReplicationViewBuilder extends EntityViewBuilder {

  /**
   * FormBuilderInterface dependency injection object.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * Constructor class.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   Entity Type Interface object.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   Entity Plug-in Manager object.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   Language Plug-in Manager object.
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   Form Builder object.
   */
  public function __construct(EntityTypeInterface $entity_type, EntityManagerInterface $entity_manager, LanguageManagerInterface $language_manager, FormBuilderInterface $form_builder) {
    parent::__construct($entity_type, $entity_manager, $language_manager);
    $this->formBuilder = $form_builder;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode) {
    $build = parent::getBuildDefaults($entity, $view_mode);
    unset($build['#theme']);
    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function view(EntityInterface $entity, $view_mode = 'FULL', $langcode = NULL) {
    $build = parent::view($entity, $view_mode, $langcode);

    $source = $entity->get('source')->entity ? $entity->get('source')->entity->label() : $this->t('<em>Archived</em>');
    $target = $entity->get('target')->entity ? $entity->get('target')->entity->label() : $this->t('<em>Archived</em>');
    $build['info'] = [
      '#prefix' => '<p>',
      '#markup' => $this->t('@source to @target', ['@source' => $source, '@target' => $target]),
      '#suffix' => '</p>',
    ];
    $form = $this->formBuilder->getForm('\Drupal\deploy\Form\ReplicationActionForm', $entity);
    $build['form'] = $form;

    return $build;
  }

}
