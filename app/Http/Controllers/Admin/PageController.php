<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PageRequest;
use App\Models\Page;
use App\PageType;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class PageController extends Controller
{
    public function index(Request $request): View
    {
        $type = PageType::tryFrom((string) $request->query('type')) ?? PageType::Page;

        return view('admin.pages.index', [
            'type' => $type,
            'pages' => Page::query()->ofType($type)->orderByDesc('published_at')->orderBy('title')->get(),
            'counts' => Page::query()->selectRaw('type, COUNT(*) as total')->groupBy('type')->pluck('total', 'type'),
        ]);
    }

    public function create(Request $request): View
    {
        $type = PageType::tryFrom((string) $request->query('type')) ?? PageType::Page;

        return view('admin.pages.form', ['page' => new Page([
            'type' => $type,
            'is_published' => true,
            'published_at' => now(),
            'cover_color' => '#e6f3e1',
        ])]);
    }

    public function store(PageRequest $request): RedirectResponse
    {
        $page = Page::query()->create($this->payload($request));

        return redirect()->route('admin.pages.index', ['type' => $page->type->value])->with('status', "« {$page->title} » créé.");
    }

    public function edit(Page $page): View
    {
        return view('admin.pages.form', compact('page'));
    }

    public function update(PageRequest $request, Page $page): RedirectResponse
    {
        $page->update($this->payload($request, $page));

        return redirect()->route('admin.pages.index', ['type' => $page->type->value])->with('status', "« {$page->title} » mis à jour.");
    }

    public function destroy(Page $page): RedirectResponse
    {
        $this->deleteCover($page);
        $page->delete();

        return back()->with('status', "« {$page->title} » supprimé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(PageRequest $request, ?Page $page = null): array
    {
        $data = $request->safe()->except(['cover_file', 'remove_cover']);

        if ($page && ($request->boolean('remove_cover') || $request->hasFile('cover_file'))) {
            $this->deleteCover($page);
            $data['cover'] = null;
        }

        if ($request->hasFile('cover_file')) {
            $data['cover'] = $request->file('cover_file')->store('pages', 'public');
        }

        return $data;
    }

    private function deleteCover(Page $page): void
    {
        if ($page->cover && ! str_starts_with($page->cover, 'img/')) {
            Storage::disk('public')->delete($page->cover);
        }
    }
}
