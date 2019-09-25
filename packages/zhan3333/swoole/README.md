# Run Swoole Server

Manager Swoole Server

## Command

`php artisan swoole {action}`: start|stop|reload|status|pid
`php artisan http-swoole start`: start http server

## Setting

Copy config to config dir: `php artisan vendor:publish --provider="Zhan3333\Swoole\Providers\SwooleServiceProvider"`
 
### Nginx

```
server {
    listen 80;
    server_name swoole.base.grianchan.com;
    location / {
        proxy_http_version 1.1;
        proxy_set_header Connection "keep-alive";
        proxy_set_header X-Real-IP $remote_addr;
        if (!-e $request_filename) {
            proxy_pass http://php:8888;
        }
    }
}
```
