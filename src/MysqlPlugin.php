<?php

namespace Nanbando\Plugin\Mysql;

use League\Flysystem\Filesystem;
use Nanbando\Core\Database\Database;
use Nanbando\Core\Database\ReadonlyDatabase;
use Nanbando\Core\Plugin\PluginInterface;
use Neutron\TemporaryFilesystem\TemporaryFilesystemInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Process\Process;

class MysqlPlugin implements PluginInterface
{
    /**
     * @var OutputInterface
     */
    private $output;

    /**
     * @var TemporaryFilesystemInterface
     */
    private $temporaryFileSystem;

    /**
     * @param OutputInterface $output
     * @param TemporaryFilesystemInterface $temporaryFileSystem
     */
    public function __construct(OutputInterface $output, TemporaryFilesystemInterface $temporaryFileSystem)
    {
        $this->output = $output;
        $this->temporaryFileSystem = $temporaryFileSystem;
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptionsResolver(OptionsResolver $optionsResolver)
    {
        $optionsResolver->setRequired(['username', 'database'])->setDefault('password', null);
    }

    /**
     * {@inheritdoc}
     */
    public function backup(Filesystem $source, Filesystem $destination, Database $database, array $parameter)
    {
        $tempFile = $this->temporaryFileSystem->createTemporaryFile('mysql');
        $process = new Process(
            $this->getExportCommand(
                $parameter['username'],
                $parameter['password'],
                $parameter['database'],
                $tempFile
            )
        );
        $process->run();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        $handler = fopen($tempFile, 'r');
        $destination->putStream('dump.sql', $handler);
        fclose($handler);
        $this->output->writeln(
            sprintf(
                '  * <comment>%s</comment>',
                $this->getExportCommand(
                    $parameter['username'],
                    $parameter['password'],
                    $parameter['database'],
                    'dump.sql',
                    true
                )
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function restore(
        Filesystem $source,
        Filesystem $destination,
        ReadonlyDatabase $database,
        array $parameter
    ) {
        $tempFile = $this->temporaryFileSystem->createTemporaryFile('mysql');
        file_put_contents($tempFile, $source->read('dump.sql'));

        $process = new Process(
            $this->getImportCommand(
                $parameter['username'],
                $parameter['password'],
                $parameter['database'],
                $tempFile
            )
        );
        $process->run();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        $this->output->writeln(
            sprintf(
                '  * <comment>%s</comment>',
                $this->getImportCommand(
                    $parameter['username'],
                    $parameter['password'],
                    $parameter['database'],
                    'dump.sql',
                    true
                )
            )
        );
    }

    /**
     * Returns command to export database.
     *
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string $file
     * @param bool $hidePassword
     *
     * @return string
     */
    private function getExportCommand($username, $password, $database, $file, $hidePassword = false)
    {
        return sprintf(
            'mysqldump -u%s %s %s > %s',
            $username,
            isset($password) ? ('-p' . ($hidePassword ? '***' : "'".addcslashes($password, "'")."'")) : '',
            $database,
            $file
        );
    }

    /**
     * Returns command to import database.
     *
     * @param string $username
     * @param string $password
     * @param string $database
     * @param string $file
     * @param bool $hidePassword
     *
     * @return string
     */
    private function getImportCommand($username, $password, $database, $file, $hidePassword = false)
    {
        return sprintf(
            'mysql -u%s%s %s < %s',
            $username,
            isset($password) ? (' -p' . ($hidePassword ? '***' : $password)) : '',
            $database,
            $file
        );
    }
}
