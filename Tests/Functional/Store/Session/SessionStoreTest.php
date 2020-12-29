<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Tests\Functional\Store\Session;

use Bitmotion\Auth0\Store\SessionStore;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @covers \Bitmotion\Auth0\Store\SessionStore
 */
class SessionStoreTest extends FunctionalTestCase
{
    /**
     * @var SessionStore
     */
    protected $subject;

    protected $testExtensionsToLoad = [
        'typo3conf/ext/auth0'
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new SessionStore();
    }

    /**
     * @test
     */
    public function storeInvalidUserDataTest()
    {
        $this->expectException(\TypeError::class);
        $this->subject->set('user', 'dummy');
        $this->subject->getUserInfo();
    }

    /**
     * @test
     */
    public function storeDataTest()
    {
        $this->subject->set('foo', 'bar');
        self::assertSame('bar', $this->subject->get('foo'));
    }

    /**
     * @test
     */
    public function storeUserData()
    {
        $user = ['name' => 'John Doe'];
        $this->subject->set('user', $user);
        self::assertSame($user, $this->subject->getUserInfo());
    }

    /**
     * @test
     */
    public function deleteUserData()
    {
        $user = ['name' => 'John Doe'];
        $this->subject->set('user', $user);
        $this->subject->deleteUserInfo();
        self::assertEmpty($this->subject->getUserInfo());
    }
}
