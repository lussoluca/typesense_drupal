<?php

declare(strict_types=1);

namespace Drupal\search_api_typesense\Api;

use Typesense\Collection;

/**
 * Interface for the Search Api Typesense client.
 */
interface TypesenseClientInterface {

  /**
   * Searches specified collection for given string.
   *
   * @param string $collection_name
   *   The name of the collection to search.
   * @param array $parameters
   *   The array of query parameters.
   *
   * @return array
   *   The results array.
   *
   * @see https://typesense.org/docs/latest/api/documents.html#search
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function searchDocuments(string $collection_name, array $parameters): array;

  /**
   * Creates a Typesense collection.
   *
   * @param array $schema
   *   A typesense schema.
   *
   * @see https://typesense.org/docs/latest/api/collections.html#create-a-collection
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function createCollection(array $schema): Collection;

  /**
   * Removes a Typesense index.
   *
   * @param string|null $collection_name
   *   The name of the index to remove.
   *
   * @see https://typesense.org/docs/latest/api/collections.html#drop-a-collection
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function dropCollection(?string $collection_name): void;

  /**
   * Lists all collections.
   *
   * @return array
   *   The set of collections for the server.
   *
   * @see https://typesense.org/docs/latest/api/collections.html#list-all-collections
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveCollections(): array;

  /**
   * Adds a document to a collection, or updates an existing document.
   *
   * @param string $collection_name
   *   The collection to create the new document on.
   * @param array $document
   *   The document to create.
   *
   * @see https://typesense.org/docs/latest/api/documents.html#index-a-document
   * @see https://typesense.org/docs/latest/api/documents.html#upsert
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function createDocument(string $collection_name, array $document): void;

  /**
   * Retrieves a specific indexed document.
   *
   * @param string $collection_name
   *   The name of the collection to query for the document.
   * @param string $id
   *   The id of the document to retrieve.
   *
   * @return array
   *   The retrieved document.
   *
   * @see https://typesense.org/docs/latest/api/documents.html#retrieve-a-document
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveDocument(string $collection_name, string $id): array;

  /**
   * Deletes a specific indexed document.
   *
   * @param string $collection_name
   *   The name of the collection containing the document.
   * @param string $id
   *   The id of the document to delete.
   *
   * @return array
   *   The deleted document.
   *
   * @see https://typesense.org/docs/latest/api/documents.html#delete-documents
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function deleteDocument(string $collection_name, string $id): array;

  /**
   * Deletes all documents satisfying a certain condition.
   *
   * @param string $collection_name
   *   The name of the collection containing the documents.
   * @param array $filter_condition
   *   The condition specifying which documents to delete.
   *
   * @return array
   *   An array containing the quantity of documents deleted.
   *
   * @see https://typesense.org/docs/latest/api/documents.html#delete-by-query
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function deleteDocuments(string $collection_name, array $filter_condition): array;

  /**
   * Adds a synonym to a collection, or updates an existing synonym.
   *
   * @param string $collection_name
   *   The collection to create the new synonym on.
   * @param string $id
   *   The id of the synonym to create.
   * @param array $synonym
   *   The synonym to create.
   *
   * @return array
   *   The newly added/updated synonym.
   *
   * @see https://typesense.org/docs/latest/api/synonyms.html#create-or-update-a-synonym
   * @see https://typesense.org/docs/latest/api/synonyms.html#multi-way-synonym
   * @see https://typesense.org/docs/latest/api/synonyms.html#arguments
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function createSynonym(string $collection_name, string $id, array $synonym): array;

  /**
   * Retrieves a specific indexed synonym.
   *
   * @param string $collection_name
   *   The name of the collection to query for the synonym.
   * @param string $id
   *   The id of the synonym to retrieve.
   *
   * @return array
   *   The retrieved synonym.
   *
   * @see https://typesense.org/docs/latest/api/synonyms.html#retrieve-a-synonym
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveSynonym(string $collection_name, string $id): array;

  /**
   * Retrieves all indexed synonyms.
   *
   * @param string $collection_name
   *   The name of the collection to query for the synonym.
   *
   * @return array
   *   The retrieved synonyms.
   *
   * @see https://typesense.org/docs/latest/api/synonyms.html#list-all-synonyms
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveSynonyms(string $collection_name): array;

  /**
   * Deletes a specific indexed synonym.
   *
   * @param string $collection_name
   *   The name of the collection containing the synonym.
   * @param string $id
   *   The id of the synonym to delete.
   *
   * @return array
   *   The deleted synonym.
   *
   * @see https://typesense.org/docs/latest/api/synonyms.html#delete-a-synonym
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function deleteSynonym(string $collection_name, string $id): array;

  /**
   * Adds a curation to a collection, or updates an existing curation.
   *
   * @param string $collection_name
   *   The collection to create the new synonym on.
   * @param string $id
   *   The id of the synonym to create.
   * @param array $curation
   *   The curation to create.
   *
   * @return array
   *   The newly added/updated curation.
   *
   * @see https://typesense.org/docs/latest/api/curation.html#create-or-update-an-override
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function createCuration(string $collection_name, string $id, array $curation): array;

  /**
   * Retrieves a specific curation.
   *
   * @param string $collection_name
   *   The name of the collection to query for the curation.
   * @param string $id
   *   The id of the curation to retrieve.
   *
   * @return array
   *   The retrieved curation.
   *
   * @see https://typesense.org/docs/latest/api/curation.html#retrieve-an-override
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveCuration(string $collection_name, string $id): array;

  /**
   * Retrieves all curations.
   *
   * @param string $collection_name
   *   The name of the collection to query for the curation.
   *
   * @return array
   *   The retrieved curations.
   *
   * @see https://typesense.org/docs/latest/api/curation.html#list-all-overrides
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveCurations(string $collection_name): array;

  /**
   * Deletes a specific curation.
   *
   * @param string $collection_name
   *   The name of the collection containing the curation.
   * @param string $id
   *   The id of the curation to delete.
   *
   * @return array
   *   The deleted curation.
   *
   * @see https://typesense.org/docs/latest/api/curation.html#delete-an-override
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function deleteCuration(string $collection_name, string $id): array;

  /**
   * Returns collection information.
   *
   * @param string $collection_name
   *   The name of the collection to retrieve information for.
   *
   * @return array
   *   An associative array containing the collection's information.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveCollectionInfo(string $collection_name): array;

  /**
   * Returns the health of the Typesense server.
   *
   * @return array
   *   An array containing a boolean 'ok': [ 'ok' => TRUE ].
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveHealth(): array;

  /**
   * Returns the debug info from the Typesense server.
   *
   * @return array
   *   An associative array containing two keys, 'state', and 'version'.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveDebug(): array;

  /**
   * Returns the metrics info from the Typesense server.
   *
   * @return array
   *   An associative array containing the server's metrics.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function retrieveMetrics(): array;

  /**
   * Returns current server keys.
   *
   * @return array
   *   The array of the server's keys.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function getKeys(): array;

  /**
   * Creates a key.
   *
   * @param array $schema
   *   A typesense schema for API Key.
   *
   * @return array
   *   The created key response.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   *
   * @see https://typesense.org/docs/latest/api/api-keys.html#create-an-api-key
   */
  public function createKey(array $schema): array;

  /**
   * Retrieves a key.
   *
   * @param int $key_id
   *   The id of the key to retrieve.
   *
   * @return array
   *   The retrieved key.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   *
   * @see https://typesense.org/docs/latest/api/api-keys.html#retrieve-an-api-key
   */
  public function retrieveKey(int $key_id): array;

  /**
   * Deletes a key.
   *
   * @param int $key_id
   *   The id of the key to delete.
   *
   * @return array
   *   The deleted key.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   *
   * @see https://typesense.org/docs/latest/api/api-keys.html#delete-api-key
   */
  public function deleteKey(int $key_id): array;

  /**
   * Export the collection configuration.
   *
   * @param string $collection_name
   *   The name of the collection to export.
   *
   * @return array
   *   The collection configuration.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function exportCollectionConfiguration(string $collection_name): array;

  /**
   * Prepares items for typesense-indexing.
   *
   * @param string|int|array|null $value
   *   The incoming entity value from Drupal.
   * @param string $type
   *   The specified data type from the Search API index configuration.
   *
   * @return bool|float|int|string
   *   The prepared item, ready for Typesense indexing.
   *
   * @throws \Drupal\search_api_typesense\Api\SearchApiTypesenseException
   */
  public function prepareItemValue(string|int|array|null $value, string $type): bool|float|int|string;

}
