<?php

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class UnsetGPerm extends PurePermsCommand
{
    public function __construct(PurePerms $plugin, string $name, string $description)
    {
        parent::__construct($plugin, $name, $description);
        $this->setPermission("pperms.command.unsetgperm");
    }

    /** @param list<string> $args */
    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        if(!$this->testPermission($sender))
            return false;
        if(count($args) < 2 || count($args) > 3)
        {
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.unsetgperm.usage"));
            return true;
        }

        $group = $this->plugin->getGroup($args[0]);
        if($group === null)
        {
            $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.unsetgperm.messages.group_not_exist", $args[0]));
            return true;
        }

        $worldName = null;
        if(isset($args[2]))
        {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($args[2]);
            if($world === null)
            {
                $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.unsetgperm.messages.level_not_exist", $args[2]));
                return true;
            }
            $worldName = $world->getDisplayName();
        }

        if(!$group->unsetGroupPermission($args[1], $worldName))
        {
            $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.unsetgperm.messages.gperm_not_found", $args[1]));
            return true;
        }

        $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.unsetgperm.messages.gperm_removed_successfully", $args[1]));
        return true;
    }
}
