<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seminars\UpdateSeminarDeliveryRequest;
use App\Models\Seminar;
use App\Services\Seminars\SeminarDeliveryService;
use Illuminate\Http\RedirectResponse;

class SeminarDeliveryController extends Controller
{
    public function __construct(private readonly SeminarDeliveryService $delivery) {}

    public function update(UpdateSeminarDeliveryRequest $request, Seminar $seminar): RedirectResponse
    {
        abort_unless($request->user()->role === 'admin' || $request->user()->hasRole('admin'), 403);
        $this->delivery->update($seminar, $request->validated(), $request->user(), true);

        return back()->with('success', 'Event access details updated.');
    }
}
