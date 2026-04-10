<?php

namespace App\Exceptions;

use Exception;

class ApiException extends Exception {
  private $statusCode;
  private $publicMessage;

  public function __construct(
    string $publicMessage,
    int $statusCode = 500,
    ?string $internalMessage = null,
    ?Exception $previous = null
  ) {
    parent::__construct($internalMessage ?? $publicMessage, $statusCode, $previous);
    $this->publicMessage = $publicMessage;
    $this->statusCode = $statusCode;
  }

  public function getPublicMessage(): string {
    return $this->publicMessage;
  }

  public function getStatusCode(): int {
    return $this->statusCode;
  }
}