<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DbalException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'dressur:repair-promo-references',
    description: 'Répare les références publiques vides ou dupliquées des promotions réseaux sociaux.'
)]
final class RepairPromoReseauReferencesCommand extends Command
{
    public function __construct(private readonly Connection $connection)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption(
            'dry-run',
            null,
            InputOption::VALUE_NONE,
            'Affiche le nombre de références à réparer sans modifier la base.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        try {
            $ids = $this->connection->fetchFirstColumn(
                "SELECT p.id
                 FROM promo_reseau p
                 LEFT JOIN (
                     SELECT reference
                     FROM promo_reseau
                     WHERE reference IS NOT NULL AND TRIM(reference) <> ''
                     GROUP BY reference
                     HAVING COUNT(*) > 1
                 ) duplicates ON duplicates.reference = p.reference
                 WHERE p.reference IS NULL OR TRIM(p.reference) = '' OR duplicates.reference IS NOT NULL
                 ORDER BY p.id"
            );
        } catch (DbalException $exception) {
            $output->writeln('<error>La colonne promo_reseau.reference est absente ou inaccessible. Exécutez d’abord schema:update --force.</error>');
            return Command::FAILURE;
        }

        $count = count($ids);
        if ($input->getOption('dry-run')) {
            $output->writeln(sprintf('<info>%d promotion(s) réseaux sociaux ont une référence vide ou dupliquée à réparer.</info>', $count));
            return Command::SUCCESS;
        }

        if ($count === 0) {
            $output->writeln('<info>Aucune référence vide ou dupliquée à réparer.</info>');
            return Command::SUCCESS;
        }

        $this->connection->beginTransaction();
        try {
            foreach ($ids as $id) {
                $reference = 'pr_' . substr(hash('sha256', $id . '-dressur-public-reference'), 0, 24);
                $this->connection->executeStatement(
                    'UPDATE promo_reseau SET reference = :reference WHERE id = :id AND (reference IS NULL OR TRIM(reference) = \'\')',
                    ['reference' => $reference, 'id' => $id]
                );
            }
            $this->connection->commit();
        } catch (\Throwable $exception) {
            $this->connection->rollBack();
            throw $exception;
        }

        $output->writeln(sprintf('<info>%d promotion(s) réseaux sociaux ont reçu une référence stable et unique.</info>', $count));
        return Command::SUCCESS;
    }
}
