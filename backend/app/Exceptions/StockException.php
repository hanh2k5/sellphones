<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Exception cho lỗi tồn kho trong CartService.
 * Mang theo thông tin stock hiện tại để Controller có thể trả về client
 * mà không cần query thêm Product model.
 */
class StockException extends RuntimeException
{
    public function __construct(string $message, private int $stock = 0)
    {
        parent::__construct($message, 422);
    }

    public function getStock(): int
    {
        return $this->stock;
    }
}
