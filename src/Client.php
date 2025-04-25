<?php

declare(strict_types=1);

namespace Sui;

class Client
{
    /**
     * @param string $url The URL of the XRPL server to connect to.
     */
    public function __construct(private string $url)
    {
        // Initialize the client with the provided URL
    }
}
