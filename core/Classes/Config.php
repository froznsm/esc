<?php

namespace esc\Classes;


use esc\Controllers\ChatController;
use esc\Controllers\ModuleController;

class Config
{
    private static $configs = [];

    public static function get($variable)
    {
        $variableExplode = explode('.', $variable);
        $config = array_shift($variableExplode);

        if (!array_key_exists($config, self::$configs)) {
            Log::error("Trying to access unloaded config: $variable");
            return null;
        }

        $out = self::$configs[$config];
        foreach ($variableExplode as $variable) {
            if (isset($out->{$variable})) {
                $out = $out->{$variable};
            } else {
//                Log::error("Trying to access undefined: " . implode('.', $variableExplode));
                return null;
            }
        }

        return $out;
    }

    /**
     * Loads all configuration files in config dir
     */
    public static function loadConfigFiles(...$args)
    {
        foreach (array_diff(scandir('config'), array('..', '.', '.gitignore', 'default')) as $configFile) {
            try {
                if (preg_match('/[a-z\-\_]+\.json/i', $configFile)) {
                    $content = file_get_contents('config/' . $configFile);
                } else {
                    Log::warning("Malicious file in config folder: $configFile.");
                    continue;
                }
            } catch (\Exception $e) {
                Log::error("File could not be read: $configFile.");
                continue;
            }

            $json = json_decode($content);

            if (!$json) {
                Log::error("Malformed json in config: $configFile.");
                continue;
            }

            if (!isset($json->module)) {
                Log::error("Missing 'module' parameter in config: $configFile.");
                continue;
            }

            self::$configs[$json->module] = $json;

            Log::info("Config ($json->module) loaded.");
        }

        if (count($args)) {
            ChatController::messageAll(warning('Config files reloaded'));
        }
    }

    public static function configReload()
    {
        self::loadConfigFiles();

        $loadedModules = ModuleController::getModules();

        $loadedModules->each(function (Module $module) {
            if (method_exists($module->class, 'onConfigReload')) {
                call_func("$module->class::onConfigReload");
            }
        });
    }
}