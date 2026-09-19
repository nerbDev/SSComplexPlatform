<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Poster;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Lets an admin manage the posters/announcements that appear in the
 * landing page hero carousel and the client dashboard carousel.
 *
 * STRUCTURE ONLY for now — index()/store()/update()/destroy() are wired
 * to the Poster model and validate correctly, but there's no Blade view
 * yet (management UI comes once we add real posters). Swap the
 * view('admin.posters.index') call for whatever view you build.
 */
class PosterController extends Controller
{
    public function index(): View
    {
        $posters = Poster::ordered()->get();

        return view('admin.posters.index', compact('posters'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('posters', 'public');
        }

        Poster::create($data);

        return back()->with('status', 'Poster added.');
    }

    public function update(Request $request, Poster $poster): RedirectResponse
    {
        $data = $this->validated($request, $poster->id);

        if ($request->hasFile('image')) {
            if ($poster->image_path) {
                Storage::disk('public')->delete($poster->image_path);
            }
            $data['image_path'] = $request->file('image')->store('posters', 'public');
        }

        $poster->update($data);

        return back()->with('status', 'Poster updated.');
    }

    public function destroy(Poster $poster): RedirectResponse
    {
        if ($poster->image_path) {
            Storage::disk('public')->delete($poster->image_path);
        }
        $poster->delete();

        return back()->with('status', 'Poster removed.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $validator = Validator::make($request->all(), [
            'title'       => ['required', 'string', 'max:255'],
            'image'       => [$ignoreId ? 'nullable' : 'required', 'image', 'max:4096'],
            'event_date'  => ['nullable', 'date'],
            'event_time'  => ['nullable', 'string', 'max:100'],
            'unit'        => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:1000'],
            'status'      => ['required', 'in:open,full,ongoing,cancelled,finished'],
            'sort_order'  => ['nullable', 'integer', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
        ]);

        return $validator->validate();
    }
}