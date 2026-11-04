<?php

namespace _64FF00\PurePerms\DataProviders;

use _64FF00\PurePerms\PurePerms;
use _64FF00\PurePerms\PPGroup;

use pocketmine\player\IPlayer;

use pocketmine\utils\Config;
use RuntimeException;

class JsonProvider implements ProviderInterface
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
    private string $userDataFolder;

    private PurePerms $plugin;

    /**
     * @param PurePerms $plugin
     */
    public function __construct(PurePerms $plugin)
    {
        $this->plugin = $plugin;
        $this->plugin->saveResource("ranks.yml");
        $this->groups = new Config($this->plugin->getDataFolder() . "ranks.yml", Config::YAML, []);
        $this->userDataFolder = $this->plugin->getDataFolder() . "players/";
        if(!file_exists($this->userDataFolder))
            @mkdir($this->userDataFolder, 0777, true);
    }
    /** @return array<string, mixed> */
    public function getGroupData(PPGroup $group): array
    {
        $groupName = $group->getName();

        return $this->getGroupsData()[$groupName] ?? [];
    }

    /**
     * @return Config
     */
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
    /**
     * @param IPlayer $player
     * @param bool $onUpdate
     * @return array<string, mixed>|Config
     */
    public function getPlayerConfig(IPlayer $player, bool $onUpdate = false): array|Config
    {
        $userName = $player->getName();

        // TODO
        if($onUpdate === true)
        {
            if(!file_exists($this->userDataFolder . strtolower($userName) . ".json"))
            {
                return new Config($this->userDataFolder . strtolower($userName) . ".json", Config::YAML, [
                    "userName" => $userName,
                    "group" => $this->plugin->getDefaultGroup()?->getName() ?? "default",
                    "permissions" => [],
                    "worlds" => [],
                    "time" => -1
                ]);
            }
            else
            {
                return new Config($this->userDataFolder . strtolower($userName) . ".json", Config::YAML, [
                ]);
            }
        }
        else
        {
            if(file_exists($this->userDataFolder . strtolower($userName) . ".json"))
            {
                return new Config($this->userDataFolder . strtolower($userName) . ".json", Config::YAML, [
                ]);
            }
            else
            {
                return [
                    "userName" => $userName,
                    "group" => $this->plugin->getDefaultGroup()?->getName() ?? "default",
                    "permissions" => [],
                    "worlds" => [],
                    "time" => -1
                ];
            }
        }
    }
    /**
     * @param IPlayer $player
     * @return array<string, mixed>
     */
    public function getPlayerData(IPlayer $player): array
    {
        $userConfig = $this->getPlayerConfig($player);

        if($userConfig instanceof Config)
        {
            /** @var array<string, mixed> $data */
            $data = $userConfig->getAll();
            return $data;
        }

        return $userConfig;
    }

    public function getUsers(): ?array
    {
        // TODO: Implement getUsers() method.
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
        $userData = $this->getPlayerConfig($player, true);

        if(!$userData instanceof Config)
            throw new RuntimeException("Failed to update player data: Invalid data type");

        $userData->setAll($tempUserData);

        $userData->save();
    }

    public function reload(): void
    {
        $this->groups->reload();
    }

    public function close(): void
    {
        // Nothing to close for the JSON provider.
    }
}
