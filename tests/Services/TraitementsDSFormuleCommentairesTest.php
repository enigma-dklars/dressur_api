<?php

namespace App\Tests\Services;

use App\Entity\FormulePromoReseau;
use App\Services\TraitementsDS;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class TraitementsDSFormuleCommentairesTest extends TestCase
{
    /**
     * @dataProvider formuleCommentairesProvider
     */
    public function testFormuleNecessiteCommentaires(
        string $titre,
        bool $necessiteCommentaires,
        ?string $titreParent = null
    ): void {
        $formule = (new FormulePromoReseau())
            ->setTitre($titre)
            ->setCommentairesRequis($necessiteCommentaires);

        if ($titreParent !== null) {
            $parent = (new FormulePromoReseau())->setTitre($titreParent);
            $formule->setParent($parent);
        }

        self::assertSame(
            $necessiteCommentaires,
            $this->createTraitementsDS()->formuleNecessiteCommentaires($formule)
        );
    }

    public function formuleCommentairesProvider(): array
    {
        return [
            'commentaires personnalisés' => ['Commentaires personnalisés', true],
            'commentaire singulier' => ['Commentaire', true],
            'variante customisée accentuée' => ['Interactions customisés', true],
            'variante customises sans accent' => ['Interactions customises', true],
            'titre parent' => ['100', true, 'Commentaires'],
            'likes sans commentaires' => ['Likes', false],
            'formule classique' => ['Abonnés', false],
        ];
    }

    /**
     * @dataProvider commentairesProvider
     */
    public function testNormaliserCommentaires(string $saisie, array $commentairesAttendus): void
    {
        self::assertSame(
            $commentairesAttendus,
            $this->createTraitementsDS()->normaliserCommentaires($saisie)
        );
    }

    public function commentairesProvider(): array
    {
        return [
            'commentaire unique' => ['  Excellent produit  ', ['Excellent produit']],
            'plusieurs commentaires' => [
                "Super publication\nTrès belle photo",
                ['Super publication', 'Très belle photo'],
            ],
            'lignes vides ignorées' => [
                "Premier commentaire\n\n  \nDeuxième commentaire  ",
                ['Premier commentaire', 'Deuxième commentaire'],
            ],
            'textarea vide' => ['', []],
        ];
    }

    private function createTraitementsDS(): TraitementsDS
    {
        /** @var TraitementsDS $traitementsDS */
        $traitementsDS = (new ReflectionClass(TraitementsDS::class))->newInstanceWithoutConstructor();

        return $traitementsDS;
    }
}