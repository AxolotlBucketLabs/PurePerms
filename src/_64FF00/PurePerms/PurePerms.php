<?php

namespace _64FF00\PurePerms;

use _64FF00\PurePerms\Commands\AddRank;
use _64FF00\PurePerms\Commands\DefRank;
use _64FF00\PurePerms\Commands\PLPerms;
use _64FF00\PurePerms\Commands\ListRanks;
use _64FF00\PurePerms\Commands\PPInfo;
use _64FF00\PurePerms\Commands\RmRank;
use _64FF00\PurePerms\Commands\UnSetUserPerm;
use _64FF00\PurePerms\Commands\SetUserPerm;
use _64FF00\PurePerms\Commands\SetRank;
use _64FF00\PurePerms\Commands\SetGPerm;
use _64FF00\PurePerms\Commands\UnsetGPerm;
use _64FF00\PurePerms\Commands\FPerms;
use _64FF00\PurePerms\Commands\PPReload;
use _64FF00\PurePerms\DataManager\UserDataManager;
use _64FF00\PurePerms\DataProviders\SQLite3Provider;
use _64FF00\PurePerms\DataProviders\DefaultProvider;
use _64FF00\PurePerms\DataProviders\MySQLProvider;
use _64FF00\PurePerms\DataProviders\ProviderInterface;
use _64FF00\PurePerms\DataProviders\JsonProvider;
use _64FF00\PurePerms\Task\PPExpDateCheckTask;

use pocketmine\permission\PermissionManager;
use pocketmine\player\IPlayer;
use pocketmine\permission\DefaultPermissions;
use pocketmine\player\Player;
use pocketmine\permission\PermissionAttachment;
use pocketmine\world\World;
use pocketmine\plugin\PluginBase;
use RuntimeException;

class PurePerms extends PluginBase
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

    const MAIN_PREFIX = "\x5b\x50\x75\x72\x65\x50\x65\x72\x6d\x73\x3a\x36\x34\x46\x46\x30\x30\x5d";

    const CORE_PERM = "\x70\x70\x65\x72\x6d\x73\x2e\x63\x6f\x6d\x6d\x61\x6e\x64\x2e\x70\x70\x69\x6e\x66\x6f";

    const NOT_FOUND = null;
    const INVALID_NAME = -1;
    const ALREADY_EXISTS = 0;
    const SUCCESS = 1;

    private bool $isGroupsLoaded = false;

    /** @var PPMessages $messages */
    private PPMessages $messages;

    /** @var ProviderInterface $provider */
    private ?ProviderInterface $provider = null;

    /** @var UserDataManager $userDataMgr */
    private UserDataManager $userDataMgr;

    /** @var array<string, PermissionAttachment> */
    private array $attachments = [];
    /** @var array<string, PPGroup> */
    private array $groups = [];

    public function onLoad(): void
    {
        $this->saveDefaultConfig();
        $this->messages = new PPMessages($this);
        $this->userDataMgr = new UserDataManager($this);
    }
    
    public function onEnable(): void
    {
        $this->registerCommands();
        $this->setProvider();
        $this->registerPlayers();
        $this->getServer()->getPluginManager()->registerEvents(new PPListener($this), $this);
        $this->getScheduler()->scheduleRepeatingTask(new PPExpDateCheckTask($this), 72000);
    }

    public function onDisable(): void
    {
        $this->unregisterPlayers();
        if($this->provider !== null)
            $this->provider->close();
    }

    private function registerCommands(): void
    {
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register("pureperms", new AddRank($this, "addrank", $this->getMessage("cmds.addrank.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new DefRank($this, "defrank", $this->getMessage("cmds.defrank.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new PLPerms($this, "plperms", $this->getMessage("cmds.plperms.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new FPerms($this, "fperms", $this->getMessage("cmds.fperms.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new ListRanks($this, "listranks", $this->getMessage("cmds.ranks.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new PPInfo($this, "ppinfo", $this->getMessage("cmds.ppinfo.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new RmRank($this, "rmrank", $this->getMessage("cmds.rmrank.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new SetRank($this, "setrank", $this->getMessage("cmds.setrank.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new SetUserPerm($this, "setuperm", $this->getMessage("cmds.setuperm.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new UnSetUserPerm($this, "unsetuperm", $this->getMessage("cmds.unsetuperm.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new SetGPerm($this, "setgperm", $this->getMessage("cmds.setgperm.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new UnsetGPerm($this, "unsetgperm", $this->getMessage("cmds.unsetgperm.desc") . ' #64FF00'));
        $commandMap->register("pureperms", new PPReload($this, "ppreload", $this->getMessage("cmds.ppreload.desc") . ' #64FF00'));

    }

    /**
     * @param bool $onEnable
     */
    private function setProvider(bool $onEnable = true): void
    {
        $providerName = $this->getConfigValue("data-provider");
        $providerName = is_string($providerName) ? $providerName : "default";
        switch(strtolower($providerName))
        {
            case "sqlite3":
                $provider = new SQLite3Provider($this);
                if($onEnable === true)
                    $this->getLogger()->notice($this->getMessage("logger_messages.setProvider_SQLITE3"));
                break;
            case "json":
                $provider = new JsonProvider($this);
                if($onEnable === true)
                    $this->getLogger()->notice($this->getMessage("logger_messages.setProvider_JSON"));
                break;
            case "mysql":
                $provider = new MySQLProvider($this);
                if($onEnable === true)
                    $this->getLogger()->notice($this->getMessage("logger_messages.setProvider_MySQL"));
                break;
            default:
                $provider = new DefaultProvider($this);
                if($onEnable === true)
                    $this->getLogger()->warning($this->getMessage("logger_messages.setProvider_NotFound", "'$providerName'"));
                break;
        }
        $this->provider = $provider;
        $this->updateGroups();
    }

    /*
          888  888          d8888 8888888b. 8888888
          888  888         d88888 888   Y88b  888
        888888888888      d88P888 888    888  888
          888  888       d88P 888 888   d88P  888
          888  888      d88P  888 8888888P"   888
        888888888888   d88P   888 888         888
          888  888    d8888888888 888         888
          888  888   d88P     888 888       8888888
    */

    /**
     * @param string $groupName
     * @return int
     */
    public function addGroup(string $groupName): int
    {
        $groupsData = $this->getProvider()->getGroupsData();
        if(!$this->isValidGroupName($groupName))
            return self::INVALID_NAME;
        if(isset($groupsData[$groupName]))
            return self::ALREADY_EXISTS;
        $groupsData[$groupName] = [
            "alias" => "",
            "isDefault" => false,
            "inheritance" => [
            ],
            "permissions" => [
            ],
            "worlds" => [
            ]
        ];
        $this->getProvider()->setGroupsData($groupsData);
        $this->updateGroups();
        return self::SUCCESS;
    }

    /**
     * @param string $date
     * @return int
     * Example: $date = '1d2h3m';
     */
    public function date2Int(string $date): int
    {
        $date = strtolower(trim($date));
        if($this->isPermanentExpiration($date))
            return -1;
        if(preg_match("/^(?:(\\d+)d)?(?:(\\d+)h)?(?:(\\d+)m)?$/", $date, $result_array) === 1)
            return time() + ((int) ($result_array[1] ?? 0) * 86400) + ((int) ($result_array[2] ?? 0) * 3600) + ((int) ($result_array[3] ?? 0) * 60);
        return -1;
    }

    public function isValidExpiration(string $date): bool
    {
        $date = strtolower(trim($date));
        if($this->isPermanentExpiration($date))
            return true;
        if(preg_match("/^(?:(\\d+)d)?(?:(\\d+)h)?(?:(\\d+)m)?$/", $date, $matches) !== 1)
            return false;
        return ((int) ($matches[1] ?? 0) + (int) ($matches[2] ?? 0) + (int) ($matches[3] ?? 0)) > 0;
    }

    private function isPermanentExpiration(string $date): bool
    {
        return $date === "-1" || $date === "perm" || $date === "permanent";
    }

    /**
     * @param Player $player
     * @return PermissionAttachment
     */
    public function getAttachment(Player $player): PermissionAttachment
    {
        $uniqueId = $this->getValidUUID($player);
        if(!isset($this->attachments[$uniqueId]))
            $this->attachments[$uniqueId] = $player->addAttachment($this);
        return $this->attachments[$uniqueId];
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getConfigValue(string $key): mixed
    {
        $value = $this->getConfig()->getNested($key);
        if($value === null)
        {
            $this->getLogger()->warning($this->getMessage("logger_messages.getConfigValue_01", $key));

            return null;
        }

        return $value;
    }

    /**
     * @param ?string $WorldName
     * @return PPGroup|null
     */
    public function getDefaultGroup(?string $WorldName = null): ?PPGroup
    {
        $defaultGroups = [];
        foreach($this->getGroups() as $defaultGroup)
        {
            if($defaultGroup->isDefault($WorldName))
                $defaultGroups[] = $defaultGroup;
        }

        if(count($defaultGroups) === 1)
        {
            return $defaultGroups[0];
        }
        else
        {
            if(count($defaultGroups) > 1)
            {
                $this->getLogger()->warning($this->getMessage("logger_messages.getDefaultGroup_01"));
            }
            elseif(count($defaultGroups) <= 0)
            {
                $this->getLogger()->warning($this->getMessage("logger_messages.getDefaultGroup_02"));
            }

            $this->getLogger()->info($this->getMessage("logger_messages.getDefaultGroup_03"));

            foreach($this->getGroups() as $tempGroup)
            {
                if(count($tempGroup->getParentGroups()) === 0)
                {
                    $this->setDefaultGroup($tempGroup, $WorldName);

                    return $tempGroup;
                }
            }
        }

        return null;
    }

    /**
     * @param string $groupName
     * @return PPGroup|null
     */
    public function getGroup(string $groupName): ?PPGroup
    {
        if(!isset($this->groups[$groupName]))
        {
            /** @var PPGroup $group */
            foreach($this->groups as $group)
            {
                if($group->getAlias() === $groupName)
                    return $group;
            }
            $this->getLogger()->debug($this->getMessage("logger_messages.getGroup_01", $groupName));
            return null;
        }

        /** @var PPGroup $group */
        $group = $this->groups[$groupName];

        if(empty($group->getData()))
        {
            $this->getLogger()->warning($this->getMessage("logger_messages.getGroup_02", $groupName));
            return null;
        }

        return $group;
    }

    /**
     * @return array<string, PPGroup>
     */
    public function getGroups(): array
    {
        if($this->isGroupsLoaded !== true)
            throw new RuntimeException("No groups loaded, maybe a provider error?");
        return $this->groups;
    }

    public function getMessage(string $node, mixed ...$vars): string
    {
        return $this->messages->getMessage($node, ...$vars);
    }

    public function reload(): void
    {
        $this->reloadConfig();
        $this->messages->reloadMessages();
        $this->getProvider()->reload();
        $this->updateGroups();
        foreach($this->getServer()->getOnlinePlayers() as $player)
            $this->updatePermissions($player);
    }


    /**
     * @param PPGroup $group
     * @return list<Player>
     */
    public function getOnlinePlayersInGroup(PPGroup $group): array
    {
        $users = [];
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            foreach($this->getServer()->getWorldManager()->getWorlds() as $World)
            {
                $WorldName = $World->getDisplayName();
                if($this->userDataMgr->getGroup($player, $WorldName) === $group)
                    $users[] = $player;
            }
        }

        return $users;
    }

    /**
     * @param IPlayer $player
     * @param ?string $WorldName
     * @return list<string>
     */
    public function getPermissions(IPlayer $player, ?string $WorldName = null): array
    {
        // TODO: Fix this
        $group = $this->userDataMgr->getGroup($player, $WorldName);
        if($group === null)
            return [];
        $groupPerms = $group->getGroupPermissions($WorldName);
        $userPerms = $this->userDataMgr->getUserPermissions($player, $WorldName);

        return array_merge($groupPerms, $userPerms);
    }

    /**
     * @param string $userName
     * @return IPlayer
     */
    public function getPlayer(string $userName): IPlayer
    {
        $player = $this->getServer()->getPlayerByPrefix($userName);
        if($player instanceof IPlayer)
            return $player;

        $offlinePlayer = $this->getServer()->getOfflinePlayer($userName);
        if($offlinePlayer instanceof IPlayer)
            return $offlinePlayer;

        throw new RuntimeException("Unable to resolve player: {$userName}");
    }

    /**
     * @return array<string>
     */
    public function getPocketMinePerms() : array
    {
        return array_keys(PermissionManager::getInstance()->getPermissions());
    }

    /**
     * @return string
     */
    public function getPPVersion(): string
    {
        return $this->getDescription()->getVersion();
    }

    /**
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        if(!$this->isValidProvider())
            $this->setProvider(false);

        if($this->provider === null)
            throw new RuntimeException("Failed to load data provider");

        return $this->provider;
    }

    /**
     * @return UserDataManager
     */
    public function getUserDataMgr(): UserDataManager
    {
        return $this->userDataMgr;
    }

    /**
     * @param Player $player
     */
    public function getValidUUID(Player $player) : string
    {
		return $player->getUniqueId()->toString();
    }

    /**
     * @param string $groupName
     * @return int
     */
    public function isValidGroupName(string $groupName): int
    {
        return preg_match('/[0-9a-zA-Z\xA1-\xFE]$/', $groupName) === 1 ? self::SUCCESS : self::INVALID_NAME;
    }

    /**
     * @return bool
     */
    public function isValidProvider()
    {
        return $this->provider !== null;
    }

    /**
     * @param Player $player
     */
    public function registerPlayer(Player $player): void
    {
        $this->getLogger()->debug($this->getMessage("logger_messages.registerPlayer", $player->getName()));
        $uniqueId = $this->getValidUUID($player);
        if(!isset($this->attachments[$uniqueId]))
        {
            $attachment = $player->addAttachment($this);
            $this->attachments[$uniqueId] = $attachment;
            $this->updatePermissions($player);
        }
    }

    public function registerPlayers(): void
    {
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            $this->registerPlayer($player);
        }
    }

    /**
     * @param string $groupName
     * @return int|null
     */
    public function removeGroup(string $groupName): ?int
    {
        if(!$this->isValidGroupName($groupName))
            return self::INVALID_NAME;
        $groupsData = $this->getProvider()->getGroupsData();
        if(!isset($groupsData[$groupName]))
            return self::NOT_FOUND;
        unset($groupsData[$groupName]);
        $this->getProvider()->setGroupsData($groupsData);
        $this->updateGroups();
        return self::SUCCESS;
    }

    /**
     * @param PPGroup $group
     * @param ?string $WorldName
     */
    public function setDefaultGroup(PPGroup $group, ?string $WorldName = null): void
    {
        foreach($this->getGroups() as $currentGroup)
        {
            if($WorldName === null)
            {
                $isDefault = $currentGroup->getNode("isDefault");

                if($isDefault)
                    $currentGroup->removeNode("isDefault");
            }
            else
            {
                $isDefault = $currentGroup->getWorldNode($WorldName, "isDefault");
                if($isDefault)
                    $currentGroup->removeWorldNode($WorldName, "isDefault");
            }
        }

        $group->setDefault($WorldName);
    }

    /**
     * @param IPlayer $player
     * @param PPGroup $group
     * @param ?string $WorldName
     * @param int $time
     */
    public function setGroup(IPlayer $player, PPGroup $group, ?string $WorldName = null, int $time = -1): void
    {
        $this->userDataMgr->setGroup($player, $group, $WorldName, $time);
    }

    public function sortGroupData(): void
    {
        foreach($this->getGroups() as $groupName => $ppGroup)
        {
            $ppGroup->sortPermissions();

            if($this->getConfigValue("enable-multiworld-perms"))
            {
                /** @var World $World */
                foreach($this->getServer()->getWorldManager()->getWorlds() as $World)
                {
                    $WorldName = $World->getDisplayName();
                    $ppGroup->createWorldData($WorldName);
                }
            }
        }
    }

    public function updateGroups(): void
    {
        if(!$this->isValidProvider())
            throw new RuntimeException("Failed to load groups: Invalid data provider");
        // Make group list empty first to reload it
        $this->groups = [];
        foreach(array_keys($this->getProvider()->getGroupsData()) as $groupName)
        {
            $this->groups[$groupName] = new PPGroup($this, $groupName);
        }
        if(empty($this->groups))
            throw new RuntimeException("No groups found, I guess there's definitely something wrong with your data provider... *cough cough*");
        $this->isGroupsLoaded = true;
        $this->sortGroupData();
    }

    /**
     * @param IPlayer $player
     * @param ?string $WorldName
     */
    public function updatePermissions(IPlayer $player, ?string $WorldName = null): void
    {
        if($player instanceof Player)
        {
            if($this->getConfigValue("enable-multiworld-perms") !== true) {
                $WorldName = null;
            }elseif($WorldName == null) {
                $WorldName = $player->getWorld()->getDisplayName();
            }
            $permissions = [];
            /** @var string $permission */
            foreach($this->getPermissions($player, $WorldName) as $permission)
            {
                if($permission === '*')
                {
                    foreach(PermissionManager::getInstance()->getPermissions() as $tmp)
                    {
                        $permissions[$tmp->getName()] = true;
                    }
                }
                else
                {
                    $isNegative = substr($permission, 0, 1) === "-";
                    if($isNegative)
                        $permission = substr($permission, 1);

                    $permissions[$permission] = !$isNegative;
                }
            }

            $permissions[self::CORE_PERM] = true;
            /** @var \pocketmine\permission\PermissionAttachment $attachment */
            $attachment = $this->getAttachment($player);
            $attachment->clearPermissions();
            $attachment->setPermissions($permissions);
        }
    }

    /**
     * @param PPGroup $group
     */
    public function updatePlayersInGroup(PPGroup $group): void
    {
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            if($this->userDataMgr->getGroup($player) === $group)
                $this->updatePermissions($player);
        }
    }

    /**
     * @param Player $player
     */
    public function unregisterPlayer(Player $player): void
    {
        $this->getLogger()->debug($this->getMessage("logger_messages.unregisterPlayer", $player->getName()));
        $uniqueId = $this->getValidUUID($player);
		if(isset($this->attachments[$uniqueId]))
			$player->removeAttachment($this->attachments[$uniqueId]);
		unset($this->attachments[$uniqueId]);
    }

    public function unregisterPlayers(): void
    {
        foreach($this->getServer()->getOnlinePlayers() as $player)
        {
            $this->unregisterPlayer($player);
        }
    }
}
