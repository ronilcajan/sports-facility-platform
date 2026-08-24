<?php

namespace App\Http\Controllers;

use App\Models\Court;
use App\Models\Reward;
use App\Models\RewardClaim;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class CustomerRewardController extends Controller
{
    /**
     * Display Customer Rewards & Points Center.
     */
    public function index(Request $request): Response
    {
        /** @var User $user */
        $user = $request->user();

        $rewards = Reward::active()
            ->latest()
            ->get()
            ->map(function (Reward $reward) use ($user): array {
                $canAfford = $user->points >= $reward->points_cost;
                $isInStock = $reward->stock === null || $reward->stock > 0;
                $pointsNeeded = max(0, $reward->points_cost - $user->points);
                $progressPercent = $reward->points_cost > 0
                    ? (int) min(100, round(($user->points / $reward->points_cost) * 100))
                    : 100;

                return [
                    'id' => $reward->id,
                    'name' => $reward->name,
                    'slug' => $reward->slug,
                    'description' => $reward->description,
                    'category' => $reward->category,
                    'points_cost' => $reward->points_cost,
                    'stock' => $reward->stock,
                    'badge_text' => $reward->badge_text,
                    'icon' => $reward->icon ?? 'Gift',
                    'terms' => $reward->terms,
                    'is_active' => $reward->is_active,
                    'can_afford' => $canAfford,
                    'is_in_stock' => $isInStock,
                    'progress_percent' => $progressPercent,
                    'points_needed' => $pointsNeeded,
                ];
            })
            ->toArray();

        $loyaltySummary = $user->getLoyaltySummary();

        $claims = $user->rewardClaims()
            ->with('reward')
            ->latest()
            ->get()
            ->map(fn (RewardClaim $c): array => [
                'id' => $c->id,
                'voucher_code' => $c->voucher_code,
                'reward_name' => $c->reward?->name ?? 'Reward Voucher',
                'reward_category' => $c->reward?->category ?? 'General',
                'points_spent' => $c->points_spent,
                'status' => $c->status,
                'expires_at' => $c->expires_at?->toFormattedDateString() ?? 'No expiration',
                'expires_in_days' => $c->expires_at ? max(0, (int) now()->diffInDays($c->expires_at, false)) : null,
                'terms' => $c->reward?->terms,
                'created_at' => $c->created_at?->toFormattedDateString() ?? '',
            ])
            ->toArray();

        $activeClaims = array_values(array_filter($claims, fn (array $c): bool => $c['status'] === 'active'));

        $courtLoyalty = Court::where('is_active', true)
            ->with('venue')
            ->take(6)
            ->get()
            ->map(function (Court $court) use ($user): array {
                $bookingsCount = $user->bookings()->where('court_id', $court->id)->count();
                $loyaltyLevel = $bookingsCount >= 10 ? 'VIP Legend (3x)' : ($bookingsCount >= 5 ? 'Pro Regular (2x)' : 'Starter (1x)');

                return [
                    'court_id' => $court->id,
                    'court_name' => $court->name,
                    'venue_name' => $court->venue?->name ?? 'Main Venue',
                    'sport_type' => $court->sport_type?->label() ?? (string) $court->sport_type,
                    'bookings_count' => $bookingsCount,
                    'points_earned' => $bookingsCount * 50,
                    'loyalty_level' => $loyaltyLevel,
                ];
            })
            ->toArray();

        $loyaltySummary['active_claims'] = $activeClaims;
        $loyaltySummary['court_loyalty'] = $courtLoyalty;

        $categories = [
            ['id' => 'all', 'label' => 'All Rewards'],
            ['id' => 'drink', 'label' => 'Drinks & Refreshments'],
            ['id' => 'gear', 'label' => 'Equipment & Gear'],
            ['id' => 'discount', 'label' => 'Booking Discounts'],
            ['id' => 'apparel', 'label' => 'Apparel & Merch'],
        ];

        return Inertia::render('customer/Rewards', [
            'rewards' => $rewards,
            'loyaltySummary' => $loyaltySummary,
            'categories' => $categories,
            'claims' => $claims,
            'courtLoyalty' => $courtLoyalty,
        ]);
    }

    /**
     * Process voucher claim for a reward.
     */
    public function claim(Request $request, Reward $reward): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if (! $reward->is_active) {
            return redirect()->back()->with('error', 'This freebie is currently inactive.');
        }

        if ($reward->stock !== null && $reward->stock <= 0) {
            return redirect()->back()->with('error', 'This freebie is out of stock.');
        }

        if ($user->points < $reward->points_cost) {
            return redirect()->back()->with('error', 'Insufficient points to claim this reward.');
        }

        DB::transaction(function () use ($user, $reward): void {
            // Deduct points
            $user->decrement('points', $reward->points_cost);

            // Decrement stock if applicable
            if ($reward->stock !== null) {
                $reward->decrement('stock');
            }

            // Create claim voucher
            $voucherCode = 'VCH-'.strtoupper(Str::random(8));

            $user->rewardClaims()->create([
                'reward_id' => $reward->id,
                'voucher_code' => $voucherCode,
                'points_spent' => $reward->points_cost,
                'status' => 'active',
                'expires_at' => now()->addDays(30),
            ]);

            // Log point transaction
            $user->pointTransactions()->create([
                'points' => -$reward->points_cost,
                'type' => 'reward_claim',
                'description' => "Redeemed freebie reward: {$reward->name} (Voucher {$voucherCode})",
            ]);
        });

        return redirect()->back()
            ->with('success', "Success! You claimed {$reward->name}. Show your voucher code at reception!");
    }
}
