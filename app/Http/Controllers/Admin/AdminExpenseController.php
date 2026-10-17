<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Court;
use App\Models\Expense;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminExpenseController extends Controller
{
    /**
     * Display a listing of expenses.
     */
    public function index(Request $request): Response
    {
        $this->authorize('viewAny', Court::class);

        /** @var User $user */
        $user = $request->user();

        $query = Expense::with(['venue:id,name', 'user:id,name'])->latest('date')->latest('id');

        // Scoping for venue admin if applicable
        if ($user->isVenueScopedAdmin()) {
            $query->where('venue_id', $user->venue_id);
        }

        if ($request->filled('search')) {
            $search = (string) $request->input('search');
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('reference_number', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        if ($request->filled('category') && $request->input('category') !== 'all') {
            $query->where('category', $request->input('category'));
        }

        if ($request->filled('venue_id') && $request->input('venue_id') !== 'all') {
            if ($request->input('venue_id') === 'platform') {
                $query->whereNull('venue_id');
            } else {
                $query->where('venue_id', $request->input('venue_id'));
            }
        }

        if ($request->filled('payment_method') && $request->input('payment_method') !== 'all') {
            $query->where('payment_method', $request->input('payment_method'));
        }

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->input('start_date'), $request->input('end_date')]);
        }

        $expenses = (clone $query)->paginate(15)->through(function (Expense $expense): array {
            return [
                'id' => $expense->id,
                'title' => $expense->title,
                'amount' => (float) $expense->amount,
                'date' => $expense->date->format('Y-m-d'),
                'date_formatted' => $expense->date->format('M d, Y'),
                'category' => $expense->category,
                'category_label' => Expense::categoryLabel($expense->category),
                'venue_id' => $expense->venue_id,
                'venue' => $expense->venue ? [
                    'id' => $expense->venue->id,
                    'name' => $expense->venue->name,
                ] : null,
                'user' => $expense->user ? [
                    'id' => $expense->user->id,
                    'name' => $expense->user->name,
                ] : null,
                'payment_method' => $expense->payment_method,
                'payment_method_label' => $expense->payment_method ? (Expense::PAYMENT_METHODS[$expense->payment_method] ?? ucfirst($expense->payment_method)) : '—',
                'reference_number' => $expense->reference_number,
                'notes' => $expense->notes,
                'created_at' => $expense->created_at?->toFormattedDateString() ?? '',
            ];
        })->withQueryString();

        // Calculate summary metrics
        $metricsQuery = Expense::query();
        if ($user->isVenueScopedAdmin()) {
            $metricsQuery->where('venue_id', $user->venue_id);
        }

        $totalExpenses = (float) (clone $metricsQuery)->sum('amount');
        $totalRecords = (clone $metricsQuery)->count();

        $startOfMonth = Carbon::now()->startOfMonth()->toDateString();
        $endOfMonth = Carbon::now()->endOfMonth()->toDateString();
        $monthQuery = (clone $metricsQuery)->whereBetween('date', [$startOfMonth, $endOfMonth]);
        $thisMonthExpenses = (float) (clone $monthQuery)->sum('amount');
        $thisMonthCount = (clone $monthQuery)->count();

        $topCategoryRow = (clone $metricsQuery)
            ->selectRaw('category, SUM(amount) as sum_amount')
            ->groupBy('category')
            ->orderByDesc('sum_amount')
            ->first();

        $topCategory = $topCategoryRow ? [
            'key' => $topCategoryRow->category,
            'label' => Expense::categoryLabel($topCategoryRow->category),
            'amount' => (float) $topCategoryRow->sum_amount,
        ] : null;

        $avgExpense = $totalRecords > 0 ? (float) ($totalExpenses / $totalRecords) : 0.0;

        $venues = Venue::select(['id', 'name'])->orderBy('name')->get()->toArray();

        $categories = collect(Expense::CATEGORIES)->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->all();

        $paymentMethods = collect(Expense::PAYMENT_METHODS)->map(fn ($label, $value) => [
            'value' => $value,
            'label' => $label,
        ])->values()->all();

        return Inertia::render('admin/expenses/Index', [
            'expenses' => $expenses,
            'venues' => $venues,
            'categories' => $categories,
            'paymentMethods' => $paymentMethods,
            'metrics' => [
                'total_expenses' => $totalExpenses,
                'total_records' => $totalRecords,
                'this_month_expenses' => $thisMonthExpenses,
                'this_month_count' => $thisMonthCount,
                'top_category' => $topCategory,
                'average_expense' => $avgExpense,
            ],
            'filters' => $request->only(['search', 'category', 'venue_id', 'payment_method', 'start_date', 'end_date']),
        ]);
    }

    /**
     * Store a newly created expense.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Court::class);

        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'date' => ['required', 'date'],
            'category' => ['required', 'string', Rule::in(array_keys(Expense::CATEGORIES))],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($user->isVenueScopedAdmin()) {
            $validated['venue_id'] = $user->venue_id;
        }

        $validated['user_id'] = $user->id;

        Expense::create($validated);

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Expense recorded successfully.');
    }

    /**
     * Update the specified expense.
     */
    public function update(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('update', Court::class);

        /** @var User $user */
        $user = $request->user();

        if ($user->isVenueScopedAdmin() && $expense->venue_id !== $user->venue_id && $expense->venue_id !== null) {
            abort(403, 'Unauthorized access to this expense.');
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'date' => ['required', 'date'],
            'category' => ['required', 'string', Rule::in(array_keys(Expense::CATEGORIES))],
            'venue_id' => ['nullable', 'exists:venues,id'],
            'payment_method' => ['nullable', 'string', Rule::in(array_keys(Expense::PAYMENT_METHODS))],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        if ($user->isVenueScopedAdmin()) {
            $validated['venue_id'] = $user->venue_id;
        }

        $expense->update($validated);

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Expense updated successfully.');
    }

    /**
     * Remove the specified expense from storage.
     */
    public function destroy(Request $request, Expense $expense): RedirectResponse
    {
        $this->authorize('delete', Court::class);

        /** @var User $user */
        $user = $request->user();

        if ($user->isVenueScopedAdmin() && $expense->venue_id !== $user->venue_id && $expense->venue_id !== null) {
            abort(403, 'Unauthorized access to this expense.');
        }

        $expense->delete();

        return redirect()->route('admin.expenses.index')
            ->with('success', 'Expense deleted successfully.');
    }
}
