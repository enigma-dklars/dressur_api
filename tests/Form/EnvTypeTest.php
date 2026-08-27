<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\Entity\Env;
use App\Form\EnvType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Validator\ValidatorExtension;
use Symfony\Component\Form\Forms;
use Symfony\Component\Validator\Validation;

final class EnvTypeTest extends TestCase
{
    public function testApiKeyFieldsAreOptionalAndNotMappedToTheEntity(): void
    {
        $validator = Validation::createValidatorBuilder()->getValidator();
        $form = Forms::createFormFactoryBuilder()
            ->addType(new EnvType())
            ->addExtension(new ValidatorExtension($validator))
            ->getFormFactory()
            ->create(EnvType::class, new Env());

        self::assertFalse($form->get('zefameApiKey')->getConfig()->getMapped());
        self::assertFalse($form->get('zefameApiKey')->getConfig()->getRequired());
        self::assertFalse($form->get('clearZefameApiKey')->getConfig()->getMapped());
        self::assertFalse($form->get('clearZefameApiKey')->getConfig()->getRequired());
        self::assertNull($form->get('zefameApiKey')->getData());
    }
}
