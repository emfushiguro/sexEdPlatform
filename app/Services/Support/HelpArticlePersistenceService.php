<?php

namespace App\Services\Support;

use App\Enums\HelpArticleStatus;
use App\Models\HelpArticle;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HelpArticlePersistenceService
{
    public function create(array $data, User $editor): HelpArticle
    {
        return $this->persist(null, $data, $editor);
    }

    public function update(HelpArticle $article, array $data, User $editor): HelpArticle
    {
        return $this->persist($article, $data, $editor);
    }

    private function persist(?HelpArticle $article, array $data, User $editor): HelpArticle
    {
        $sections = $data['sections'] ?? [];
        unset($data['sections']);
        $data['created_by'] = $article?->created_by ?? $editor->id;
        $data['updated_by'] = $editor->id;
        $data['published_at'] = ($data['status'] ?? null) === HelpArticleStatus::Published->value
            ? ($article?->published_at ?? now()) : null;
        $newPaths = [];
        $obsoletePaths = [];

        try {
            $saved = DB::transaction(function () use ($article, $data, $sections, &$newPaths, &$obsoletePaths): HelpArticle {
                $model = $article ?? HelpArticle::query()->create($data);
                if ($article !== null) {
                    $model->fill($data)->save();
                }
                $existing = $model->sections()->get()->keyBy('id');
                $submittedIds = collect($sections)->pluck('id')->filter()->map(fn ($id) => (int) $id);
                abort_if($submittedIds->diff($existing->keys())->isNotEmpty(), 422, 'Invalid section.');
                $keptIds = [];
                foreach (array_values($sections) as $order => $section) {
                    $id = isset($section['id']) && $section['id'] !== '' ? (int) $section['id'] : null;
                    $current = $id ? $existing->get($id) : ($article !== null ? $existing->values()->get($order) : null);
                    $path = $current?->image_path;
                    if (($section['image'] ?? null) instanceof UploadedFile) {
                        $path = $section['image']->store("help/articles/{$model->id}", 'public');
                        $newPaths[] = $path;
                        if ($current?->image_path) {
                            $obsoletePaths[] = $current->image_path;
                        }
                    } elseif (($section['remove_image'] ?? false) && $current?->image_path) {
                        $obsoletePaths[] = $current->image_path;
                        $path = null;
                    }
                    $heading = trim((string) ($section['heading'] ?? ''));
                    $values = [
                        'heading' => $heading !== '' ? $heading : null,
                        'body' => $section['body'],
                        'image_path' => $path,
                        'image_alt_text' => $path ? 'Screenshot for '.($heading !== '' ? $heading : $model->title) : null,
                        'sort_order' => $order,
                    ];
                    if ($current) {
                        $current->update($values);
                        $keptIds[] = $current->id;
                    } else {
                        $keptIds[] = $model->sections()->create($values)->id;
                    }
                }
                if ($article !== null) {
                    $model->sections()->whereNotIn('id', $keptIds)->get()->each(function ($section) use (&$obsoletePaths): void {
                        if ($section->image_path) {
                            $obsoletePaths[] = $section->image_path;
                        }
                        $section->delete();
                    });
                }

                return $model->fresh(['sections']);
            });
            if ($obsoletePaths !== []) {
                Storage::disk('public')->delete(array_values(array_unique($obsoletePaths)));
            }

            return $saved;
        } catch (Throwable $exception) {
            if ($newPaths !== []) {
                Storage::disk('public')->delete($newPaths);
            }
            throw $exception;
        }
    }
}
