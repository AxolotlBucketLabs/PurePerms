<?php

namespace _64FF00\PurePerms\DataManager;

use _64FF00\PurePerms\PPGroup;
use _64FF00\PurePerms\PurePerms;
use _64FF00\PurePerms\EventManager\PPRankChangedEvent;

use pocketmine\player\IPlayer;

class UserDataManager
{
    private PurePerms $plugin;

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

    /**
     * @param PurePerms $plugin
     */
    public function __construct(PurePerms $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @param IPlayer $player
     * @return array
     */
    /** @return array<string, mixed> */
    public function getData(IPlayer $player): array
    {
        return $this->plugin->getProvider()->getPlayerData($player);
    }

    public function getExpDate(IPlayer $player, ?string $WorldName = null): int
    {
        $expDate = $WorldName !== null ? $this->getWorldData($player, $WorldName)["expTime"] ?? -1 : $this->getNode($player, "expTime");
        return is_int($expDate) ? $expDate : -1;
    }

    /**
     * @param IPlayer $player
     * @param ?string $WorldName
     * @return PPGroup|null
     */
    public function getGroup(IPlayer $player, ?string $WorldName = null): ?PPGroup
    {
        $groupName = $WorldName !== null ? $this->getWorldData($player, $WorldName)["group"] : $this->getNode($player, "group");
        $group = is_string($groupName) ? $this->plugin->getGroup($groupName) : null;
        // TODO: ...
        if($group === null)
        {
            $this->plugin->getLogger()->critical("Invalid group name found in " . $player->getName() . "'s player data (World: " . ($WorldName === null ? "GLOBAL" : $WorldName) . ")");
            $this->plugin->getLogger()->critical("Restoring the group data to 'default'");
            $defaultGroup = $this->plugin->getDefaultGroup($WorldName);
            if($defaultGroup === null)
                return null;
            $this->setGroup($player, $defaultGroup, $WorldName);
            return $defaultGroup;
        }

        return $group;
    }

    /**
     * @param IPlayer $player
     * @param string $node
     * @return mixed
     */
    public function getNode(IPlayer $player, string $node): mixed
    {
        $userData = $this->getData($player);
        if(!isset($userData[$node]))
            return null;
        return $userData[$node];
    }

    /**
     * @param ?string $WorldName
     * @return list<string>
     */
    public function getUserPermissions(IPlayer $player, ?string $WorldName = null): array
    {
        $permissions = $WorldName !== null ? $this->getWorldData($player, $WorldName)["permissions"] ?? [] : $this->getNode($player, "permissions");
        return $this->stringList($permissions);
    }

    /**
     * @param IPlayer $player
     * @param ?string $WorldName
     * @return array<string, mixed>
     */
    public function getWorldData(IPlayer $player, ?string $WorldName = null): array
    {
        if($WorldName === null)
        {
            $defaultWorld = $this->plugin->getServer()->getWorldManager()->getDefaultWorld();
            if($defaultWorld === null)
            {
                /** @var array<string, mixed> $fallback */
                $fallback = ["group" => $this->plugin->getDefaultGroup()?->getName() ?? "default", "permissions" => [], "expTime" => -1];
                return $fallback;
            }
            $WorldName = $defaultWorld->getDisplayName();
        }
        $data = $this->getData($player);
        $worlds = $data["worlds"] ?? null;
        if(!is_array($worlds) || !isset($worlds[$WorldName]) || !is_array($worlds[$WorldName]))
        {
            /** @var array<string, mixed> $fallback */
            $fallback = [
                "group" => $this->plugin->getDefaultGroup($WorldName)?->getName() ?? "default",
                "permissions" => [
                ],
                "expTime" => -1
            ];
            return $fallback;
        }
        /** @var array<string, mixed> $worldData */
        $worldData = $worlds[$WorldName];
        return $worldData;
    }

    public function removeNode(IPlayer $player, string $node): void
    {
        $tempUserData = $this->getData($player);
        if(isset($tempUserData[$node]))
        {
            unset($tempUserData[$node]);
            $this->setData($player, $tempUserData);
        }
    }

    /**
     * @param IPlayer $player
     * @param array<string, mixed> $data
     */
    public function setData(IPlayer $player, array $data): void
    {
        $this->plugin->getProvider()->setPlayerData($player, $data);
    }

    /**
     * @param IPlayer $player
     * @param PPGroup $group
     * @param ?string $WorldName
     * @param int $time
     */
    public function setGroup(IPlayer $player, PPGroup $group, ?string $WorldName = null, int $time = -1): void
    {
        if($WorldName === null)
        {
            $this->setNode($player, "group", $group->getName());
            $this->setNode($player, "expTime", $time);
        }
        else
        {
            $worldData = $this->getWorldData($player, $WorldName);
            $worldData["group"] = $group->getName();
            $worldData["expTime"] = $time;
            $this->setWorldData($player, $WorldName, $worldData);
        }

        $event = new PPRankChangedEvent($this->plugin, $player, $group, $WorldName);

        $event->call();
    }

    /**
     * @param IPlayer $player
     * @param string $node
     * @param mixed $value
     */
    public function setNode(IPlayer $player, string $node, mixed $value): void
    {
        $tempUserData = $this->getData($player);
        $tempUserData[$node] = $value;
        $this->setData($player, $tempUserData);
    }

    /**
     * @param IPlayer $player
     * @param string $permission
     * @param ?string $WorldName
     */
    public function setPermission(IPlayer $player, string $permission, ?string $WorldName = null): void
    {
        if($WorldName === null)
        {
            $tempUserData = $this->getData($player);
            $permissions = $this->stringList($tempUserData["permissions"] ?? null);
            $permissions[] = $permission;
            $tempUserData["permissions"] = $permissions;
            $this->setData($player, $tempUserData);
        }
        else
        {
            $worldData = $this->getWorldData($player, $WorldName);
            $permissions = $this->stringList($worldData["permissions"] ?? null);
            $permissions[] = $permission;
            $worldData["permissions"] = $permissions;
            $this->setWorldData($player, $WorldName, $worldData);
        }

        $this->plugin->updatePermissions($player);
    }

    /** @param array<string, mixed> $worldData */
    public function setWorldData(IPlayer $player, string $WorldName, array $worldData): void
    {
        $tempUserData = $this->getData($player);
        $worlds = $tempUserData["worlds"] ?? null;
        if(!is_array($worlds))
            $worlds = [];
        if(!isset($worlds[$WorldName]))
        {
            $worlds[$WorldName] = [
                "group" => $this->plugin->getDefaultGroup()?->getName() ?? "default",
                "permissions" => [
                ],
                "expTime" => -1
            ];

            $tempUserData["worlds"] = $worlds;
            $this->setData($player, $tempUserData);
        }
        $worlds[$WorldName] = $worldData;
        $tempUserData["worlds"] = $worlds;
        $this->setData($player, $tempUserData);
    }

    /**
     * @param IPlayer $player
     * @param string $permission
     * @param ?string $WorldName
     */
    public function unsetPermission(IPlayer $player, string $permission, ?string $WorldName = null): void
    {
        if($WorldName === null)
        {
            $tempUserData = $this->getData($player);
            $permissions = $this->stringList($tempUserData["permissions"] ?? null);
            if(!in_array($permission, $permissions, true)) return;
            $tempUserData["permissions"] = array_values(array_diff($permissions, [$permission]));
            $this->setData($player, $tempUserData);
        }
        else
        {
            $worldData = $this->getWorldData($player, $WorldName);
            $permissions = $this->stringList($worldData["permissions"] ?? null);
            if(!in_array($permission, $permissions, true)) return;
            $worldData["permissions"] = array_values(array_diff($permissions, [$permission]));
            $this->setWorldData($player, $WorldName, $worldData);
        }

        $this->plugin->updatePermissions($player);
    }

    /**
     * @param mixed $value
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if(!is_array($value))
            return [];

        $result = [];
        foreach($value as $item)
        {
            if(is_string($item))
                $result[] = $item;
        }

        return $result;
    }
}
