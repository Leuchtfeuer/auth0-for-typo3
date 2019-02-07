<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Helpers\ApiClient;
use Auth0\SDK\Exception\ApiException;
use Bitmotion\Auth0\Domain\Model\Auth0\ApiError;
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

    protected $apiClient;

    protected $objectName = '';

    // TODO: add DateTimeNormalizer if necessary
    protected $normalizer = [];

    protected $serializeFormat = 'json';

    public function __construct(ApiClient $apiClient)
    {
        $this->apiClient = $apiClient;
    }

    protected function setObjectName(string $objectName)
    {
        $this->objectName = $objectName;
    }

    /**
     * @throws ApiException
     * @throws Exception
     * @return object|array[object]
     */
    protected function mapResponse(Response $response)
    {
        $jsonResponse = $response->getBody()->getContents();
        $objectName = $this->getObjectName($response);

        $this->normalizer[] = new ObjectNormalizer(null, new CamelCaseToSnakeCaseNameConverter());

        if (substr($jsonResponse, 0, 1) === '[') {
            $this->normalizer[] = new ArrayDenormalizer();
            $objectName .= '[]';
        }

        $serializer = new Serializer($this->normalizer, [new JsonEncoder()]);
        $object = $serializer->deserialize($jsonResponse, $objectName, $this->serializeFormat);

        if ($object instanceof ApiError) {
            $this->getResponseObject($object);
        }

        if (is_array($object) && count($object) === 1) {
            return array_shift($object);
        }

        return $object;
    }

    /**
     * @throws Exception
     */
    private function getObjectName(Response $response): string
    {
        if ($response->getStatusCode() !== 200) {
            return ApiError::class;
        }

        if ($this->objectName !== '') {
            return $this->objectName;
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
        $modelClass = $parts[0] . '\\' . $parts[1] . '\\Domain\\Model\\Auth0\\' . $modelName;

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        throw new Exception(sprintf('Class "%s" does not exist.', $modelClass), 1549388794);
    }

    /**
     * @throws ApiException
     */
    private function getResponseObject(ApiError $error)
    {
        $errorMessage = sprintf('%s (%s): %s', $error->getError(), $error->getErrorCode(), $error->getMessage());
        $this->logger->critical($errorMessage);

        if (GeneralUtility::getApplicationContext()->isProduction()) {
            throw new ApiException('Could not handle request. See log for further details.', 1549382279);
        }

        throw new ApiException($errorMessage, 1549559117);
    }

    protected function addStringProperty(array &$data, string $key, string $value)
    {
        if ($value !== '') {
            $data[$key] = $value;
        }
    }

    protected function addArrayProperty(array &$data, string $key, array $value)
    {
        if (!empty($value)) {
            $data[$key] = $value;
        }
    }

    protected function addIntegerProperty(array &$data, string $key, int $value)
    {
        if ($value !== 0) {
            $data[$key] = $value;
        }
    }

    protected function addBooleanProperty(array &$data, string $key, $value)
    {
        if ($value !== null) {
            $data[$key] = (bool)$value;
        }
    }
}
