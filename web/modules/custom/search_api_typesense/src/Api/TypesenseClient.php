<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Api;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Http\Client\Exception;
use Typesense\Client;
use Typesense\Collection;

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
   * @throws \Http\Client\Exception
   */
  public function __construct(Config $config) {
    try {
      $this->client = new Client($config->toArray());
      $this->client->health->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function searchDocuments(string $collection_name, array $parameters): array {
    try {
      if ($collection_name != '' || $parameters != '') {
        return [];
      }

      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $this->client->collections[$collection_name]->documents->search($parameters);
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCollection(?string $collection_name): ?Collection {
    try {
      $collection = $this->client->collections[$collection_name];
      // Ensure that collection exists on the typesense server by retrieving it.
      // This throws exception if it is not found.
      $collection->retrieve();

      return $collection;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createCollection(array $schema): Collection {
    try {
      $this->client->collections->create($schema);
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
    catch (Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
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
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveCollections(): array {
    try {
      return $this->client->collections->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDocument(string $collection_name, array $document): void {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        $this->client->collections[$collection_name]->documents->upsert($document);
      }
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDocument(string $collection_name, string $id): array|null {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->documents[$this->prepareItemValue($id, 'typesense_id')]->retrieve();
      }

      return NULL;
    }
    catch (\Exception $e) {
      return NULL;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocument(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        $typesense_id = $this->prepareItemValue($id, 'typesense_id');

        $document = $this->retrieveDocument($collection_name, $id);
        if ($document != NULL) {

          return $collection->documents[$typesense_id]->delete();
        }
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocuments(string $collection_name, array $filter_condition): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL && count($filter_condition) > 0) {
        return $collection->documents->delete($filter_condition);
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createSynonym(string $collection_name, string $id, array $synonym): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $this->client->collections[$collection_name]->synonyms->upsert($id, $synonym);
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveSynonym(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->synonyms[$id]->retrieve();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveSynonyms(string $collection_name): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->synonyms->retrieve();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteSynonym(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection != NULL) {
        return $collection->synonyms[$id]->delete();
      }

      return [];
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveHealth(): array {
    try {
      return $this->client->health->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDebug(): array {
    try {
      return $this->client->debug->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveMetrics(): array {
    try {
      return $this->client->metrics->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getKeys(): array {
    try {
      return $this->client->getKeys()->retrieve();
    }
    catch (SearchApiTypesenseException $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createKey(array $schema): array {
    try {
      return $this->client->keys->create($schema);
    }
    catch (\Exception | Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
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
    catch (\Exception | Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
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
    catch (\Exception | Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
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
  public function prepareItemValue(string|int|array|null $value, string $type): bool|float|int|string {
    if (is_array($value) && count($value) <= 1) {
      $value = reset($value);
    }

    switch ($type) {
      // TypeSense does not allow characters that require encoding in urls. The
      // Search API ID has "/" character in it that is not compatible with that
      // requirement. So replace that with an underscore.
      // @see https://typesense.org/docs/0.21.0/api/documents.html#index-a-document.
      case 'typesense_id':
        $value = \str_replace('/', '_', $value);
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

}
