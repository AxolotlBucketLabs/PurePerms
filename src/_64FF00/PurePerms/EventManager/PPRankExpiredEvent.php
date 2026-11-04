<?php

namespace _64FF00\PurePerms\EventManager;

use _64FF00\PurePerms\PPGroup;
use _64FF00\PurePerms\PurePerms;

use pocketmine\event\plugin\PluginEvent;

use pocketmine\player\IPlayer;
use pocketmine\world\World;

class PPRankExpiredEvent extends PluginEvent
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

    /** @var ?\pocketmine\event\HandlerList<PPRankExpiredEvent> */
    public static ?\pocketmine\event\HandlerList $handlerList = null;

    private IPlayer $player;
    private ?string $worldName;

    /**
     * @param PurePerms $plugin
     * @param IPlayer $player
     * @param $worldName
     */
    public function __construct(PurePerms $plugin, IPlayer $player, ?string $worldName)
    {
        parent::__construct($plugin);
        $this->player = $player;
        $this->worldName = $worldName;
    }

    /**
     * @return World
     */
    public function getWorld(): ?World
    {
        return $this->getPlugin() instanceof PurePerms ? $this->getPlugin()->getServer()->getWorldManager()->getWorldByName($this->worldName ?? "") : null;
    }

    /**
     * @return string
     */
    public function getLevelName(): ?string
    {
        return $this->worldName;
    }

    /**
     * @return IPlayer
     */
    public function getPlayer(): IPlayer
    {
        return $this->player;
    }
}
