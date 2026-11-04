<?php

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

class SetGPerm extends PurePermsCommand
{
    public function __construct(PurePerms $plugin, string $name, string $description)
    {
        parent::__construct($plugin, $name, $description);
        $this->setPermission("pperms.command.setgperm");
    }

    /** @param list<string> $args */
    public function execute(CommandSender $sender, string $label, array $args): bool
    {
        if(!$this->testPermission($sender))
            return false;
        if(count($args) < 2 || count($args) > 3)
        {
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setgperm.usage"));
            return true;
        }

        $group = $this->plugin->getGroup($args[0]);
        if($group === null)
        {
            $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setgperm.messages.group_not_exist", $args[0]));
            return true;
        }

        $worldName = null;
        if(isset($args[2]))
        {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($args[2]);
            if($world === null)
            {
                $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setgperm.messages.level_not_exist", $args[2]));
                return true;
            }
            $worldName = $world->getDisplayName();
        }

        $group->setGroupPermission($args[1], $worldName);
        $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setgperm.messages.gperm_added_successfully", $args[1]));
        return true;
    }
}
