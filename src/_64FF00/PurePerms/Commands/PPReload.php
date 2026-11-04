<?php

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;
use Throwable;

class PPReload extends PurePermsCommand
{
    public function __construct(PurePerms $plugin, string $name, string $description)
    {
        parent::__construct($plugin, $name, $description);
        $this->setPermission("pperms.command.ppreload");
    }

    /** @param list<string> $args */
    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        if(!$this->testPermission($sender))
            return false;

        try
        {
            $this->plugin->reload();
        }
        catch(Throwable $exception)
        {
            $this->plugin->getLogger()->error("Unable to reload PurePerms: " . $exception->getMessage());
            $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.ppreload.messages.failed"));
            return true;
        }

        $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.ppreload.messages.successfully_reloaded"));
        return true;
    }
}
