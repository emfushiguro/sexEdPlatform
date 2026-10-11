<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\OrderHelpCategoriesRequest;
use App\Http\Requests\Admin\StoreHelpCategoryRequest;
use App\Http\Requests\Admin\UpdateHelpCategoryRequest;
use App\Models\HelpCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HelpCategoryController extends Controller
{
    public function index(): View
    {
        return view('admin.help.categories.index', [
            'categories' => HelpCategory::query()->withCount('articles')->orderBy('sort_order')->orderBy('name')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.help.categories.form', ['category' => new HelpCategory]);
    }

    public function store(StoreHelpCategoryRequest $request): RedirectResponse
    {
        HelpCategory::query()->create($request->validated());

        return redirect()->route('admin.help.categories.index')->with('success', 'Help category created.');
    }

    public function edit(HelpCategory $helpCategory): View
    {
        return view('admin.help.categories.form', ['category' => $helpCategory]);
    }

    public function update(UpdateHelpCategoryRequest $request, HelpCategory $helpCategory): RedirectResponse
    {
        $helpCategory->update($request->validated());

        return redirect()->route('admin.help.categories.index')->with('success', 'Help category updated.');
    }

    public function deactivate(HelpCategory $helpCategory): RedirectResponse
    {
        $helpCategory->update(['is_active' => false]);

        return back()->with('success', 'Help category deactivated.');
    }

    public function order(OrderHelpCategoriesRequest $request): RedirectResponse
    {
        foreach ($request->validated('items') as $item) {
            HelpCategory::query()->whereKey($item['id'])->update(['sort_order' => $item['sort_order']]);
        }

        return back()->with('success', 'Help categories reordered.');
    }
}
