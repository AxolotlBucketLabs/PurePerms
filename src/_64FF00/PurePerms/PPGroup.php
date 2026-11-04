<?php

namespace _64FF00\PurePerms;

class PPGroup
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

    private string $name;
    private PurePerms $plugin;
    /** @var list<PPGroup> */
    private array $parents = [];

    /**
     * @param PurePerms $plugin
     * @param string $name
     */
    public function __construct(PurePerms $plugin, string $name)
    {
        $this->plugin = $plugin;
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->name;
    }

    /**
     * @param PPGroup $group
     * @return bool
     */
    public function addParent(PPGroup $group)
    {
        $tempGroupData = $this->getData();
        if($this === $group || in_array($this, $group->getParentGroups(), true))
            return false;
        $inheritance = $this->stringList($tempGroupData["inheritance"] ?? null);
        $inheritance[] = $group->getName();
        $tempGroupData["inheritance"] = $inheritance;
        $this->setData($tempGroupData);
        $this->plugin->updatePlayersInGroup($this);
        return true;
    }

    /**
     * @param string $WorldName
     */
    public function createWorldData(string $WorldName): void
    {
        $data = $this->getData();
        if(!isset($data["worlds"]) || !is_array($data["worlds"]))
            $data["worlds"] = [];
        if(!isset($data["worlds"][$WorldName]))
        {
            $data["worlds"][$WorldName] = [
                "isDefault" => false,
                "permissions" => [
                ]
            ];
            $this->setData($data);
        }
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        $alias = $this->getNode("alias");
        return is_string($alias) ? $alias : $this->name;
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->plugin->getProvider()->getGroupData($this);
    }

    /**
     * @param ?string $WorldName
     * @return list<string>
     */
    public function getGroupPermissions(?string $WorldName = null): array
    {
        $data = $WorldName !== null ? $this->getWorldData($WorldName) : $this->getData();
        $permissions = $this->stringList($data["permissions"] ?? null);

        /** @var PPGroup $parentGroup */
        foreach($this->getParentGroups() as $parentGroup)
        {
            $parentPermissions = $parentGroup->getGroupPermissions($WorldName);
            // Fixed by @mad-hon (https://github.com/mad-hon) / Tysm! :D
            $permissions = array_merge($parentPermissions, $permissions);
        }
        return $permissions;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $node
     * @return mixed
     */
    public function getNode(string $node): mixed
    {
        $data = $this->getData();
        return $data[$node] ?? null;
    }

    /**
     * @return PPGroup[]
     */
    public function getParentGroups(): array
    {
        if($this->parents === [])
        {
            foreach($this->stringList($this->getNode("inheritance")) as $parentGroupName)
            {
                $parentGroup = $this->plugin->getGroup($parentGroupName);
                if($parentGroup !== null)
                    $this->parents[] = $parentGroup;
            }
        }

        return $this->parents;
    }

    /**
     * @param string $WorldName
     * @return array<string, mixed>
     */
    public function getWorldData(string $WorldName): array
    {
        $this->createWorldData($WorldName);
        $worlds = $this->getData()["worlds"] ?? null;
        if(!is_array($worlds) || !isset($worlds[$WorldName]) || !is_array($worlds[$WorldName]))
            return [];
        /** @var array<string, mixed> $worldData */
        $worldData = $worlds[$WorldName];
        return $worldData;
    }

    /**
     * @param string $WorldName
     * @param string $node
     * @return mixed
     */
    public function getWorldNode(string $WorldName, string $node): mixed
    {
        return $this->getWorldData($WorldName)[$node] ?? null;
    }

    /**
     * @param ?string $WorldName
     * @return bool
     */
    public function isDefault(?string $WorldName = null): bool
    {
        if($WorldName === null)
        {
            return ($this->getNode("isDefault") === true);
        }
        else
        {
            return ($this->getWorldData($WorldName)["isDefault"] === true);
        }
    }

    /**
     * @param string $node
     */
    public function removeNode(string $node): void
    {
        $tempGroupData = $this->getData();
        if(isset($tempGroupData[$node]))
        {               
            unset($tempGroupData[$node]);   
            
            $this->setData($tempGroupData);
        }
    }

    /**
     * @param PPGroup $group
     * @return bool
     */
    public function removeParent(PPGroup $group)
    {
        $tempGroupData = $this->getData();
        $tempGroupData["inheritance"] = array_values(array_diff($this->stringList($tempGroupData["inheritance"] ?? null), [$group->getName()]));
        $this->setData($tempGroupData);
        $this->plugin->updatePlayersInGroup($this);

        return true;
    }

    /**
     * @param string $WorldName
     * @param string $node
     */
    public function removeWorldNode(string $WorldName, string $node): void
    {
        $worldData = $this->getWorldData($WorldName);
        if(isset($worldData[$node]))
        {
            unset($worldData[$node]);
            $this->setWorldData($WorldName, $worldData);
        }
    }

    /**
     * @param array $data
     */
    /** @param array<string, mixed> $data */
    public function setData(array $data): void
    {
        $this->plugin->getProvider()->setGroupData($this, $data);
    }

    /**
     * @param ?string $WorldName
     */
    public function setDefault(?string $WorldName = null): void
    {
        if($WorldName === null)
        {
            $this->setNode("isDefault", true);
        }
        else
        {
            $worldData = $this->getWorldData($WorldName);
            $worldData["isDefault"] = true;
            $this->setWorldData($WorldName, $worldData);
        }
    }

    /**
     * @param string $permission
     * @param ?string $WorldName
     *
     * @return bool
     */
    public function setGroupPermission(string $permission, ?string $WorldName = null): bool
    {
        if($WorldName == null)
        {
            $tempGroupData = $this->getData();
            $permissions = $this->stringList($tempGroupData["permissions"] ?? null);
            $permissions[] = $permission;
            $tempGroupData["permissions"] = $permissions;
            $this->setData($tempGroupData);
        }
        else
        {
            $worldData = $this->getWorldData($WorldName);
            $permissions = $this->stringList($worldData["permissions"] ?? null);
            $permissions[] = $permission;
            $worldData["permissions"] = $permissions;
            $this->setWorldData($WorldName, $worldData);
        }
        $this->plugin->updatePlayersInGroup($this);

        return true;
    }

    /**
     * @param string $node
     * @param mixed $value
     */
    public function setNode(string $node, mixed $value): void
    {
        $tempGroupData = $this->getData();
        $tempGroupData[$node] = $value;
        $this->setData($tempGroupData);
    }

    /**
     * @param string $WorldName
     * @param array<string, mixed> $worldData
     */
    public function setWorldData(string $WorldName, array $worldData): void
    {
        $tempGroupData = $this->getData();
        $worlds = $tempGroupData["worlds"] ?? null;
        if(is_array($worlds))
        {
            $worlds[$WorldName] = $worldData;
            $tempGroupData["worlds"] = $worlds;
            $this->setData($tempGroupData);
        }
    }

    /**
     * @param string $WorldName
     * @param string $node
     * @param mixed $value
     */
    public function setWorldNode(string $WorldName, string $node, mixed $value): void
    {
        $worldData = $this->getWorldData($WorldName);
        $worldData[$node] = $value;
        $this->setWorldData($WorldName, $worldData);
    }

    public function sortPermissions(): void
    {
        $tempGroupData = $this->getData();
        if(isset($tempGroupData["permissions"]))
        {
            $tempGroupData["permissions"] = $this->stringList($tempGroupData["permissions"]);
            $tempGroupData["permissions"] = array_unique($tempGroupData["permissions"]);
            sort($tempGroupData["permissions"]);
        }
        
        $isMultiWorldPermsEnabled = $this->plugin->getConfigValue("enable-multiworld-perms");
        $worlds = $tempGroupData["worlds"] ?? null;
        if($isMultiWorldPermsEnabled === true && is_array($worlds))
        {
            foreach($this->plugin->getServer()->getWorldManager()->getWorlds() as $world)
            {
                $WorldName = $world->getDisplayName();
                if(isset($worlds[$WorldName]) && is_array($worlds[$WorldName]))
                {
                    $worldData = $worlds[$WorldName];
                    $worldData["permissions"] = $this->stringList($worldData["permissions"] ?? null);
                    $worldData["permissions"] = array_values(array_unique($worldData["permissions"]));
                    sort($worldData["permissions"]);
                    $worlds[$WorldName] = $worldData;
                }
            }
            $tempGroupData["worlds"] = $worlds;
        }
        $this->setData($tempGroupData);
    }

    /**
     * @param string $permission
     * @param ?string $WorldName
     * @return bool
     */
    public function unsetGroupPermission(string $permission, ?string $WorldName = null): bool
    {
        if($WorldName == null)
        {
            $tempGroupData = $this->getData();
            $permissions = $this->stringList($tempGroupData["permissions"] ?? null);
            if(!in_array($permission, $permissions, true)) return false;
            $tempGroupData["permissions"] = array_values(array_diff($permissions, [$permission]));
            $this->setData($tempGroupData);
        }
        else
        {
            $worldData = $this->getWorldData($WorldName);
            $permissions = $this->stringList($worldData["permissions"] ?? null);
            if(!in_array($permission, $permissions, true)) return false;
            $worldData["permissions"] = array_values(array_diff($permissions, [$permission]));
            $this->setWorldData($WorldName, $worldData);
        }
        $this->plugin->updatePlayersInGroup($this);
        return true;
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
