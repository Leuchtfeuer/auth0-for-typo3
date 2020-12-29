<?php


namespace Leuchtfeuer\Auth0\Tests\Functional\Store\Session;


use Bitmotion\Auth0\Store\SessionStore;
use PHPUnit\Framework\Error\Error;
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
        try {
            parent::setUp();
        } catch (\Exception $e) {
            throw new \Exception(print_r($GLOBALS['TYPO3_CONF_VARS']['DB'], true));

        }
        
        $this->subject = new SessionStore();
    }

    /**
     * @test
     */
    public function storeInvalidUserDataTest()
    {
        $this->expectException(Error::class);
        $this->subject->set('user', 'dummy');
        $this->subject->getUserInfo();
    }

    /**
     * @test
     */
    public function storeDataTest()
    {
        $this->subject->set('foo', 'bar');
        $this->assertSame('bar', $this->subject->get('foo'));
    }

    /**
     * @test
     */
    public function storeUserData()
    {
        $user = ['name' => 'John Doe'];
        $this->subject->set('user', $user);
        $this->assertSame($user, $this->subject->getUserInfo());
    }

    /**
     * @test
     */
    public function deleteUserData()
    {
        $user = ['name' => 'John Doe'];
        $this->subject->set('user', $user);
        $this->subject->delete($user);
        $this->assertEmpty($this->subject->getUserInfo());

    }
}
