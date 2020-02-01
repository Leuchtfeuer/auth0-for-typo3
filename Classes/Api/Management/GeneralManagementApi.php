<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

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

use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Client;
use Bitmotion\Auth0\Domain\Model\Auth0\Api\Error;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\Exception;

class GeneralManagementApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $client;

    protected $objectNormalizer;

    protected $normalizer = [];

    protected $serializeFormat = 'json';

    protected $extractor = null;

    protected $defaultContext = [];

    public function __construct(Client $client)
    {
        $this->client = $client;
        $this->objectNormalizer = $this->getObjectNormalizer();
    }

    protected function getObjectNormalizer()
    {
        return new ObjectNormalizer(
            null,
            new CamelCaseToSnakeCaseNameConverter(),
            null,
            $this->extractor,
            null,
            null,
            $this->defaultContext
        );
    }

    /**
     * @throws ApiException
     * @throws Exception
     * @return object|object[]
     */
    protected function mapResponse(Response $response, string $objectName = '', bool $returnRaw = false)
    {
        $jsonResponse = $response->getBody()->getContents();
        $objectName = ($objectName !== '') ? $objectName : $this->getObjectName($response, $returnRaw);

        if ($returnRaw === true && !$objectName !== Error::class) {
            return $jsonResponse;
        }

        if (substr($jsonResponse, 0, 1) === '[') {
            $this->normalizer[] = new ArrayDenormalizer();
            $objectName .= '[]';
        }

        $serializer = $this->getSerializer();
        $object = $serializer->deserialize($jsonResponse, $objectName, $this->serializeFormat);

        if ($object instanceof Error) {
            $this->getResponseObject($object);
        }

        if (is_array($object) && count($object) === 1) {
            return array_shift($object);
        }

        return $object;
    }

    protected function serialize(object $object, string $format = 'json', array $allowedAttributes = []): string
    {
        $options = [
            ObjectNormalizer::SKIP_NULL_VALUES => true,
        ];

        $this->addArrayProperty($options, ObjectNormalizer::ATTRIBUTES, $allowedAttributes);

        return $this->getSerializer()->serialize($object, $format, $options);
    }

    protected function normalize(object $object, $format = null, array $attributes = [], bool $negate = false)
    {
        $options = [
            ObjectNormalizer::SKIP_NULL_VALUES => true,
        ];

        if ($negate === true) {
            $this->addArrayProperty($options, ObjectNormalizer::IGNORED_ATTRIBUTES, $attributes);
        } else {
            $this->addArrayProperty($options, ObjectNormalizer::ATTRIBUTES, $attributes);
        }

        return $this->getSerializer()->normalize($object, $format, $options);
    }

    protected function getSerializer(): Serializer
    {
        return new Serializer(
            array_merge($this->normalizer, [$this->objectNormalizer]),
            [new JsonEncoder()]
        );
    }

    /**
     * @throws Exception
     */
    private function getObjectName(Response $response, bool $returnRaw): string
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode !== 200 && $statusCode !== 201) {
            return Error::class;
        }

        if ($returnRaw === true) {
            return '';
        }

        return $this->getObjectNameByClassName();
    }

    /**
     * @throws Exception
     */
    private function getObjectNameByClassName(): string
    {
        $className = get_called_class();
        $parts = explode('\\', $className);
        $modelName = rtrim(array_pop($parts), 'Api');
        $modelClass = $parts[0] . '\\' . $parts[1] . '\\Domain\\Model\\Auth0\\Management\\' . $modelName;

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        throw new Exception(sprintf('Class "%s" does not exist.', $modelClass), 1549388794);
    }

    /**
     * @throws ApiException
     */
    private function getResponseObject(Error $error): void
    {
        $errorMessage = sprintf('%s (%s): %s', $error->getError(), $error->getErrorCode(), $error->getMessage());
        $this->logger->critical($errorMessage);

        if (GeneralUtility::getApplicationContext()->isProduction()) {
            throw new ApiException('Could not handle request. See log for further details.', 1549382279);
        }

        throw new ApiException($errorMessage, 1549559117);
    }

    protected function addStringProperty(array &$data, string $key, string $value): void
    {
        if ($value !== '') {
            $data[$key] = $value;
        }
    }

    protected function addArrayProperty(array &$data, string $key, array $value): void
    {
        if (!empty($value)) {
            $data[$key] = $value;
        }
    }

    protected function addIntegerProperty(array &$data, string $key, int $value): void
    {
        if ($value !== 0) {
            $data[$key] = $value;
        }
    }

    protected function addBooleanProperty(array &$data, string $key, $value): void
    {
        if ($value !== null) {
            $data[$key] = (bool)$value;
        }
    }
}
