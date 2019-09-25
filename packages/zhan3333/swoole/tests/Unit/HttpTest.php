<?php
/**
 * User: zhan
 * Date: 2019/9/25
 * Email: <grianchan@gmail.com>
 */

namespace Tests\Unit;


use App\Models\User;
use GuzzleHttp\Client;
use Tests\TestCase;

class HttpTest extends TestCase
{
    private $url;
    private $email = 'test@test.com';
    private $password = '000000';

    public function setUp(): void
    {
        parent::setUp();
        $this->defaultHeaders['X-Requested-With'] = 'XMLHttpRequest';
        $this->defaultHeaders['Accept'] = 'application/json';
        $driver = config('swoole.use_server');
        $this->url = 'http://' . config("swoole.services.$driver.host") . ':' . config("swoole.services.$driver.port");
        \Route::any('_test', function () {
            return \Route::getCurrentRoute()->uri;
            return 'OK';
        });
        \Artisan::call('http-swoole', [
            'action' => 'restart'
        ]);
        $this->client = new Client([
            'base_uri' => $this->url,
            'http_errors' => false,
        ]);
        if (!User::where('email', $this->email)->exists()) {
            User::create([
                'name' => 'test',
                'password' => bcrypt($this->password),
                'email' => $this->email,
            ]);
        }
        $response = $this->testLogin();
        $this->defaultHeaders['Authorization'] = $response->json('token_type') . ' ' . $response->json('access_token');
    }

    /**
     * @test
     */
    public function baseTest()
    {
        $response = $this->get($this->url . '/');
        $response->assertStatus(200);
    }

    /**
     * @test
     */
    public function contentTest()
    {
        $response = $this->get($this->url . '/test');
        $response->assertStatus(200);
        $this->assertEquals('test', $response->getContent());
    }

    /**
     * @test
     */
    public function testAppendRoute()
    {
        $response = $this->get($this->url . '/_test');
        $response->assertStatus(200);
    }

    /**
     * @test
     * @throws \Exception
     */
    public function testLogin()
    {
        $response = $this->post($this->url . '/api/auth/login', [
            'email' => $this->email,
            'password' => $this->password,
        ]);
        $response->assertStatus(200);
        $response->assertJsonStructure([
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
        $response = $this->post($this->url . '/api/auth/me');
        $response->assertStatus(200);
        $response->assertJson([
            'email' => $this->email,
        ]);
    }

    /**
     * @test
     */
    public function loginOtherUser()
    {
        // get old user
        $this->post($this->url . '/api/auth/me')
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
        $response = $this
            ->post($this->url . '/api/auth/login', [
                'email' => $email,
                'password' => '000000',
            ])
            ->assertStatus(200)
            ->assertJsonStructure([
                'access_token'
            ]);
        $this->post($this->url . '/api/auth/me', [], ['Authorization' => $response->json('token_type') . ' ' . $response->json('access_token')])
            ->assertStatus(200)
            ->assertJson([
                'email' => $email,
            ]);

        dump(\Auth::id());
        // get old user
        $this->post($this->url . '/api/auth/me', [], $this->defaultHeaders)
            ->assertStatus(200)
            ->assertJson([
                'email' => $this->email,
            ]);
    }
}
