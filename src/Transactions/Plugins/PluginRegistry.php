<?php

declare(strict_types=1);

namespace Sui\Transactions\Plugins;

class PluginRegistry
{
    private static ?PluginRegistry $instance = null;
    /**
     * @var array<string, TransactionPlugin>
     */
    public array $buildPlugins;

    /**
     * @var array<string, TransactionPlugin>
     */
    public array $serializationPlugins;

    /**
     * constructor
     */
    public function __construct()
    {
        $this->buildPlugins = [];
        $this->serializationPlugins = [];
    }

    /**
     * @return PluginRegistry
     */
    public static function getInstance(): PluginRegistry
    {
        if (null === self::$instance) {
            self::$instance = new PluginRegistry();
        }

        return self::$instance;
    }

    /**
     * @param string $name
     * @param TransactionPlugin $step
     * @return void
     */
    public function registerBuildPlugin(string $name, TransactionPlugin $step): void
    {
        $this->buildPlugins[$name] = $step;
    }

    /**
     * @param string $name
     * @param TransactionPlugin $step
     * @return void
     */
    public function registerSerializationPlugin(string $name, TransactionPlugin $step): void
    {
        $this->serializationPlugins[$name] = $step;
    }

    /**
     * @param string $name
     * @return void
     */
    public function unregisterBuildPlugin(string $name): void
    {
        unset($this->buildPlugins[$name]);
    }

    /**
     * @param string $name
     * @return void
     */
    public function unregisterSerializationPlugin(string $name): void
    {
        unset($this->serializationPlugins[$name]);
    }
}
