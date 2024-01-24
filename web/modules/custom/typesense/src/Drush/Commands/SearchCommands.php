<?php

namespace Drupal\typesense\Drush\Commands;

use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Drush\Attributes as CLI;
use Drush\Commands\DrushCommands;
use Typesense\Client;

/**
 * A Drush commandfile.
 *
 * In addition to this file, you need a drush.services.yml
 * in root of your module, and a composer.json file that provides the name
 * of the services file to use.
 */
final class SearchCommands extends DrushCommands {

  /**
   * Command description here.
   */
  #[CLI\Command(name: 'typesense:create-collection')]
  public function createCollection(): void {
    $this->logger()->success(dt('create collection.'));

    $nodeSchema = [
      'name' => 'nodes_ml',
      'fields' => [
        ['name' => 'title', 'type' => 'string'],
        ['name' => 'body', 'type' => 'string'],
        ['name' => 'lang', 'type' => 'string'],
        ['name' => 'category', 'type' => 'string[]', 'facet' => true],
        ['name' => 'nid', 'type' => 'int32'],
        // [
        //   'name' => 'embedding',
        //   'type' => 'float[]',
        //   'embed' => [
        //     'from' => ['title', 'body'],
        //     'model_config' => [
        //       "model_name" => "sentence-bert-base-italian-uncased",
        //     ],
        //   ],
        // ],
      ],
      'default_sorting_field' => 'nid',
    ];

    $this->getClient()->collections->create($nodeSchema);
  }

  /**
   * Import nodes from Wikipedia articles.
   */
  #[CLI\Command(name: 'typesense:import')]
  public function import(): void {
    $this->logger()->success(dt('import.'));

    $parser = xml_parser_create("UTF-8");
    xml_set_object($parser, $this);
    xml_set_element_handler($parser, "startTag", "endTag");
    xml_set_default_handler($parser, "handler");

    $fh = fopen('/var/www/html/web/itwiki-latest-pages-articles1.xml', "r");
    //$fh = fopen('/var/www/html/web/test.xml', "r");
    if (!$fh) {
      die("Epic fail!\n");
    }

    while (!feof($fh)) {
      $data = fread($fh, 4096);
      xml_parse($parser, $data, feof($fh));
    }

    foreach (array_slice($this->stack, 0, 20000) as $page) {
      $text = $page['text'] ?? '';

      $re = '/\[\[Categoria:(.*)\]\]/m';

      preg_match_all($re, $text, $matches, PREG_SET_ORDER, 0);

      $terms = [];
      foreach ($matches as $match) {
        // Load a term by name.
        $term = \Drupal::entityTypeManager()
          ->getStorage('taxonomy_term')
          ->loadByProperties(['name' => $match[1]]);

        if (empty($term)) {
          $term = Term::create([
            'vid' => 'tags',
            'name' => $match[1],
          ]);
          $term->save();
          $this->logger()->success(dt('Create term @name.', ['@name' => $match[1]]));
        }
        else {
          $term = reset($term);
        }

        $terms[] = $term->id();
      }

      Node::create([
        'type' => 'article',
        'title' => $page['title'],
        'body' => [
          'value' => $page['text'] ?? '',
          'format' => 'full_html',
        ],
        'field_tags' => $terms,
        'langcode' => 'en',
      ])->save();
      $this->logger()->success(dt('Create node @name.', ['@name' => $page['title']]));
    }
  }

  protected $skip = FALSE;

  protected $capture_title = FALSE;

  protected $capture_text = FALSE;

  protected $current_page = 0;

  protected $current_text = 0;

  protected $stack = [];

  public function startTag($parser, $name, $attribs) {
    if ($name == 'TITLE') {
      $this->capture_title = TRUE;
    }

    if ($name == 'TEXT') {
      $this->capture_text = TRUE;
    }
  }

  public function endTag($parser, $name) {
    if ($name == 'TITLE') {
      $this->capture_title = FALSE;
    }

    if ($name == 'TEXT') {
      $this->capture_text = FALSE;
      $this->current_page++;
      $this->current_text++;
    }
  }

  public function handler($parser, string $data): void {
    if ($this->capture_title) {
      if (strlen($data) < 5 || str_starts_with($data,
          'Wikipedia:') || str_starts_with($data,
          'File:') || str_starts_with($data,
          'Template:') || str_starts_with($data,
          'Categoria:') || str_starts_with($data,
          'Portal:') || str_starts_with($data,
          'Help:') || str_starts_with($data,
          'MediaWiki:') || str_starts_with($data,
          'Draft:') || str_starts_with($data,
          'Module:') || str_starts_with($data,
          'Book:') || str_starts_with($data,
          'TimedText:') || str_starts_with($data,
          'Topic:') || str_starts_with($data,
          'Gadget:') || str_starts_with($data,
          'Special:') || str_starts_with($data,
          'Template talk:') || str_starts_with($data,
          'Category talk:') || str_starts_with($data,
          'Portal talk:') || str_starts_with($data,
          'Help talk:') || str_starts_with($data,
          'MediaWiki talk:') || str_starts_with($data,
          'Draft talk:') || str_starts_with($data,
          'Module talk:') || str_starts_with($data,
          'Book talk:') || str_starts_with($data,
          'TimedText talk:') || str_starts_with($data,
          'Topic talk:') || str_starts_with($data,
          'Gadget talk:') || str_starts_with($data, 'Special talk:') || str_starts_with($data, 'Anni')) {
        $this->skip = TRUE;
      }
      else {
        $this->stack[$this->current_page]['title'] = $data;
        $this->skip = FALSE;
      }
    }
    if ($this->capture_text) {
      if (!$this->skip) {
        if (!isset($this->stack[$this->current_page]['text'])) {
          $this->stack[$this->current_page]['text'] = '';
        }

        $this->stack[$this->current_page]['text'] .= $data;
      }
    }
  }

  /**
   * An example of the table output format.
   */
  #[CLI\Command(name: 'typesense:index')]
  public function index(): void {
    $this->logger()->success(dt('loading nodes.'));

    $nodes = Node::loadMultiple();

    $this->logger()->success(dt('indexing.'));

    foreach ($nodes as $node) {
      $this->logger()->success(dt('index node @nid.', ['@nid' => $node->id()]));

      // load terms from field_category and create an array with names
      $term_names = [];
      $refs = $node->referencedEntities();
      foreach ($refs as $ref) {
        if ($ref->getEntityTypeId() == 'taxonomy_term') {
          $term_names[] = $ref->getName();
        }
      }

      $this->getClient()->collections['nodes_ml']->documents->upsert([
        'id' => $node->id(),
        'title' => $node->getTitle() ?? 'no title',
        'body' => $node->get('body')->value ?? '',
        'lang' => $node->get('langcode')->value,
        'category' => $term_names,
        'nid' => intval($node->id()),
      ]);
    }
  }

  /**
   * Return a Typesense client.
   *
   * @return \Typesense\Client
   *   The Typesense client.
   *
   * @throws \Typesense\Exceptions\ConfigError
   */
  private function getClient(): Client {
    return new Client(
      [
        'api_key' => 'ddev',
        'nodes' => [
          [
            'host' => 'typesense',
            'port' => '8108',
            'protocol' => 'http',
          ],
        ],
        'connection_timeout_seconds' => 2,
      ]
    );
  }

}
