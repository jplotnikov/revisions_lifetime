<?php

namespace Drupal\revisions_lifetime;

/**
 * @file
 * Contains Drupal\revisions_lifetime\RevisionsLifetime.
 */

use Drupal\Core\Database\Database;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class RevisionLifetimeCleanup.
 */
class RevisionLifetimeCleanup {

  /**
   * An instance of the "entity_type.manager" service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The config object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Constructs this RevisionsLifetime object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Instance of the "entity_type.manager" service.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config
   *   The configuration factory object to use.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, ConfigFactoryInterface $config) {
    $this->entityTypeManager = $entity_type_manager;
    $this->config = $config;
  }

  /**
   * Delete old revisions.
   */
  public function deleteOldRevision() {
    // Get period, quantity items from config and calculate delete age.
    $period = $this->config->get('revisions_lifetime.settings')->get('period');
    $quantity_items = $this->config->get('revisions_lifetime.settings')->get('quantity_items');
    $deleted_age = time() - $period * $quantity_items;

    // Get delete content types from config.
    $content_types = $this->config->get('revisions_lifetime.settings')->get('content_types_items');

    // Selection of revisions suitable for the condition.
    $revisions = Database::getConnection()->select('node_revision', 'r')
      ->fields('r', ['nid', 'vid']);
    $revisions->addJoin('left', 'node', 'n', 'r.nid=n.nid');
    $revisions->fields('n', ['vid']);
    $revisions->condition('n.type', $content_types, 'IN');
    $revisions->condition('r.revision_timestamp', $deleted_age, '<');
    $data = $revisions->execute();
    $result = $data->fetchAll(\PDO::FETCH_OBJ);

    if (!empty($result)) {
      foreach ($result as $value) {
        // Delete revisions if it is not the last.
        if ($value->vid != $value->n_vid) {
          $this->entityTypeManager->getStorage('node')->deleteRevision($value->vid);
        }
      }
    }
  }

}
