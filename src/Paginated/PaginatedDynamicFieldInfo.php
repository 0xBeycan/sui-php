<?php

declare(strict_types=1);

namespace Sui\Paginated;

use Sui\Type\DynamicFieldInfo;

class PaginatedDynamicFieldInfo extends PaginatedBase
{
    /**
     * @var array<DynamicFieldInfo>
     */
    public array $data;

    /**
     * @param PaginatedBase &$instance
     * @param array<mixed> $data
     * @return void
     */
    public static function prepare(PaginatedBase &$instance, array $data): void
    {
        $instance->data = array_map(
            fn(array $item) => new DynamicFieldInfo($item),
            $data
        );
    }
}
