<?php

namespace Drupal\xmlsitemap;

use Drupal\Core\Url;

/**
 * Extended class for writing XML sitemap indexes.
 */
class XmlSitemapIndexWriter extends XmlSitemapWriter {

  /**
   * Name of the root element of the document.
   *
   * @var string
   */
  protected $rootElement = 'sitemapindex';

  /**
   * {@inheritdoc}
   */
  public function __construct(XmlSitemapInterface $sitemap, $page = 'index') {
    parent::__construct($sitemap, 'index');
  }

  /**
   * {@inheritdoc}
   *
   * @todo Should this call parent::getRootAttributes()?
   */
  public function getRootAttributes() {
    $attributes['xmlns'] = 'http://www.sitemaps.org/schemas/sitemap/0.9';
    if (\Drupal::state()->get('xmlsitemap_developer_mode')) {
      $attributes['xmlns:xsi'] = 'http://www.w3.org/2001/XMLSchema-instance';
      $attributes['xsi:schemaLocation'] = 'http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd';
    }

    \Drupal::moduleHandler()->alter('xmlsitemap_root_attributes', $attributes, $this->sitemap);

    return $attributes;
  }

  /**
   * {@inheritdoc}
   *
   * @codingStandardsIgnoreStart
   */
  public function generateXML() {
    // @codingStandardsIgnoreEnd
    $lastmod_format = \Drupal::config('xmlsitemap.settings')->get('lastmod_format');

    $url_options = $this->sitemap->uri['options'];
    $url_options += [
      'absolute' => TRUE,
      'xmlsitemap_base_url' => \Drupal::state()->get('xmlsitemap_base_url'),
      'language' => \Drupal::languageManager()->getDefaultLanguage(),
      'alias' => TRUE,
    ];

    for ($i = 1; $i <= $this->sitemap->chunks; $i++) {
      $url_options['query']['page'] = $i;
      $element = [
        'loc' => Url::fromRoute('xmlsitemap.sitemap_xml', [], $url_options)->toString(),
        // @todo Use the actual lastmod value of the chunk file.
        'lastmod' => gmdate($lastmod_format, REQUEST_TIME),
      ];
      $this->writeSitemapElement('sitemap', $element);
    }
  }

}
