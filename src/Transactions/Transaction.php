<?php

declare(strict_types=1);

namespace Sui\Transactions;

class Transaction
{
    public TransactionDataBuilder $data;

    /**
     * Undocumented function
     */
    public function __construct()
    {
    }

    /**
     * Build the transaction
     *
     * @param array<mixed> $options
     * @return array<mixed>
     */
    public function build(array $options = []): array
    {
        return [];
    }

    /**
     * @param string $sender
     * @return void
     */
    public function setSender(string $sender): void
    {
        $this->data->sender = $sender;
    }

    /**
     * Sets the sender only if it has not already been set.
     * This is useful for sponsored transaction flows where the sender may not be the same as the signer address.
     *
     * @param string $sender
     * @return void
     */
    public function setSenderIfNotSet(string $sender): void
    {
        if (!$this->data->sender) {
            $this->data->sender = $sender;
        }
    }

    /**
     * @param int $index
     * @param int $length
     * @return Proxy
     */
    public static function createTransactionResult(int $index, int $length = PHP_INT_MAX): Proxy
    {
        $baseResult = (object) [
            '$kind' => 'Result',
            'Result' => $index,
        ];

        $nestedResults = [];

        $nestedResultFor = function (int $resultIndex) use ($index, &$nestedResults): object {
            if (!isset($nestedResults[$resultIndex])) {
                $nestedResults[$resultIndex] = (object) [
                    '$kind' => 'NestedResult',
                    'NestedResult' => [$index, $resultIndex],
                ];
            }
            return $nestedResults[$resultIndex];
        };

        return new Proxy($baseResult, new class ($nestedResultFor, $length) {
            /**
             * @var \Closure
             */
            private \Closure $nestedResultFor;

            /**
             * @var int
             */
            private int $length;

            /**
             * @param \Closure $nestedResultFor
             * @param int $length
             */
            public function __construct(\Closure $nestedResultFor, int $length)
            {
                $this->nestedResultFor = $nestedResultFor;
                $this->length = $length;
            }

            /**
             * @param int $resultIndex
             * @return object
             */
            private function nestedResultFor(int $resultIndex): object
            {
                return ($this->nestedResultFor)($resultIndex);
            }

            /**
             * @param object $target
             * @param string $property
             * @param mixed $value
             * @return void
             */
            public function set(object $target, string $property, mixed $value): void
            {
                throw new \Exception(
                    'The transaction result is a proxy, and does not support setting properties directly',
                );
            }

            /**
             * @param object $target
             * @param string $property
             * @return mixed
             */
            public function get(object $target, string $property): mixed
            {
                // This allows this transaction argument to be used in the singular form:
                if (property_exists($target, $property)) {
                    return Proxy::reflectGet($target, $property);
                }

                // Check if the property is __iterator__ for iteration support
                if ('__iterator__' === $property) {
                    return function () {
                        $i = 0;
                        while ($i < $this->length) {
                            yield $this->nestedResultFor($i);
                            $i++;
                        }
                    };
                }

                // Handle symbol-like properties
                if (Proxy::isSymbol($property)) {
                    return null;
                }

                // Check for numeric property
                if (is_numeric($property)) {
                    $resultIndex = (int)$property;
                    if ($resultIndex < 0 || $resultIndex >= $this->length) {
                        return null;
                    }
                    return $this->nestedResultFor($resultIndex);
                }

                return null;
            }
        });
    }
}
