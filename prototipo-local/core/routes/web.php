<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', fn () => Inertia::render('Home', [
    'tenant' => request()->attributes->get('tenant'),
    'activeHost' => request()->getHost(),
]));
