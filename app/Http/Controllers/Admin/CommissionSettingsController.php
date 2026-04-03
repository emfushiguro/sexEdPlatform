<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCommissionPolicyRequest;
use App\Http\Requests\Admin\UpdateCommissionPolicyRequest;
use App\Models\CommissionPolicy;
use App\Models\User;
use App\Services\AdminActivityLogService;

class CommissionSettingsController extends Controller
{
    public function __construct(
        private readonly AdminActivityLogService $adminActivityLogService,
    ) {
    }

    public function index()
    {
        $policies = CommissionPolicy::query()
            ->with(['updatedBy', 'instructor'])
            ->latest('effective_from')
            ->latest('id')
            ->get();

        $instructors = User::query()
            ->where('role', 'instructor')
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.monetization.commission-settings', compact('policies', 'instructors'));
    }

    public function store(StoreCommissionPolicyRequest $request)
    {
        $data = $request->validated();
        if (($data['scope_type'] ?? null) === CommissionPolicy::SCOPE_GLOBAL) {
            $data['scope_id'] = null;
        }

        $data['updated_by'] = auth()->id();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $policy = CommissionPolicy::query()->create($data);

        $this->adminActivityLogService->logCommissionPolicyMutation(
            actionType: 'created',
            before: null,
            after: $policy,
            request: request(),
        );

        return redirect()->route('admin.monetization.commission-settings.index')
            ->with('success', 'Commission policy saved successfully.');
    }

    public function update(UpdateCommissionPolicyRequest $request, CommissionPolicy $commissionPolicy)
    {
        $data = $request->validated();
        if (($data['scope_type'] ?? null) === CommissionPolicy::SCOPE_GLOBAL) {
            $data['scope_id'] = null;
        }

        $data['updated_by'] = auth()->id();
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $before = $commissionPolicy->replicate();
        $before->setRawAttributes($commissionPolicy->getOriginal());

        $commissionPolicy->update($data);

        $this->adminActivityLogService->logCommissionPolicyMutation(
            actionType: 'updated',
            before: $before,
            after: $commissionPolicy->fresh(),
            request: request(),
        );

        return redirect()->route('admin.monetization.commission-settings.index')
            ->with('success', 'Commission policy updated successfully.');
    }
}
