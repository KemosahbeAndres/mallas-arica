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

    /**
     * GET al dominio público (sin subdominio admin). Usado, entre otras
     * cosas, por el flujo de login con Google en local (ver routes/web.php:
     * "localhost" sin subdominio es el único host que Google acepta ahí).
     */
    protected function getPublico(string $uri): TestResponse
    {
        return $this->get('http://'.config('app.domain').$uri);
    }
}
