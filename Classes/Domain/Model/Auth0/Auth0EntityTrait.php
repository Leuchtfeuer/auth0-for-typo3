<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Domain\Model\Auth0;

use TYPO3\CMS\Core\Utility\GeneralUtility;

trait Auth0EntityTrait
{
    protected $furtherProperties = [];

    public function __construct($values)
    {
        if (!$this instanceof Auth0EntityInterface) {
            throw new \Exception(__CLASS__ . ' has to implement ' . Auth0EntityInterface::class, 1549379399);
        }

        foreach ($values as $key => $value) {
            $property = GeneralUtility::underscoredToLowerCamelCase($key);
            if (property_exists(__CLASS__, $property)) {
                $this->$property = $value;
            } else {
                $this->furtherProperties[$property] = $value;
            }
        }
    }

    public function getFurtherProperties(): array
    {
        return $this->furtherProperties;
    }

    public function setFurtherProperties(array $furtherProperties)
    {
        $this->furtherProperties = $furtherProperties;
    }

    /**
     * @throws \Exception
     */
    public function __call($method, $properties)
    {
        if (strpos($method, 'get', 0) !== false) {
            $property = lcfirst(ltrim($method, 'get'));
            if (isset($this->furtherProperties[$property])) {
                return $this->furtherProperties[$property];
            }
        }

        throw new \Exception('Invalid Property', 1549379223);
    }
}
