<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveIdentityReferenceRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdentityReferenceController extends Controller
{
    private function label(string $type): string
    {
        $labels = array_map(fn ($item) => $item['label'], config('guardian_identity.id_types', []));
        $labels += ['school_id' => 'School ID', 'institution_id' => 'Institution ID', 'assisted_review' => 'Assisted review evidence', 'dependent_birth_record' => 'Birth and civil registry records'];
        abort_unless(isset($labels[$type]), 404);
        return $labels[$type];
    }

    public function show(string $type)
    {
        $label = $this->label($type);
        $examples = DB::table('identity_reference_examples')->where('id_type', $type)->orderByDesc('published_on')->get();
        return response()->view('admin.parent-verifications.identity-references', compact('type', 'label', 'examples'))->header('Cache-Control', 'private, no-store');
    }

    public function store(SaveIdentityReferenceRequest $request, string $type)
    {
        $this->label($type);
        $data = $request->safe()->except('image');
        $image = $request->file('image');
        $path = $image->store('identity-reference-examples', 'local');
        if (! is_string($path) || $path === '') { throw new \RuntimeException('Could not store the reference image.'); }
        try {
            DB::table('identity_reference_examples')->insert($data + ['id_type'=>$type, 'image_path'=>$path, 'image_mime'=>$image->getMimeType(), 'updated_by'=>$request->user()->id, 'created_at'=>now(), 'updated_at'=>now()]);
        } catch (\Throwable $e) { Storage::disk('local')->delete($path); throw $e; }
        return redirect()->route('admin.identity-references.show', $type)->with('success', 'Reference example added.');
    }

    public function update(SaveIdentityReferenceRequest $request, string $type, int $example)
    {
        $this->label($type);
        $row = DB::table('identity_reference_examples')->where('id', $example)->where('id_type', $type)->first();
        abort_unless($row, 404);
        $data = $request->safe()->except('image');
        $image = $request->file('image');
        $newPath = $image?->store('identity-reference-examples', 'local');
        if ($image && (! is_string($newPath) || $newPath === '')) { throw new \RuntimeException('Could not store the reference image.'); }
        if ($image) { $data += ['image_path'=>$newPath, 'image_mime'=>$image->getMimeType()]; }
        try { DB::table('identity_reference_examples')->where('id', $example)->where('id_type', $type)->update($data + ['updated_by'=>$request->user()->id, 'updated_at'=>now()]); }
        catch (\Throwable $e) { if ($newPath) Storage::disk('local')->delete($newPath); throw $e; }
        if ($newPath) Storage::disk('local')->delete($row->image_path);
        return redirect()->route('admin.identity-references.show', $type)->with('success', 'Reference example updated.');
    }

    public function image(string $type, int $example)
    {
        $this->label($type);
        $row = DB::table('identity_reference_examples')->where('id', $example)->where('id_type', $type)->first();
        abort_unless($row && in_array($row->image_mime, ['image/jpeg','image/png','image/webp'], true) && Storage::disk('local')->exists($row->image_path), 404);
        return response()->file(Storage::disk('local')->path($row->image_path), ['Content-Type'=>$row->image_mime, 'Cache-Control'=>'private, no-store', 'X-Content-Type-Options'=>'nosniff']);
    }
}
