<?php

namespace App\Http\Controllers\Connector;

use App\Http\Controllers\Controller;
use App\Http\Requests\Seminars\UpdateSeminarDeliveryRequest;
use App\Models\Connector;
use App\Models\Seminar;
use App\Services\Seminars\SeminarAccessService;
use App\Services\Seminars\SeminarDeliveryService;
use Illuminate\Http\RedirectResponse;

class SeminarDeliveryController extends Controller
{
    public function __construct(
        private readonly SeminarAccessService $access,
        private readonly SeminarDeliveryService $delivery,
    ) {}

    public function update(UpdateSeminarDeliveryRequest $request, Connector $connector, Seminar $seminar): RedirectResponse
    {
        $this->access->abortUnlessConnectorOwnsSeminar($connector, $seminar);
        $this->access->abortUnlessCanManageConnectorSeminars($request->user(), $connector);
        $this->delivery->update($seminar, $request->validated(), $request->user());

        return back()->with('success', 'Event access details updated.');
    }
}
