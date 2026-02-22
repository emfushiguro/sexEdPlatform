<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('CSV Import Preview') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <!-- Breadcrumb -->
            <div class="mb-4">
                <nav class="text-sm text-gray-500">
                    <a href="{{ route('instructor.quizzes.index') }}" class="hover:text-gray-700">Quizzes</a>
                    <span class="mx-2">/</span>
                    <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="hover:text-gray-700">{{ $quiz->title }}</a>
                    <span class="mx-2">/</span>
                    <span class="text-gray-900">Import Preview</span>
                </nav>
            </div>

            <!-- Summary Card -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Import Summary</h3>
                    
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
                                <button type="submit" class="w-full px-6 py-3 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg transition">
                                    ✓ Confirm Import ({{ count($validRows) }} questions)
                                </button>
                            </form>
                            <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="flex-1 text-center px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg transition">
                                Cancel
                            </a>
                        </div>
                    @else
                        <div class="mt-6">
                            <a href="{{ route('instructor.quizzes.show', $quiz) }}" class="block text-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg transition">
                                Back to Quiz
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Valid Rows -->
            @if(count($validRows) > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-green-700 mb-4">✓ Valid Questions ({{ count($validRows) }})</h3>
                        
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Row</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Question</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Type</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Points</th>
                                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Details</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($validRows as $row)
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $row['row_number'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-900">
                                                {{ Str::limit($row['data']['question_text'], 60) }}
                                            </td>
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
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ $row['data']['points'] }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">
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
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-red-700 mb-4">✗ Invalid Questions ({{ count($invalidRows) }})</h3>
                        <p class="text-sm text-gray-600 mb-4">These questions will not be imported. Fix the errors in your CSV and upload again.</p>
                        
                        <div class="space-y-4">
                            @foreach($invalidRows as $row)
                                <div class="border-l-4 border-red-500 bg-red-50 p-4 rounded">
                                    <div class="flex items-start gap-3">
                                        <div class="flex-shrink-0">
                                            <span class="inline-flex items-center justify-center w-8 h-8 bg-red-600 text-white font-bold rounded-full text-sm">
                                                {{ $row['row_number'] }}
                                            </span>
                                        </div>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-900 mb-2">
                                                {{ Str::limit($row['data']['question_text'] ?: 'No question text', 80) }}
                                            </p>
                                            <ul class="list-disc list-inside text-sm text-red-700 space-y-1">
                                                @foreach($row['errors'] as $error)
                                                    <li>{{ $error }}</li>
                                                @endforeach
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
