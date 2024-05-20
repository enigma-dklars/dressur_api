<?php 

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Process\Process;

class ExportDatabase extends AbstractController
{
    private $params;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
    }

    #[Route('/export/database', name: 'export_database')]
    public function exportDatabase(): Response
    {
        $databaseUrl = $this->params->get('database_url');
        $backupDir = $this->params->get('backup_directory');

        // Parse database URL
        $dbParams = parse_url($databaseUrl);
        $dbName = ltrim($dbParams['path'], '/');
        $dbUser = $dbParams['user'];
        $dbPass = $dbParams['pass'];
        $dbHost = $dbParams['host'];

        // Create backup directory if it doesn't exist
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0777, true);
        }

        // Define the filename and path for the backup file
        $dateTime = (new \DateTime())->format('d_m_Y-H_i_s');
        $fileName = sprintf('db-%s.sql', $dateTime);
        $filePath = $backupDir . DIRECTORY_SEPARATOR . $fileName;

        // Create the mysqldump command
        $command = sprintf(
            'mysqldump --user=%s --password=%s --host=%s %s > %s',
            $dbUser,
            $dbPass,
            $dbHost,
            $dbName,
            $filePath
        );

        // Execute the command
        $process = Process::fromShellCommandline($command);
        $process->run();

        if (!$process->isSuccessful()) {
            return new Response('Database export failed: ' . $process->getErrorOutput(), 500);
        }

        return new Response('Database exported successfully to ' . $filePath);
    }
}
