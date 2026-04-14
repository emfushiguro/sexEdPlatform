<?php

namespace App\Http\Controllers\Learner;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorProfileController extends Controller
{
    public function show(Request $request, User $instructor): View
    {
        abort_unless($instructor->can('access instructor panel'), 404);

        $instructor->loadMissing(['instructorProfile']);
        $profile = $instructor->instructorProfile;

        return view('learner.instructors.show', [
            'instructor' => $instructor,
            'profile' => $profile,
            'certifications' => $this->normalizeCertificationItems($profile?->certifications ?? []),
            'educationalEntries' => $this->normalizeEducationalEntries($profile),
        ]);
    }

    /**
     * @param array<int, mixed> $certifications
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

                if (!is_array($certification)) {
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
    private function normalizeEducationalEntries($profile): array
    {
        if ($profile === null) {
            return [];
        }

        $entries = collect($profile->educational_background_entries ?? [])
            ->map(function ($entry): ?array {
                if (!is_array($entry)) {
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

        if (!empty($profile->educational_background)) {
            return [[
                'school_name' => '',
                'degree_program' => (string) $profile->educational_background,
                'graduation_date' => null,
            ]];
        }

        return [];
    }
}
