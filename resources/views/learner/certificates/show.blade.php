@extends('layouts.learner-app')

@section('title', 'Certificate')

@section('content')
    <div class="space-y-5">
        <div class="rounded-2xl bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 shadow-sm p-5">
            <div class="flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white">Certificate</h1>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">{{ $certificate->module_title }}</p>
                </div>
                <span class="text-xs font-mono font-semibold text-gray-600 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 px-2.5 py-1 rounded-lg">
                    {{ $certificate->certificate_number }}
                </span>
            </div>
        </div>

        <div class="bg-white dark:bg-gray-800 rounded-2xl overflow-hidden shadow-sm border border-gray-100 dark:border-gray-700">
            <div class="p-8">
                    <style>
                        @import url('https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&family=Poppins:wght@500;600;700&display=swap');

                        .certificate-overlay-name {
                            font-family: 'Poppins', 'Montserrat', 'Segoe UI', sans-serif;
                            font-weight: 700;
                            letter-spacing: 0.005em;
                        }

                        .certificate-overlay-module {
                            font-family: 'Poppins', 'Montserrat', 'Segoe UI', sans-serif;
                            font-weight: 500;
                            letter-spacing: 0.01em;
                            display: -webkit-box;
                            -webkit-line-clamp: 3;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                            word-break: break-word;
                        }

                        .certificate-overlay-meta {
                            font-family: 'Montserrat', 'Segoe UI', sans-serif;
                            font-weight: 500;
                            letter-spacing: 0.03em;
                        }
                    </style>

                    @if($templateImageUrl)
                        <div class="w-full overflow-x-auto">
                            <div
                                class="relative w-full min-w-[600px] max-w-[1000px] mx-auto border border-slate-200 rounded-lg shadow-md"
                                style="aspect-ratio: 297 / 210; container-type: inline-size;"
                            >
                                <img
                                    src="{{ $templateImageUrl }}"
                                    alt="Certificate template"
                                    class="absolute inset-0 w-full h-full object-cover"
                                >

                                                                    <div class="absolute text-center text-slate-900 truncate certificate-overlay-name"
                                                                            style="left: 31.04%; top: 44.62%; width: 38.05%; font-size: clamp(14px, 5.06cqi, 56px); line-height: 1.02;">
                                    {{ $certificate->learner_name }}
                                </div>

                                  <div class="absolute text-center text-slate-900 certificate-overlay-module"
                                                                            style="left: 23.47%; top: 64.57%; width: 52.86%; font-size: clamp(10px, 3.20cqi, 36px); line-height: 1.1;">
                                    {{ $certificate->module_title }}
                                </div>

                                <div class="absolute text-slate-500 certificate-overlay-meta"
                                      style="left: 19.16%; top: 83.95%; width: 21.89%; font-size: clamp(8px, 1.25cqi, 16px);">
                                    Issued {{ $certificate->issued_at->format('F d, Y') }}
                                </div>

                                  <div class="absolute text-center text-slate-500 certificate-overlay-meta"
                                      style="left: 42.79%; top: 93.81%; width: 14.48%; font-size: clamp(8px, 1.25cqi, 16px);">
                                    {{ $certificate->certificate_number }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Certificate template image is missing at <span class="font-mono">storage/app/public/certificate-template/module-certificate-template.png</span>.
                            Upload the file to preview the final certificate design.
                        </div>
                    @endif

                    <div class="mt-6 flex gap-4">
                        <a href="{{ route('learner.certificates.index') }}"
                           class="flex-1 text-center px-6 py-3 rounded-xl text-sm font-semibold text-purple-700 dark:text-purple-300 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800/40 hover:bg-purple-100 dark:hover:bg-purple-900/30 transition-colors">
                            ← Back to Certificates
                        </a>
                        @if(auth()->user()->isPremium())
                            <a href="{{ route('learner.certificates.download', $certificate) }}"
                               class="flex-1 text-center px-6 py-3 rounded-xl text-sm font-semibold text-white transition hover:opacity-90"
                               style="background: linear-gradient(135deg, #A30EB2, #3B0CB1);">
                                Download PDF
                            </a>
                        @else
                            <a href="{{ route('subscription.index') }}"
                               class="flex-1 text-center px-6 py-3 rounded-xl text-sm font-semibold text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/40 hover:bg-amber-100 dark:hover:bg-amber-900/30 transition-colors">
                                Upgrade to Download
                            </a>
                        @endif
                    </div>
            </div>
        </div>
    </div>
@endsection
