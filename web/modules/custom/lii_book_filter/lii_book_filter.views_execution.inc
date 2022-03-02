<?php

/**
 * @file
 * Views hooks.
 */

use Drupal\views\ViewExecutable;
use Drupal\views\Plugin\views\query\QueryPluginBase;
/*Adds an IS NULL condition to the book.depth, so that the view includes top level books and book pages that aren't in a book hierarchy*/

function lii_book_filter_views_query_alter(ViewExecutable $view, QueryPluginBase $query){
  
   if ($view->id() == 'bookshelf' && $view->getDisplay()->display['id'] == 'law_books') {
     if($query->where['2']){
       $query->where['2']['conditions'][]=array(
         'field'=>'book.depth',
         'operator'=>'IS NULL',
       );
     }
   }
}
