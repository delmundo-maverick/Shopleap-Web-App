<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\PlatformPolicy;
use App\Models\PlatformSetting;
use App\Services\ActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PlatformSettingController extends Controller
{
    private const AUDIENCES = ['all', 'buyer', 'seller', 'logistics', 'courier'];

    public function index(Request $request)
    {
        $tab = in_array($request->get('tab'), ['general', 'announcements', 'policies'], true)
            ? $request->get('tab') : 'general';

        $settings = [
            'platform_name' => PlatformSetting::get('platform_name', 'Shopleap'),
            'support_email' => PlatformSetting::get('support_email', ''),
            'commission_rate' => PlatformSetting::get('commission_rate', 10),
        ];

        $announcements = Announcement::latest()->paginate(10, ['*'], 'announcements_page');
        $policies = PlatformPolicy::orderBy('title')->get();

        return view('admin.settings.index', compact('tab', 'settings', 'announcements', 'policies'));
    }

    public function updateGeneral(Request $request)
    {
        $validated = $request->validate([
            'platform_name' => ['required', 'string', 'max:100'],
            'support_email' => ['required', 'email', 'max:255'],
            'commission_rate' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value);
        }

        ActivityLogger::log('settings.general_updated', 'Platform Settings', 'General platform settings updated.', null, $validated);

        return back()->with('status', 'Settings updated.');
    }

    public function storeAnnouncement(Request $request)
    {
        $validated = $this->validateAnnouncement($request);

        $announcement = Announcement::create($validated + ['created_by' => $request->user()->id]);

        ActivityLogger::log('announcement.created', 'Platform Settings', "Announcement posted: \"{$announcement->title}\".", $announcement);

        return back()->with('status', 'Announcement posted.');
    }

    public function updateAnnouncement(Request $request, Announcement $announcement)
    {
        $validated = $this->validateAnnouncement($request);

        $announcement->update($validated);

        ActivityLogger::log('announcement.updated', 'Platform Settings', "Announcement updated: \"{$announcement->title}\".", $announcement);

        return back()->with('status', 'Announcement updated.');
    }

    public function toggleAnnouncement(Announcement $announcement)
    {
        $announcement->update(['is_active' => ! $announcement->is_active]);

        ActivityLogger::log(
            $announcement->is_active ? 'announcement.activated' : 'announcement.deactivated',
            'Platform Settings',
            ($announcement->is_active ? 'Activated' : 'Deactivated') . " announcement: \"{$announcement->title}\".",
            $announcement
        );

        return back()->with('status', 'Announcement ' . ($announcement->is_active ? 'activated' : 'deactivated') . '.');
    }

    public function destroyAnnouncement(Announcement $announcement)
    {
        $title = $announcement->title;
        $announcement->delete();

        ActivityLogger::log('announcement.deleted', 'Platform Settings', "Announcement deleted: \"{$title}\".");

        return back()->with('status', 'Announcement deleted.');
    }

    public function updatePolicy(Request $request, PlatformPolicy $policy)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string'],
        ]);

        $policy->update([
            'title' => $validated['title'],
            'body' => $validated['body'],
            'version' => $policy->version + 1,
            'updated_by' => $request->user()->id,
        ]);

        ActivityLogger::log('policy.updated', 'Platform Settings', "{$policy->title} updated (now v{$policy->version}).", $policy);

        return back()->with('status', "{$policy->title} updated.");
    }

    private function validateAnnouncement(Request $request): array
    {
        return $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:1000'],
            'audience' => ['required', Rule::in(self::AUDIENCES)],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'is_active' => ['sometimes', 'boolean'],
        ]) + ['is_active' => $request->boolean('is_active', true)];
    }
}
