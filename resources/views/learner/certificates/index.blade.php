<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Certificates') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    @if($certificates->count() > 0)
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                            @foreach($certificates as $certificate)
                                <div class="border rounded-lg p-6 shadow hover:shadow-lg transition">
                                    <div class="text-center mb-4">
                                        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-2xl mb-3">
                                            🏆
                                        </div>
                                        <h3 class="text-lg font-bold text-gray-800">
                                            {{ $certificate->module->title }}
                                        </h3>
                                    </div>
                                    
                                    <div class="space-y-2 text-sm text-gray-600 mb-4">
                                        <p>
                                            <span class="font-semibold">Certificate No:</span><br>
                                            <span class="text-xs font-mono">{{ $certificate->certificate_number }}</span>
                                        </p>
                                        <p>
                                            <span class="font-semibold">Issued:</span><br>
                                            {{ $certificate->issued_at->format('F d, Y') }}
                                        </p>
                                    </div>

                                    <div class="flex gap-2">
                                        <a href="{{ route('learner.certificates.show', $certificate) }}" 
                                           class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                            View
                                        </a>
                                        <a href="{{ route('learner.certificates.download', $certificate) }}" 
                                           class="flex-1 text-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                            Download
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-12">
                            <div class="inline-flex items-center justify-center w-24 h-24 rounded-full bg-gray-100 text-gray-400 text-4xl mb-4">
                                🏆
                            </div>
                            <h3 class="text-xl font-bold text-gray-800 mb-2">No Certificates Yet</h3>
                            <p class="text-gray-600 mb-6">
                                Complete modules and pass final quizzes to earn certificates!
                            </p>
                            <a href="{{ route('learner.modules.index') }}" 
                               class="inline-block px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                                Browse Modules
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
