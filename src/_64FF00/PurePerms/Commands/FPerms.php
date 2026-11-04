<?php

namespace _64FF00\PurePerms\Commands;

use _64FF00\PurePerms\PurePerms;

class FPerms extends PLPerms
{
    public function __construct(PurePerms $plugin, string $name, string $description)
    {
        parent::__construct($plugin, $name, $description);
        $this->setPermission("pperms.command.fperms");
    }
}
