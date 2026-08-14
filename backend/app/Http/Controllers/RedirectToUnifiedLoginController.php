<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;

class RedirectToUnifiedLoginController
{
    public function __invoke(): RedirectResponse
    {
        $frontendUrl = rtrim((string) config('app.frontend_url'), '/');

        return redirect()->away($frontendUrl.'/login?'.http_build_query(['redirect' => '/admin']));
    }
}
