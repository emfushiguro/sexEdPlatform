<div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" x-data="{ selectedCode: '', chars: 0 }">
 <div @click.outside="rejectOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6">
 <h3 class="text-lg font-semibold text-gray-900">Reject Instructor Application</h3>
 <p class="mt-2 text-sm text-gray-600">Select a reason category and provide optional context for the learner.</p>

 <form method="POST" action="{{ route('admin.instructor-applications.reject', $application) }}" class="mt-4 space-y-4">
 @csrf
 <div>
 <label class="block text-sm font-medium text-gray-700" for="rejection_reason_code">Reason category</label>
 <select id="rejection_reason_code" name="rejection_reason_code" x-model="selectedCode" required class="mt-1 block w-full rounded-lg border-gray-300 text-sm">
 <option value="" disabled selected>Select a reason</option>
 @foreach(\App\Enums\InstructorApplicationRejectionReason::cases() as $reason)
 <option value="{{ $reason->value }}">{{ $reason->label() }}</option>
 @endforeach
 </select>
 </div>

 <div>
 <label class="block text-sm font-medium text-gray-700" for="rejection_reason_note">Custom note <span class="text-xs text-gray-500">(required when reason is Other)</span></label>
 <textarea id="rejection_reason_note"
 name="rejection_reason_note"
 rows="4"
 x-bind:required="selectedCode === 'other'"
 x-on:input="chars = $event.target.value.length"
 class="mt-1 block w-full rounded-lg border-gray-300 text-sm"
 placeholder="Add specific guidance the learner can use to improve and reapply."></textarea>
 <div class="mt-1 flex justify-between text-xs text-gray-500">
 <span x-show="selectedCode === 'other'" x-cloak>Required for Other reason</span>
 <span x-show="selectedCode !== 'other'" x-cloak>Optional but recommended for clarity</span>
 <span x-text="chars + ' characters'"></span>
 </div>
 </div>

 <div class="rounded-lg bg-rose-50 p-3 text-xs text-rose-800">
 Learners receive this rationale in their notification. Keep wording respectful, specific, and actionable.
 </div>

 <div class="flex justify-end gap-2">
 <button type="button" @click="rejectOpen = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancel</button>
 <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Submit Rejection</button>
 </div>
 </form>
 </div>
</div>
