<div x-show="rejectOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4" x-data="{ chars: 0 }">
 <div @click.outside="rejectOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6">
 <h3 class="text-lg font-semibold text-gray-900">Reject Instructor Application</h3>
 <p class="mt-2 text-sm text-gray-600">Provide a clear reason so the applicant can improve and reapply.</p>

 <form method="POST" action="{{ route('admin.instructor-applications.reject', $application) }}" class="mt-4 space-y-3">
 @csrf
 <div>
 <label class="block text-sm font-medium text-gray-700" for="rejection_reason">Rejection reason</label>
 <textarea id="rejection_reason" name="rejection_reason" rows="5" minlength="10" required x-on:input="chars = $event.target.value.length" class="mt-1 block w-full rounded-lg border-gray-300 text-sm" placeholder="Explain why this application was not approved."></textarea>
 <div class="mt-1 flex justify-between text-xs text-gray-500">
 <span>Minimum 10 characters</span>
 <span x-text="chars + ' characters'"></span>
 </div>
 </div>

 <div class="rounded-lg bg-rose-50 p-3 text-xs text-rose-800">
 Common reasons: incomplete documentation, expired clearance, insufficient relevant credentials, or illegible uploads.
 </div>

 <div class="flex justify-end gap-2">
 <button type="button" @click="rejectOpen = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancel</button>
 <button type="submit" class="rounded-lg bg-rose-600 px-4 py-2 text-sm font-semibold text-white hover:bg-rose-700">Submit Rejection</button>
 </div>
 </form>
 </div>
</div>
