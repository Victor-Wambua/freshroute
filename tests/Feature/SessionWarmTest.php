<?php

use App\Support\WarmSafeSessionHandler;
use Illuminate\Http\Request;

function sessionHandler(): WarmSafeSessionHandler
{
    return new WarmSafeSessionHandler(DB::connection(), 'sessions', 120, app());
}

it('ignores session writes from the service worker warm-up requests but not from normal requests', function () {
    $h = sessionHandler();
    app()->instance('request', Request::create('/sell', 'GET', [], [], [], ['HTTP_X_FR_WARM' => '1']));
    $h->write('warm-session', serialize(['stale' => true]));
    expect(DB::table('sessions')->where('id', 'warm-session')->count())->toBe(0);

    app()->instance('request', Request::create('/sell', 'GET'));
    $h->write('real-session', 'payload');
    expect(DB::table('sessions')->where('id', 'real-session')->count())->toBe(1);

    // a warm request cannot overwrite what a real request saved
    app()->instance('request', Request::create('/sell', 'GET', [], [], [], ['HTTP_X_FR_WARM' => '1']));
    $h->write('real-session', 'STALE');
    expect(DB::table('sessions')->where('id', 'real-session')->value('payload'))->not->toBe('STALE');
});

it('uses the warm-safe handler for the database session driver', function () {
    config(['session.driver' => 'database']);
    $driver = app('session')->driver()->getHandler();
    expect($driver)->toBeInstanceOf(WarmSafeSessionHandler::class);
});
