<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Certificate') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8">
                    <!-- Certificate Design -->
                    <div class="border-8 border-double border-yellow-600 p-8 bg-gradient-to-br from-yellow-50 to-white">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center justify-center w-20 h-20 rounded-full bg-gradient-to-r from-yellow-400 to-yellow-600 text-white text-3xl mb-4">
                                🏆
                            </div>
                            <h1 class="text-4xl font-bold text-gray-800 mb-2">Certificate of Completion</h1>
                            <p class="text-gray-600">Sexual and Reproductive Health Education Platform</p>
                        </div>

                        <!-- Divider -->
                        <div class="border-t-2 border-yellow-600 mb-8"></div>

                        <!-- Content -->
                        <div class="text-center mb-8">
                            <p class="text-lg text-gray-700 mb-4">This is to certify that</p>
                            <h2 class="text-3xl font-bold text-gray-800 mb-6">
                                {{ $certificate->user->name }}
                            </h2>
                            <p class="text-lg text-gray-700 mb-2">has successfully completed the module</p>
                            <h3 class="text-2xl font-bold text-blue-600 mb-6">
                                {{ $certificate->module->title }}
                            </h3>
                            <p class="text-gray-600 mb-2">
                                Demonstrating knowledge and understanding of the course material
                            </p>
                        </div>

                        <!-- Details -->
                        <div class="flex justify-between items-end border-t-2 border-gray-300 pt-6">
                            <div class="text-left">
                                <p class="text-sm text-gray-600">Certificate Number:</p>
                                <p class="font-mono text-sm font-bold text-gray-800">
                                    {{ $certificate->certificate_number }}
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-600">Date Issued:</p>
                                <p class="font-bold text-gray-800">
                                    {{ $certificate->issued_at->format('F d, Y') }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="mt-6 flex gap-4">
                        <a href="{{ route('learner.certificates.index') }}" 
                           class="flex-1 text-center px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition">
                            ← Back to Certificates
                        </a>
                        <a href="{{ route('learner.certificates.download', $certificate) }}" 
                           class="flex-1 text-center px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            Download PDF
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
