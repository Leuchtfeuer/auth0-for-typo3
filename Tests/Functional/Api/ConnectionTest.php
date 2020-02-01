<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Tests\Functional\Api;

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

use Bitmotion\Auth0\Api\Management\ConnectionApi;
use Bitmotion\Auth0\Domain\Model\Auth0\Management\Connection;
use Bitmotion\Auth0\Scope;
use Bitmotion\Auth0\Tests\Functional\Auth0TestCase;

class ConnectionTest extends Auth0TestCase
{
    protected $scopes = [
        Scope::CONNECTION_READ,
        Scope::CONNECTION_CREATE,
        Scope::CONNECTION_DELETE,
        Scope::CONNECTION_UPDATE,
    ];

    /**
     * Tries to instantiate the ClientGrantApi
     *
     * @test
     * @covers \Bitmotion\Auth0\Utility\ApiUtility::getConnectionApi
     */
    public function instantiateApi(): ConnectionApi
    {
        $connectionApi = $this->getApiUtility()->getConnectionApi(...$this->scopes);
        $this->assertInstanceOf(ConnectionApi::class, $connectionApi);

        return $connectionApi;
    }

    /**
     * Find all Connections in Auth0
     *
     * @test
     * @depends instantiateApi
     * @covers \Bitmotion\Auth0\Api\Management\ConnectionApi::list
     */
    public function listConnections(ConnectionApi $connectionApi)
    {
        $connections = $connectionApi->list();
        $this->assertNotEmpty($connections);

        $connections = $connectionApi->list(Connection::STRATEGY_AUTH0);
        // TODO ???
    }

    /**
     * Checks whether ClientGrant array contains a ClientGrant object
     *
     * @test
     * @depends instantiateApi
     */
    public function getConnection(ConnectionApi $connectionApi): Connection
    {
        $connection = $connectionApi->get('con_VAr3ro5CceHHNCwj');
        $this->assertInstanceOf(Connection::class, $connection);
        $this->assertEquals($connection->getStrategy(), Connection::STRATEGY_AUTH0);

        return $connection;
    }

    // TODO: add test for delete
    // TODO: add test for deleteUser
    // TODO: add test for create
    // TODO: add test for update
}
