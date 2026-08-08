<?php

namespace App\Support;

class InventoryTransactionType
{
    public const INITIAL_STOCK = 'initial_stock';

    public const PURCHASE = 'purchase';

    public const STOCK_IN = 'stock_in';

    public const ISSUE = 'issue';

    public const CONSUMPTION = 'consumption';

    public const RETURN = 'return';

    public const TRANSFER_IN = 'transfer_in';

    public const TRANSFER_OUT = 'transfer_out';

    public const TRANSFER = 'transfer';

    public const ADJUSTMENT = 'adjustment';

    public const DAMAGED = 'damaged';

    public const LOST = 'lost';

    public const DISPOSAL = 'disposal';

    public const BORROW = 'borrow';

    public const SALE = 'sale';

    public const SALE_RETURN = 'sale_return';

    public const CONDITION_CHANGE = 'condition_change';

    /** @deprecated Use ISSUE or CONSUMPTION */
    public const STOCK_OUT = 'stock_out';

    /**
     * Transaction types that permanently increase quantity.
     *
     * @return list<string>
     */
    public static function inbound(): array
    {
        return [
            self::INITIAL_STOCK,
            self::PURCHASE,
            self::STOCK_IN,
            self::RETURN,
            self::SALE_RETURN,
            self::TRANSFER_IN,
        ];
    }

    /**
     * Transaction types that permanently decrease quantity.
     *
     * @return list<string>
     */
    public static function outbound(): array
    {
        return [
            self::ISSUE,
            self::CONSUMPTION,
            self::STOCK_OUT,
            self::SALE,
            self::TRANSFER_OUT,
            self::DAMAGED,
            self::LOST,
            self::DISPOSAL,
        ];
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        return [
            self::INITIAL_STOCK,
            self::PURCHASE,
            self::STOCK_IN,
            self::ISSUE,
            self::CONSUMPTION,
            self::STOCK_OUT,
            self::RETURN,
            self::TRANSFER_IN,
            self::TRANSFER_OUT,
            self::TRANSFER,
            self::ADJUSTMENT,
            self::DAMAGED,
            self::LOST,
            self::DISPOSAL,
            self::BORROW,
            self::SALE,
            self::SALE_RETURN,
            self::CONDITION_CHANGE,
        ];
    }

    public static function label(string $type): string
    {
        return match ($type) {
            self::SALE => 'Sale',
            self::SALE_RETURN => 'Sale Return',
            default => ucwords(str_replace('_', ' ', $type)),
        };
    }
}
