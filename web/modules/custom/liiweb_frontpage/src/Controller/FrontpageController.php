<?php

namespace Drupal\liiweb_frontpage\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\image\Entity\ImageStyle;


/**
 * Class FrontpageController.
 */
class FrontpageController extends ControllerBase
{

  /**
   * Construct the "frontpage" page with code
   *
   * @return array $build.
   *
   */
  public function build()
  {
    $build = [];
    $build = [
      '#theme'  => 'frontpage',
      '#title' => [],
      '#sub_title' => [],
      '#background_image' => [],
      '#block_partner' => [],
      'search_form' => [],
    ];


    $frontpage = $this::fetch();
    if ($frontpage) {
      foreach ($frontpage as $revision_id => $nid) {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $frontpage = $node_storage->load($nid);

        $node = $node_storage->load($nid);
        $build['#title'] = $node->get('title')->value;
        if ($node->hasField('field_sub_title') && !$node->get('field_sub_title')->isEmpty()) {
          $build['#sub_title'] = $node->get('field_sub_title')->value;
        }

        if ($node->hasField('field_image_ref') && !$node->get('field_image_ref')->isEmpty()) {
          $media_entities = $node->get('field_image_ref')->referencedEntities();

          // Render the images to a view mode
          $view_mode    = 'letterbox_lg';
          $view_builder = \Drupal::entityTypeManager()->getViewBuilder('media');

          foreach ($media_entities as $media_entity) {
            $build['#background_image'] = $view_builder->view($media_entity, $view_mode);
          }
          // $build['first_block'] = $render_array;
          // $build['second_block'] = $another_render_array;
          // dd($build);
        }

      }
      // Render partner frontpage view hero block
      $block_partner = views_embed_view('rw_organisations', 'block_partner_hero');
      if ($block_partner) {
        $build['#block_partner'] = $block_partner;
      }

      // Render search_block_form
      // $search_form = drupal_get_form('search_block_form');
      // $build['#search_form'] = drupal_render($search_form);
      $block = \Drupal\block\Entity\Block::load('search_block_form');
      // dd($block);
      // $variables['block_search_form'] = \Drupal::entityTypeManager()
      //   ->getViewBuilder('block')
      //   ->view($block);

    }
    return $build;
  }


  /**
   * Fetch all the branch entities
   *
   * @return array
   */
  private function fetch()
  {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'page', '=')
      ->condition('status', 1);
    //    ->range(0, $count);

    $node = $query->execute();

    return $node;
  }
}
