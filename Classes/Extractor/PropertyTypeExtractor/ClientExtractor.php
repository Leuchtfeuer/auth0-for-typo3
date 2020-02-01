<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Extractor\PropertyTypeExtractor;

/***
 *
 * This file is part of the "Auth0 for TYPO3" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@bitmotion.de>, Bitmotion GmbH
 *
 ***/

use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\Addon;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\EncryptionKey;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\JwtConfiguration;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Client\Mobile;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyTypeExtractorInterface;
use Symfony\Component\PropertyInfo\Type;

class ClientExtractor implements PropertyTypeExtractorInterface
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
                case 'JwtConfiguration':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            JwtConfiguration::class
                        ),
                    ];
                case 'EncryptionKey':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            EncryptionKey::class
                        ),
                    ];
                case 'Mobile':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            Mobile::class
                        ),
                    ];
                case 'Addon':
                    return [
                        new Type(
                            Type::BUILTIN_TYPE_OBJECT,
                            true,
                            Addon::class
                        ),
                    ];
            }
        }

        return $this->reflectionExtractor->getTypes($class, $property, $context);
    }
}
