<?php

namespace App\Http\Controllers;

use App\Models\PlatformPolicy;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PolicyController extends Controller
{
    public function terms(Request $request): View
    {
        return $this->showPolicy($request, PlatformPolicy::TERMS_SLUG);
    }

    public function privacy(Request $request): View
    {
        return $this->showPolicy($request, PlatformPolicy::PRIVACY_SLUG);
    }

    public function termsContent(): JsonResponse
    {
        return $this->content(PlatformPolicy::TERMS_SLUG);
    }

    public function privacyContent(): JsonResponse
    {
        return $this->content(PlatformPolicy::PRIVACY_SLUG);
    }

    public function show(PlatformPolicy $policy): View
    {
        return view('policies.show', [
            'policy' => $policy,
            'backUrl' => route('home'),
            'backLabel' => 'Back to Shopleap',
        ]);
    }

    private function showPolicy(Request $request, string $slug): View
    {
        $policy = PlatformPolicy::query()->where('slug', $slug)->first();
        $backUrl = $this->backUrl($request);

        return view('policies.show', [
            'policy' => $policy,
            'backUrl' => $backUrl,
            'backLabel' => $backUrl === route('home') ? 'Back to Shopleap' : 'Back',
        ]);
    }

    private function content(string $slug): JsonResponse
    {
        $policy = PlatformPolicy::query()->where('slug', $slug)->first();

        if (! $policy || ! $policy->hasPublishedContent()) {
            return response()->json([
                'title' => $policy?->title,
                'content' => null,
                'version' => $policy?->version,
                'updated_at' => $policy?->updated_at?->toIso8601String(),
                'updated_at_human' => $policy?->updated_at?->format('F j, Y'),
                'message' => 'This policy has not been published yet. Please check back later.',
            ]);
        }

        return response()->json([
            'title' => $policy->title,
            'content' => $policy->contentHtml(),
            'version' => $policy->version,
            'updated_at' => $policy->updated_at?->toIso8601String(),
            'updated_at_human' => $policy->updated_at?->format('F j, Y'),
            'message' => null,
        ]);
    }

    /**
     * Only send the user back to a page on this host. Direct visits fall back
     * to the home page instead of an external referrer.
     */
    private function backUrl(Request $request): string
    {
        $referer = $request->headers->get('referer');

        if (is_string($referer) && $referer !== '') {
            $refererHost = parse_url($referer, PHP_URL_HOST);
            $refererPath = parse_url($referer, PHP_URL_PATH);

            if ($refererHost === $request->getHost() && $refererPath !== $request->getPathInfo()) {
                return $referer;
            }
        }

        return route('home');
    }
}
