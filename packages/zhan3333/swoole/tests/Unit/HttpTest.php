<?php
/**
 * User: zhan
 * Date: 2019/9/25
 * Email: <grianchan@gmail.com>
 */

namespace Tests\Unit;


use App\Models\User;
use Artisan;
use Exception;
use GuzzleHttp\Client;
use Illuminate\Foundation\Testing\TestResponse;
use Illuminate\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Route;
use Tests\TestCase;

class HttpTest extends TestCase
{
    private $port;
    private $host;
    private $email = 'test@test.com';
    private $password = '000000';
    /** @var Client */
    private $client;

    public function setUp(): void
    {
        parent::setUp();

        $driver = config('swoole.use_server');
        $this->host = config("swoole.services.$driver.host");
        $this->port = config("swoole.services.$driver.port");
        Route::any('_test', static function () {
            return 'OK';
        });
        Artisan::call('http-swoole', [
            'action' => 'restart'
        ]);
        if (!User::where('email', $this->email)->exists()) {
            User::create([
                'name' => 'test',
                'password' => bcrypt($this->password),
                'email' => $this->email,
            ]);
        }


        $this->client = new Client([
            'base_uri' => 'http://127.0.0.1:' . $this->port,
            'http_errors' => true,
            'debug' => false,
            'headers' => [
                'X-Requested-With' => 'XMLHttpRequest',
                'Accept' => 'application/json',
            ],
        ]);

        try {
            $response = $this->testLogin();
        } catch (Exception $e) {
            $this->fail($e);
        }
        $this->defaultHeaders['X-Requested-With'] = 'XMLHttpRequest';
        $this->defaultHeaders['Accept'] = 'application/json';
        $this->defaultHeaders['Authorization'] = $response->json('token_type') . ' ' . $response->json('access_token');

    }

    /**
     * 转换
     * @param ResponseInterface $response
     * @return TestResponse
     */
    public function conversionTestResponse(ResponseInterface $response): TestResponse
    {
        $response = Response::create($response->getBody(), $response->getStatusCode(), $response->getHeaders());
        return TestResponse::fromBaseResponse($response);
    }

    /**
     * @test
     */
    public function baseTest()
    {
        $response = $this->conversionTestResponse($this->client->get('/'));
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function contentTest()
    {
        $response = $this->conversionTestResponse($this->client->get('/test'));
        $response->assertStatus(200);
        $this->assertEquals('test', $response->getContent());
    }

    /**
     * @test
     * @throws Exception
     */
    public function testLogin()
    {
        $response = $this->client->post('/api/auth/login', [
            'form_params' => [
                'email' => $this->email,
                'password' => $this->password,
            ],
            'headers' => $this->defaultHeaders,
        ]);
        $response = $this->conversionTestResponse($response);
        $response->assertStatus(200)
            ->assertJsonStructure([
                'access_token',
                'token_type',
                'expires_in',
            ]);
        return $response;
    }

    /**
     * @test
     */
    public function testMe()
    {
        $response = $this->client->post('/api/auth/me', [
            'headers' => $this->defaultHeaders
        ]);
        $this->conversionTestResponse($response)
            ->assertStatus(200)
            ->assertJson([
                'email' => $this->email,
            ]);
    }

    /**
     * @test
     */
    public function loginOtherUser()
    {
        // get old user
        $this->conversionTestResponse($this->client->post('/api/auth/me', ['headers' => $this->defaultHeaders]))
            ->assertStatus(200)
            ->assertJson([
                'email' => $this->email,
            ]);

        // get new user
        $email = 'test2@test.com';
        if (!User::where('email', 'test2@test.com')->exists()) {
            User::create([
                'name' => 'test2',
                'email' => $email,
                'password' => bcrypt('000000'),
            ]);
        }
        $response = $this->conversionTestResponse($this->client->post('/api/auth/login', [
            'form_params' => [
                'email' => $email, 'password' => '000000'
            ],
        ]))
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token'
            ]);
        $this->conversionTestResponse(
            $this->client->post('/api/auth/me', [
                'headers' => ['Authorization' => $response->json('token_type') . ' ' . $response->json('access_token')]
            ])
        )
            ->assertStatus(200)
            ->assertJson([
                'email' => $email,
            ]);
        // get old user
        $this->conversionTestResponse($this->client->post('/api/auth/me', [
            'headers' => $this->defaultHeaders
        ]))
            ->assertStatus(200)
            ->assertJson([
                'email' => $this->email,
            ]);
    }
}
