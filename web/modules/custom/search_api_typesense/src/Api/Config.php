<?php

declare(strict_types = 1);

namespace Drupal\search_api_typesense\Api;

/**
 * The Search Api Typesense client factory.
 */
class Config {

  /**
   * @param string $api_key
   * @param array $nodes
   * @param int $retry_interval_seconds
   */
  public function __construct(
    public readonly string $api_key,
    public readonly array $nodes,
    public readonly int $retry_interval_seconds
  ) {}

  /**
   * @return array
   */
  public function toArray(): array {
    return [
      'api_key' => $this->api_key,
      'nodes' => $this->nodes,
      'retry_interval_seconds' => $this->retry_interval_seconds,
    ];
  }

  /**
   * @return bool
   */
  public function valid(): bool {
    return $this->api_key !== ''
      && count($this->nodes) > 0;
  }

}
