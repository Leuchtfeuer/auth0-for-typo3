<?php

declare(strict_types=1);

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Bitmotion\Auth0\Extractor\PropertyTypeExtractor;

use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\ErrorPage;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\Flag;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\MfaPage;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Tenant\PasswordPage;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class TenantExtractor implements PropertyTypeExtractorInterface
{
    private $reflectionExtractor;

    public function __construct()
    {
        $this->reflectionExtractor = new ReflectionExtractor();
    }

    public function getTypes($class, $property, array $context = [])
    {
        if (is_a($class, self::class, true)) {
            switch ($property) {
                case 'ErrorPage':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            ErrorPage::class
                        ),
                    ];
                case 'Flags':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            Flag::class
                        ),
                    ];
                case 'GuradianMfaPage':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            MfaPage::class
                        ),
                    ];
                case 'ChangePassword':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            PasswordPage::class
                        ),
                    ];
            }
        }

        return $this->reflectionExtractor->getTypes($class, $property, $context);
    }
}
