<?php

declare(strict_types=1);

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;

class GuidelinesController extends Controller
{
    public function __invoke()
    {
        return view('instructor.guidelines');
    }
}
