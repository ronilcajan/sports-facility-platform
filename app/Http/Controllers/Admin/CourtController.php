<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CourtStatus;
use App\Enums\SportType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCourtRequest;
use App\Http\Requests\Admin\UpdateCourtRequest;
use App\Models\Court;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CourtController extends Controller
{
    /**
     * Display a listing of the courts the current user may see.
     */
    public function index(): Response
    {
        $this->authorize('viewAny', Court::class);

        $courts = Court::query()
            ->visibleTo(request()->user())
            ->withCount('staff')
            ->latest('id')
            ->get();

        return Inertia::render('admin/courts/Index', [
            'courts' => $courts,
        ]);
    }

    /**
     * Show the form for creating a new court.
     */
    public function create(): Response
    {
        $this->authorize('create', Court::class);

        return Inertia::render('admin/courts/Create', [
            'sportTypes' => $this->sportTypeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Store a newly created court.
     */
    public function store(StoreCourtRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['slug'] = $this->uniqueSlug($data['name']);

        Court::create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court created.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Display the specified court.
     */
    public function show(Court $court): Response
    {
        $this->authorize('view', $court);

        $court->load(['images', 'staff']);

        return Inertia::render('admin/courts/Show', [
            'court' => $court,
        ]);
    }

    /**
     * Show the form for editing the specified court.
     */
    public function edit(Court $court): Response
    {
        $this->authorize('update', $court);

        return Inertia::render('admin/courts/Edit', [
            'court' => $court,
            'sportTypes' => $this->sportTypeOptions(),
            'statuses' => $this->statusOptions(),
        ]);
    }

    /**
     * Update the specified court.
     */
    public function update(UpdateCourtRequest $request, Court $court): RedirectResponse
    {
        $data = $request->validated();

        if ($data['name'] !== $court->name) {
            $data['slug'] = $this->uniqueSlug($data['name'], $court);
        }

        $court->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court updated.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Remove the specified court.
     */
    public function destroy(Court $court): RedirectResponse
    {
        $this->authorize('delete', $court);

        $court->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Court deleted.')]);

        return to_route('admin.courts.index');
    }

    /**
     * Generate a slug from the name that is unique across courts.
     */
    private function uniqueSlug(string $name, ?Court $ignore = null): string
    {
        $base = Str::slug($name);
        $slug = $base;
        $suffix = 2;

        while (Court::withTrashed()
            ->where('slug', $slug)
            ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
            ->exists()
        ) {
            $slug = "{$base}-{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    /**
     * Sport type options for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function sportTypeOptions(): array
    {
        return array_map(
            fn (SportType $type): array => ['value' => $type->value, 'label' => $type->label()],
            SportType::cases(),
        );
    }

    /**
     * Court status options for select inputs.
     *
     * @return array<int, array{value: string, label: string}>
     */
    private function statusOptions(): array
    {
        return array_map(
            fn (CourtStatus $status): array => ['value' => $status->value, 'label' => $status->label()],
            CourtStatus::cases(),
        );
    }
}
