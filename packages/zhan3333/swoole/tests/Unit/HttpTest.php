<?php
/**
 * User: zhan
 * Date: 2019/9/25
 * Email: <grianchan@gmail.com>
 */

namespace Tests\Unit;


use Tests\TestCase;

class HttpTest extends TestCase
{
    private $url;

    public function setUp(): void
    {
        parent::setUp();
        $driver = config('swoole.use_server');
        $this->url = 'http://' . config("swoole.services.$driver.host") . ':' . config("swoole.services.$driver.port");
        \Artisan::call('http-swoole:restart');
    }

    /**
     * @test
     */
    public function baseTest()
    {
        $response = $this->get($this->url . '/test');
        $response->assertStatus(200);
        $this->assertEquals('test', $response->getContent());
    }
}
