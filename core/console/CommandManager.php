<?php

namespace SnapPHP\Core\Console;

class CommandManager
{
    protected $commands = [
        'make:controller' => 'SnapPHP\Core\Console\Commands\MakeController',
        'make:model' => 'SnapPHP\Core\Console\Commands\MakeModel',
        'make:view' => 'SnapPHP\Core\Console\Commands\MakeView',
        'help' => 'SnapPHP\Core\Console\Commands\HelpCommand',
    ];

    public function run(array $args)
    {
        $command = $args[1] ?? 'help';
        $arguments = array_slice($args, 2);

        define('BASE_PATH_IN_COMMANDS', dirname(__DIR__, 5));

        if (!isset($this->commands[$command])) {
            echo "Command not recognized. Use 'php console.php help' for assistance.\n";
            return;
        }

        $commandClass = $this->commands[$command];
        (new $commandClass())->execute($arguments);
    }
}
