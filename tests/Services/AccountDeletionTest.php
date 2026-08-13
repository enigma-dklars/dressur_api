<?php

namespace App\Tests\Services;

use App\Entity\Boost;
use App\Entity\DeletedDS;
use App\Entity\FormuleBoost;
use App\Entity\HistoriqueProgrammeRecompense;
use App\Entity\Message;
use App\Entity\Notification;
use App\Entity\Preuve;
use App\Entity\Promotion;
use App\Entity\PromotionMotifRefus;
use App\Entity\Signalement;
use App\Entity\Transaction;
use App\Entity\User;
use App\Entity\UserSocialNetwork;
use App\Repository\BoostRepository;
use App\Repository\DeletedDSRepository;
use App\Repository\EnvMailSenderRepository;
use App\Repository\EnvPaiementApiRepository;
use App\Repository\EnvRepository;
use App\Repository\FormuleBoostRepository;
use App\Repository\FormuleDressurBotRepository;
use App\Repository\FormulePromoAffaireRepository;
use App\Repository\FormulePromoReseauRepository;
use App\Repository\HistoriqueProgrammeRecompenseRepository;
use App\Repository\MethodePaiementRepository;
use App\Repository\MessageRepository;
use App\Repository\PreferenceRepository;
use App\Repository\PromotionRepository;
use App\Repository\PromoReseauRepository;
use App\Repository\SignalementRepository;
use App\Repository\SuggestionRepository;
use App\Repository\TransactionRepository;
use App\Repository\UserRepository;
use App\Repository\VerifMailRepository;
use App\Services\CookieDS;
use App\Services\SessionDS;
use App\Services\TraitementsDS;
use App\Services\VerificationsDS;
use App\Controller\API\UserController;
use App\Utilities\SendMail;
use App\Utilities\ZefameApi;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Doctrine\ORM\Tools\SchemaTool;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;

final class AccountDeletionTest extends TestCase
{
    private EntityManagerInterface $entityManager;

    private TraitementsDS $traitementsDS;

    private DebugStack $sqlLogger;

    protected function setUp(): void
    {
        parent::setUp();

        $config = ORMSetup::createAttributeMetadataConfiguration(
            [dirname(__DIR__, 2).'/src/Entity'],
            true
        );
        $this->entityManager = EntityManager::create(
            ['driver' => 'pdo_sqlite', 'memory' => true],
            $config
        );
        // SQLite réserve TRANSACTION comme mot-clé ; la base de production
        // conserve le nom de table défini par l'application.
        $this->entityManager->getClassMetadata(Transaction::class)->setPrimaryTable([
            'name' => 'transactions',
        ]);

        $entityClasses = [];
        foreach (glob(dirname(__DIR__, 2).'/src/Entity/*.php') ?: [] as $file) {
            $class = 'App\\Entity\\'.basename($file, '.php');
            if (class_exists($class)) {
                $entityClasses[] = $class;
            }
        }

        $metadata = array_map(
            fn (string $class) => $this->entityManager->getClassMetadata($class),
            $entityClasses
        );
        (new SchemaTool($this->entityManager))->createSchema($metadata);
        $this->entityManager->getConnection()->executeStatement(
            'CREATE TABLE dsbonus_historique (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INTEGER NULL)'
        );

        $this->sqlLogger = new DebugStack();
        $config->setSQLLogger($this->sqlLogger);
        $this->traitementsDS = $this->createTraitementsDS($this->entityManager);
    }

    protected function tearDown(): void
    {
        if ($this->entityManager->isOpen()) {
            $this->entityManager->getConnection()->close();
        }

        parent::tearDown();
    }

    public function testCompleteAccountDeletionRemovesDependenciesPreservesPartnersAndCreatesOneDeletedRecord(): void
    {
        $fixture = $this->persistCompleteAccountFixture();
        $ownerId = $fixture['owner']->getId();
        $partnerIds = [
            $fixture['partner']->getId(),
            $fixture['secondPartner']->getId(),
        ];

        $this->sqlLogger->queries = [];
        $this->traitementsDS->execPurge(
            $fixture['owner'],
            true,
            'Je souhaite supprimer définitivement mon compte et mes données.'
        );
        $this->entityManager->clear();

        self::assertNull($this->findUser($ownerId));
        self::assertSame(
            0,
            $this->countByField(Preuve::class, 'user', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(HistoriqueProgrammeRecompense::class, 'user', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(Notification::class, 'user', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(Boost::class, 'user', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(Transaction::class, 'user', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(Message::class, 'emetteur', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(Message::class, 'recepteur', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(Signalement::class, 'signaler', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(Signalement::class, 'signalant', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByField(UserSocialNetwork::class, 'user', $fixture['owner'])
        );
        self::assertSame(
            0,
            $this->countByPromotionMotif($fixture['promotion'])
        );
        self::assertSame(
            0,
            $this->countByField(Promotion::class, 'user', $fixture['owner'])
        );
        self::assertSame(
            0,
            (int) $this->entityManager->getConnection()->fetchOne(
                'SELECT COUNT(*) FROM dsbonus_historique WHERE user_id = ?',
                [$ownerId]
            )
        );

        foreach ($partnerIds as $partnerId) {
            $partner = $this->findUser($partnerId);
            self::assertInstanceOf(User::class, $partner);
            self::assertNull($partner->getPartenaire());
        }

        self::assertSame(
            1,
            (int) $this->entityManager->createQuery(
                'SELECT COUNT(d.id) FROM App\Entity\DeletedDS d WHERE d.mail = :mail'
            )
                ->setParameter('mail', $fixture['owner']->getMail())
                ->getSingleScalarResult()
        );

        $deleteQueries = array_values(array_filter(
            $this->sqlLogger->queries,
            static fn (array $query): bool => preg_match('/^\s*DELETE\s/i', $query['sql']) === 1
        ));
        $proofDelete = $this->deleteIndex($deleteQueries, 'preuve');
        $historyDelete = $this->deleteIndex($deleteQueries, 'HistoriqueProgrammeRecompense');
        $motifDelete = $this->deleteIndex($deleteQueries, 'PromotionMotifRefus');
        $promotionDelete = $this->deleteIndex($deleteQueries, 'Promotion');
        $userDelete = $this->deleteIndex($deleteQueries, 'user');

        self::assertLessThan($historyDelete, $proofDelete);
        self::assertLessThan($promotionDelete, $motifDelete);
        self::assertSame(count($deleteQueries) - 1, $userDelete);
    }

    public function testCompleteAccountDeletionRollsBackEveryChangeWhenALateStepFails(): void
    {
        $fixture = $this->persistCompleteAccountFixture();
        $ownerId = $fixture['owner']->getId();
        $partnerId = $fixture['partner']->getId();

        $this->entityManager->getConnection()->executeStatement('DROP TABLE dsbonus_historique');

        try {
            $this->traitementsDS->execPurge($fixture['owner'], true, 'Motif de test suffisamment long.');
            self::fail('La purge aurait dû échouer lorsque la table finale est indisponible.');
        } catch (\RuntimeException $exception) {
            self::assertSame('La suppression du compte a échoué.', $exception->getMessage());
        }

        self::assertInstanceOf(
            User::class,
            $this->findUser($ownerId)
        );
        self::assertSame(1, $this->countByField(Promotion::class, 'user', $fixture['owner']));
        self::assertSame(1, $this->countByField(Preuve::class, 'user', $fixture['owner']));
        self::assertSame(
            1,
            $this->countByField(HistoriqueProgrammeRecompense::class, 'user', $fixture['owner'])
        );
        self::assertSame(1, $this->countByField(Notification::class, 'user', $fixture['owner']));
        self::assertSame(1, $this->countByField(Boost::class, 'user', $fixture['owner']));
        self::assertSame(1, $this->countByField(Transaction::class, 'user', $fixture['owner']));
        self::assertSame(1, $this->countByField(Message::class, 'emetteur', $fixture['owner']));
        self::assertSame(1, $this->countByField(Signalement::class, 'signaler', $fixture['owner']));
        self::assertSame(1, $this->countByField(UserSocialNetwork::class, 'user', $fixture['owner']));
        self::assertSame(1, $this->countByPromotionMotif($fixture['promotion']));

        $partner = $this->findUser($partnerId);
        self::assertInstanceOf(User::class, $partner);
        self::assertSame($ownerId, $partner->getPartenaire()?->getId());
        self::assertSame(
            0,
            (int) $this->entityManager->createQuery(
                'SELECT COUNT(d.id) FROM App\Entity\DeletedDS d WHERE d.mail = :mail'
            )
                ->setParameter('mail', $fixture['owner']->getMail())
                ->getSingleScalarResult()
        );
    }

    public function testDeleteAccountEndpointReturnsControlledJsonErrorWhenPurgeFails(): void
    {
        $owner = $this->user('api-error');
        $traitementsDS = $this->createMock(TraitementsDS::class);
        $traitementsDS
            ->expects(self::once())
            ->method('execPurge')
            ->willThrowException(new \RuntimeException('database failure'));

        $cookieDS = $this->createMock(CookieDS::class);
        $cookieDS
            ->method('getWithFallback')
            ->willReturn($owner->getUid());

        $userRepository = $this->createMock(UserRepository::class);
        $userRepository
            ->method('findOneBy')
            ->with(['uid' => $owner->getUid()])
            ->willReturn($owner);

        $controller = new UserController(
            $this->createMock(EntityManagerInterface::class),
            $this->createMock(EnvRepository::class),
            $traitementsDS,
            $cookieDS,
            $this->createMock(SendMail::class),
            $this->createMock(LoggerInterface::class)
        );

        $response = $controller->deleteCompteDS(
            Request::create(
                '/api/deleteCompteDS',
                'POST',
                ['motifDeleted' => str_repeat('motif de suppression contrôlé ', 5)]
            ),
            $traitementsDS,
            $this->createMock(VerificationsDS::class),
            $userRepository
        );

        self::assertSame(Response::HTTP_INTERNAL_SERVER_ERROR, $response->getStatusCode());
        self::assertSame(
            [
                'error' => true,
                'titre' => 'Erreur!',
                'message' => 'La suppression du compte a échoué. Veuillez réessayer.',
            ],
            json_decode((string) $response->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );
    }

    /**
     * @return array{
     *     owner: User,
     *     partner: User,
     *     secondPartner: User,
     *     promotion: Promotion
     * }
     */
    private function persistCompleteAccountFixture(): array
    {
        $owner = $this->user('owner');
        $partner = $this->user('partner')->setPartenaire($owner);
        $secondPartner = $this->user('second-partner')->setPartenaire($owner);
        $promotion = (new Promotion())->setUser($owner);
        $history = (new HistoriqueProgrammeRecompense())
            ->setUser($owner)
            ->setPromotion($promotion);
        $proof = (new Preuve())
            ->setCaptureListeStatut('capture-liste')
            ->setCaptureStatutOuvert('capture-ouvert')
            ->setUser($owner)
            ->setHistoriqueProgrammeRecompense($history);
        $motif = (new PromotionMotifRefus())
            ->setPromotion($promotion)
            ->setMotif('Motif de refus');
        $formuleBoost = (new FormuleBoost())
            ->setTitre('Boost de test')
            ->setPrix(500)
            ->setNbrJour(7)
            ->setActivated(true);
        $boost = (new Boost())
            ->setFormuleBoost($formuleBoost)
            ->setUser($owner);
        $transaction = (new Transaction())->setUser($owner);
        $notification = (new Notification())
            ->setUser($owner)
            ->setText('Notification de test')
            ->setCreatedAt(new \DateTime());
        $message = (new Message())
            ->setEmetteur($owner)
            ->setRecepteur($partner)
            ->setMessage('Message de test')
            ->setDateEnvoi(new \DateTime());
        $signalement = (new Signalement())
            ->setSignaler($owner)
            ->setSignalant($partner)
            ->setMotif('Signalement de test');
        $socialNetwork = (new UserSocialNetwork())
            ->setUser($owner)
            ->setNetworkType('github')
            ->setUrl('https://github.com/test-account');

        foreach ([
            $owner,
            $partner,
            $secondPartner,
            $formuleBoost,
            $promotion,
            $history,
            $proof,
            $motif,
            $boost,
            $transaction,
            $notification,
            $message,
            $signalement,
            $socialNetwork,
        ] as $entity) {
            $this->entityManager->persist($entity);
        }
        $this->entityManager->flush();
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO dsbonus_historique (user_id) VALUES (?)',
            [$owner->getId()]
        );

        return [
            'owner' => $owner,
            'partner' => $partner,
            'secondPartner' => $secondPartner,
            'promotion' => $promotion,
        ];
    }

    private function user(string $suffix): User
    {
        return (new User())
            ->setUid('uid-'.$suffix.'-'.bin2hex(random_bytes(4)))
            ->setPseudo($suffix)
            ->setNom('Test '.$suffix)
            ->setMail($suffix.'@example.test')
            ->setTel('+2299000'.random_int(1000, 9999))
            ->setPassword('test-password');
    }

    private function createTraitementsDS(EntityManagerInterface $entityManager): TraitementsDS
    {
        $dependencies = [
            EnvRepository::class,
            VerificationsDS::class,
            BoostRepository::class,
            UserRepository::class,
            SessionDS::class,
            DeletedDSRepository::class,
            PreferenceRepository::class,
            TransactionRepository::class,
            VerifMailRepository::class,
            SignalementRepository::class,
            PromotionRepository::class,
            FormulePromoReseauRepository::class,
            FormuleBoostRepository::class,
            FormuleDressurBotRepository::class,
            CookieDS::class,
            PromoReseauRepository::class,
            SuggestionRepository::class,
            MessageRepository::class,
            ZefameApi::class,
            EnvPaiementApiRepository::class,
            EnvMailSenderRepository::class,
            FormulePromoAffaireRepository::class,
            MethodePaiementRepository::class,
            HistoriqueProgrammeRecompenseRepository::class,
            SendMail::class,
            LoggerInterface::class,
            CacheInterface::class,
        ];

        $mocks = array_map(fn (string $class) => $this->createMock($class), $dependencies);

        return new TraitementsDS($entityManager, ...$mocks);
    }

    private function countByField(string $entity, string $field, User $user): int
    {
        return (int) $this->entityManager->createQuery(
            sprintf(
                'SELECT COUNT(e.id) FROM %s e WHERE IDENTITY(e.%s) = :userId',
                $entity,
                $field
            )
        )
            ->setParameter('userId', $user->getId())
            ->getSingleScalarResult();
    }

    private function findUser(int $id): ?User
    {
        return $this->entityManager->createQuery(
            'SELECT user FROM App\Entity\User user WHERE user.id = :id'
        )
            ->setParameter('id', $id)
            ->getOneOrNullResult();
    }

    private function countByPromotionMotif(Promotion $promotion): int
    {
        return (int) $this->entityManager->createQuery(
            'SELECT COUNT(motif.id) FROM App\Entity\PromotionMotifRefus motif WHERE motif.promotion = :promotion'
        )
            ->setParameter('promotion', $promotion)
            ->getSingleScalarResult();
    }

    /**
     * @param list<array{sql: string}> $deleteQueries
     */
    private function deleteIndex(array $deleteQueries, string $table): int
    {
        foreach ($deleteQueries as $index => $query) {
            if (preg_match(
                '/DELETE\s+FROM\s+[`"]?'.preg_quote($table, '/').'[`"]?(?:\s|$)/i',
                $query['sql']
            ) === 1) {
                return $index;
            }
        }

        self::fail(sprintf('Aucune suppression de la table %s n’a été enregistrée.', $table));
    }
}