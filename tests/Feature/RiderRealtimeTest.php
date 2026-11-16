<?php

use App\Models\CourierProfile;
use App\Services\RiderRealtime;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

beforeEach(function () {
    config()->set('services.supabase', [
        'url' => 'https://realtime.example.test/',
        'service_key' => 'test-service-key',
    ]);
    Http::preventStrayRequests();
});

it('silently skips both notification methods when either setting is missing', function (mixed $url, mixed $serviceKey) {
    config()->set('services.supabase', ['url' => $url, 'service_key' => $serviceKey]);
    Http::fake();
    Log::spy();

    RiderRealtime::notify(42);
    RiderRealtime::notifyMany([42, 57]);

    Http::assertNothingSent();
    Log::shouldNotHaveReceived('warning');
    Log::shouldNotHaveReceived('error');
})->with([
    'both absent' => [null, null],
    'URL absent' => [null, 'test-service-key'],
    'key absent' => ['https://realtime.example.test', null],
    'URL empty' => ['', 'test-service-key'],
    'key empty' => ['https://realtime.example.test', ''],
    'URL blank' => ['  ', 'test-service-key'],
    'key blank' => ['https://realtime.example.test', '  '],
]);

it('broadcasts refresh to the courier profile ID rather than the user ID using configured credentials', function () {
    Http::fake(['https://realtime.example.test/*' => Http::response([], 202)]);
    $courier = new CourierProfile;
    $courier->forceFill(['id' => 42, 'user_id' => 987]);

    RiderRealtime::notify($courier->id);

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'POST'
        && $request->url() === 'https://realtime.example.test/realtime/v1/api/broadcast'
        && $request->hasHeader('apikey', 'test-service-key')
        && $request->hasHeader('Authorization', 'Bearer test-service-key')
        && $request['messages'][0]['topic'] === 'rider:42'
        && $request['messages'][0]['event'] === 'refresh'
        && str_contains($request->body(), '"payload":{}'));
});

it('batches distinct courier profile IDs with the refresh event', function () {
    Http::fake(['https://realtime.example.test/*' => Http::response([], 202)]);

    RiderRealtime::notifyMany(collect([42, 57, 42, null, 0]));

    Http::assertSentCount(1);
    Http::assertSent(fn (Request $request): bool => array_column($request['messages'], 'topic') === ['rider:42', 'rider:57']
        && array_column($request['messages'], 'event') === ['refresh', 'refresh']);
});

it('does not send requests for an empty recipient list', function () {
    Http::fake();

    RiderRealtime::notify(null);
    RiderRealtime::notifyMany([]);

    Http::assertNothingSent();
});

it('does not break callers when the broadcast server returns an error', function (int $status) {
    Http::fake(['https://realtime.example.test/*' => Http::response([], $status)]);

    RiderRealtime::notify(42);
    RiderRealtime::notifyMany([42, 57]);

    Http::assertSentCount(2);
})->with([401, 429, 500]);

it('does not break callers when the broadcast connection fails', function () {
    Http::fake(['https://realtime.example.test/*' => Http::failedConnection()]);

    expect(fn () => RiderRealtime::notify(42))->not->toThrow(Throwable::class);
    expect(fn () => RiderRealtime::notifyMany([42, 57]))->not->toThrow(Throwable::class);
});
