<?php

declare(strict_types=1);

$source = file_get_contents(dirname(__DIR__) . "/src/_64FF00/PurePerms/PurePerms.php");
if($source === false || !preg_match('/case "mysql":\s*\$provider = new MySQLProvider/', $source))
    throw new RuntimeException("MySQL provider selection regression failed");

$schema = file_get_contents(dirname(__DIR__) . "/resources/mysql_deploy_01.sql");
if($schema === false)
    throw new RuntimeException("MySQL schema is missing");
if(!str_contains($schema, "UNIQUE KEY group_world (groupName, worldName)"))
    throw new RuntimeException("MySQL group/world key regression failed");
if(!str_contains($schema, "UNIQUE KEY user_world (userName, worldName)"))
    throw new RuntimeException("MySQL user/world key regression failed");

$provider = file_get_contents(dirname(__DIR__) . "/src/_64FF00/PurePerms/DataProviders/MySQLProvider.php");
if($provider === false || str_contains($provider, "DELETE OR IGNORE FROM groups_mw"))
    throw new RuntimeException("MySQL delete query regression failed");

echo "PurePerms regressions: PASS\n";
