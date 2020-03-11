<?php
declare(strict_types = 1);
namespace Bitmotion\Auth0\Domain\Model\Auth0\Api;

/***
 *
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 *  (c) 2018 Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 *
 ***/

use Auth0\SDK\API\Header\ContentType;
use Auth0\SDK\API\Header\Header;
use Auth0\SDK\API\Helpers\InformationHeaders;
use Auth0\SDK\API\Helpers\RequestBuilder;
use Auth0\SDK\Exception\CoreException;
use TYPO3\CMS\Core\Package\Exception;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

class Client
{
    const METHOD_GET = 'get';
    const METHOD_POST = 'post';
    const METHOD_PATCH = 'patch';
    const METHOD_PUT = 'put';
    const METHOD_DELETE = 'delete';

    /**
     * @var string
     */
    protected $domain = '';

    /**
     * @var string
     */
    protected $basePath = '';

    /**
     * @var array
     */
    protected $guzzleOptions = [];

    /**
     * @var
     */
    protected $returnType = 'object';

    /**
     * @var Header[]
     */
    protected $headers = [];

    /**
     * @throws Exception
     */
    public function __construct(bool $setInfoHeader = true)
    {
        if ($setInfoHeader === true) {
            $this->headers[] = new Header('Auth0-Client', $this->buildInfoHeader()->build());
        }
    }

    /**
     * @throws Exception
     */
    protected function buildInfoHeader(): InformationHeaders
    {
        $informationHeader = new InformationHeaders();
        $informationHeader->setPackage('auth0-typo3', ExtensionManagementUtility::getExtensionVersion('auth0'));
        $informationHeader->setEnvProperty('php', phpversion());

        return $informationHeader;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): void
    {
        $this->domain = $domain;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function setBasePath(string $basePath): void
    {
        $this->basePath = $basePath;
    }

    public function getGuzzleOptions(): array
    {
        return $this->guzzleOptions;
    }

    public function setGuzzleOptions(array $guzzleOptions): void
    {
        $this->guzzleOptions = $guzzleOptions;
    }

    public function getReturnType()
    {
        return $this->returnType;
    }

    public function setReturnType($returnType): void
    {
        $this->returnType = $returnType;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function addHeader(Header $header): void
    {
        $this->headers[] = $header;
    }

    /**
     * Create a new RequestBuilder.
     * Similar to the above but does not use a magic method.
     *
     * @param string $method - HTTP method to use (GET, POST, PATCH, etc).
     *
     * @throws CoreException
     */
    public function request(string $method): RequestBuilder
    {
        $method = strtolower($method);
        $builder = new RequestBuilder([
            'domain' => $this->domain,
            'basePath' => $this->basePath,
            'method' => $method,
            'guzzleOptions' => $this->guzzleOptions,
            'returnType' => $this->returnType,
        ]);
        $builder->withHeaders($this->headers);

        if (in_array($method, [ self::METHOD_PATCH, self::METHOD_POST, self::METHOD_PUT ])) {
            $builder->withHeader(new ContentType('application/json'));
        }

        return $builder;
    }
}
