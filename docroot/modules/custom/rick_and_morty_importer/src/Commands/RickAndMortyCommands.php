<?php

namespace Drupal\rick_and_morty_importer\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drush\Commands\DrushCommands;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drupal\media\Entity\Media;
use Drupal\Core\File\FileSystemInterface;

/**
 * Provides commands to control rick_and_morty_importer actions.
 */
class RickAndMortyCommands extends DrushCommands {
  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeManager() {
    return $this->entityTypeManager;
  }

  /**
   * RickAndMortyCommands constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entityTypeManager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Drush command to import content from api.
   *
   * @command rick_and_morty_importer:importContent
   * @aliases rick_and_morty_importer-importContent
   * @usage rick_and_morty_importer:importContent
   */

  public function importContent() {
    $node_storage = $this->entityTypeManager->getStorage('node');
    $media_storage = $this->entityTypeManager->getStorage('media');
    $page = 1;

    do {
      $url = 'https://rickandmortyapi.com/api/character?page=' . $page;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPGET, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response_json = curl_exec($ch);
      curl_close($ch);

      $response = json_decode($response_json, true);

      if ($response["results"] != NULL) {
        $results = $response["results"];

        foreach ($results as $key => $value) {
          $nid = $node_storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('type', ['character'], 'IN')
            ->condition('field_id', $value["id"], 'IN')
            ->execute();

          $mid = $media_storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('bundle', ['character_image'], 'IN')
            ->condition('name', $value['name'], 'IN')
            ->execute();

            if (empty($mid)) {
              $image_data = file_get_contents($value['image']);
              $file_repository = \Drupal::service('file.repository');
              $processed_name = strtolower(str_replace(' ', '-', $value['name']));
              $image = $file_repository->writeData($image_data, "public://" . $processed_name . ".jpeg", FileSystemInterface::EXISTS_REPLACE);

              $image_media = Media::create([
                'name' => $value['name'],
                'bundle' => 'character_image',
                'field_media_image' => [
                  'target_id' => $image->id(),
                  'alt' => t($value['name']),
                  'title' => t($value['name']),
                ],
              ]);

              $image_media->save();
              dump("New Media image: " . $processed_name . ".jpeg");
            }

          if (empty($nid)) {
            $node = Node::create([
              'type' => 'character',
              'field_id' => $value["id"],
              'title' => $value["name"],
              'field_status' => [['target_id' => $this->GetStatusTaxonomy($value["status"])]],
              'field_image' => $image_media,
            ]);

            $node->save();
            $message = t('Created Node @id.', [
              '@id' => $node->id(),
            ]);
            \Drupal::messenger()->addMessage($message);
          } else {

          }
        }
      }

      $page++;
    } while($response != NULL);
  }

  public function GetStatusTaxonomy($str) {

    switch ($str) {
      case 'Alive':
        $str = 1;
        break;
      case 'Dead':
        $str = 2;
        break;
      case 'unknown':
        $str = 3;
        break;
    }

    return $str;
  }
}
