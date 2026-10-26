<?php

return [
    // Minimum total item quantity per checkout.
    // Must match FE-bakpia/lib/order-rules.ts
    'min_checkout_qty' => 2,

    // Flash sale: quota reservation window in minutes. Applied as the
    // Midtrans Snap expiry on flash-sale orders and used by the
    // flash-sales:release-expired sweep as the backstop.
    'flashsale_reservation_ttl' => 15,

    // Flash sale: cache TTL (seconds) for the public /flash-sales/active
    // payload. Checkout always recomputes regardless of cache staleness.
    'flashsale_cache_ttl' => 30,
];
