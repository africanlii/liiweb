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
      '#titles' => [],
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

      // $legislation = $query->execute();
      // // $legislation = $this::fetch();
      // if ($legislation) {
      //   $title = [];
      //   $output_array = [];
      //   $first = '';
      //   $view_mode    = 'teaser';
      //   foreach ($legislation as $revision_id => $nid) {
      //     $node_storage = \Drupal::entityTypeManager()->getStorage('node');
      //     $view_builder = \Drupal::entityTypeManager()->getViewBuilder('node');
      //     $legislation = $node_storage->load($nid);
      //     $node = $node_storage->load($nid);

      //     $title[] = $node->get('title')->value;
      //     $browser_block = $view_builder->view($node, $view_mode);

      //     $charecters = [];
      //       // for ($i = 0; $i < strlen($title); $i++) {
      //       //   $charecters[] = $title[$i];
      //     }
      //   // dump($title);
      //   // sort($title, SORT_STRING | SORT_FLAG_CASE);
      //   // Loop over the one you have...
      //   // foreach ($title as $state) {
      //   //   $first = strtoupper($state[0]);
      //   //   // Create the sub-array if it doesn't exist
      //   //   if (!isset($output_array[$first])) {
      //   //     $ouput_array[$first] = array();
      //   //   }

      //   //   $output_array[$first][] = $state;
      //   //   // dump($state);

      //   // }
      //   sort($title);
      //   $result= [];
      //   foreach ($title as $sWord) {
      //     $result[strtoupper(substr($sWord, 0, 1))][] = render($browser_block);
      //   }

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

  public function sortAndIndexArray($aArray)
  {
    sort($aArray);
    foreach ($aArray as $sWord) {
      $aFinal[strtoupper(substr($sWord, 0, 1))][] = ucfirst($sWord);
    }
    ksort($aFinal);
    return $aFinal;
  }

}

