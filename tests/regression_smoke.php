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

$main = file_get_contents(dirname(__DIR__) . "/src/_64FF00/PurePerms/PurePerms.php");
if($main === false || !str_contains($main, 'new SetGPerm') || !str_contains($main, 'new UnsetGPerm') || !str_contains($main, 'new FPerms') || !str_contains($main, 'new PPReload'))
    throw new RuntimeException("PurePerms command registration regression failed");

$providerInterface = file_get_contents(dirname(__DIR__) . "/src/_64FF00/PurePerms/DataProviders/ProviderInterface.php");
if($providerInterface === false || !str_contains($providerInterface, 'public function reload(): void'))
    throw new RuntimeException("Provider reload contract regression failed");

$setRank = file_get_contents(dirname(__DIR__) . "/src/_64FF00/PurePerms/Commands/SetRank.php");
if($setRank === false || !str_contains($setRank, 'isValidExpiration'))
    throw new RuntimeException("Temporary rank validation regression failed");

$expectedLanguages = ["eng", "vie", "kor", "rus", "spa", "ukr", "zho", "ind", "tur", "fra", "por", "deu", "jpn", "ita"];
$languageFiles = glob(dirname(__DIR__) . "/resources/lang/messages-*.yml");
if($languageFiles === false)
    throw new RuntimeException("Language directory scan failed");
$actualLanguages = [];
foreach($languageFiles as $languageFile)
{
    if(preg_match('/messages-([a-z]{3})\.yml$/', $languageFile, $matches) === 1)
        $actualLanguages[] = $matches[1];
}
sort($actualLanguages);
$expectedLanguagesSorted = $expectedLanguages;
sort($expectedLanguagesSorted);
if($actualLanguages !== $expectedLanguagesSorted)
    throw new RuntimeException("PurePerms language coverage regression failed");

echo "PurePerms regressions: PASS\n";
