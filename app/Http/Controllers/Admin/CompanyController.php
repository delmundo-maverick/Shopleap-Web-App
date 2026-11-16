<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Notifications\CompanyDecisionNotification;
use App\Services\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CompanyController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['status' => ['nullable', 'in:pending,approved,rejected,suspended'], 'search' => ['nullable', 'string', 'max:100']]);
        $companies = Company::with(['users' => fn ($query) => $query->where('role', 'logistics_company')])->withCount('hubs')
            ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
            ->when($data['search'] ?? null, fn ($query, $search) => $query->where(fn ($q) => $q->where('name', 'ilike', '%'.$search.'%')->orWhereHas('users', fn ($users) => $users->where('email', 'ilike', '%'.$search.'%'))))
            ->latest()->paginate(15)->withQueryString();

        return view('admin.companies.index', compact('companies'));
    }

    public function approve(Request $request, Company $company): RedirectResponse
    {
        $this->decide($request, $company, true);

        return back()->with('status', 'Company approved. The account holder will be notified by email.');
    }

    public function document(Company $company, string $document): StreamedResponse
    {
        $definition = Company::DOCUMENTS[$document] ?? abort(404);
        $path = $company->{$definition['path']};
        $disk = Storage::disk('r2');
        abort_unless($path && $disk->exists($path), 404);
        $mimeType = $disk->mimeType($path);
        abort_unless(in_array($mimeType, ['image/jpeg', 'image/png', 'application/pdf'], true), 404);

        return $disk->response($path, null, [
            'Content-Type' => $mimeType,
            'Cache-Control' => 'private, no-store',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; frame-ancestors 'self'",
            'X-Robots-Tag' => 'noindex, nofollow',
        ]);
    }

    public function reject(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate(['rejection_reason' => ['required', 'string', 'max:2000']]);
        $this->decide($request, $company, false, $data['rejection_reason']);

        return back()->with('status', 'Company rejected. The account holder will be notified by email.');
    }

    public function accountStatus(Request $request, Company $company): RedirectResponse
    {
        $data = $request->validate(['status' => ['required', 'in:approved,suspended']]);
        DB::transaction(function () use ($data, $company): void {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            abort_unless(in_array($company->status, ['approved', 'suspended'], true), 422);
            $company->update(['status' => $data['status']]);
            $company->users()->where('role', 'logistics_company')->update(['account_status' => $data['status'] === 'approved' ? 'active' : 'suspended']);
        });

        return back()->with('status', 'Company account status updated.');
    }

    private function decide(Request $request, Company $company, bool $approved, ?string $reason = null): void
    {
        DB::transaction(function () use ($request, $company, $approved, $reason): void {
            $company = Company::whereKey($company->id)->lockForUpdate()->firstOrFail();
            if ($company->status !== 'pending') {
                throw ValidationException::withMessages(['company' => 'This company has already been reviewed.']);
            }
            $company->update(['status' => $approved ? 'approved' : 'rejected', 'rejection_reason' => $reason, 'approved_by' => $approved ? $request->user()->id : null, 'approved_at' => $approved ? now() : null]);
            foreach ($company->users()->where('role', 'logistics_company')->get() as $user) {
                $user->notify((new CompanyDecisionNotification($approved, $reason))->afterCommit());
            }
            ActivityLogger::log($approved ? 'company.approved' : 'company.rejected', 'User Management', 'Company application reviewed: '.$company->name, $company);
        });
    }
}
