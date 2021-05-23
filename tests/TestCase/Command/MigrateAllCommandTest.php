<?php
declare(strict_types=1);

namespace MigrateAll\Test\TestCase\Command;

use Cake\TestSuite\ConsoleIntegrationTestTrait;
use Cake\TestSuite\TestCase;
use MigrateAll\Command\MigrateAllCommand;

/**
 * MigrateAll\Command\MigrateAllCommand Test Case
 *
 * @uses \MigrateAll\Command\MigrateAllCommand
 */
class MigrateAllCommandTest extends TestCase
{
    use ConsoleIntegrationTestTrait;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->useCommandRunner();
    }

    /**
     * Test buildOptionParser method
     *
     * @return void
     */
    public function testBuildOptionParser(): void
    {
        $this->exec('MigrateAll.migrate_all --help');
        $this->assertOutputContains('cake migrate_all.migrate_all');
    }

    /**
     * Test execute method
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->exec('MigrateAll.migrate_all');
        $this->assertExitCode(MigrateAllCommand::CODE_SUCCESS);
    }
}
