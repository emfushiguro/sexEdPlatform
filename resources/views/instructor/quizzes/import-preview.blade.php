@extends('layouts.instructor')
@section('title', 'CSV Import Preview')
@section('page-title', 'CSV Import Preview')
@section('content')

<div class="mb-5">
    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="inline-flex items-center gap-1.5 text-sm text-gray-500 hover:text-brand-500 dark:text-gray-400 dark:hover:text-brand-400 transition-colors">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        Back to {{ $quiz->title }}
    </a>
</div>

<div class="space-y-6">

            <!-- Summary Card -->
            <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-white">Import Summary</h3>
                </div>
                <div class="p-6">
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="p-4 bg-green-50 border border-green-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-3xl font-bold text-green-700">{{ count($validRows) }}</p>
                                    <p class="text-sm text-green-600">Valid Questions</p>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 bg-red-50 border border-red-200 rounded-lg">
                            <div class="flex items-center gap-3">
                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <div>
                                    <p class="text-3xl font-bold text-red-700">{{ count($invalidRows) }}</p>
                                    <p class="text-sm text-red-600">Invalid Questions</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if(count($validRows) > 0)
                        <div class="mt-6 flex gap-3">
                            <form method="POST" action="{{ route('instructor.quizzes.import.confirm', $quiz) }}" class="flex-1">
                                @csrf
                                <button type="submit" class="w-full px-6 py-2.5 rounded-lg bg-success-500 hover:bg-success-600 text-white text-sm font-medium shadow-theme-xs transition-colors">
                                    Confirm Import ({{ count($validRows) }} questions)
                                </button>
                            </form>
                            <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="flex-1 text-center px-6 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                Cancel
                            </a>
                        </div>
                    @else
                        <div class="mt-6">
                            <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="block text-center px-6 py-2.5 rounded-lg border border-gray-200 dark:border-gray-700 text-sm font-medium text-gray-600 dark:text-gray-400 hover:bg-gray-50 dark:hover:bg-white/5 transition-colors">
                                Back to Quiz
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Valid Rows -->
            @if(count($validRows) > 0)
                <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-base font-semibold text-success-700 dark:text-success-400">Valid Questions ({{ count($validRows) }})</h3>
                    </div>
                    <div class="p-6">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
                                <thead>
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Row</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Question</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Points</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Details</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                                    @foreach($validRows as $row)
                                    <tr class="hover:bg-gray-50 dark:hover:bg-white/[0.02]">
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $row['row_number'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ Str::limit($row['data']['question_text'], 60) }}</td>
                                            <td class="px-4 py-3 text-sm">
                                                <span class="px-2 py-1 text-xs font-semibold rounded-full
                                                    @if($row['data']['question_type'] === 'multiple_choice') bg-blue-100 text-blue-800
                                                    @elseif($row['data']['question_type'] === 'true_false') bg-green-100 text-green-800
                                                    @elseif($row['data']['question_type'] === 'multiple_select') bg-purple-100 text-purple-800
                                                    @elseif($row['data']['question_type'] === 'fill_blank_text') bg-yellow-100 text-yellow-800
                                                    @elseif($row['data']['question_type'] === 'fill_blank_select') bg-orange-100 text-orange-800
                                                    @elseif($row['data']['question_type'] === 'identification') bg-pink-100 text-pink-800
                                                    @endif">
                                                    {{ str_replace('_', ' ', ucwords($row['data']['question_type'])) }}
                                                </span>
                                            </td>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">{{ $row['data']['points'] }}</td>
                                        <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                                @if(in_array($row['data']['question_type'], ['multiple_choice', 'true_false', 'multiple_select']))
                                                    Options: {{ !empty($row['data']['option_c']) ? '4' : '2' }}
                                                @elseif(in_array($row['data']['question_type'], ['fill_blank_text', 'fill_blank_select']))
                                                    Blanks: {{ substr_count($row['data']['question_text'], '_____') }}
                                                @elseif($row['data']['question_type'] === 'identification')
                                                    @if(!empty($row['data']['image_filename']))
                                                        <span class="text-purple-600">📷 {{ $row['data']['image_filename'] }}</span>
                                                    @else
                                                        No image
                                                    @endif
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Invalid Rows -->
            @if(count($invalidRows) > 0)
                <div class="rounded-2xl bg-white dark:bg-white/[0.03] border border-gray-200 dark:border-gray-800 shadow-theme-xs overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-100 dark:border-gray-800">
                        <h3 class="text-base font-semibold text-red-700 dark:text-red-400">Invalid Questions ({{ count($invalidRows) }})</h3>
                    </div>
                    <div class="p-6">
                        
                        <div class="space-y-4">
                            @foreach($invalidRows as $row)
                                <div class="border-l-4 border-red-500 bg-red-50 dark:bg-red-500/10 p-4 rounded-lg">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-red-600 text-white font-bold rounded-full text-sm">{{ $row['row_number'] }}</span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 dark:text-white mb-2">{{ Str::limit($row['data']['question_text'] ?: 'No question text', 80) }}</p>
                                            <ul class="list-disc list-inside text-sm text-red-700 dark:text-red-400 space-y-1">
                                                @foreach($row['errors'] as $error)<li>{{ $error }}</li>@endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div><!-- end space-y-6 -->

@endsection
