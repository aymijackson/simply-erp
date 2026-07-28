<?php

namespace App\Http\Controllers;

use App\Models\SystemNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class NotificationController extends Controller
{
    public function index()
    {
        return view('notifications.index');
    }

    public function datatable(Request $request)
    {
        $userId = auth()->id();

        $q = SystemNotification::query()
            ->where('user_id', $userId);

        if ($request->filled('type')) {
            $q->where('type', $request->type);
        }

        if ($request->filled('status')) {
            if ($request->status === 'read') {
                $q->where('is_read', 1);
            } elseif ($request->status === 'unread') {
                $q->where('is_read', 0);
            }
        }

        if ($request->filled('date_from')) {
            $q->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $q->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('q')) {
            $term = trim((string) $request->q);
            $q->where(function ($x) use ($term) {
                $x->where('title', 'like', "%{$term}%")
                  ->orWhere('message', 'like', "%{$term}%")
                  ->orWhere('reference_type', 'like', "%{$term}%");
            });
        }

        $draw = (int) ($request->draw ?? 1);
        $start = (int) ($request->start ?? 0);
        $length = (int) ($request->length ?? 10);

        $recordsTotal = (clone $q)->count();

        $rows = $q->orderByDesc('id')
            ->offset($start)
            ->limit($length)
            ->get();

        $data = $rows->map(function ($n) {
            $statusBadge = $n->is_read
                ? '<span class="badge bg-success">READ</span>'
                : '<span class="badge bg-warning text-dark">UNREAD</span>';

            $typeBadgeClass = match(strtolower((string) $n->type)) {
                'success' => 'bg-success',
                'error', 'danger' => 'bg-danger',
                'warning' => 'bg-warning text-dark',
                'workflow' => 'bg-info text-dark',
                default => 'bg-secondary',
            };

            $typeBadge = '<span class="badge '.$typeBadgeClass.'">'.e(strtoupper($n->type ?? 'INFO')).'</span>';

            $json = [
                'id' => $n->id,
                'title' => $n->title,
                'message' => $n->message,
                'type' => $n->type,
                'reference_type' => $n->reference_type,
                'reference_id' => $n->reference_id,
                'is_read' => (int) $n->is_read,
                'created_at' => optional($n->created_at)->format('Y-m-d H:i:s'),
            ];

            $actions = view('notifications.partials.actions', [
                'n' => $n,
                'json' => $json,
            ])->render();

            return [
                'check' => '<input type="checkbox" class="row-check" value="'.$n->id.'">',
                'id' => $n->id,
                'title' => e($n->title ?? '—'),
                'message' => e(\Illuminate\Support\Str::limit((string) $n->message, 80)),
                'type' => $typeBadge,
                'reference' => e(($n->reference_type ?? '—').($n->reference_id ? ' #'.$n->reference_id : '')),
                'status' => $statusBadge,
                'created_at' => optional($n->created_at)->format('Y-m-d H:i'),
                'actions' => $actions,
            ];
        })->values();

        return response()->json([
            'draw' => $draw,
            'recordsTotal' => $recordsTotal,
            'recordsFiltered' => $recordsTotal,
            'data' => $data,
        ]);
    }

    public function show($id)
    {
        $notification = SystemNotification::where('user_id', auth()->id())
            ->findOrFail((int) $id);

        return response()->json([
            'id' => $notification->id,
            'title' => $notification->title,
            'message' => $notification->message,
            'type' => $notification->type,
            'reference_type' => $notification->reference_type,
            'reference_id' => $notification->reference_id,
            'is_read' => (int) $notification->is_read,
            'created_at' => optional($notification->created_at)->format('Y-m-d H:i:s'),
            'updated_at' => optional($notification->updated_at)->format('Y-m-d H:i:s'),
        ]);
    }

    public function markRead($id)
    {
        $notification = SystemNotification::where('user_id', auth()->id())
            ->findOrFail((int) $id);

        $notification->update([
            'is_read' => 1,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Notification marked as read.']);
    }

    public function markUnread($id)
    {
        $notification = SystemNotification::where('user_id', auth()->id())
            ->findOrFail((int) $id);

        $notification->update([
            'is_read' => 0,
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Notification marked as unread.']);
    }

    public function markAllRead()
    {
        SystemNotification::where('user_id', auth()->id())
            ->where('is_read', 0)
            ->update([
                'is_read' => 1,
                'updated_at' => now(),
            ]);

        return response()->json(['message' => 'All notifications marked as read.']);
    }

    public function destroy($id)
    {
        $notification = SystemNotification::where('user_id', auth()->id())
            ->findOrFail((int) $id);

        $notification->delete();

        return response()->json(['message' => 'Notification deleted.']);
    }

    public function bulkDelete(Request $request)
    {
        $data = Validator::make($request->all(), [
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ])->validate();

        SystemNotification::where('user_id', auth()->id())
            ->whereIn('id', $data['ids'])
            ->delete();

        return response()->json(['message' => 'Selected notifications deleted.']);
    }

    public function unreadCount()
    {
        $count = SystemNotification::where('user_id', auth()->id())
            ->where('is_read', 0)
            ->count();

        return response()->json(['count' => $count]);
    }

    public function latestDropdown()
    {
        $rows = SystemNotification::where('user_id', auth()->id())
            ->latest()
            ->limit(8)
            ->get();

        return response()->json([
            'items' => $rows->map(function ($n) {
                return [
                    'id' => $n->id,
                    'title' => $n->title,
                    'message' => \Illuminate\Support\Str::limit((string) $n->message, 60),
                    'is_read' => (int) $n->is_read,
                    'created_at' => optional($n->created_at)->format('Y-m-d H:i'),
                ];
            })->values(),
        ]);
    }
}