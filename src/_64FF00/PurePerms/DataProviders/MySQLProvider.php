<?php

namespace _64FF00\PurePerms\DataProviders;

use _64FF00\PurePerms\PurePerms;
use _64FF00\PurePerms\PPGroup;
use _64FF00\PurePerms\Task\PPMySQLTask;

use pocketmine\player\IPlayer;
use RuntimeException;

class MySQLProvider implements ProviderInterface
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

    private \mysqli $db;
    private PurePerms $plugin;
    /** @var array<string, array<string, mixed>> */
    private array $groupsData = [];

    /**
     * @param PurePerms $plugin
     */
    public function __construct(PurePerms $plugin)
    {
        $this->plugin = $plugin;
        $settings = $this->plugin->getConfigValue("mysql-settings");
        if(!is_array($settings))
            throw new RuntimeException("Failed to connect to the MySQL database: Invalid MySQL settings");

        $host = $settings["host"] ?? null;
        $port = $settings["port"] ?? null;
        $user = $settings["user"] ?? null;
        $password = $settings["password"] ?? null;
        $database = $settings["db"] ?? null;
        if(!is_string($host) || !is_int($port) || !is_string($user) || !is_string($password) || !is_string($database))
            throw new RuntimeException("Failed to connect to the MySQL database: Invalid MySQL settings");

        $this->db = new \mysqli($host, $user, $password, $database, $port);
        if($this->db->connect_error)
            throw new RuntimeException("Failed to connect to the MySQL database: " . $this->db->connect_error);
        $resource = $this->plugin->getResource("mysql_deploy_01.sql");
        if($resource === null)
            throw new RuntimeException("Failed to connect to the MySQL database: Missing schema resource");
        $schema = stream_get_contents($resource);
        if($schema === false)
            throw new RuntimeException("Failed to connect to the MySQL database: Unable to read schema resource");

        if(!$this->db->multi_query($schema))
            throw new RuntimeException("Failed to initialize the MySQL database schema: " . $this->db->error);
        while($this->db->more_results())
        {
            if(!$this->db->next_result())
                throw new RuntimeException("Failed to initialize the MySQL database schema: " . $this->db->error);
        }
        fclose($resource);
        $this->migrateMultiworldSchema();
        $this->loadGroupsData();
        $this->plugin->getScheduler()->scheduleRepeatingTask(new PPMySQLTask($this->plugin, $this->db), 1200);
    }

    /**
     * @param PPGroup $group
     * @return array<string, mixed>
     */
    public function getGroupData(PPGroup $group): array
    {
        $groupName = $group->getName();
        return $this->groupsData[$groupName] ?? [];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getGroupsData(): array
    {
        return $this->groupsData;
    }

    /**
     * @param IPlayer $player
     * @return array<string, mixed>
     */
    public function getPlayerData(IPlayer $player): array
    {
        $userData = [
            "userName" => $player->getName(),
            "group" => $this->plugin->getDefaultGroup()?->getName() ?? "default",
            "permissions" => [],
            "worlds" => []
        ];

        $result01 = $this->db->query("SELECT * FROM players WHERE userName = '" .  $this->db->escape_string($player->getName()) . "';");
        if($result01 instanceof \mysqli_result)
        {
            while($currentRow = $result01->fetch_assoc())
            {
                $userGroup = $currentRow["userGroup"] ?? null;
                $permissions = $currentRow["permissions"] ?? null;
                if(is_string($userGroup))
                    $userData["group"] = $userGroup;
                $userData["permissions"] = is_string($permissions) ? explode(",", $permissions) : [];
            }

            $result01->free();
        }
        $result02 = $this->db->query("SELECT * FROM players_mw WHERE userName = '" .  $this->db->escape_string($player->getName()) . "';");
        if($result02 instanceof \mysqli_result)
        {
            while($currentRow = $result02->fetch_assoc())
            {
                $userGroup = $currentRow["userGroup"] ?? null;
                $worldName = $currentRow["worldName"] ?? null;
                $permissions = $currentRow["permissions"] ?? null;
                if(!is_string($userGroup) || !is_string($worldName))
                    continue;
                $worldPerms = is_string($permissions) ? explode(",", $permissions) : [];

                $userData["worlds"][$worldName]["group"] = $userGroup;

                $userData["worlds"][$worldName]["permissions"] = $worldPerms;
            }

            $result02->free();
        }
        return $userData;
    }

    public function getUsers(): ?array
    {
        // TODO
        return null;
    }

    public function loadGroupsData(): void
    {
        $this->groupsData = [];
        $result01 = $this->db->query("SELECT * FROM groups;");
        if($result01 === false)
            throw new RuntimeException("Failed to load groups from MySQL: " . $this->db->error);

        if($result01 instanceof \mysqli_result)
        {
            if($result01->num_rows <= 0)
            {
                $this->plugin->getLogger()->notice("No groups found in table 'groups', loading groups defined in default SQL script");
                $resource = $this->plugin->getResource("mysql_deploy_02.sql");
                if($resource === null)
                    return;
                $schema = stream_get_contents($resource);
                if($schema === false)
                    return;
                $this->db->multi_query($schema);
                while($this->db->more_results())
                {
                    if(!$this->db->next_result())
                        throw new RuntimeException("Failed to initialize default MySQL groups: " . $this->db->error);
                }

                fclose($resource);

                $result01 = $this->db->query("SELECT * FROM groups;");
                if(!$result01 instanceof \mysqli_result)
                    throw new RuntimeException("Failed to load default MySQL groups: " . $this->db->error);
            }
            while($currentRow = $result01->fetch_assoc())
            {
                $groupName = $currentRow["groupName"] ?? null;
                if(!is_string($groupName))
                    continue;
                $alias = $currentRow["alias"] ?? "";
                $isDefault = $currentRow["isDefault"] ?? "0";
                $inheritance = $currentRow["inheritance"] ?? "";
                $permissions = $currentRow["permissions"] ?? "";
                $this->groupsData[$groupName] = [
                    "alias" => is_string($alias) ? $alias : "",
                    "isDefault" => $isDefault === "1",
                    "inheritance" => is_string($inheritance) && $inheritance !== "" ? explode(",", $inheritance) : [],
                    "permissions" => is_string($permissions) ? explode(",", $permissions) : [],
                ];
            }
            $result01->free();
        }

        $result02 = $this->db->query("SELECT * FROM groups_mw;");
        if($result02 === false)
            throw new RuntimeException("Failed to load multiworld groups from MySQL: " . $this->db->error);
        if($result02 instanceof \mysqli_result)
        {
            while($currentRow = $result02->fetch_assoc())
            {
                $isDefault = ($currentRow["isDefault"] ?? "0") === "1";
                $groupName = $currentRow["groupName"] ?? null;
                $worldName = $currentRow["worldName"] ?? null;
                $permissions = $currentRow["permissions"] ?? null;
                if(!is_string($groupName) || !is_string($worldName))
                    continue;
                $worldPerms = is_string($permissions) ? explode(",", $permissions) : [];
                if(!isset($this->groupsData[$groupName]))
                    $this->groupsData[$groupName] = ["alias" => "", "isDefault" => false, "inheritance" => [], "permissions" => []];
                $groupData = $this->groupsData[$groupName];
                $worlds = $groupData["worlds"] ?? [];
                $worlds[$worldName] = ["isDefault" => $isDefault, "permissions" => $worldPerms];
                $groupData["worlds"] = $worlds;
                $this->groupsData[$groupName] = $groupData;
            }

            $result02->free();
        }
    }

    private function migrateMultiworldSchema(): void
    {
        $tables = [
            ["groups_mw", "groupName", "groupName", "group_world"],
            ["players_mw", "userName", "userName", "user_world"],
        ];
        foreach($tables as [$table, $keyColumn, $legacyIndex, $compositeIndex])
        {
            $columnResult = $this->db->query("SHOW COLUMNS FROM `$table` LIKE 'worldName'");
            if(!$columnResult instanceof \mysqli_result)
                throw new RuntimeException("Failed to inspect MySQL table '$table': " . $this->db->error);

            $column = $columnResult->fetch_assoc();
            $columnResult->free();
            $columnType = is_array($column) ? ($column["Type"] ?? null) : null;
            if(!is_string($columnType) || strtolower($columnType) !== "varchar(255)")
            {
                if(!$this->db->query("ALTER TABLE `$table` MODIFY `worldName` VARCHAR(255) NOT NULL"))
                    throw new RuntimeException("Failed to migrate MySQL table '$table': " . $this->db->error);
            }

            $result = $this->db->query("SHOW INDEX FROM `$table`");
            if(!$result instanceof \mysqli_result)
                throw new RuntimeException("Failed to inspect MySQL table '$table': " . $this->db->error);

            $indexes = [];
            while($row = $result->fetch_assoc())
            {
                $indexName = $row["Key_name"] ?? null;
                if(is_string($indexName))
                    $indexes[$indexName] = true;
            }
            $result->free();

            if(isset($indexes[$compositeIndex]))
                continue;

            $dropLegacyIndex = isset($indexes[$legacyIndex]) ? "DROP INDEX `$legacyIndex`, " : "";
            $query = "ALTER TABLE `$table` $dropLegacyIndex ADD UNIQUE KEY `$compositeIndex` (`$keyColumn`, `worldName`)";
            if(!$this->db->query($query))
                throw new RuntimeException("Failed to migrate MySQL table '$table': " . $this->db->error);
        }
    }

    /**
     * @param string $groupName
     */
    public function removeGroupData(string $groupName): void
    {
         $this->db->query("
            DELETE FROM groups
            WHERE groupName = '" . $this->db->escape_string($groupName) . "';");
        $this->db->query("
            DELETE FROM groups_mw
            WHERE groupName = '" . $this->db->escape_string($groupName) . "';");
    }

    /**
     * @param PPGroup $group
     * @param array<string, mixed> $tempGroupData
     */
    public function setGroupData(PPGroup $group, array $tempGroupData): void
    {
        $groupName = $group->getName();
        $this->updateGroupData($groupName, $tempGroupData);
        $this->loadGroupsData();
    }

    /**
     * @param array<string, array<string, mixed>> $tempGroupsData
     */
    public function setGroupsData(array $tempGroupsData): void
    {
        $removedGroupNames = array_keys(array_diff_key($this->groupsData, $tempGroupsData));
        foreach($removedGroupNames as $removedGroupName)
            $this->removeGroupData($removedGroupName);
        foreach($tempGroupsData as $tempGroupName02 => $tempGroupData02)
        {
            $this->updateGroupData($tempGroupName02, $tempGroupData02);
        }
        $this->loadGroupsData();
    }

    /**
     * @param IPlayer $player
     * @param array<string, mixed> $tempUserData
     */
    public function setPlayerData(IPlayer $player, array $tempUserData): void
    {
        if(isset($tempUserData["group"]) and isset($tempUserData["permissions"]))
        {
            $userName = $player->getName();
            $userGroup = $tempUserData["group"];
            $permissionList = $this->stringList($tempUserData["permissions"]);
            if(!is_string($userGroup))
                return;
            $permissions = implode(",", $permissionList);

            $this->db->query("INSERT INTO players
                (userName, userGroup, permissions)
                VALUES
                ('" . $this->db->escape_string($userName) . "', '" . $this->db->escape_string($userGroup) . "', '" . $this->db->escape_string($permissions) . "')
                ON DUPLICATE KEY UPDATE
                userGroup = VALUES(userGroup),
                permissions = VALUES(permissions);");

            if(isset($tempUserData["worlds"]) && is_array($tempUserData["worlds"]))
            {
                foreach($tempUserData["worlds"] as $worldName => $worldData)
                {
                    if(!is_string($worldName))
                        continue;
                    if(!is_array($worldData))
                        continue;
                    $worldGroup = $worldData["group"] ?? null;
                    if(!is_string($worldGroup))
                        continue;
                    $worldPerms = implode(",", $this->stringList($worldData["permissions"] ?? null));

                    $this->db->query("INSERT INTO players_mw
                            (userName, worldName, userGroup, permissions)
                            VALUES
                            ('" . $this->db->escape_string($userName) . "', '" . $this->db->escape_string($worldName) . "', '" . $this->db->escape_string($worldGroup) . "', '" . $this->db->escape_string($worldPerms) . "')
                            ON DUPLICATE KEY UPDATE
                            userGroup = VALUES(userGroup),
                            permissions = VALUES(permissions);");

                }
            }
        }
    }

    /**
     * @param string $groupName
     * @param array<string, mixed> $tempGroupData
     */
    public function updateGroupData(string $groupName, array $tempGroupData): void
    {
        if(isset($tempGroupData["isDefault"]) and isset($tempGroupData["inheritance"]) and isset($tempGroupData["permissions"]))
        {
            $alias = $tempGroupData["alias"] ?? "";
            if(!is_string($alias))
                $alias = "";
            $isDefault = $tempGroupData["isDefault"] === true ? "1" : "0";
            $inheritance = implode(",", $this->stringList($tempGroupData["inheritance"]));
            $permissions = implode(",", $this->stringList($tempGroupData["permissions"]));
            $this->db->query("INSERT INTO groups
                (groupName, alias, isDefault, inheritance, permissions)
                VALUES
                ('" . $this->db->escape_string($groupName) . "', '" . $this->db->escape_string($alias) . "', '" . $this->db->escape_string($isDefault) . "', '" . $this->db->escape_string($inheritance) . "', '" . $this->db->escape_string($permissions) . "')
                ON DUPLICATE KEY UPDATE
                alias = VALUES(alias),
                isDefault = VALUES(isDefault),
                inheritance = VALUES(inheritance),
                permissions = VALUES(permissions);");

            if(isset($tempGroupData["worlds"]) && is_array($tempGroupData["worlds"]))
            {
                foreach($tempGroupData["worlds"] as $worldName => $worldData)
                {
                    if(!is_string($worldName))
                        continue;
                    if(!is_array($worldData))
                        continue;
                    $isDefault = ($worldData["isDefault"] ?? false) === true ? "1" : "0";
                    $worldPerms = implode(",", $this->stringList($worldData["permissions"] ?? null));
                    $this->db->query("INSERT INTO groups_mw
                            (groupName, isDefault, worldName, permissions)
                            VALUES
                            ('" . $this->db->escape_string($groupName) . "', '" . $this->db->escape_string($isDefault) . "', '" . $this->db->escape_string($worldName) . "', '" . $this->db->escape_string($worldPerms) . "')
                            ON DUPLICATE KEY UPDATE
                            isDefault = VALUES(isDefault),
                            worldName = VALUES(worldName),
                            permissions = VALUES(permissions);");
                    }
            }
        }
    }
    
    public function close(): void
    {
        $this->db->close();
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
