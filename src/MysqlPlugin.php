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
        $optionsResolver->setRequired(['username', 'database'])
            ->setDefault('password', null)
            ->setDefault('host', null)
            ->setDefault('port', null)
            ->setDefault('exportOptions', null)
            ->setDefault('importOptions', null);
    }

    /**
     * {@inheritdoc}
     */
    public function backup(Filesystem $source, Filesystem $destination, Database $database, array $parameter)
    {
        $this->output->writeln(
            sprintf('  * <comment>%s</comment>', $this->getExportCommand($parameter, 'dump.sql', true))
        );

        $tempFile = $this->temporaryFileSystem->createTemporaryFile('mysql');
        $process = new Process($this->getExportCommand($parameter, $tempFile));
        $process->run();

        while ($process->isRunning()) {
            // waiting for process to finish
        }

        $handler = fopen($tempFile, 'r');
        $destination->putStream('dump.sql', $handler);
        fclose($handler);
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
        $this->output->writeln(
            sprintf('  * <comment>%s</comment>', $this->getImportCommand($parameter, 'dump.sql', true))
        );

        $tempFile = $this->temporaryFileSystem->createTemporaryFile('mysql');
        file_put_contents($tempFile, $source->read('dump.sql'));

        $process = new Process($this->getImportCommand($parameter, $tempFile));
        $process->run();

        while ($process->isRunning()) {
            // waiting for process to finish
        }
    }

    /**
     * Returns command to export database.
     *
     * @param array $parameter
     * @param string $file
     * @param bool $hidePassword
     *
     * @return string
     */
    private function getExportCommand(array $parameter, $file, $hidePassword = false)
    {
        $username = $parameter['username'];
        $password = $parameter['password'];
        $database = $parameter['database'];
        $host = $parameter['host'];
        $port = $parameter['port'];
        $options = $parameter['exportOptions'];

        return sprintf(
            'mysqldump -u%s%s%s%s%s %s > %s',
            $username,
            isset($password) ? (' -p' . ($hidePassword ? '***' : "'" . addcslashes($password, "'") . "'")) : '',
            isset($host) ? (' -h ' . $host) : '',
            isset($port) ? (' -P ' . $port) : '',
            isset($options) ? (' ' . $options) : '',
            $database,
            $file
        );
    }

    /**
     * Returns command to import database.
     *
     * @param array $parameter
     * @param string $file
     * @param bool $hidePassword
     *
     * @return string
     */
    private function getImportCommand(array $parameter, $file, $hidePassword = false)
    {
        $username = $parameter['username'];
        $password = $parameter['password'];
        $database = $parameter['database'];
        $host = $parameter['host'];
        $port = $parameter['port'];
        $options = $parameter['importOptions'];

        return sprintf(
            'mysql -u%s%s%s%s%s %s < %s',
            $username,
            isset($password) ? (' -p' . ($hidePassword ? '***' : $password)) : '',
            isset($host) ? (' -h ' . $host) : '',
            isset($port) ? (' -P ' . $port) : '',
            isset($options) ? (' ' . $options) : '',
            $database,
            $file
        );
    }
}
