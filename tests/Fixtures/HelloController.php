<?php
/**
 * كائن تحكم تجريبي لاختبارات Router
 */

namespace Tests\Fixtures;

class HelloController
{
    public function index(): string
    {
        return 'index-ok';
    }

    public function greet(string $name): string
    {
        return 'Hello ' . $name;
    }

    public function show(int $id): string
    {
        return 'id=' . $id;
    }
}
