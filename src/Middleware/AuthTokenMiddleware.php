<?php
namespace DevinciIT\Blprnt\Middleware;

class AuthTokenMiddleware
{
    public function handle()
    {
        $expectedToken = $_ENV['API_TOKEN'] ?? null;

        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? null;

        if ($token !== 'Bearer ' . $expectedToken) {
            http_response_code(401);
            exit(json_encode(['error' => 'Unauthorized']));
        }
    }
}