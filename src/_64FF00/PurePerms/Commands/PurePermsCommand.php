<?php

declare(strict_types=1);

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;
use pocketmine\command\Command;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;

abstract class PurePermsCommand extends Command implements PluginOwned
{
    public function __construct(
        protected PurePerms $plugin,
        string $name,
        string $description,
    ) {
        parent::__construct($name, $description);
    }

    public function getPlugin(): PurePerms
    {
        return $this->plugin;
    }

    public function getOwningPlugin(): Plugin
    {
        return $this->plugin;
    }
}
