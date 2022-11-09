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
    echo $this->convert(memory_get_usage(true));
    echo "\n";

    do {
      $url = 'https://rickandmortyapi.com/api/character?page=' . $page;
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_HTTPGET, true);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $response_json = curl_exec($ch);
      curl_close($ch);
      echo "\n";

      $response = json_decode($response_json, true);

      if (!isset($response['error'])) {
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
            } else {
              foreach ($mid as $mid_key => $mid_value) {
                $image_media = Media::load($mid_key);
              }
            }

          if (empty($nid)) {
            $node = Node::create([
              'type' => 'character',
              'field_id' => $value["id"],
              'title' => $value["name"],
              'field_status' => [['target_id' => $this->GetStatusTaxonomy($value["status"])]],
              'field_type' => $value["type"],
              'field_image' => $image_media,
              'field_gender' => [['target_id' => $this->GetGenderTaxonomy($value["gender"])]],
              'field_created' => $value["created"],
            ]);

            $node->save();
            $message = t('Created Node @id.', [
              '@id' => $node->id(),
            ]);
            \Drupal::messenger()->addMessage($message);
          } else {
            foreach ($nid as $nid_index => $nid_value) {
              $modify_node = Node::Load($nid_value);
              $modify_node->field_id = $value["id"];
              $modify_node->field_image = $image_media;
              $modify_node->field_status = [['target_id' => $this->GetStatusTaxonomy($value["status"])]];
              $modify_node->field_gender = [['target_id' => $this->GetGenderTaxonomy($value["gender"])]];
              $modify_node->field_created = $value["created"];
              $modify_node->field_type = $value["type"];

              $modify_node->save();
              $message = t('Updated Node @id.', [
                '@id' => $modify_node->id(),
              ]);
              \Drupal::messenger()->addMessage($message);
            }
          }
        }
      }

      $page++;
    } while(!isset($response['error']));

    $message = t('Memory usage @memory.', [
      '@memory' => $this->convert(memory_get_usage(true)),
    ]);
    \Drupal::messenger()->addMessage($message);
  }

  public function convert($size)
  {
    $unit=array('b','kb','mb','gb','tb','pb');
    return @round($size/pow(1024,($i=floor(log($size,1024)))),2).' '.$unit[$i];
  }


  public function GetStatusTaxonomy($str) {
    $termId = \Drupal::entityQuery("taxonomy_term")->condition("vid", "status")->condition("name", $str)->execute();

    if (empty($termId)) {
      $term = [
        'name'     => $str,
        'vid'      => 'status',
      ];

      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create($term)->save();
      $termId = \Drupal::entityQuery("taxonomy_term")->condition("vid", "status")->condition("name", $str)->execute();
      foreach ($termId as $term_key => $term_value) {
        $str = $term_value;
      }
    } else {
      foreach ($termId as $term_key => $term_value) {
        $str = $term_value;
      }
    }

    return $str;
  }

  public function GetGenderTaxonomy($str) {
    $termId = \Drupal::entityQuery("taxonomy_term")->condition("vid", "gender")->condition("name", $str)->execute();

    if (empty($termId)) {
      $term = [
        'name'     => $str,
        'vid'      => 'gender',
      ];

      $term = \Drupal::entityTypeManager()->getStorage('taxonomy_term')->create($term)->save();
      $termId = \Drupal::entityQuery("taxonomy_term")->condition("vid", "status")->condition("name", $str)->execute();
      foreach ($termId as $term_key => $term_value) {
        $str = $term_value;
      }
    } else {
      foreach ($termId as $term_key => $term_value) {
        $str = $term_value;
      }
    }

    return $str;
  }
}
