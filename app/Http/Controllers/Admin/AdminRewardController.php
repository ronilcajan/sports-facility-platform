<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Reward;
use App\Models\RewardClaim;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminRewardController extends Controller
{
    /**
     * Display a listing of freebies and rewards.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $query = Reward::with(['venue', 'claims'])
            ->withCount('claims')
            ->latest();

        // Venue admin scoping
        if ($user->isVenueScopedAdmin()) {
            $query->forVenue($user->venue_id);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('status') && $request->input('status') !== 'all') {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        $rewards = $query->paginate(12)->through(function (Reward $reward): array {
            return [
                'id' => $reward->id,
                'name' => $reward->name,
                'slug' => $reward->slug,
                'description' => $reward->description,
                'category' => $reward->category,
                'points_cost' => $reward->points_cost,
                'stock' => $reward->stock,
                'badge_text' => $reward->badge_text,
                'icon' => $reward->icon,
                'terms' => $reward->terms,
                'is_active' => $reward->is_active,
                'claims_count' => $reward->claims_count,
                'venue_id' => $reward->venue_id,
                'venue' => $reward->venue ? [
                    'id' => $reward->venue->id,
                    'name' => $reward->venue->name,
                ] : null,
                'created_at' => $reward->created_at?->toFormattedDateString() ?? '',
            ];
        })->withQueryString();

        // Compute metrics
        $metricsQuery = Reward::query();
        if ($user->isVenueScopedAdmin()) {
            $metricsQuery->forVenue($user->venue_id);
        }

        $totalRewards = (clone $metricsQuery)->count();
        $activeRewards = (clone $metricsQuery)->where('is_active', true)->count();
        $totalClaims = RewardClaim::when($user->isVenueScopedAdmin(), function ($q) use ($user): void {
            $q->whereHas('reward', fn ($rq) => $rq->forVenue($user->venue_id));
        })->count();
        $totalPointsRedeemed = (int) RewardClaim::when($user->isVenueScopedAdmin(), function ($q) use ($user): void {
            $q->whereHas('reward', fn ($rq) => $rq->forVenue($user->venue_id));
        })->sum('points_spent');

        $venues = Venue::select(['id', 'name'])->orderBy('name')->get()->toArray();

        $categories = [
            ['value' => 'drink', 'label' => 'Drink & Refreshments'],
            ['value' => 'apparel', 'label' => 'Apparel & Merch'],
            ['value' => 'discount', 'label' => 'Booking Discount Vouchers'],
            ['value' => 'gear', 'label' => 'Equipment & Gear Rental'],
            ['value' => 'ticket', 'label' => 'Event & Pass Tickets'],
            ['value' => 'general', 'label' => 'General Perks'],
        ];

        $icons = ['Gift', 'CupSoda', 'Shirt', 'Crown', 'Percent', 'Dumbbell', 'Ticket', 'Sparkles'];

        return Inertia::render('admin/rewards/Index', [
            'rewards' => $rewards,
            'venues' => $venues,
            'metrics' => [
                'total_rewards' => $totalRewards,
                'active_rewards' => $activeRewards,
                'total_claims' => $totalClaims,
                'total_points_redeemed' => $totalPointsRedeemed,
            ],
            'filters' => $request->only(['search', 'category', 'status']),
            'categories' => $categories,
            'icons' => $icons,
        ]);
    }

    /**
     * Store a newly created reward.
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'string', Rule::in(['drink', 'apparel', 'discount', 'gear', 'ticket', 'general'])],
            'points_cost' => ['required', 'integer', 'min:1'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'icon' => ['required', 'string', 'max:50'],
            'terms' => ['nullable', 'string', 'max:1000'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'is_active' => ['boolean'],
        ]);

        // Venue admin override
        if ($user->isVenueScopedAdmin()) {
            $validated['venue_id'] = $user->venue_id;
        }

        $validated['slug'] = Str::slug($validated['name']).'-'.Str::random(6);

        Reward::create($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Freebie reward created successfully.');
    }

    /**
     * Update the specified reward.
     */
    public function update(Request $request, Reward $reward): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isVenueScopedAdmin() && $reward->venue_id !== $user->venue_id && $reward->venue_id !== null) {
            abort(403, 'Unauthorized access to this reward.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'category' => ['required', 'string', Rule::in(['drink', 'apparel', 'discount', 'gear', 'ticket', 'general'])],
            'points_cost' => ['required', 'integer', 'min:1'],
            'stock' => ['nullable', 'integer', 'min:0'],
            'badge_text' => ['nullable', 'string', 'max:50'],
            'icon' => ['required', 'string', 'max:50'],
            'terms' => ['nullable', 'string', 'max:1000'],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'is_active' => ['boolean'],
        ]);

        if ($user->isVenueScopedAdmin()) {
            $validated['venue_id'] = $user->venue_id;
        }

        $reward->update($validated);

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Freebie reward updated successfully.');
    }

    /**
     * Toggle active status of a reward.
     */
    public function toggleActive(Request $request, Reward $reward): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isVenueScopedAdmin() && $reward->venue_id !== $user->venue_id && $reward->venue_id !== null) {
            abort(403, 'Unauthorized access to this reward.');
        }

        $reward->update(['is_active' => ! $reward->is_active]);

        return redirect()->back()
            ->with('success', 'Reward status toggled successfully.');
    }

    /**
     * Remove the specified reward.
     */
    public function destroy(Request $request, Reward $reward): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->isVenueScopedAdmin() && $reward->venue_id !== $user->venue_id && $reward->venue_id !== null) {
            abort(403, 'Unauthorized access to this reward.');
        }

        $reward->delete();

        return redirect()->route('admin.rewards.index')
            ->with('success', 'Freebie reward deleted successfully.');
    }
}
