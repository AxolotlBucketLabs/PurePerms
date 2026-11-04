<?php

namespace _64FF00\PurePerms;

use _64FF00\PurePerms\EventManager\PPRankChangedEvent;
use pocketmine\event\Event;
use pocketmine\event\EventPriority;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\Player;
use RuntimeException;

final class ScoreHudIntegration implements Listener
{
    /**
     * @param PurePerms $plugin
     * @return bool
     */
    /** @devtools-allow-dynamic-class $resolveEventClass */
    public static function register(PurePerms $plugin): bool
    {
        $scoreHud = $plugin->getServer()->getPluginManager()->getPlugin("ScoreHud");
        if($scoreHud === null || !$scoreHud->isEnabled() || version_compare($scoreHud->getDescription()->getVersion(), "8.0.0", "<"))
            return false;

        $resolveEventClass = "Ifera\\ScoreHud\\event\\TagsResolveEvent";
        if(!class_exists($resolveEventClass))
            throw new RuntimeException("ScoreHud TagsResolveEvent is unavailable.");
        if(!class_exists("Ifera\\ScoreHud\\event\\PlayerTagsUpdateEvent"))
            throw new RuntimeException("ScoreHud PlayerTagsUpdateEvent is unavailable.");
        if(!class_exists("Ifera\\ScoreHud\\scoreboard\\ScoreTag"))
            throw new RuntimeException("ScoreHud ScoreTag is unavailable.");

        $pluginManager = $plugin->getServer()->getPluginManager();
        $pluginManager->registerEvents(new self($plugin), $plugin);
        $pluginManager->registerEvent(
            $resolveEventClass,
            function(object $event) use ($plugin): void {
                self::resolve($plugin, $event);
            },
            EventPriority::NORMAL,
            $plugin
        );
        return true;
    }

    private PurePerms $plugin;

    public function __construct(PurePerms $plugin)
    {
        $this->plugin = $plugin;
    }

    public function onJoin(PlayerJoinEvent $event): void
    {
        $this->sendUpdate($event->getPlayer());
    }

    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $this->sendUpdate($event->getPlayer());
    }

    public function onRankChanged(PPRankChangedEvent $event): void
    {
        $player = $event->getPlayer();
        if($player instanceof Player && $player->isOnline())
            $this->sendUpdate($player);
    }

    private function getPlayerRank(Player $player): string
    {
        return $this->plugin->getUserDataMgr()->getGroup($player)?->getName() ?? "No Rank";
    }

    private function getPrefix(Player $player): string
    {
        $prefix = $this->plugin->getUserDataMgr()->getNode($player, "prefix");
        return is_string($prefix) && $prefix !== "" ? $prefix : "No Prefix";
    }

    private function getSuffix(Player $player): string
    {
        $suffix = $this->plugin->getUserDataMgr()->getNode($player, "suffix");
        return is_string($suffix) && $suffix !== "" ? $suffix : "No Suffix";
    }

    private function sendUpdate(Player $player): void
    {
        $tags = [
            self::createScoreTag("ppscore.rank", $this->getPlayerRank($player)),
            self::createScoreTag("ppscore.prefix", $this->getPrefix($player)),
            self::createScoreTag("ppscore.suffix", $this->getSuffix($player)),
        ];
        $event = self::createUpdateEvent($player, $tags);
        $event->call();
    }

    /** @return \Ifera\ScoreHud\scoreboard\ScoreTag */
    private static function createScoreTag(string $name, string $value): object
    {
        $reflection = new \ReflectionClass("Ifera\\ScoreHud\\scoreboard\\ScoreTag");
        return $reflection->newInstance($name, $value);
    }

    /** @param array<int, \Ifera\ScoreHud\scoreboard\ScoreTag> $tags */
    private static function createUpdateEvent(Player $player, array $tags): Event
    {
        $reflection = new \ReflectionClass("Ifera\\ScoreHud\\event\\PlayerTagsUpdateEvent");
        return $reflection->newInstance($player, $tags);
    }

    private static function resolve(PurePerms $plugin, object $event): void
    {
        if(!method_exists($event, "getPlayer") || !method_exists($event, "getTag"))
            return;
        $player = $event->getPlayer();
        $tag = $event->getTag();
        if(!$player instanceof Player || !is_object($tag) || !method_exists($tag, "getName") || !method_exists($tag, "setValue"))
            return;

        $tagName = $tag->getName();
        if(!is_string($tagName))
            return;
        $value = match($tagName) {
            "ppscore.rank" => $plugin->getUserDataMgr()->getGroup($player)?->getName() ?? "No Rank",
            "ppscore.prefix" => self::nodeValue($plugin, $player, "prefix", "No Prefix"),
            "ppscore.suffix" => self::nodeValue($plugin, $player, "suffix", "No Suffix"),
            default => null,
        };
        if($value !== null)
            $tag->setValue($value);
    }

    private static function nodeValue(PurePerms $plugin, Player $player, string $node, string $fallback): string
    {
        $value = $plugin->getUserDataMgr()->getNode($player, $node);
        return is_string($value) && $value !== "" ? $value : $fallback;
    }
}
