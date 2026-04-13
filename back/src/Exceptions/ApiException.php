<?php
declare(strict_types=1);

namespace App\Exceptions;

use Exception;

class ApiException extends Exception {
  private string $publicMessage;

  /**
   * @param string $publicMessage
   * @param int $statusCode
   * @param string $internalMessage
   * @param Exception $previous
   * @return void
   */
  public function __construct(
    string $publicMessage,
    int $statusCode = 500,
    ?string $internalMessage = null,
    ?Exception $previous = null
  ) {
    parent::__construct($internalMessage ?? $publicMessage, $statusCode, $previous);
    $this->publicMessage = $publicMessage;
  }

  public function getPublicMessage(): string {
    return $this->publicMessage;
  }

  public function getStatusCode(): int {
    return $this->code;
  }
}