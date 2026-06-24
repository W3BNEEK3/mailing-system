<?php

declare(strict_types=1);

namespace App\Interfaces;

/**
 * LoggerInterface
 *
 * Defines the contract for all logger implementations.
 * Each method corresponds to a severity level.
 *
 * @param string $message  What happened
 * @param array  $context  Extra data to include (will be JSON-encoded)
 */
interface LoggerInterface
{
    public function debug(string $message, array $context = []): void;
    public function info(string $message, array $context = []): void;
    public function warning(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;
    public function critical(string $message, array $context = []): void;
}
