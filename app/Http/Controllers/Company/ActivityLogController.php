<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\CompanyActivityLog;
use App\Services\CompanyActivity;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ActivityLogController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate(['action' => ['nullable', Rule::in(array_keys(CompanyActivity::ACTIONS))], 'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', Rule::when($request->filled('from'), 'after_or_equal:from')]]);
        $logs = CompanyActivityLog::where('company_id', $request->user()->company_id)->with('user:id,name')
            ->when($data['action'] ?? null, fn ($query, $action) => $query->where('action', $action))
            ->when($data['from'] ?? null, fn ($query, $from) => $query->where('created_at', '>=', Carbon::parse($from, 'Asia/Manila')->startOfDay()->setTimezone(config('app.timezone'))))
            ->when($data['to'] ?? null, fn ($query, $to) => $query->where('created_at', '<', Carbon::parse($to, 'Asia/Manila')->startOfDay()->addDay()->setTimezone(config('app.timezone'))))
            ->latest('created_at')->orderByDesc('id')->paginate(25)->withQueryString();

        return view('company.activity-log', ['logs' => $logs, 'actions' => CompanyActivity::ACTIONS]);
    }
}
