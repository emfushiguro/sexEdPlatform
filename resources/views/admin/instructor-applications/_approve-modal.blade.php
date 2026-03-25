<div x-show="approveOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 px-4">
 <div @click.outside="approveOpen = false" class="w-full max-w-lg rounded-2xl bg-white p-6">
 <h3 class="text-lg font-semibold text-gray-900">Approve Instructor Application</h3>
 <p class="mt-2 text-sm text-gray-600">This will transition the user to instructor role, preserve learner data, and grant instructor panel access.</p>

 <ul class="mt-3 list-disc pl-6 text-sm text-gray-700 space-y-1">
 <li>Role changes from learner to instructor.</li>
 <li>Instructor profile will be created.</li>
 <li>Approval notification will be sent.</li>
 </ul>

 <form method="POST" action="{{ route('admin.instructor-applications.approve', $application) }}" class="mt-5 flex justify-end gap-2">
 @csrf
 <button type="button" @click="approveOpen = false" class="rounded-lg border border-gray-300 px-4 py-2 text-sm">Cancel</button>
 <button type="submit" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">Confirm Approval</button>
 </form>
 </div>
</div>
