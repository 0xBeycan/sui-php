<?php

declare(strict_types=1);

namespace Sui;

class Constants
{
    /**
     * Number of decimals used in SUI coin
     */
    public const SUI_DECIMALS = 9;

    /**
     * Number of MIST per SUI (10^9)
     */
    public const MIST_PER_SUI = '1000000000';

    /**
     * Move stdlib address
     */
    public const MOVE_STDLIB_ADDRESS = '0x1';

    /**
     * SUI framework address
     */
    public const SUI_FRAMEWORK_ADDRESS = '0x2';

    /**
     * SUI system address
     */
    public const SUI_SYSTEM_ADDRESS = '0x3';

    /**
     * SUI clock object ID
     * Note: Original JS uses normalizeSuiObjectId function
     */
    public const SUI_CLOCK_OBJECT_ID = '0x6';

    /**
     * SUI system module name
     */
    public const SUI_SYSTEM_MODULE_NAME = 'sui_system';

    /**
     * SUI type argument
     */
    public const SUI_TYPE_ARG = '0x2::sui::SUI';

    /**
     * SUI system state object ID
     * Note: Original JS uses normalizeSuiObjectId function
     */
    public const SUI_SYSTEM_STATE_OBJECT_ID = '0x5';

    /**
     * SUI address length
     */
    public const SUI_ADDRESS_LENGTH = 32;
}
