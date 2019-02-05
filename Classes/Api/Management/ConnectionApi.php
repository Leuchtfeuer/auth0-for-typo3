<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Api\Management;

use Auth0\SDK\API\Management\Connections;
use Bitmotion\Auth0\Domain\Model\Auth0\Connection;
use GuzzleHttp\Psr7\Response;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

class ConnectionApi
{
    private $connections;

    public function __construct(Connections $connections)
    {
        $this->connections = $connections;
    }

    public function getAll(
        string $strategy = '',
        string $fields = '',
        bool $includeFields = true,
        int $page = 0,
        int $perPage = 0,
        array $params = []
    ): ObjectStorage {
        // Connection strategy to filter results by.
        if ($strategy !== '') {
            $params['strategy'] = $strategy;
        }

        // Results fields.
        if ($fields !== '') {
            $params['fields'] = $fields;
            if ($includeFields === true) {
                $params['include_fields'] = $includeFields;
            }
        }

        // Pagination.
        if ($page > 0) {
            $params['page'] = abs((int)$page);
            if ($perPage > 0) {
                $params['per_page'] = $perPage;
            }
        }

        /** @var Response $response */
        $response = $this->connections
            ->getApiClient()
            ->method('get')
            ->addPath('connections')
            ->withDictParams($params)
            ->setReturnType('object')
            ->call();

        return $this->mapResponse($response);
    }

    /**
     * @throws \Exception
     */
    protected function mapResponse(Response $response): ObjectStorage
    {
        $connections = \GuzzleHttp\json_decode($response->getBody()->getContents(), true);

        $connectionObjects = new ObjectStorage();
        foreach ($connections as $connection) {
            $connectionObjects->attach(new Connection($connection));
        }

        return $connectionObjects;
    }

    /**
     * @todo Rewrite
     * @param null|mixed $fields
     * @param null|mixed $include_fields
     */
    public function get($id, $fields = null, $include_fields = null)
    {
        return $this->connections->get($id, $fields, $include_fields);
    }

    /**
     * @todo Rewrite
     */
    public function delete($id)
    {
        return $this->connections->delete($id);
    }

    /**
     * @todo Rewrite
     */
    public function deleteUser($id, $email)
    {
        return $this->connections->deleteUser($id, $email);
    }

    /**
     * @todo Rewrite
     */
    public function create(array $data)
    {
        return $this->connections->create($data);
    }

    /**
     * @todo Rewrite
     */
    public function update(string $id, array $data)
    {
        return $this->connections->update($id, $data);
    }
}
