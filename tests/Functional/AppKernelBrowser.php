<?php

namespace App\Tests\Functional;

use Zenstruck\Browser\HttpOptions;
use Zenstruck\Browser\KernelBrowser;

class AppKernelBrowser extends KernelBrowser
{
    public function apiPatch(string $url, array $content = []): self
    {
        return $this->patch(
            $url,
            HttpOptions::json($content)
                ->withHeader('Content-Type', 'application/merge-patch+json')
        );
    }
}