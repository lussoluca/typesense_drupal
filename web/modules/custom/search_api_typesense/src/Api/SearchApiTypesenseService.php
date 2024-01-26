<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Api;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\DependencyInjection\DependencySerializationTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\search_api_typesense\Client\SearchApiTypesenseClientFactoryInterface;
use Typesense\Client;
use Typesense\Collection;

/**
 * Class SearchApiTypesenseService.
 *
 * @todo
 *   - Refactor so method names match 1:1 with typesense/typesense-php. It'll
 *     make for a simpler life :)
 */
class SearchApiTypesenseService implements SearchApiTypesenseServiceInterface {

  use StringTranslationTrait;
  use DependencySerializationTrait;

  /**
   * The ConfigFactory instance.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The Typesense client.
   *
   * @var \Typesense\Client|null
   */
  protected ?Client $client = NULL;

  /**
   * The Typesense client factory.
   *
   * @var \Drupal\search_api_typesense\Client\SearchApiTypesenseClientFactoryInterface
   */
  protected SearchApiTypesenseClientFactoryInterface $clientFactory;

  /**
   * The Typesense schema.
   *
   * @var array
   */
  protected $schema;

  /**
   * SearchApiTypesenseService constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\search_api_typesense\Client\SearchApiTypesenseClientFactoryInterface $client_factory
   *   The Typesense client factory.
   */
  public function __construct(ConfigFactoryInterface $config_factory, SearchApiTypesenseClientFactoryInterface $client_factory) {
    $this->configFactory = $config_factory;
    $this->clientFactory = $client_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function connection(): Client {
    if ($this->client === NULL) {
      $this->client = $this->clientFactory->getInstance($this->getAuthorization());

      try {
        $this->client->health->retrieve();
      }
      catch (\Exception $e) {
        throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
      }
    }

    return $this->client;
  }

  /**
   * {@inheritdoc}
   */
  public function setAuthorization(string $api_key, array $nodes, int $connection_timeout_seconds): void {
    $this->auth = [
      'api_key' => $api_key,
      'nodes' => $nodes,
      'connection_timeout_seconds' => $connection_timeout_seconds,
    ];
  }

  /**
   * {@ihneritdoc}
   */
  public function getAuthorization(): array {
    return $this->auth;
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
        return $this->connection()->collections[$collection_name]->documents->search($parameters);
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
  public function retrieveCollection(string $collection_name): ?Collection {
    try {
      $collection = $this->connection()->collections[$collection_name];
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
      return $this->connection()->collections->create($schema);
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dropCollection(?string $collection_name): Collection {
    try {
      $this->connection()->collections[$collection_name]->delete();
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
      return $this->connection()->collections->retrieve();
    }
    catch (\Exception $e) {
      throw new SearchApiTypesenseException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function createDocument(string $collection_name, array $document): array {
    try {
      $collection = $this->retrieveCollection($collection_name);

      if ($collection) {
        $created_document = $this->connection()->collections[$collection_name]->documents->upsert($document);

        return $created_document;
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
        $created_synonym = $this->connection()->collections[$collection_name]->synonyms->upsert($id, $synonym);

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
      return $this->connection()->health->retrieve();
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
      return $this->connection()->debug->retrieve();
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
      return $this->connection()->getKeys();
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
