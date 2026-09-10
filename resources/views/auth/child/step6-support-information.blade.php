<x-auth-split-layout :showTabs="false">
    <x-slot name="panel">
        <div class="flex h-full flex-col items-center justify-center p-12 text-center">
            <img src="{{ asset('/media/Logo.png') }}" alt="Conscious Connections" class="mx-auto mb-3 h-20 w-auto">
            <h2 class="mb-4 text-4xl font-bold text-white">Optional support information</h2>
            <p class="max-w-xs text-lg text-white/80">Share only what is relevant to learning, accessibility, participation, or safety.</p>
        </div>
    </x-slot>

    <x-wizard-stepper :steps="[
        ['label' => 'Dependent Info', 'active' => false, 'done' => true],
        ['label' => 'Location', 'active' => false, 'done' => true],
        ['label' => 'Credentials', 'active' => false, 'done' => true],
        ['label' => 'Validation', 'active' => false, 'done' => true],
        ['label' => 'Relationship', 'active' => false, 'done' => true],
        ['label' => 'Support', 'active' => true, 'done' => false],
    ]" />

    <section x-data="{ hasInformation: false }" class="space-y-5">
        <div class="rounded-2xl border border-purple-100 bg-purple-50 p-5">
            <h1 class="text-2xl font-bold text-purple-950">Relevant Health &amp; Support Information</h1>
            <p class="mt-2 text-sm text-gray-700">This is optional. It helps you and an authorized guardian record relevant support needs. It is not used for diagnosis, treatment, or Guardian-Dependent approval.</p>
            <p class="mt-2 text-xs text-gray-600">Do not use this page for emergencies or upload medical documents.</p>
        </div>

        @if ($errors->any())
            <div role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-inside list-disc">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('parent.create-child.support-information.store') }}" class="space-y-5">
            @csrf
            <input type="hidden" name="has_relevant_support_information" :value="hasInformation ? 1 : 0">
            <label class="flex items-start gap-3 rounded-xl border border-gray-200 p-4">
                <input type="checkbox" x-model="hasInformation" class="mt-1 rounded border-gray-300">
                <span><strong>I want to provide relevant support information.</strong><span class="mt-1 block text-xs text-gray-600">You can skip this now and add information later.</span></span>
            </label>

            <div x-cloak x-show="hasInformation" class="space-y-4">
                <label class="block text-sm font-medium">Relevant health considerations
                    <textarea name="relevant_health_considerations" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300"></textarea>
                </label>
                <label class="block text-sm font-medium">Accessibility or learning-support needs
                    <textarea name="accessibility_support_needs" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300"></textarea>
                </label>
                <label class="block text-sm font-medium">Additional relevant participation or safety information
                    <textarea name="additional_relevant_information" maxlength="1000" rows="4" class="mt-1 w-full rounded-xl border-gray-300"></textarea>
                </label>
                <label class="flex items-start gap-2 rounded-xl border border-amber-200 bg-amber-50 p-3 text-xs text-amber-950">
                    <input type="checkbox" name="purpose_acknowledged" value="1" class="mt-0.5 rounded border-amber-300">
                    <span>I understand the stated purpose and choose to provide only information relevant to platform support.</span>
                </label>
            </div>

            <button type="submit" class="w-full rounded-xl bg-purple-700 px-6 py-3 font-semibold text-white">Continue</button>
        </form>

        <form method="POST" action="{{ route('parent.create-child.support-information.skip') }}">
            @csrf
            <button type="submit" class="w-full rounded-xl border border-gray-300 px-6 py-3 font-semibold text-gray-700">Skip for now</button>
        </form>
    </section>
</x-auth-split-layout>
