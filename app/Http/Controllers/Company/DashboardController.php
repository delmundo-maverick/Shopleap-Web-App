<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\DashboardRequest;
use App\Services\CompanyDashboard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(DashboardRequest $request, CompanyDashboard $dashboard): View
    {
        return view('company.dashboard', $dashboard->build($request->user()->company, $request->validated('range') ?? '7d'));
    }

    public function attention(Request $request, CompanyDashboard $dashboard): View
    {
        $data = $request->validate(['type' => ['required', 'in:delayed,managers']]);
        $company = $request->user()->company;
        $type = $data['type'];
        $rows = $type === 'delayed'
            ? $dashboard->delayed($company)->with([
                'currentHub' => fn (Builder $hubs) => $hubs->where('company_id', $company->id)->select(['id', 'business_name']),
                'logisticsProfile' => fn (Builder $hubs) => $hubs->where('company_id', $company->id)->select(['id', 'business_name']),
            ])->oldest('updated_at')->paginate(25)->withQueryString()
            : $dashboard->inactiveManagers($company)->with('user:id,name,email,last_login_at,created_at')->orderBy('business_name')->paginate(25)->withQueryString();

        return view('company.attention', ['rows' => $rows, 'type' => $type, 'staleHours' => $dashboard->staleHours()]);
    }

    public function status(Request $request): View
    {
        return view('company.status', ['company' => $request->user()->company]);
    }
}
