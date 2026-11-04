<?php

namespace _64FF00\PurePerms;              

use pocketmine\utils\Config;                                                    

class PPMessages
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

    private PurePerms $plugin;
    private string $language = "en";
    private Config $messages;
    /** @var list<string> */
    private array $langList = [];

    /**
     * @param PurePerms $plugin
     */
    public function __construct(PurePerms $plugin)
    {
        $this->plugin = $plugin;
        $this->registerLanguages();
        $this->loadMessages();
    }

    public function registerLanguages(): void
    {
        $result = [];
        foreach($this->plugin->getResources() as $resource)
        {
            $resourceName = $resource->getFilename();
            if(mb_strpos($resourceName, "messages-") !== false) $result[] = substr($resourceName, -6, -4);
        }
        $this->langList = $result;
    }

    /**
     * @param string $node
     * @param mixed ...$vars
     * @return string
     */
    public function getMessage(string $node, mixed ...$vars): string
    {
        $msg = $this->messages->getNested($node);
        if(is_string($msg))
        {
            $number = 0;
            foreach($vars as $v)
            {           
                $msg = str_replace("%var$number%", is_scalar($v) ? (string) $v : "", $msg);
                $number++;
            }
            return $msg;
        }
        return "";
    }

    public function getVersion() : string
    {
        $version = $this->messages->get("messages-version");
        return is_string($version) ? $version : "0.0.0";
    }

    public function loadMessages(): void
    {       
        $defaultLangValue = $this->plugin->getConfigValue("default-language");
        $defaultLang = is_string($defaultLangValue) ? $defaultLangValue : "en";
        $languageFound = false;
        foreach($this->langList as $langName)
        {
            if(strtolower($defaultLang) == $langName)
            {
                $this->language = $langName;
                $languageFound = true;
            }
        }
        
        if(!$languageFound)
        {
            $this->plugin->getLogger()->warning("Language resource " . $defaultLang . " not found. Using default language resource by " . $this->plugin->getDescription()->getAuthors()[0]);
            $this->language = "en";
        }
        
        $this->plugin->saveResource("messages-" . $this->language . ".yml");
        $this->messages = new Config($this->plugin->getDataFolder() . "messages-" . $this->language . ".yml", Config::YAML, [
        ]);
        $this->plugin->getLogger()->info("Setting default language to '" . $defaultLang . "'");
        if(version_compare($this->getVersion(), $this->plugin->getPPVersion()) === -1)
        {
            $this->plugin->saveResource("messages-" . $this->language . ".yml", true);
            $this->messages = new Config($this->plugin->getDataFolder() . "messages-" . $this->language . ".yml", Config::YAML, [
            ]);
        }
    }
    
    public function reloadMessages(): void
    {
        $this->messages->reload();
    }    
}
