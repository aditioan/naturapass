<?php
/**
 * Created by PhpStorm.
 * User: vincentvalot
 * Date: 23/06/15
 * Time: 14:59
 */

require_once __DIR__ . '/bootstrap.php.cache';
require_once __DIR__.'/AppKernel.php';

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;

use Doctrine\Bundle\DoctrineBundle\Command\DropDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand;
use Doctrine\Bundle\FixturesBundle\Command\LoadDataFixturesDoctrineCommand;


$kernel = new AppKernel('test', true); // create a "test" kernel
$kernel->boot();

$application = new Application($kernel);
$application->getDefinition();

// add the database:drop command to the application and run it
$command = new DropDatabaseDoctrineCommand();
$application->add($command);
$input = new ArrayInput(array(
    'command' => 'doctrine:database:drop',
    '--if-exists' => true,
    '--force' => true,
));
$command->run($input, new ConsoleOutput());

// add the database:create command to the application and run it
$command = new CreateDatabaseDoctrineCommand();
$application->add($command);
$input = new ArrayInput(array(
    'command' => 'doctrine:database:create',
));
$command->run($input, new ConsoleOutput());

//Make sure we close the original connection because it lost the reference to the database
$connection = $application->getKernel()->getContainer()->get('doctrine')->getConnection();

if ($connection->isConnected()) {
    $connection->close();
}

// let Doctrine create the database schema (i.e. the tables)
$command = new CreateSchemaDoctrineCommand();
$application->add($command);
$input = new ArrayInput(array(
    'command' => 'doctrine:schema:create',
));
$command->run($input, new ConsoleOutput());

// let Doctrine create the database schema (i.e. the tables)
$command = new LoadDataFixturesDoctrineCommand();
$application->add($command);

$input = new ArrayInput(array('command' => 'doctrine:fixtures:load'));
$input->setInteractive(false);
$command->run($input, new ConsoleOutput());
