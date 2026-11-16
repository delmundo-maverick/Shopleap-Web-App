<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\CompanyAnnouncement;
use App\Models\CompanyAnnouncementReceipt;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CompanyAnnouncementController extends Controller
{
    public static function receipts(Request $request): Builder
    {
        return CompanyAnnouncementReceipt::where('user_id', $request->user()->id)
            ->whereHas('announcement', fn (Builder $announcements) => $announcements->where('company_id', $request->user()->logisticsProfile->company_id));
    }

    public function index(Request $request): View
    {
        $receipts = self::receipts($request)->with('announcement')->latest()->orderByDesc('id')->paginate(15);

        return view('logistics.company-announcements.index', compact('receipts'));
    }

    public function show(Request $request, CompanyAnnouncement $announcement): View
    {
        $receipt = $this->receipt($request, $announcement);
        DB::transaction(function () use ($receipt): void {
            $receipt->update(['read_at' => $receipt->read_at ?? now()]);
            $receipt->notification?->markAsRead();
        });

        return view('logistics.company-announcements.show', compact('announcement', 'receipt'));
    }

    public function dismiss(Request $request, CompanyAnnouncement $announcement): RedirectResponse
    {
        $receipt = $this->receipt($request, $announcement);
        DB::transaction(function () use ($receipt): void {
            $receipt->update(['dismissed_at' => $receipt->dismissed_at ?? now(), 'read_at' => $receipt->read_at ?? now()]);
            $receipt->notification?->markAsRead();
        });

        return back()->with('status', 'Company announcement dismissed.');
    }

    private function receipt(Request $request, CompanyAnnouncement $announcement): CompanyAnnouncementReceipt
    {
        abort_unless($request->user()->logisticsProfile->company_id !== null
            && (int) $announcement->company_id === (int) $request->user()->logisticsProfile->company_id, 403);
        $receipt = self::receipts($request)->where('company_announcement_id', $announcement->id)->with('notification')->first();
        abort_unless($receipt, 403);

        return $receipt;
    }
}
