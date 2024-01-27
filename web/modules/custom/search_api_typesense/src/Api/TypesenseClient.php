<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Api;

use Http\Client\Exception;
use Typesense\Client;
use Typesense\Collection;

/**
 * The Search Api Typesense client.
 */
class TypesenseClient implements TypesenseClientInterface {

  private Client $client;

  /**
   * TypesenseClient constructor.
   *
   * @param \Drupal\search_api_typesense\api\Config $config
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
      if (empty($collection_name) || empty($parameters)) {
        return [];
      }

      $collection = $this->retrieveCollection($collection_name);

      if ($collection) {
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
      $this->client->collections[$collection_name]->delete();
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

      if ($collection) {
        $this->client->collections[$collection_name]->documents->upsert($document);
      }

      throw new \Exception($this->t('Error creating document.')->render());
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function retrieveDocument(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection) {
        return $collection->documents[$id]->retrieve();
      }

      return FALSE;
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function deleteDocument(string $collection_name, string $id): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection) {
        return $collection->documents[$id]->delete();
      }

      return FALSE;
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

      if ($collection && $collection->documents && !empty($filter_condition)) {
        return $collection->documents->delete($filter_condition);
      }

      return FALSE;
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

      if ($collection) {
        $created_synonym = $this->client->collections[$collection_name]->synonyms->upsert($id, $synonym);

        return $created_synonym;
      }

      return FALSE;
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

      if ($collection) {
        return $collection->synonyms[$id]->retrieve();
      }

      return FALSE;
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

      if ($collection) {
        return $collection->synonyms->retrieve();
      }

      return FALSE;
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

      if ($collection) {
        return $collection->synonyms[$id]->delete();
      }

      return FALSE;
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
      return $this->client->getKeys();
    }
    catch (SearchApiTypesenseException $e) {
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
  public function prepareItemValue($value, $type): array {
    if (is_array($value) && count($value <= 1)) {
      $value = reset($value);
    }

    switch ($type) {
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
