<?php

declare(strict_types=1);

namespace Sui\Transactions\Commands;

class TypeSignature
{
    /**
     * @var array<string>
     */
    private array $ref = ['&', '&mut'];

    /**
     * @var array<mixed>
     * @see https://github.com/MystenLabs/ts-sdks/blob/b4cfe2f551d2133c1a87a008c1f99560f609c229/packages/typescript/src/transactions/data/internal.ts#L153
     */
    private array $body;

    /**
     * @param array<string,string> $ref
     * @param array<mixed> $body
     */
    public function __construct(array $ref, array $body)
    {
        $this->ref = $ref;
        $this->body = $body;
    }

    /**
     * @return array<string,string>
     */
    public function getRef(): array
    {
        return $this->ref;
    }

    /**
     * @return array<mixed>
     */
    public function getBody(): array
    {
        return $this->body;
    }

    /**
     * @param array<string,string> $ref
     * @return self
     */
    public function setRef(array $ref): self
    {
        $this->ref = $ref;
        return $this;
    }

    /**
     * @param array<mixed> $body
     * @return self
     */
    public function setBody(array $body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * @return array<string,array<string,mixed>>
     */
    public function toArray(): array
    {
        return [
            'ref' => $this->ref,
            'body' => $this->body,
        ];
    }
}
