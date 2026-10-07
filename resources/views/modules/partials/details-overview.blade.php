<section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-theme-xs" aria-labelledby="module-details-overview">
    <p class="text-xs font-semibold uppercase tracking-[0.18em] text-brand-700">Module overview</p>
    <h2 id="module-details-overview" class="mt-2 text-2xl font-bold text-gray-900">{{ $module->title }}</h2>
    <div class="prose prose-sm mt-3 max-w-none text-gray-600">
        {!! strip_tags((string) ($module->description ?? 'No description.'), '<p><br><strong><em><ul><ol><li>') !!}
    </div>
</section>
