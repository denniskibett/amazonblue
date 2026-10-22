<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Support\StaffNotifications;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/** Staff notifications: the full list, the small feed the bell polls, and "mark all read". */
class NotificationsController extends Controller
{
    public function index(Request $request): View
    {
        $feed = StaffNotifications::for($request->user());

        return view('staff.desk.notifications', ['items' => $feed->items(), 'type' => $request->query('type')]);
    }

    public function feed(Request $request): JsonResponse
    {
        $items = StaffNotifications::for($request->user())->items(40);

        return response()->json([
            'unread' => count(array_filter($items, fn ($i) => $i['unread'])),
            'items' => array_map(fn ($i) => [
                'key' => $i['key'], 'title' => $i['title'], 'line' => $i['line'], 'href' => $i['href'], 'icon' => $i['icon'],
                'tone' => StaffNotifications::TONES[$i['tone']] ?? StaffNotifications::TONES['brand'],
                'when' => $i['at']->diffForHumans(short: true), 'unread' => $i['unread'],
            ], array_slice($items, 0, 12)),
        ]);
    }

    public function read(Request $request): RedirectResponse|JsonResponse
    {
        StaffNotifications::for($request->user())->markAllRead();

        return $request->expectsJson() ? response()->json(['ok' => true]) : back()->with('success', 'All notifications marked as read.');
    }
}
