<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Http\Requests\Site\ContactRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    /**
     * Show the contact page.
     */
    public function show(): Response
    {
        return Inertia::render('site/Contact', [
            'content' => config('site_content.contact'),
        ]);
    }

    /**
     * Handle a contact form submission.
     *
     * V1 records the enquiry to the log; a later sub-project will persist and
     * notify. Kept behind a Form Request so validation lives in one place.
     */
    public function store(ContactRequest $request): RedirectResponse
    {
        Log::info('Contact enquiry received', $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Thanks — we will get back to you within one business day.'),
        ]);

        return back();
    }
}
