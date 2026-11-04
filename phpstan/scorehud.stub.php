<?php

namespace Ifera\ScoreHud\event;

use pocketmine\event\Event;
use pocketmine\player\Player;
use Ifera\ScoreHud\scoreboard\ScoreTag;

class PlayerTagsUpdateEvent extends Event
{
    /** @param array<int, ScoreTag> $tags */
    public function __construct(Player $player, array $tags) {}
}

class TagsResolveEvent extends Event
{}

namespace Ifera\ScoreHud\scoreboard;

class ScoreTag
{
    public function __construct(string $name, string $value = "") {}
}
