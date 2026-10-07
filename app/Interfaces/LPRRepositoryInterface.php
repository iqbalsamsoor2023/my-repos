<?php

namespace App\Interfaces;

use Illuminate\Http\Request;

interface LPRRepositoryInterface
{
    public function create(Request $request);
}
