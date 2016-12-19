<?php

use Carbon\Carbon;
use Okashoi\Laravel5ConohaObjectHandler\ObjectHandler;

class ObjectHandlerTest extends Orchestra\Testbench\TestCase
{

    /**
     * Get package providers.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return array
     */
    protected function getPackageProviders($app)
    {
        return [
            'Okashoi\Laravel5ConohaObjectHandler\ConohaObjectServiceProvider',
        ];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        $app['config']->set('conoha', [
            'tenant_id'     => 'tenant_id',
            'username'      => 'user_name',
            'password'      => 'password',
            'base_uri'      => 'base_uri',
            'auth_endpoint' => 'auth_endpoint',
        ]);

        $app['config']->set('cache.prefix', 'testing_laravel5_conoha_object_handler');
    }

    /**
     * Setup the test environment.
     *
     * @return void
     */
    public function setUp()
    {
        parent::setUp();

        // Some codes will be here...
    }

    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     */
    public function tearDown()
    {
        Cache::forget('cache_key');
        Mockery::close();
    }

    /**
     * Test caching auth tokens.
     */
    public function test_cachingTokens()
    {
        $dummyToken = 'dummy_token';

        $tokenResponse = Mockery::mock('tokenResponse');
        $tokenResponse->shouldReceive('getBody')
            ->once()
            ->andReturn(json_encode([
                'access' => [
                    'token' => [
                        'id'      => $dummyToken,
                        'expires' => (string)Carbon::now()->addDays(1),
                    ]
                ],
            ]));

        $client = Mockery::mock('client');
        $client->shouldReceive('post')
            ->with(
                config('conoha.auth_endpoint'),
                [
                    'json' => [
                        'auth' => [
                            'tenantId' => config('conoha.tenant_id'),
                            'passwordCredentials' => [
                                'username' => config('conoha.username'),
                                'password' => config('conoha.password'),
                            ],
                        ],
                    ],
                ]
            )
            ->once()
            ->andReturn($tokenResponse);

        new ObjectHandler('cache_key', $client);

        $actual = Cache::get('cache_key')->id;
        $expected = 'dummy_token';

        $this->assertEquals($expected, $actual);
    }

    /**
     * Test the getList method.
     */
    public function test_getList()
    {
        $dummyToken = 'dummy_token';
        $containerName = 'dummy_container';

        $tokenResponse = Mockery::mock('tokenResponse');
        $tokenResponse->shouldReceive('getBody')
            ->once()
            ->andReturn(json_encode([
                'access' => [
                    'token' => [
                        'id'      => $dummyToken,
                        'expires' => (string)Carbon::now()->addDays(1),
                    ]
                ],
            ]));

        $client = Mockery::mock('client');
        $client->shouldReceive('post')
            ->with(
                config('conoha.auth_endpoint'),
                [
                    'json' => [
                        'auth' => [
                            'tenantId' => config('conoha.tenant_id'),
                            'passwordCredentials' => [
                                'username' => config('conoha.username'),
                                'password' => config('conoha.password'),
                            ],
                        ],
                    ],
                ]
            )
            ->once()
            ->andReturn($tokenResponse);

        $listResponse = Mockery::mock('listResponse');
        $listResponse->shouldReceive('getBody')
            ->once()
            ->andReturn(json_encode('It\'s a dummy response!'));

        $client->shouldReceive('get')
            ->with(
                'nc_' . config('conoha.tenant_id') . '/' . $containerName,
                [
                    'headers' => [
                        'Accept' => 'application/json',
                        'X-Auth-Token' => $dummyToken,
                    ],
                ]
            )
            ->once()
            ->andReturn($listResponse);

        $handler = new ObjectHandler(null, $client);
        $actual = $handler->getList($containerName);

        $expected = 'It\'s a dummy response!';

        $this->assertEquals($expected, $actual);
    }
}
