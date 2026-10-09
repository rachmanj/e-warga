<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * @param  array<string, mixed>  $headers
     */
    public function get($uri, array $headers = []): TestResponse
    {
        $path = parse_url($uri, PHP_URL_PATH);
        $path = is_string($path) ? $path : $uri;

        $publicBasenames = [
            '/manifest.webmanifest' => 'application/manifest+json',
            '/sw.js' => 'application/javascript',
            '/offline.html' => 'text/html; charset=UTF-8',
        ];

        if (isset($publicBasenames[$path])) {
            $file = public_path(ltrim($path, '/'));

            if (is_file($file)) {
                $request = Request::create($path, 'GET');

                return TestResponse::fromBaseResponse(
                    response(
                        file_get_contents($file),
                        200,
                        ['Content-Type' => $publicBasenames[$path]],
                    ),
                    $request,
                );
            }
        }

        return parent::get($uri, $headers);
    }
}
