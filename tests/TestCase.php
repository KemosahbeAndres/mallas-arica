<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Testing\TestResponse;

abstract class TestCase extends BaseTestCase
{
    /**
     * GET a una ruta del panel admin, que vive en su propio subdominio
     * (admin.{APP_DOMAIN}, ver routes/web.php) desde el rediseño de dominios.
     */
    protected function getAdmin(string $uri): TestResponse
    {
        return $this->get('http://admin.'.config('app.domain').$uri);
    }
}
