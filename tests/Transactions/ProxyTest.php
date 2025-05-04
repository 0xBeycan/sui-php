<?php

declare(strict_types=1);

namespace Tests\Transactions;

use PHPUnit\Framework\TestCase;
use Sui\Transactions\Proxy;
use Sui\Transactions\Transaction;

class ProxyTest extends TestCase
{
    /**
     * Test the get and set functionality of the Proxy class
     * @return void
     */
    public function testProxyGetAndSet(): void
    {
        $target = new \stdClass();
        $target->name = 'Test';

        $handler = new class {
            /**
             * @param object $target
             * @param string $name
             * @return mixed
             */
            public function get(object $target, string $name): mixed
            {
                return $target->$name ?? null;
            }

            /**
             * @param object $target
             * @param string $name
             * @param mixed $value
             * @return void
             */
            public function set(object $target, string $name, mixed $value): void
            {
                $target->$name = $value;
            }
        };

        $proxy = new Proxy($target, $handler);

        $this->assertEquals('Test', $proxy->name);

        $proxy->name = 'New Test';
        $this->assertEquals('New Test', $target->name);
    }

    /**
     * Test the method calling functionality of the Proxy class
     * @return void
     */
    public function testProxyCall(): void
    {
        $target = new class {
            /**
             * @param string $param
             * @return string
             */
            public function testMethod(string $param): string
            {
                return "Called with: $param";
            }
        };

        $handler = new class {
            /**
             * @param object $target
             * @param string $name
             * @param array<mixed> $arguments
             * @return mixed
             */
            public function call(object $target, string $name, array $arguments): mixed
            {
                return call_user_func_array([$target, $name], $arguments);
            }
        };

        $proxy = new Proxy($target, $handler);

        $this->assertEquals('Called with: test', $proxy->testMethod('test'));
    }

    /**
     * Test the createTransactionResult method functionality
     * @return void
     */
    public function testCreateTransactionResult(): void
    {
        $result = Transaction::createTransactionResult(0);

        // Test basic result structure
        $this->assertEquals('Result', $result->{'$kind'});
        $this->assertEquals(0, $result->result);

        // Test nested result access
        $nestedResult = $result->{0};
        $this->assertEquals('NestedResult', $nestedResult->{'$kind'});
        $this->assertEquals([0, 0], $nestedResult->NestedResult); // @phpcs:ignore

        // Test multiple nested results
        $nestedResult1 = $result->{1};
        $this->assertEquals('NestedResult', $nestedResult1->{'$kind'});
        $this->assertEquals([0, 1], $nestedResult1->NestedResult); // @phpcs:ignore

        // Test negative index
        $this->assertNull($result->{-1});

        // Test non-numeric property
        $this->assertNull($result->invalid);
    }

    /**
     * Test the createTransactionResult method with length parameter
     * @return void
     */
    public function testCreateTransactionResultWithLength(): void
    {
        $result = Transaction::createTransactionResult(0, 2);

        // Test basic result structure
        $this->assertEquals('Result', $result->{'$kind'});
        $this->assertEquals(0, $result->result);

        // Test nested results within length limit
        $this->assertNotNull($result->{0});
        $this->assertNotNull($result->{1});

        // Test beyond length limit - should return null for numeric access
        $this->assertNull($result->{2});

        // Test that the result is still iterable
        $count = 0;
        foreach ($result as $item) {
            $count++;
        }
        $this->assertEquals(2, $count);
    }

    /**
     * Test the handling of symbol-like properties in Proxy
     * @return void
     */
    public function testProxySymbolProperties(): void
    {
        $target = new \stdClass();
        $handler = new class {
            /**
             * @param object $target
             * @param string $name
             * @return mixed
             */
            public function get(object $target, string $name): mixed
            {
                return $target->$name ?? null;
            }
        };

        $proxy = new Proxy($target, $handler);

        // Test symbol-like properties
        $this->assertNull($proxy->iterator);
        $this->assertNull($proxy->toStringTag);
        $this->assertNull($proxy->constructor);
        $this->assertNull($proxy->prototype);
        $this->assertNull($proxy->{'Symbol(test)'});
    }
}
