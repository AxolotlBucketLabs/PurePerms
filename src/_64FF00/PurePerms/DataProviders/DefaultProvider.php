<?php

namespace _64FF00\PurePerms\DataProviders;

use _64FF00\PurePerms\PurePerms;
use _64FF00\PurePerms\PPGroup;

use pocketmine\player\IPlayer;

use pocketmine\utils\Config;
use RuntimeException;

class DefaultProvider implements ProviderInterface
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

    private Config $groups;
    private Config $players;
    private PurePerms $plugin;

    /**
     * @param PurePerms $plugin
     */
    public function __construct(PurePerms $plugin)
    {
        $this->plugin = $plugin;
        $this->plugin->saveResource("ranks.yml");
        $this->groups = new Config($this->plugin->getDataFolder() . "ranks.yml", Config::YAML);
        if(empty($this->groups->getAll())){
			throw new RuntimeException($this->plugin->getMessage("logger_messages.YAMLProvider_InvalidGroupsSettings"));
		}
        $this->plugin->saveResource("players.yml");
        $this->players = new Config($this->plugin->getDataFolder() . "players.yml", Config::YAML);
    }

    /** @return array<string, mixed> */
    public function getGroupData(PPGroup $group): array
    {
        $groupName = $group->getName();
        return $this->getGroupsData()[$groupName] ?? [];
    }

    public function getGroupsConfig(): Config
    {
        return $this->groups;
    }

    /** @return array<string, array<string, mixed>> */
    public function getGroupsData(): array
    {
        $groups = $this->groups->getAll();
        /** @var array<string, array<string, mixed>> $groups */
        return $groups;
    }

    public function getPlayerData(IPlayer $player): array
    {
        $userName = strtolower($player->getName());

        if(!$this->players->exists($userName))
        {
            return [
                "group" => $this->plugin->getDefaultGroup()?->getName() ?? "default",
                "permissions" => [],
                "worlds" => [],
                "time" => -1
            ];
        }

        $data = $this->players->get($userName);
        if(!is_array($data))
            return [];
        $result = [];
        foreach($data as $key => $value)
        {
            if(is_string($key))
                $result[$key] = $value;
        }
        return $result;
    }

    public function getUsers(): ?array
    {
        /*
        if(empty($this->players->getAll()))
            return null;

        return $this->players->getAll();
        */
        return null;
    }

    /**
     * @param PPGroup $group
     * @param array<string, mixed> $tempGroupData
     */
    public function setGroupData(PPGroup $group, array $tempGroupData): void
    {
        $groupName = $group->getName();
        $this->groups->set($groupName, $tempGroupData);
        $this->groups->save();
    }

    /**
     * @param array<string, array<string, mixed>> $tempGroupsData
     */
    public function setGroupsData(array $tempGroupsData): void
    {
        $this->groups->setAll($tempGroupsData);
        $this->groups->save();
    }

    /**
     * @param IPlayer $player
     * @param array<string, mixed> $tempUserData
     */
    public function setPlayerData(IPlayer $player, array $tempUserData): void
    {
        $userName = strtolower($player->getName());
        if(!$this->players->exists($userName))
        {
            $this->players->set($userName, [
                "group" => $this->plugin->getDefaultGroup()?->getName() ?? "default",
                "permissions" => [],
                "worlds" => [],
                "time" => -1
            ]);
        }

        if(isset($tempUserData["userName"]))
            unset($tempUserData["userName"]);
        $this->players->set($userName, $tempUserData);
        $this->players->save();
    }

    public function reload(): void
    {
        $this->groups->reload();
        $this->players->reload();
    }

    public function close(): void
    {
        // Nothing to close for the file-backed provider.
    }
}
