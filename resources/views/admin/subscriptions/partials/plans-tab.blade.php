<!-- Plans Management Tab -->
<div class="bg-white rounded-lg shadow">
    <!-- Filters -->
    <div class="p-6 border-b border-gray-200">
        <form method="GET" action="{{ route('admin.subscriptions.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <input type="hidden" name="tab" value="plans">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Search Plans</label>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Plan name or description..."
                       class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Status</label>
                <select name="status" class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">All Plans</option>
                    <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                    <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>

            <div class="flex items-end space-x-2">
                <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-medium transition">
                     Filter
                </button>
                <a href="{{ route('admin.subscriptions.index', ['tab' => 'plans']) }}"
                   class="bg-gray-300 hover:bg-gray-400 text-gray-700 px-4 py-2 rounded font-medium transition">
                    Clear
                </a>
            </div>
        </form>
    </div>

    <!-- Plans List -->
    <div class="overflow-x-auto">
        @if(isset($plans) && $plans->count() > 0)
            <table class="w-full table-auto">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Plan</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pricing</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Features</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Subscribers</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($plans as $plan)
                        @php
                            // Compute once per row, reused in Subscribers + Actions
                            if ($plan->isFree()) {
                                $activeSubCount = \App\Models\User::where('role', 'learner')
                                    ->whereDoesntHave('subscriptions', function ($q) {
                                        $q->where('status', 'active')
                                          ->whereHas('plan', function ($p) {
                                              $p->where('price', '>', 0);
                                          });
                                    })
                                    ->count();
                                $totalSubCount = $activeSubCount;
                            } else {
                                $activeSubCount = $plan->subscriptions->where('status', 'active')->count();
                                $totalSubCount  = $plan->subscriptions->count();
                            }

                            // Features: first 3 visible + overflow count
                            $featureLabelMap = [
                                'unlimited_quizzes'          => 'Unlimited Quizzes',
                                'certificates'               => 'Certificates',
                                'priority_support'           => 'Priority Support',
                                'downloadable_content'       => 'Downloadable Resources',
                                'downloadable_resources'     => 'Downloadable Resources',
                                'consultations'              => 'Live Consultations',
                                'offline_access'             => 'Offline Access',
                                'progress_analytics'         => 'Progress Analytics',
                                'all_modules'                => 'All Modules',
                                'admin_dashboard'            => 'Admin Dashboard',
                                'progress_tracking'          => 'Progress Tracking',
                                'bulk_enrollment'            => 'Bulk Enrollment',
                                'custom_branding'            => 'Custom Branding',
                                'api_access'                 => 'API Access',
                                'dedicated_account_manager'  => 'Dedicated Account Manager',
                                'custom_reporting'           => 'Custom Reporting',
                            ];
                            $allFeatures = collect();
                            if ($plan->isFree()) {
                                $allFeatures->push('3 quiz attempts/day');
                                $allFeatures->push('Limited modules');
                            } else {
                                if ($plan->trial_days > 0) {
                                    $allFeatures->push($plan->trial_days . '-day Trial');
                                }
                                if (is_array($plan->features)) {
                                    foreach ($plan->features as $f) {
                                        if (in_array($f, ['test_mode', 'duration_minutes'])) continue;
                                        $allFeatures->push($featureLabelMap[$f] ?? ucwords(str_replace('_', ' ', $f)));
                                    }
                                }
                            }
                            $visibleFeatures  = $allFeatures->take(3);
                            $hiddenFeatures   = $allFeatures->slice(3);
                            $hiddenCount      = $hiddenFeatures->count();
                        @endphp
                        <tr class="hover:bg-gray-50 {{ $plan->hasFeature('test_mode') ? 'bg-yellow-50' : '' }}">

                            <!-- Plan Name -->
                            <td class="px-6 py-4">
                                <div class="flex items-start gap-2">
                                    @if($plan->hasFeature('test_mode'))
                                        <span class="mt-0.5 shrink-0 text-xs bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded-full">TEST</span>
                                    @endif
                                    <div>
                                        <div class="text-sm font-semibold text-gray-900">{{ $plan->name }}</div>
                                        <div class="text-xs text-gray-400">{{ $plan->slug }}</div>
                                        @if($plan->description)
                                            <div class="text-xs text-gray-500 mt-0.5 max-w-xs truncate" title="{{ $plan->description }}">
                                                {{ $plan->description }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Pricing -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($plan->isFree())
                                    <span class="text-sm font-semibold text-green-600">Free</span>
                                @else
                                    <div class="text-sm font-medium text-gray-900">₱{{ number_format($plan->price, 0) }}</div>
                                @endif
                            </td>

                            <!-- Features (compact: max 3 + overflow badge) -->
                            <td class="px-6 py-4">
                                @if($allFeatures->isEmpty())
                                    <span class="text-xs text-gray-400 italic">None</span>
                                @else
                                    <div class="flex flex-wrap gap-1">
                                        @foreach($visibleFeatures as $feat)
                                            <span class="text-xs bg-gray-100 text-gray-700 px-2 py-0.5 rounded-full">{{ $feat }}</span>
                                        @endforeach
                                        @if($hiddenCount > 0)
                                            <span class="text-xs bg-blue-50 text-blue-600 px-2 py-0.5 rounded-full cursor-default font-medium"
                                                  title="{{ $hiddenFeatures->join(', ') }}">
                                                +{{ $hiddenCount }} more
                                            </span>
                                        @endif
                                    </div>
                                @endif
                            </td>

                            <!-- Status -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center gap-1 px-2 py-1 text-xs font-semibold rounded-full
                                    {{ $plan->is_active ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $plan->is_active ? 'bg-green-500' : 'bg-red-400' }}"></span>
                                    {{ $plan->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>

                            <!-- Subscribers -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">{{ $totalSubCount }}</div>
                                @if(!$plan->isFree() && $activeSubCount > 0)
                                    <div class="text-xs text-green-600">{{ $activeSubCount }} active</div>
                                @elseif($plan->isFree())
                                    <div class="text-xs text-gray-400">on free plan</div>
                                @endif
                            </td>

                            <!-- Actions -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-2">
                                    {{-- Toggle active/inactive --}}
                                    @if($plan->is_active && $activeSubCount > 0)
                                        <span class="inline-flex items-center gap-1 text-xs text-gray-400 bg-gray-100 px-2 py-1 rounded cursor-not-allowed"
                                              title="Cannot deactivate: {{ $activeSubCount }} active subscriber(s)">
                                            🔒 Locked
                                        </span>
                                    @else
                                        <form method="POST" action="{{ route('admin.subscriptions.quick-action') }}">
                                            @csrf
                                            <input type="hidden" name="action" value="toggle_plan">
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit"
                                                    class="text-xs px-2 py-1 rounded border font-medium transition
                                                        {{ $plan->is_active
                                                            ? 'border-red-200 text-red-600 hover:bg-red-50'
                                                            : 'border-green-200 text-green-600 hover:bg-green-50' }}">
                                                {{ $plan->is_active ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>
                                    @endif

                                    {{-- Edit --}}
                                    <button type="button"
                                        onclick='openEditPlanModal(@json($plan->id), @json($plan->name), @json($plan->description), @json($plan->price), @json($plan->trial_days ?? 0), @json($plan->max_modules ?? 0), @json($plan->sort_order), @json($plan->is_active), @json($plan->features ?? []))'
                                        class="text-xs px-2 py-1 rounded border border-indigo-200 text-indigo-600 hover:bg-indigo-50 font-medium transition">
                                        Edit
                                    </button>

                                    {{-- Delete (only when no active subscribers) --}}
                                    @if($activeSubCount === 0)
                                        <form method="POST" action="{{ route('admin.subscriptions.quick-action') }}">
                                            @csrf
                                            <input type="hidden" name="action" value="delete_plan">
                                            <input type="hidden" name="plan_id" value="{{ $plan->id }}">
                                            <button type="submit"
                                                    onclick="return confirm('Delete \'{{ addslashes($plan->name) }}\'? This cannot be undone.')"
                                                    class="text-xs px-2 py-1 rounded border border-red-200 text-red-600 hover:bg-red-50 font-medium transition">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @else
            <div class="text-center py-12">
                <svg class="w-12 h-12 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 9a2 2 0 012-2m0 0V5a2 2 0 012 2v2M7 7h10"></path>
                </svg>
                <h3 class="text-lg font-medium text-gray-900 mb-2">No subscription plans found</h3>
                <p class="text-gray-500 mb-4">Get started by creating your first subscription plan.</p>
                <button onclick="openModal('createPlanModal')"
                   class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded font-medium transition">
                    Create Plan
                </button>
            </div>
        @endif
    </div>

    <!-- Pagination -->
    @if(isset($plans) && $plans->hasPages())
        <div class="px-6 py-4 border-t border-gray-200">
            {{ $plans->appends(request()->query())->links() }}
        </div>
    @endif
</div>

<!-- Edit Plan Modal -->
<div id="editPlanModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
    <div class="relative top-20 mx-auto p-5 border w-full max-w-3xl shadow-lg rounded-md bg-white">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-xl font-bold text-gray-900">Edit Plan</h3>
            <button onclick="closeModal('editPlanModal')" class="text-gray-400 hover:text-gray-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        </div>

        <form method="POST" id="editPlanForm" class="space-y-4">
            @csrf
            @method('PUT')

            <input type="hidden" id="editPlanId" name="plan_id">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name *</label>
                <input type="text" id="editName" name="name" required
                       class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="e.g., Premium Plan">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                <textarea id="editDescription" name="description" rows="3"
                          class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                          placeholder="Brief description of the plan"></textarea>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-2">Price (₱) *</label>
                <input type="number" id="editPrice" name="price" step="0.01" min="0" required
                       class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                       placeholder="299.00">
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Trial Days</label>
                    <input type="number" id="editTrialDays" name="trial_days" min="0" max="365"
                           class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="0">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                    <input type="number" id="editSortOrder" name="sort_order" min="0"
                           class="w-full p-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="10">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-3">Features</label>
                <div class="grid grid-cols-1 gap-2" id="editFeaturesContainer">
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="unlimited_quizzes" id="ef_unlimited_quizzes" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Unlimited quiz attempts</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="certificates" id="ef_certificates" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Completion certificates</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="priority_support" id="ef_priority_support" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Priority support</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="downloadable_content" id="ef_downloadable_content" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Downloadable resources</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="consultations" id="ef_consultations" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Live consultations</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="offline_access" id="ef_offline_access" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Offline access</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="progress_analytics" id="ef_progress_analytics" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Progress analytics</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="all_modules" id="ef_all_modules" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Access to all modules</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="admin_dashboard" id="ef_admin_dashboard" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Admin dashboard</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="progress_tracking" id="ef_progress_tracking" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Progress tracking</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="bulk_enrollment" id="ef_bulk_enrollment" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Bulk enrollment</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="custom_branding" id="ef_custom_branding" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Custom branding</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="api_access" id="ef_api_access" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">API access</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="dedicated_account_manager" id="ef_dedicated_account_manager" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Dedicated account manager</span>
                    </label>
                    <label class="flex items-center gap-2.5 cursor-pointer">
                        <input type="checkbox" name="feature_keys[]" value="custom_reporting" id="ef_custom_reporting" class="w-4 h-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-gray-700">Custom reporting</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center">
                <input type="checkbox" id="editIsActive" name="is_active" value="1"
                       class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                <label for="editIsActive" class="ml-2 text-sm text-gray-700">Active (available for subscription)</label>
            </div>

            <div class="flex justify-end space-x-3 pt-4 border-t border-gray-200">
                <button type="button" onclick="closeModal('editPlanModal')"
                        class="px-4 py-2 bg-gray-300 hover:bg-gray-400 text-gray-700 rounded font-medium transition">
                    Cancel
                </button>
                <button type="submit"
                        class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded font-medium transition shadow">
                    Save Plan
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditPlanModal(id, name, description, price, trialDays, maxModules, sortOrder, isActive, features) {
    document.getElementById('editPlanId').value = id;
    document.getElementById('editName').value = name;
    document.getElementById('editDescription').value = description || '';
    document.getElementById('editPrice').value = price;
    document.getElementById('editTrialDays').value = trialDays;
    document.getElementById('editSortOrder').value = sortOrder;
    document.getElementById('editIsActive').checked = isActive;

    // Uncheck all feature checkboxes first
    const allCheckboxes = ['unlimited_quizzes','certificates','priority_support','downloadable_content','consultations','offline_access','progress_analytics','all_modules','admin_dashboard','progress_tracking','bulk_enrollment','custom_branding','api_access','dedicated_account_manager','custom_reporting'];
    allCheckboxes.forEach(key => {
        const cb = document.getElementById('ef_' + key);
        if (cb) cb.checked = false;
    });

    // Check the ones this plan has
    const featureList = Array.isArray(features) ? features : (typeof features === 'object' ? Object.keys(features) : []);
    featureList.forEach(key => {
        const cb = document.getElementById('ef_' + key);
        if (cb) cb.checked = true;
    });

    document.getElementById('editPlanForm').action = `/admin/subscriptions/plan/${id}`;
    document.getElementById('editPlanModal').classList.remove('hidden');
}
</script>
