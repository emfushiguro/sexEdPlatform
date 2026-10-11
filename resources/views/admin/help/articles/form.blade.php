@extends('layouts.admin')

@section('title', $article->exists ? 'Edit Help Article' : 'Add Help Article')
@section('page-title', $article->exists ? 'Edit Help Article' : 'Add Help Article')

@section('content')
<div class="mx-auto max-w-4xl px-4 pt-8"><x-support.admin-tabs /></div>
<div class="mx-auto max-w-4xl px-4 py-8">
    <form
        method="POST"
        enctype="multipart/form-data"
        action="{{ $article->exists ? route('admin.help.articles.update', $article) : route('admin.help.articles.store') }}"
        class="space-y-6 rounded-2xl border border-gray-200 bg-white p-6 shadow-sm"
        x-data="{
            sections: {{ Js::from(old('sections', $article->sections?->map(fn ($section) => [
                'id' => $section->id,
                'heading' => $section->heading,
                'body' => $section->body,
                'image_path' => $section->image_path,
                'image_url' => $section->image_path ? route('admin.help.articles.section.image', ['helpArticle' => $article, 'section' => $section]) : null,
                'previewUrl' => '',
                'remove_image' => false,
            ])->values()->all() ?: [[
                'heading' => '',
                'body' => '',
                'image_path' => null,
                'image_url' => null,
                'previewUrl' => '',
                'remove_image' => false,
            ]])) }},
            previewSectionImage(index, event) {
                const file = event.target.files[0];
                const section = this.sections[index];
                if (section.previewUrl) URL.revokeObjectURL(section.previewUrl);
                section.previewUrl = file ? URL.createObjectURL(file) : '';
                section.remove_image = false;
            },
            clearSectionImage(index) {
                const section = this.sections[index];
                if (section.previewUrl) URL.revokeObjectURL(section.previewUrl);
                section.previewUrl = '';
                section.remove_image = true;
            }
        }"
    >
        @csrf
        @if($article->exists) @method('PUT') @endif

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.14em] text-purple-700">Help Center</p>
                <p class="mt-1 text-sm text-gray-500">Write a concise guide and choose the roles who should see it.</p>
            </div>
            @if($article->exists)
                <a href="{{ route('admin.help.articles.preview', $article) }}" class="inline-flex min-h-10 items-center rounded-xl border border-purple-200 px-3 py-2 text-sm font-semibold text-purple-700 transition hover:bg-purple-50 focus:outline-none focus:ring-2 focus:ring-purple-400">Preview article</a>
            @endif
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="title" class="text-sm font-semibold text-gray-700">Title</label>
                <input id="title" name="title" value="{{ old('title', $article->title) }}" class="mt-1 w-full rounded-xl border-gray-300" required>
                @error('title')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
            <div>
                <label for="help_category_id" class="text-sm font-semibold text-gray-700">Category</label>
                <select id="help_category_id" name="help_category_id" class="mt-1 w-full rounded-xl border-gray-300" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('help_category_id', $article->help_category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('help_category_id')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
            </div>
        </div>

        <div>
            <label for="summary" class="text-sm font-semibold text-gray-700">Summary</label>
            <textarea id="summary" name="summary" rows="2" class="mt-1 w-full rounded-xl border-gray-300" required>{{ old('summary', $article->summary) }}</textarea>
            @error('summary')<p class="mt-1 text-sm text-rose-600">{{ $message }}</p>@enderror
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="status" class="text-sm font-semibold text-gray-700">Status</label>
                <select id="status" name="status" class="mt-1 w-full rounded-xl border-gray-300">
                    @foreach(['draft','published','archived'] as $status)
                        <option value="{{ $status }}" @selected(old('status', $article->status?->value ?? 'draft') === $status)>{{ str($status)->headline() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="sort_order" class="text-sm font-semibold text-gray-700">Display order</label>
                <input id="sort_order" type="number" min="0" name="sort_order" value="{{ old('sort_order', $article->sort_order ?? 0) }}" class="mt-1 w-full rounded-xl border-gray-300">
            </div>
        </div>

        <x-support.audience-picker id="article-audiences" :selected="old('audiences', $article->audiences ?? ['all'])" />

        <section class="space-y-4">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-semibold text-gray-900">Guide sections</h2>
                    <p class="text-xs text-gray-500">Keep each section short and action-oriented.</p>
                </div>
                <button type="button" @click="sections.push({id: null, heading: '', body: '', image_path: null, image_url: null, previewUrl: '', remove_image: false})" class="rounded-lg border border-purple-200 px-3 py-2 text-xs font-semibold text-purple-700">Add section</button>
            </div>

            <template x-for="(section, index) in sections" :key="index">
                <div class="space-y-3 rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between">
                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="'Section '+(index+1)"></p>
                        <button type="button" @click="sections.length > 1 && sections.splice(index, 1)" class="text-xs font-semibold text-rose-600">Remove</button>
                    </div>
                    <input type="hidden" :name="`sections[${index}][id]`" x-model="section.id">
                    <input :name="`sections[${index}][heading]`" x-model="section.heading" placeholder="Heading (optional)" class="w-full rounded-xl border-gray-300">
                    <textarea :name="`sections[${index}][body]`" x-model="section.body" rows="4" placeholder="Explain the steps..." class="w-full rounded-xl border-gray-300" required></textarea>
                    <label :for="`section-image-${index}`" class="text-xs font-semibold uppercase tracking-wide text-gray-500">Screenshot (optional)</label>
                    <input :id="`section-image-${index}`" :name="`sections[${index}][image]`" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp" @change="previewSectionImage(index, $event)" class="mt-1 block w-full rounded-xl border border-gray-300 bg-white text-sm text-gray-600 file:mr-4 file:border-0 file:bg-purple-50 file:px-4 file:py-3 file:font-semibold file:text-purple-700 hover:file:bg-purple-100">
                    <div x-cloak x-show="section.previewUrl || section.image_url" data-help-section-image-preview class="overflow-hidden rounded-xl border border-purple-100 bg-purple-50/50 p-2">
                        <img data-help-section-image-preview-image :src="section.previewUrl || section.image_url" alt="Section image preview" class="max-h-64 w-full rounded-lg object-contain">
                        <button type="button" @click="clearSectionImage(index)" class="mt-2 text-xs font-semibold text-rose-700">Remove image</button>
                    </div>
                    <label class="flex items-center gap-2 text-xs text-gray-600">
                        <input type="checkbox" :name="`sections[${index}][remove_image]`" value="1" x-model="section.remove_image">
                        Remove existing image
                    </label>
                    <p class="text-xs text-gray-500">JPG, PNG, or WebP up to 5 MB. Alternative text is generated from the section heading.</p>
                    @error('sections.*.image')<p class="text-sm text-rose-600">{{ $message }}</p>@enderror
                </div>
            </template>
        </section>

        <div class="flex justify-end gap-3">
            <a href="{{ route('admin.help.articles.index') }}" class="rounded-xl border border-gray-300 px-4 py-2.5 text-sm font-semibold text-gray-700">Cancel</a>
            <button class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-brand-900">Save article</button>
        </div>
    </form>
</div>
@endsection
