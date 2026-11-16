<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Http\Requests\Company\StoreAnnouncementRequest;
use App\Models\CompanyAnnouncement;
use App\Models\CompanyAnnouncementReceipt;
use App\Models\User;
use App\Notifications\ActivityNotification;
use App\Services\CompanyActivity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class AnnouncementController extends Controller
{
    public function index(Request $request): View
    {
        $announcements = CompanyAnnouncement::where('company_id', $request->user()->company_id)
            ->withCount(['receipts', 'receipts as read_count' => fn (Builder $receipts) => $receipts->read()])
            ->latest()->orderByDesc('id')->paginate(15)->withQueryString();

        return view('company.announcements', compact('announcements'));
    }

    public function store(StoreAnnouncementRequest $request): RedirectResponse
    {
        DB::transaction(function () use ($request): void {
            $company = $request->user()->company;
            $announcement = CompanyAnnouncement::create($request->validated() + ['company_id' => $company->id, 'created_by' => $request->user()->id]);
            $count = 0;
            User::where('role', 'logistics')->whereIn('id', $company->hubs()->select('user_id'))->chunkById(200, function ($users) use ($announcement, &$count): void {
                $notifications = $receipts = [];
                foreach ($users as $user) {
                    $id = (string) Str::uuid();
                    $notification = new ActivityNotification('company.announcement', $announcement->title, $announcement->body,
                        route('logistics.company-announcements.show', $announcement->public_code, false), ['announcement_code' => $announcement->public_code]);
                    $notifications[] = ['id' => $id, 'type' => ActivityNotification::class, 'notifiable_type' => $user->getMorphClass(), 'notifiable_id' => $user->id,
                        'data' => json_encode($notification->toArray($user), JSON_THROW_ON_ERROR), 'read_at' => null, 'created_at' => now(), 'updated_at' => now()];
                    $receipts[] = ['company_announcement_id' => $announcement->id, 'user_id' => $user->id, 'notification_id' => $id, 'created_at' => now(), 'updated_at' => now()];
                }
                DatabaseNotification::insert($notifications);
                CompanyAnnouncementReceipt::insert($receipts);
                $count += $users->count();
            });
            CompanyActivity::record($company->id, 'announcement.created', $announcement, ['recipients' => $count]);
        });

        return back()->with('status', 'Announcement posted to all current hub managers.');
    }
}
