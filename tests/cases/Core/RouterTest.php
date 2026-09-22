<?php
/**
 * اختبارات الموجه (app/Core/Router.php)
 */

use App\Core\Router;

class RouterTest extends TestCase
{
    private function makeRouter(): Router
    {
        $router = new Router();
        $router->get('hello', 'Tests\Fixtures\HelloController@index');
        $router->get('hello/:name', 'Tests\Fixtures\HelloController@greet');
        $router->get('users/:id', 'Tests\Fixtures\HelloController@show');
        return $router;
    }

    public function testDirectMatch(): void
    {
        $router = $this->makeRouter();
        $this->assertSame('index-ok', $router->direct('hello', 'GET'));
    }

    public function testParamMatchString(): void
    {
        $router = $this->makeRouter();
        $this->assertSame('Hello Ali', $router->direct('hello/Ali', 'GET'));
    }

    public function testParamMatchInteger(): void
    {
        $router = $this->makeRouter();
        $this->assertSame('id=42', $router->direct('users/42', 'GET'));
    }

    public function testUnknownRouteRenders404(): void
    {
        $router = $this->makeRouter();
        ob_start();
        try {
            $router->direct('no/such/route', 'GET');
            $output = ob_get_clean();
        } catch (Throwable $e) {
            ob_end_clean();
            throw $e;
        }
        $this->assertSame(404, http_response_code());
        $this->assertStringContainsString('404', $output);
    }

    public function testMissingControllerThrowsException(): void
    {
        $router = new Router();
        $router->get('broken', 'No\Such\Namespace\MissingController@index');
        $this->assertThrows(fn () => $router->direct('broken', 'GET'), Exception::class);
    }

    public function testMissingActionThrowsException(): void
    {
        $router = new Router();
        $router->get('hello', 'Tests\Fixtures\HelloController@nonexistentMethod');
        $this->assertThrows(fn () => $router->direct('hello', 'GET'), Exception::class);
    }

    public function testStaticLoadReadsRouteFile(): void
    {
        $router = Router::load(APP_ROOT . '/routes/web.php');
        $this->assertInstanceOf(Router::class, $router);
    }
}
