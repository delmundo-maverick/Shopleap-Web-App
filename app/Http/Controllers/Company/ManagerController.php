<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\UpdateManagerRequest;
use App\Models\LogisticsProfile;
use App\Services\CompanyHubs;
use App\Services\CompanyManagers;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ManagerController extends Controller
{
    public function index(Request $request, CompanyManagers $access): View
    {
        $data = $request->validate(['search' => ['nullable', 'string', 'max:100'], 'hub' => ['nullable', 'uuid'], 'status' => ['nullable', 'in:active,suspended']]);
        $company = $request->user()->company;
        if (isset($data['hub'])) {
            $hub = LogisticsProfile::where('public_code', $data['hub'])->firstOrFail();
            $access->authorizeHub($request->user(), $hub);
        }
        $hubs = $company->hubs()->select(['public_code', 'business_name'])->orderBy('business_name')->get();
        $managers = $company->hubs()->whereHas('user', fn ($query) => $query->where('role', 'logistics'))
            ->with('user')->when($data['hub'] ?? null, fn ($query, $code) => $query->where('public_code', $code))
            ->when($data['status'] ?? null, fn ($query, $status) => $query->whereHas('user', fn ($users) => $status === 'suspended' ? $users->whereNotNull('logistics_login_suspended_at') : $users->whereNull('logistics_login_suspended_at')))
            ->when($data['search'] ?? null, fn ($query, $search) => $query->whereHas('user', fn ($users) => $users->where(fn ($names) => $names->where('name', 'ilike', '%'.$search.'%')->orWhere('email', 'ilike', '%'.$search.'%'))))
            ->orderBy('business_name')->paginate(15)->withQueryString();

        return view('company.managers.index', compact('managers', 'hubs'));
    }

    public function update(UpdateManagerRequest $request, LogisticsProfile $hub, CompanyManagers $managers): RedirectResponse
    {
        $data = $request->validated();
        $managers->update($request->user(), $hub, $data['name'], $data['phone']);

        return back()->with('status', 'Manager details updated.');
    }

    public function suspend(Request $request, LogisticsProfile $hub, CompanyManagers $managers): RedirectResponse
    {
        $managers->setSuspended($request->user(), $hub, true);

        return back()->with('status', 'Manager login suspended and active sessions ended. The hub status is unchanged.');
    }

    public function reactivate(Request $request, LogisticsProfile $hub, CompanyManagers $managers): RedirectResponse
    {
        $managers->setSuspended($request->user(), $hub, false);

        return back()->with('status', 'Manager login reactivated. A suspended hub still needs to be reactivated separately.');
    }

    public function resetPassword(Request $request, LogisticsProfile $hub, CompanyManagers $managers): RedirectResponse
    {
        $managers->authorizeHub($request->user(), $hub);
        if ($hub->manager_password_set_at === null) {
            throw ValidationException::withMessages(['manager' => 'Resend the invitation until this manager has set their password.']);
        }
        $managers->sendReset($request->user(), $hub);

        return back()->with('status', 'A password reset link will be emailed to the manager. It expires in one hour.');
    }

    public function invite(Request $request, LogisticsProfile $hub, CompanyManagers $managers, CompanyHubs $hubs): RedirectResponse
    {
        $managers->authorizeHub($request->user(), $hub);
        $hubs->invite($hub);

        return back()->with('status', 'A new invitation will be emailed to the manager.');
    }

    public function forceLogout(Request $request, LogisticsProfile $hub, CompanyManagers $managers): RedirectResponse
    {
        $managers->forceLogout($request->user(), $hub);

        return back()->with('status', 'The manager?s active sessions have been invalidated.');
    }
}
