<?php
declare(strict_types=1);
namespace Bitmotion\Auth0\Utility;

use Bitmotion\Auth0\Utility\Database\UpdateUtility;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Core\SingletonInterface;

class ParseFuncUtility implements SingletonInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    const PARSING_FUNCTION = 'parseFunc';

    public function updateWithParseFunc(string $typo3FieldName, array $auth0FieldName, array $user)
    {
        $fieldName = $auth0FieldName[UpdateUtility::TYPO_SCRIPT_NODE_VALUE];
        $value = '';

        if (isset($user[$fieldName]) && isset($auth0FieldName[self::PARSING_FUNCTION])) {
            $value = $this->handleParseFunc($auth0FieldName[self::PARSING_FUNCTION], $user[$fieldName]);
            $this->logger->debug(sprintf('Set dynamic value "%s" for "%s"', $value, $typo3FieldName));
        } elseif (strpos($auth0FieldName[UpdateUtility::TYPO_SCRIPT_NODE_VALUE], 'user_metadata') !== false) {
            $value = $this->getValueFromMetadata($auth0FieldName, $user);
            $this->logger->debug(sprintf('Set dynamic value "%s" for "%s" from metadata.', $value, $typo3FieldName));
        } elseif (isset($user[$typo3FieldName]) && isset($auth0FieldName[self::PARSING_FUNCTION]) && $auth0FieldName[self::PARSING_FUNCTION] === 'const') {
            $value = $auth0FieldName[UpdateUtility::TYPO_SCRIPT_NODE_VALUE];
            $this->logger->debug(sprintf('Set static value "%s" for "%s"', $value, $typo3FieldName));
        }

        if ($value !== '') {
            return $value;
        }

        return false;
    }

    public function updateWithoutParseFunc(string $auth0FieldName, array $user)
    {
        if (isset($user[$auth0FieldName])) {
            return $user[$auth0FieldName];
        } elseif (strpos($auth0FieldName, 'user_metadata') !== false) {
            return $this->getAuth0ValueRecursive($user, explode('.', $auth0FieldName));
        }

        return false;
    }

    protected function getAuth0ValueRecursive(array $user, array $properties): string
    {
        $value = '';
        $property = array_shift($properties);

        if (isset($user[$property])) {
            $value = $user[$property];

            if (is_array($properties) && ($value instanceof \stdClass || (is_array($value) && !empty($value)))) {
                return $this->getAuth0ValueRecursive($value, $properties);
            }
        }

        return (string)$value;
    }

    protected function handleParseFunc(string $function, $value)
    {
        $parseFunctions = explode('|', $function);

        foreach ($parseFunctions as $function) {
            $value = $this->transformValue($function, $value);
        }

        return $value;
    }

    protected function transformValue(string $function, $value)
    {
        switch ($function) {
            case 'strtotime':
                $value = strtotime($value);
                break;

            case 'bool':
                $value = (int)(bool)$value;
                break;

            case 'negate':
                $value = (bool)$value ? 0 : 1;
                break;

            default:
                $this->logger->notice(sprintf('"%s" is not a valid parseFunc', $function));
        }

        return $value;
    }

    protected function getValueFromMetadata(array $auth0FieldName, array $user)
    {
        return $this->handleParseFunc(
            $auth0FieldName[self::PARSING_FUNCTION],
            $this->getAuth0ValueRecursive(
                $user,
                explode('.', $auth0FieldName[UpdateUtility::TYPO_SCRIPT_NODE_VALUE])
            )
        );
    }
}
