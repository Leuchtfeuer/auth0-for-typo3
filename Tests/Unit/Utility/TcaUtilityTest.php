<?php

/*
 * This file is part of the "Auth0" extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * Florian Wessels <f.wessels@Leuchtfeuer.com>, Leuchtfeuer Digital Marketing
 */

namespace Leuchtfeuer\Auth0\Tests\Unit\Utility;

use Bitmotion\Auth0\Utility\TcaUtility;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TcaUtilityTest extends UnitTestCase
{
    private const TCA = [
        'fe_users' => [
            'columns' => [
                'uid' => [
                    'config' => [
                        'type' => 'input'
                    ],
                    'label' => 'id',
                ],
                'username' => [
                    'config' => [
                        'type' => 'input'
                    ],
                    'label' => 'username',
                ],
                'crdate' => [
                    'config' => [
                        'type' => 'input'
                    ],
                    'label' => 'create date',
                ],
            ]
        ],
        'someTableName' => [
            'columns' => [
                'someColumn' => [
                    'config' => [
                        'type' => 'input'
                    ],
                    'label' => 'someLabel',
                ],
                'username' => [
                    'config' => [
                        'type' => 'input'
                    ],
                    'label' => 'username',
                ],
            ]
        ]
    ];

    private const AUTH0_CONFIGURATION = [
        'properties' =>
            [
                'fe_users' =>
                    [
                        'root' =>
                            [
                                [
                                    'auth0Property' => 'created_at',
                                    'databaseField' => 'crdate',
                                    'readOnly' => true,
                                    'processing' => 'strtotime'
                                ],
                                [
                                    'auth0Property' => 'newUsename',
                                    'databaseField' => 'username',
                                    'readOnly' => true,
                                    'processing' => 'strtotime',
                                    'foreignTable' => 'someTableName'
                                ],
                            ]
                    ]
            ]
    ];

    /** @var TcaUtility */
    private $tcaUtility;

    public function setUp(): void
    {
        parent::setUp();

        /** @var LanguageService|MockObject $languageServiceMock */
        $languageServiceMock = $this->createMock(LanguageService::class);
        $this->tcaUtility = new TcaUtility(self::TCA, self::AUTH0_CONFIGURATION, $languageServiceMock);
    }

    public function tableDataProvider(): array
    {
        return [
            ['fe_users', 1],
            ['be_users', 2],
            [null, 2],
        ];
    }

    /**
     * @dataProvider tableDataProvider
     */
    public function testGetTables(?string $excludedTable, int $expected): void
    {
        self::assertCount($expected, $this->tcaUtility->getTables($excludedTable));
    }

    /**
     * @dataProvider configurationDataProvider
     */
    public function testGetColumnsFromConfiguration(?string $foreignTable, ?string $exclude, int $expected): void
    {
        self::assertCount($expected, $this->tcaUtility->getUnusedColumnsFromTable('fe_users', $exclude, $foreignTable));
    }

    public function configurationDataProvider(): array
    {
        return [
            [null, null, 2],
            ['someForeign', null, 0],
            ['someTableName', null, 1],
            ['someTableName', 'username', 2],
        ];
    }
}
