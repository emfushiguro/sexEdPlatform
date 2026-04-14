<?php

namespace App\Http\Controllers\Instructor;

use App\Enums\EnrollmentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Instructor\UpdateInstructorProfileRequest;
use App\Models\InstructorProfile;
use App\Models\Module;
use App\Models\ModuleEnrollment;
use App\Models\Quiz;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function show(): View
    {
        $user = Auth::user();
        $user->loadMissing(['learnerProfile', 'profile']);

        $profile = InstructorProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['bio' => '']
        );

        $moduleIds = Module::query()
            ->where('created_by', $user->id)
            ->pluck('id');

        $overview = [
            'modules_created' => $moduleIds->count(),
            'total_learners_enrolled' => ModuleEnrollment::query()
                ->whereIn('module_id', $moduleIds)
                ->where('status', EnrollmentStatus::Approved)
                ->distinct('user_id')
                ->count('user_id'),
            'total_quizzes_created' => Quiz::query()
                ->whereIn('module_id', $moduleIds)
                ->orWhereHas('lesson', fn ($query) => $query->whereIn('module_id', $moduleIds))
                ->count(),
            'average_rating' => 'Not yet available',
        ];

        return view('instructor.profile.show', [
            'user' => $user,
            'learnerProfile' => $user->learnerProfile,
            'profile' => $profile,
            'certifications' => $this->normalizeCertificationItems($profile->certifications ?? []),
            'educationalEntries' => $this->normalizeEducationalEntries($profile),
            'overview' => $overview,
        ]);
    }

    public function edit(): View
    {
        $user = Auth::user();
        $profile = InstructorProfile::firstOrCreate(['user_id' => $user->id], ['bio' => '']);

        $this->authorize('update', $profile);

        return view('instructor.profile.edit', [
            'profile' => $profile,
            'user' => $user,
            'certificationsForForm' => $this->normalizeCertificationItems($profile->certifications ?? []),
            'educationalEntriesForForm' => $this->normalizeEducationalEntries($profile),
        ]);
    }

    public function update(UpdateInstructorProfileRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $profile = InstructorProfile::firstOrCreate(['user_id' => $user->id], ['bio' => '']);

        $this->authorize('update', $profile);

        $validated = $request->validated();
        
        if ($request->hasFile('profile_photo')) {
            if ($profile->profile_photo_path) {
                Storage::disk('public')->delete($profile->profile_photo_path);
            }
            $validated['profile_photo_path'] = $request->file('profile_photo')->store('avatars', 'public');
        }

        unset($validated['profile_photo']);

        $existingAttachmentPaths = collect($this->normalizeCertificationItems($profile->certifications ?? []))
            ->pluck('attachment_path')
            ->filter();

        $certifications = collect($validated['certifications'] ?? [])
            ->map(function (array $certification, int $index) use ($request): ?array {
                $title = trim((string) data_get($certification, 'title'));
                $organization = trim((string) data_get($certification, 'organization'));
                $completionDate = (string) data_get($certification, 'completion_date');

                if ($title === '') {
                    return null;
                }

                $attachmentPath = data_get($certification, 'existing_attachment');

                if ($request->hasFile("certifications.$index.attachment")) {
                    $attachmentPath = $request->file("certifications.$index.attachment")
                        ->store('instructor-certifications', 'public');
                }

                return [
                    'title' => $title,
                    'organization' => $organization,
                    'completion_date' => $completionDate,
                    'attachment_path' => $attachmentPath ?: null,
                ];
            })
            ->filter(fn (?array $certification): bool => $certification !== null)
            ->values();

        $retainedAttachmentPaths = $certifications->pluck('attachment_path')->filter();
        $attachmentPathsToDelete = $existingAttachmentPaths->diff($retainedAttachmentPaths);
        if ($attachmentPathsToDelete->isNotEmpty()) {
            Storage::disk('public')->delete($attachmentPathsToDelete->all());
        }

        $educationalEntries = collect($validated['educational_background_entries'] ?? [])
            ->map(fn (array $entry): array => [
                'school_name' => trim((string) data_get($entry, 'school_name')),
                'degree_program' => trim((string) data_get($entry, 'degree_program')),
                'graduation_date' => (string) data_get($entry, 'graduation_date'),
            ])
            ->filter(fn (array $entry): bool => $entry['school_name'] !== '' || $entry['degree_program'] !== '')
            ->values();

        $validated['expertise_tags'] = collect($validated['expertise_tags'] ?? [])
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->values()
            ->all();
        $validated['credentials'] = collect($validated['credentials'] ?? [])
            ->map(fn ($credential) => trim((string) $credential))
            ->filter()
            ->values()
            ->all();
        $validated['certifications'] = $certifications->all();
        $validated['educational_background_entries'] = $educationalEntries->all();

        if ($educationalEntries->isNotEmpty()) {
            $validated['educational_background'] = $educationalEntries
                ->map(function (array $entry): string {
                    $summary = $entry['degree_program'] !== '' ? $entry['degree_program'] : 'Education';

                    if ($entry['school_name'] !== '') {
                        $summary .= ' - ' . $entry['school_name'];
                    }

                    if (! empty($entry['graduation_date'])) {
                        $summary .= ' (' . $entry['graduation_date'] . ')';
                    }

                    return $summary;
                })
                ->implode("\n");
        }

        $profile->update($validated);

        return redirect()->route('instructor.profile.show')
            ->with('success', 'Instructor profile updated successfully.');
    }

    /**
     * @param  array<int, mixed>  $certifications
     * @return array<int, array{title: string, organization: ?string, completion_date: ?string, attachment_path: ?string}>
     */
    private function normalizeCertificationItems(array $certifications): array
    {
        return collect($certifications)
            ->map(function ($certification): ?array {
                if (is_string($certification)) {
                    return [
                        'title' => $certification,
                        'organization' => null,
                        'completion_date' => null,
                        'attachment_path' => null,
                    ];
                }

                if (! is_array($certification)) {
                    return null;
                }

                return [
                    'title' => (string) data_get($certification, 'title', data_get($certification, 'name', '')),
                    'organization' => data_get($certification, 'organization'),
                    'completion_date' => data_get($certification, 'completion_date'),
                    'attachment_path' => data_get($certification, 'attachment_path', data_get($certification, 'proof_path')),
                ];
            })
            ->filter(fn (?array $certification): bool => $certification !== null && $certification['title'] !== '')
            ->values()
            ->all();
    }

    /**
     * @return array<int, array{school_name: string, degree_program: string, graduation_date: ?string}>
     */
    private function normalizeEducationalEntries(InstructorProfile $profile): array
    {
        $entries = collect($profile->educational_background_entries ?? [])
            ->map(function ($entry): ?array {
                if (! is_array($entry)) {
                    return null;
                }

                return [
                    'school_name' => (string) data_get($entry, 'school_name', ''),
                    'degree_program' => (string) data_get($entry, 'degree_program', ''),
                    'graduation_date' => data_get($entry, 'graduation_date'),
                ];
            })
            ->filter(fn (?array $entry): bool => $entry !== null && ($entry['school_name'] !== '' || $entry['degree_program'] !== ''))
            ->values();

        if ($entries->isNotEmpty()) {
            return $entries->all();
        }

        if (! empty($profile->educational_background)) {
            return [[
                'school_name' => '',
                'degree_program' => (string) $profile->educational_background,
                'graduation_date' => null,
            ]];
        }

        return [];
    }
}
