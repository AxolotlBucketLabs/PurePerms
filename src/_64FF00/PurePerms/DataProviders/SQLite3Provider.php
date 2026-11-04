<?php
namespace _64FF00\PurePerms\DataProviders;

use _64FF00\PurePerms\PPGroup;
use _64FF00\PurePerms\PurePerms;
use pocketmine\player\IPlayer;

class SQLite3Provider implements ProviderInterface
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

    private \SQLite3 $db;
    /** @var array<string, array<string, mixed>> */
    private array $groupsData = [];

    /**
     * @param PurePerms $plugin
     */
    public function __construct(PurePerms $plugin)
    {
        $this->db = new \SQLite3($plugin->getDataFolder()."pureperms.db");
        $this->db->exec("");
        $this->loadGroupsData();
    }

    public function loadGroupsData(): void
    {
        //
    }

    public function getGroupData(PPGroup $group): array{
        return [];
    }

    public function getGroupsData(): array{
        return $this->groupsData;
    }

    public function getPlayerData(IPlayer $player): array{
        return [];
    }

    public function getUsers(): ?array{
        return null;
    }

    public function setGroupData(PPGroup $group, array $tempGroupData): void{
    }

    public function setGroupsData(array $tempGroupsData): void{
    }

    public function setPlayerData(IPlayer $player, array $tempPlayerData): void{
    }

    public function reload(): void{
        $this->loadGroupsData();
    }

    public function close(): void{
    }

}
