<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Api;

/**
 * The Search Api Typesense client factory.
 */
class Config {

  /**
   * Config constructor.
   *
   * @param string $api_key
   *   The Typesense API key.
   * @param array $nodes
   *   The Typesense nodes.
   * @param int $retry_interval_seconds
   *   The Typesense retry interval in seconds.
   */
  public function __construct(
    public readonly string $api_key,
    public readonly array $nodes,
    public readonly int $retry_interval_seconds
  ) {}

  /**
   * Returns the config as an array.
   *
   * @return array
   *   The config as an array.
   */
  public function toArray(): array {
    return [
      'api_key' => $this->api_key,
      'nodes' => $this->nodes,
      'retry_interval_seconds' => $this->retry_interval_seconds,
    ];
  }

  /**
   * Checks if the config is valid.
   *
   * @return bool
   *   TRUE if the config is valid, FALSE otherwise.
   */
  public function valid(): bool {
    return $this->api_key !== ''
      && count($this->nodes) > 0;
  }

}
