<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Helpers\ApiClient;
use Auth0\SDK\Exception\ApiException;
use GuzzleHttp\Psr7\Response;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\VarExporter\Exception\ClassNotFoundException;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class GeneralManagementApi implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected $apiClient;

    protected $objectName = '';

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
     * @throws ClassNotFoundException
     * @return object|ObjectStorage
     */
    protected function mapResponse(Response $response)
    {
        $bodyContent = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

        if (isset($bodyContent['statusCode'])) {
            $this->getResponseObject($bodyContent);
        }

        $objectName = $this->getObjectName();

        if (isset($bodyContent['id']) || isset($bodyContent['ticket'])) {
            return new $objectName($bodyContent);
        }

        $objects = new ObjectStorage();
        foreach ($bodyContent as $object) {
            $objects->attach(new $objectName($object));
        }

        return $objects;
    }

    /**
     * @throws ClassNotFoundException
     */
    private function getObjectName(): string
    {
        if ($this->objectName !== '') {
            return $this->objectName;
        }

        $className = get_called_class();
        $parts = explode('\\', $className);
        $modelName = rtrim(array_pop($parts), 'Api');
        $modelClass = $parts[0] . '\\' . $parts[1] . '\\Domain\\Model\\Auth0\\' . $modelName;

        if (class_exists($modelClass)) {
            return $modelClass;
        }

        throw new ClassNotFoundException(sprintf('Class "%s" does not exist.', $modelClass), 1549388794);
    }

    private function getResponseObject(array $response)
    {
        if ($response['statusCode'] !== 200) {
            $this->logger->critical(
                sprintf(
                    '%s (%s): %s',
                    $response['error'],
                    $response['errorCode'],
                    $response['message']
                )
            );

            throw new ApiException('Could not handle request. See log for further details.', 1549382279);
        }
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
