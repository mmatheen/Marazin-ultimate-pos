<?php

namespace App\Services\Product;

use App\Services\Product\Concerns\ProductStockAutocomplete;
use App\Services\Product\Concerns\ProductStockHistory;
use App\Services\Product\Concerns\ProductStockImei;
use App\Services\Product\Concerns\ProductStockListing;
use App\Services\Product\Concerns\ProductStockOpening;
use App\Services\Product\Concerns\ProductStockNotifications;

class ProductStockService
{
    use ProductStockNotifications;
    use ProductStockImei;
    use ProductStockOpening;
    use ProductStockHistory;
    use ProductStockAutocomplete;
    use ProductStockListing;
}
