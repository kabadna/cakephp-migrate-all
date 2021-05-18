<?php
declare(strict_types=1);

namespace MigrateAll\Command;

use Cake\Command\Command;
use Cake\Console\Arguments;
use Cake\Console\ConsoleIo;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\Plugin;
use Migrations\Command\MigrationsMigrateCommand;

/**
 * Migrate App & Plugin migration files.
 */
class MigrateAllCommand extends Command
{
    protected ?MigrationsMigrateCommand $_migrate = null;

    /**
     * @var array Argments for Migrations.migrate
     */
    protected array $argsForMigrate = [];

    /**
     * @var string Name of plugin that is not processed. (comma separated string)
     */
    protected string $excludePluginName = '';

    /**
     * path to Migration directory
     *
     * @var string
     */
    public $pathFragment = 'config/Migrations/';

    /**
     * Hook method invoked by CakePHP when a command is about to be executed.
     *
     * Override this method and implement expensive/important setup steps that
     * should not run on every command run. This method will be called *before*
     * the options and arguments are validated and processed.
     *
     * @return void
     */
    public function initialize(): void
    {
        $this->_migrate = new MigrationsMigrateCommand();
    }

    /**
     * Hook method for defining this command's option parser.
     *
     * @see https://book.cakephp.org/4/en/console-commands/commands.html#defining-arguments-and-options
     * @param \Cake\Console\ConsoleOptionParser $parser The parser to be defined
     * @return \Cake\Console\ConsoleOptionParser The built parser.
     */
    public function buildOptionParser(ConsoleOptionParser $parser): ConsoleOptionParser
    {
        $parser = parent::buildOptionParser($parser);

        $parser->setDescription(__('Migrate the database (App migration and all plugin migration)'));

        $parser->addOption('exclude', [
            'help' =>
                __('The name of the plugin that is not processed.')
                . __('If there are more than one, separate the plug-in names with commas.'),
            'default' => '',
        ]);

        // Get Original migrate options. And unset unnecessary options.
        $migrateOptions = $this->_migrate->getOptionParser()->toArray();
        if (isset($migrateOptions['description'])) {
            unset($migrateOptions['description']);
        }
        if (isset($migrateOptions['options']['plugin'])) {
            unset($migrateOptions['options']['plugin']);
        }

        $parser->merge($migrateOptions);

        return $parser;
    }

    /**
     * @inheritDoc
     */
    public function run(array $argv, ConsoleIo $io): ?int
    {
        // backup argv
        $this->argsForMigrate = $this->_extractMigrateArgs($argv);

        return parent::run($argv, $io);
    }

    /**
     * Parse argv and extract arguments of migrate.
     *
     * @param array $argv Arguments for this batch.
     * @return array Argument for migrate batch.
     */
    protected function _extractMigrateArgs(array $argv): array
    {
        // unset `exclude` option
        $pos = array_search('--exclude', $argv, true);
        if ($pos !== false) {
            unset($argv[$pos]);

            if (isset($argv[$pos + 1])) {
                if (strpos($argv[$pos + 1], '-') !== 0) {
                    unset($argv[$pos + 1]);
                }
            }
        }

        return $argv;
    }

    /**
     * Migrate the database (App migration and all plugin migration
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @return null|void|int The exit code or null for success
     */
    public function execute(Arguments $args, ConsoleIo $io): ?int
    {
        $loadedPlugins = Plugin::getCollection();

        // create target plugin name list.
        $pluginNames = [];
        foreach ($loadedPlugins as $pluginName => $loadedPlugin) {
            $path = $loadedPlugin->getPath() . $this->pathFragment;
            if (!empty(glob($path . '*.php'))) {
                $pluginNames[] = $pluginName;

                $io->info($pluginName . ' has migration folder. It\'s target of migrate.');
            }
        }

        // exec migrate
        $io->info('************ App ************', 2);
        $result = $this->_execMigrate($args, $io);
        if ($result !== static::CODE_SUCCESS) {
            $io->error('App migration error!');

            return $result;
        }
        foreach ($pluginNames as $pluginName) {
            $io->info('************ ' . $pluginName . ' ************', 2);
            $result = $this->_execMigrate($args, $io, $pluginName);
            if ($result !== static::CODE_SUCCESS) {
                $io->error($pluginName . ' migration error!');

                return $result;
            }
        }

        $io->success('----------------------------');
        $io->success(__('All migration finished successfully.'));

        return static::CODE_SUCCESS;
    }

    /**
     * Execute migration command
     *
     * @param \Cake\Console\Arguments $args The command arguments.
     * @param \Cake\Console\ConsoleIo $io The console io
     * @param string $pluginName plugin name (If App, set '')
     * @return null|void|int The exit code or null for success
     */
    protected function _execMigrate(Arguments $args, ConsoleIo $io, ?string $pluginName = null): ?int
    {
        // Prepare arguments
        $argv = $this->argsForMigrate;
        if ($pluginName) {
            $argv = array_merge($argv, ['--plugin', $pluginName]);
        }

        $io->verbose('--- argv ---');
        $io->verbose($argv);
        $io->verbose('------------');

        return $this->executeCommand($this->_migrate, $argv, $io);
    }
}
