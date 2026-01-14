<?php

namespace App\Http\Controllers;

use App\Models\CalendarEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalendarEventController extends Controller
{
    /**
     * Get calendar events with optional filters
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = CalendarEvent::with(['candidate:id,fullName,fullNameCyrillic', 'company:id,nameOfCompany']);

            // Filter by date range
            if ($request->has('date_from')) {
                $query->where('date', '>=', $request->date_from);
            }

            if ($request->has('date_to')) {
                $query->where('date', '<=', $request->date_to);
            }

            // Filter by type
            if ($request->has('type')) {
                $types = is_array($request->type) ? $request->type : explode(',', $request->type);
                $query->whereIn('type', $types);
            }

            // Filter by company
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Order by date and time
            $query->orderBy('date', 'asc')->orderBy('time', 'asc');

            $events = $query->get()->map(function ($event) {
                return [
                    'id' => $event->id,
                    'type' => $event->type,
                    'title' => $event->title,
                    'date' => $event->date->format('Y-m-d'),
                    'time' => $event->time ? $event->time->format('H:i') : null,
                    'candidateId' => $event->candidate_id,
                    'candidateName' => $event->candidate?->fullNameCyrillic ?? $event->candidate?->fullName,
                    'companyId' => $event->company_id,
                    'companyName' => $event->company?->nameOfCompany,
                    'description' => $event->description,
                ];
            });

            return response()->json([
                'success' => true,
                'events' => $events,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch calendar events',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
