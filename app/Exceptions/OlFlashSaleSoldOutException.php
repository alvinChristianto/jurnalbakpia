<?php

namespace App\Exceptions;

use Exception;

class OlFlashSaleSoldOutException extends Exception
{
    public function __construct(
        public readonly string $productId,
        public readonly string $flashSaleItemId,
    ) {
        parent::__construct('Flash sale quota is exhausted');
    }
}
