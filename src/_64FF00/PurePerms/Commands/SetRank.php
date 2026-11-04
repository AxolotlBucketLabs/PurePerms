<?php

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;

use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;

use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SetRank extends PurePermsCommand
{
    /*
        PurePerms by 64FF00 (Twitter: @64FF00)

          888  888    .d8888b.      d8888  8888888888 8888888888 .d8888b.   .d8888b.
          888  888   d88P  Y88b    d8P888  888        888       d88P  Y88b d88P  Y88b
        888888888888 888          d8P 888  888        888       888    888 888    888
          888  888   888d888b.   d8P  888  8888888    8888888   888    888 888    888
          888  888   888P "Y88b d88   888  888        888       888    888 888    888
        888888888888 888    888 8888888888 888        888       888    888 888    888
          888  888   Y88b  d88P       888  888        888       Y88b  d88P Y88b  d88P
          888  888    "Y8888P"        888  888        888        "Y8888P"   "Y8888P"
    */

    public function __construct(PurePerms $plugin, string $name, string $description)
    {
        parent::__construct($plugin, $name, $description);
        $this->setPermission("pperms.command.setrank");
    }

    /**
     * @param CommandSender $sender
     * @param $label
     * @param list<string> $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args) : bool
    {
        if(!$this->testPermission($sender))
        {
            return false;
        }
        
        if(count($args) < 2 || count($args) > 4)
        {
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setrank.usage"));
            return true;
        }
        $player = $this->plugin->getPlayer($args[0]);
        $group = $this->plugin->getGroup($args[1]);
        if($group === null)
        {
            $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setrank.messages.rank_not_exist", $args[1]));
            return true;
        }

        $expTime = -1;
        if(isset($args[2]))
            $expTime = $this->plugin->date2Int($args[2]);
        $WorldName = null;
        if(isset($args[3]))
        {
            $world = $this->plugin->getServer()->getWorldManager()->getWorldByName($args[3]);
            if($world === null)
            {
                $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setrank.messages.level_not_exist", $args[3]));
                return true;
            }

            $WorldName = $world->getDisplayName();
        }

        $superAdminRanks = $this->plugin->getConfigValue("superadmin-ranks");
        /** @var array<string, int> $tmpSuperAdminRanks */
        $tmpSuperAdminRanks = [];
        foreach(is_array($superAdminRanks) ? array_values($superAdminRanks) : [] as $value)
        {
            if(is_string($value))
                $tmpSuperAdminRanks[$value] = 1;
        }

        if(!($sender instanceof ConsoleCommandSender))
        {
            if(isset($tmpSuperAdminRanks[$group->getName()]))
            {
                $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setrank.messages.access_denied_01", $group->getName()));
                return true;
            }

            $userGroup = $this->plugin->getUserDataMgr()->getGroup($player, $WorldName);
            if($userGroup !== null && isset($tmpSuperAdminRanks[$userGroup->getName()]))
            {
                $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setrank.messages.access_denied_02", $userGroup->getName()));
                return true;
            }
        }

        $this->plugin->getUserDataMgr()->setGroup($player, $group, $WorldName, $expTime);
        
        $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setrank.messages.setrank_successfully", $player->getName()));
        
        if($player instanceof Player)
        {
            if($this->plugin->getConfigValue("enable-multiworld-perms") !== true || ($WorldName === $player->getWorld()->getDisplayName()))
                $player->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.setrank.messages.on_player_rank_change", strtolower($group->getName())));
        }

        return true;
    }
    
}
