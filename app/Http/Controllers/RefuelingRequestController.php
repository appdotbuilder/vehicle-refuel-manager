<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRefuelingRequestRequest;
use App\Http\Requests\UpdateRefuelingRequestRequest;
use App\Models\RefuelingRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;

class RefuelingRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = auth()->user();
        
        // If user is not authenticated, show welcome page
        if (!$user) {
            return Inertia::render('welcome');
        }
        
        $query = RefuelingRequest::with(['creator', 'approver']);

        // Filter based on user role
        if ($user->isDistributor()) {
            $query->where('created_by', $user->id);
        } elseif ($user->isSales()) {
            $query->whereIn('status', ['pending', 'approved', 'rejected']);
        } elseif ($user->isShift()) {
            $query->whereIn('status', ['approved', 'completed']);
        }

        $requests = $query->latest()->paginate(10);

        return Inertia::render('welcome', [
            'requests' => $requests,
            'userRole' => $user->role,
            'canCreateRequest' => $user->isDistributor(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRefuelingRequestRequest $request)
    {
        $refuelingRequest = RefuelingRequest::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->route('home')
            ->with('success', '⛽ Refueling request created successfully!');
    }

    /**
     * Display the specified resource.
     */
    public function show(RefuelingRequest $refuelingRequest)
    {
        $refuelingRequest->load(['creator', 'approver']);

        return Inertia::render('refueling-requests/show', [
            'request' => $refuelingRequest,
            'userRole' => auth()->user()->role,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRefuelingRequestRequest $request, RefuelingRequest $refuelingRequest)
    {
        // Check if it's an approval/rejection action
        if ($request->has('action')) {
            return $this->handleStatusUpdate($request, $refuelingRequest);
        }

        // Regular update
        $refuelingRequest->update($request->validated());

        return redirect()->route('home')
            ->with('success', '✏️ Refueling request updated successfully!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(RefuelingRequest $refuelingRequest)
    {
        // Only distributors can delete their own pending requests
        if (!auth()->user()->isDistributor() || 
            $refuelingRequest->created_by !== auth()->id() ||
            !$refuelingRequest->canBeEdited()) {
            abort(403, 'Unauthorized action.');
        }

        $refuelingRequest->delete();

        return redirect()->route('home')
            ->with('success', '🗑️ Refueling request deleted successfully.');
    }

    /**
     * Handle status updates (approve, reject, complete).
     */
    protected function handleStatusUpdate(Request $request, RefuelingRequest $refuelingRequest)
    {
        $action = $request->input('action');
        $user = auth()->user();

        switch ($action) {
            case 'approve':
                if (!$user->isSales() || !$refuelingRequest->canBeReviewed()) {
                    abort(403, 'Unauthorized action.');
                }

                $refuelingRequest->update([
                    'status' => 'approved',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'rejection_reason' => null,
                ]);

                return redirect()->route('home')
                    ->with('success', '✅ Refueling request approved successfully!');

            case 'reject':
                if (!$user->isSales() || !$refuelingRequest->canBeReviewed()) {
                    abort(403, 'Unauthorized action.');
                }

                $request->validate([
                    'rejection_reason' => 'required|string|max:1000',
                ]);

                $refuelingRequest->update([
                    'status' => 'rejected',
                    'approved_by' => $user->id,
                    'approved_at' => now(),
                    'rejection_reason' => $request->rejection_reason,
                ]);

                return redirect()->route('home')
                    ->with('success', '❌ Refueling request rejected.');

            case 'complete':
                if (!$user->isShift() || !$refuelingRequest->canBeCompleted()) {
                    abort(403, 'Unauthorized action.');
                }

                $refuelingRequest->update([
                    'status' => 'completed',
                ]);

                return redirect()->route('home')
                    ->with('success', '🚛 Refueling completed successfully!');

            default:
                abort(400, 'Invalid action.');
        }
    }
}