<?php

namespace _64FF00\PurePerms\DataProviders;

use _64FF00\PurePerms\PPGroup;

use pocketmine\player\IPlayer;

interface ProviderInterface
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

    /** @return array<string, mixed> */
    public function getGroupData(PPGroup $group): array;

    /** @return array<string, array<string, mixed>> */
    public function getGroupsData(): array;

    /** @return array<string, mixed> */
    public function getPlayerData(IPlayer $player): array;

    /** @return null|array<string, mixed> */
    public function getUsers(): ?array;

    /** @param array<string, mixed> $tempGroupData */
    public function setGroupData(PPGroup $group, array $tempGroupData): void;

    /** @param array<string, array<string, mixed>> $tempGroupsData */
    public function setGroupsData(array $tempGroupsData): void;

    /** @param array<string, mixed> $tempPlayerData */
    public function setPlayerData(IPlayer $player, array $tempPlayerData): void;

    /** Reload provider-backed data without replacing the provider instance. */
    public function reload(): void;

    public function close(): void;
}
