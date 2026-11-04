<?php

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;

use pocketmine\command\CommandSender;
use pocketmine\console\ConsoleCommandSender;
use pocketmine\permission\Permission;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

class PLPerms extends PurePermsCommand
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
        $this->setPermission("pperms.command.plperms");
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
        if(!isset($args[0]) || count($args) > 2)
        {
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.plperms.usage"));
            return true;
        }
        
        $isPocketMine = strtolower($args[0]) === 'pocketmine' || strtolower($args[0]) === 'pmmp';
        $plugin = $isPocketMine ? null : $this->plugin->getServer()->getPluginManager()->getPlugin($args[0]);
        if(!$isPocketMine && $plugin === null)
        {
            $sender->sendMessage(TextFormat::RED . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.plperms.messages.plugin_not_exist", $args[0]));
            return true;
        }
        
        $permissions = [];
        if($plugin instanceof PluginBase)
        {
            foreach($plugin->getDescription()->getPermissions() as $permissionList)
            {
                foreach($permissionList as $permission)
                    $permissions[] = $permission->getName();
            }
        }
        else
        {
            foreach($this->plugin->getPocketMinePerms() as $permission)
                $permissions[] = $permission;
        }
        if(empty($permissions))
        {
            $pluginName = $plugin instanceof PluginBase ? $plugin->getName() : 'PocketMine-MP';
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.plperms.messages.no_plugin_perms", $pluginName));
            return true;
        }
        
        $pageHeight = $sender instanceof ConsoleCommandSender ? 48 : 6;
        $maxPageNumber = max(1, (int) ceil(count($permissions) / $pageHeight));
        if(!isset($args[1]) || !is_numeric($args[1]) || $args[1] <= 0) 
        {
            $pageNumber = 1;
        }
        else if($args[1] > $maxPageNumber)
        {
            $pageNumber = $maxPageNumber;   
        }
        else 
        {
        $pageNumber = (int) $args[1];
        }
        
        $pluginName = $plugin instanceof PluginBase ? $plugin->getName() : 'PocketMine-MP';
        $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' ' . $this->plugin->getMessage("cmds.plperms.messages.plugin_perms_list", $pluginName, $pageNumber, $maxPageNumber));
        $pagePermissions = array_slice($permissions, ($pageNumber - 1) * $pageHeight, $pageHeight);
        foreach($pagePermissions as $permission)
        {
            $sender->sendMessage(TextFormat::GREEN . PurePerms::MAIN_PREFIX . ' - ' . $permission);
        }
        return true;
    }
    
}
