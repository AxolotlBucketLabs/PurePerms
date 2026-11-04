<?php

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;

use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\utils\TextFormat;

class PPInfo extends PurePermsCommand
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
        $this->setPermission("pperms.command.ppinfo");
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
            return false;
        $author = $this->plugin->getDescription()->getAuthors()[0];
        $version = $this->plugin->getDescription()->getVersion();
        if($sender instanceof ConsoleCommandSender)
        {
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.ppinfo.messages.ppinfo_console", $version, $author));
        }
        else
        {
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.ppinfo.messages.ppinfo_player", $version, $author));
        }

        return true;
    }
    
}
