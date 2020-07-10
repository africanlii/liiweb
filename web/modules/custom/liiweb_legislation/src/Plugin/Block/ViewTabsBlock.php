<?php

namespace Drupal\liiweb_legislation\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\node\Entity\Node;
use Drupal\file\Entity\File;
use Drupal\Core\Url;
use Drupal\media\Entity\Media;
use Drupal\image\Entity\ImageStyle;

/**
 * Provides a 'ViewTabsBlock' block.
 *
 * @Block(
 *  id = "view_tabs_block",
 *  admin_label = @Translation("View tabs block"),
 * )
 */
class ViewTabsBlock extends BlockBase {

  /**
   * {@inheritdoc}
   *
   * render lislation view blocks (subject,search,borwser)
   */
  public function build() {
    $build = [];

    $build = [
      '#theme'  => 'view_tabs_block',
      '#subject' => [],
      '#search' => [],
      '#browser' => [],
    ];

    //Render legislation bk_legislation_browser
    $block_browser = views_embed_view('legislation', 'bk_legislation_browser');
    // dump($block_browser);
    if ($block_browser) {
      $build['#browser'] = $block_browser;

      // $query = \Drupal::entityQuery('node')
      //   ->condition('type', 'legislation', '=')
      //   ->condition('status', 1);
      // //    ->range(0, $count);

      // $frontpage = $query->execute();
      // // $frontpage = $this::fetch();
      // if ($frontpage) {
      //   foreach ($frontpage as $revision_id => $nid) {
      //     $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      //     $frontpage = $node_storage->load($nid);

      //     $node = $node_storage->load($nid);
      //     $title = $node->get('title')->value;
      //     for ($i = 0; $i < strlen($title); $i++) {
      //       dump($title[$i]);
      //     }

      //     }
      //   }
      }
    //Render legislation bk_legislation_subject
    $block_subject = views_embed_view('legislation', 'bk_legislation_subject');
    if ($block_subject) {
      $build['#subject'] = $block_subject;
    }

    //Render legislation bk_legislation_search
    $block_search = views_embed_view('index_search_legislation', 'bk_legislation_search');
    if ($block_search) {
      $build['#search'] = $block_search;
    }


    return $build;
  }

}
