<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Api;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Http\Client\Exception;
use Typesense\Client;
use Typesense\Collection;
use Typesense\Exceptions\TypesenseClientError;

/**
 * The Search Api Typesense client.
 */
class TypesenseClient implements TypesenseClientInterface {

  use StringTranslationTrait;

  private Client $client;

  /**
   * TypesenseClient constructor.
   *
   * @param \Drupal\search_api_typesense\Api\Config $config
   *   The Typesense config.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function __construct(Config $config) {
    try {
      $this->client = new Client($config->toArray());
      $this->client->health->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCollections(): array {
    try {
      return $this->client->collections->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function searchDocuments(
    string $collection_name,
    array $parameters,
  ): array {
    try {
      if ($collection_name == '' || $parameters == []) {
        return [];
      }

      $collection = $this->retrieveCollection($collection_name);

      return $collection->documents->search($parameters);
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection(array $schema): Collection {
    try {
      $this->client->collections->create($schema);
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }

    return $this->retrieveCollection($schema['name']);
  }

  /**
   * {@inheritdoc}
   */
  public function dropCollection(?string $collection_name): void {
    try {
      $collections = $this->client->collections;
      if ($collections->offsetExists($collection_name)) {
        $collections[$collection_name]->delete();
      }
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDocument(
    string $collection_name,
    array $document,
  ): void {
    try {
      $collection = $this->retrieveCollection($collection_name);
      $collection->documents->upsert($document);
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDocument(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->documents[$this->prepareItemValue($id,
        'typesense_id')]->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocument(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      $typesense_id = $this->prepareItemValue($id, 'typesense_id');

      $document = $this->retrieveDocument($collection_name, $id);
      if ($document != NULL) {
        return $collection->documents[$typesense_id]->delete();
      }

      return [];
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocuments(
    string $collection_name,
    array $filter_condition,
  ): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->documents->delete($filter_condition);
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createSynonym(
    string $collection_name,
    string $id,
    array $synonym,
  ): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->synonyms->upsert($id, $synonym);
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveSynonym(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->synonyms[$id]->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveSynonyms(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->synonyms->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSynonym(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->synonyms[$id]->delete();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createCuration(
    string $collection_name,
    string $id,
    array $curation,
  ): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->overrides->upsert($id, $curation);
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCuration(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->overrides[$id]->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCurations(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->overrides->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteCuration(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      return $collection->overrides[$id]->delete();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCollectionInfo(string $collection_name,): array | null {
    try {
      $collection = $this->retrieveCollection($collection_name, FALSE);

      if ($collection === NULL) {
        return NULL;
      }

      $collection_data = $collection->retrieve();

      return [
        'created_at' => $collection_data['created_at'],
        'num_documents' => $collection_data['num_documents'],
      ];
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveHealth(): array {
    try {
      return $this->client->health->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDebug(): array {
    try {
      return $this->client->debug->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveMetrics(): array {
    try {
      return $this->client->metrics->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(): array {
    try {
      return $this->client->getKeys()->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createKey(array $schema): array {
    try {
      return $this->client->keys->create($schema);
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveKey(int $key_id): array {
    try {
      $key = $this->client->keys[$key_id];

      return $key->retrieve();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteKey(int $key_id): array {
    try {
      $key = $this->client->keys[$key_id];

      return $key->delete();
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function importCollection(string $collection_name, array $data): void {
    try {
      $collection = $this->retrieveCollection($collection_name);

      foreach ($data['synonyms'] as $synonym) {
        $collection->synonyms->upsert($synonym['id'], $synonym);
      }

      foreach ($data['curations'] as $curation) {
        $collection->overrides->upsert($curation['id'], $curation);
      }
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function exportCollection(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      $schema = $collection->retrieve();
      unset($schema['created_at']);
      unset($schema['num_documents']);

      return [
        'schema' => $schema,
        'synonyms' => $collection->synonyms->retrieve()['synonyms'],
        'curations' => $collection->overrides->retrieve()['overrides'],
      ];
    }
    catch (Exception | TypesenseClientError $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(),
        $e);
    }
  }

  /**
   * {@inheritdoc}
   *
   * @todo
   *   - Figure out int64 -vs- int32 casting.
   *   - Throw an exception if the value received is incompatible with the
   *     declared type.
   *   - Equip this function to handle multiples (i.e. int32[] etc).
   */
  public function prepareItemValue(
    string | int | array | null $value,
    string $type,
  ): bool | float | int | string {
    if (is_array($value) && count($value) <= 1) {
      $value = reset($value);
    }

    switch ($type) {
      // TypeSense does not allow characters that require encoding in urls. The
      // Search API ID has "/" character in it that is not compatible with that
      // requirement. So replace that with an underscore.
      // @see https://typesense.org/docs/latest/api/documents.html#index-a-document.
      case 'typesense_id':
        $value = \str_replace('/', '-', $value);
        break;

      case 'typesense_bool':
        $value = (bool) $value;
        break;

      case 'typesense_float':
        $value = (float) $value;
        break;

      case 'typesense_int32':
      case 'typesense_int64':
        $value = (int) $value;
        break;

      case 'typesense_string':
        $value = (string) $value;
        break;
    }

    return $value;
  }

  /**
   * {@inheritdoc}
   */
  public function getFields(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);
      if ($collection === NULL) {
        return [];
      }

      $schema = $collection->retrieve();

      return array_map(function (array $field) {
        return $field['name'];
      }, $schema['fields']);
    }
    catch (Exception | TypesenseClientError | SearchApiTypesenseException $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsForQueryBy(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);
      if ($collection === NULL) {
        return [];
      }

      $schema = $collection->retrieve();

      return array_map(function (array $field) {
        return $field['name'];
      }, array_filter($schema['fields'], function ($field) {
        return in_array($field['type'], ['string', 'string[]']);
      }));
    }
    catch (Exception | TypesenseClientError | SearchApiTypesenseException $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsForFacetNumber(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);
      if ($collection === NULL) {
        return [];
      }

      $schema = $collection->retrieve();

      return array_values(array_map(function (array $field) {
        return $field['name'];
      }, array_filter($schema['fields'], function ($field) {
        return $field['facet'] == TRUE && in_array($field['type'], [
          'int32',
          'int64',
          'float',
          'int32[]',
          'int64[]',
          'float[]',
        ]);
      })));
    }
    catch (Exception | TypesenseClientError | SearchApiTypesenseException $e) {
      return [];
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldsForFacetString(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);
      if ($collection === NULL) {
        return [];
      }

      $schema = $collection->retrieve();

      return array_values(array_map(function (array $field) {
        return $field['name'];
      }, array_filter($schema['fields'], function ($field) {
        return $field['facet'] == TRUE && in_array($field['type'], [
          'string',
          'string[]',
        ]);
      })));
    }
    catch (Exception | TypesenseClientError | SearchApiTypesenseException $e) {
      return [];
    }
  }

  /**
   * Gets a Typesense collection.
   *
   * @param string|null $collection_name
   *   The name of the collection to retrieve.
   * @param bool $throw
   *   Whether to throw an exception if the collection is not found.
   *
   * @return \Typesense\Collection|null
   *   The collection, or NULL if none was found.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   *
   * @see https://typesense.org/docs/latest/api/collections.html#retrieve-a-collection
   */
  private function retrieveCollection(
    ?string $collection_name,
    bool $throw = TRUE,
  ): ?Collection {
    try {
      $collection = $this->client->collections[$collection_name];
      // Ensure that collection exists on the typesense server by retrieving it.
      // This throws exception if it is not found.
      $collection->retrieve();

      return $collection;
    }
    catch (Exception | TypesenseClientError $e) {
      if ($throw) {
        throw new SearchApiTypesenseException(
          $e->getMessage(),
          $e->getCode(),
          $e,
        );
      }

      return NULL;
    }
  }

}
