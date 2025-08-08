<?php

use App\Models\RefuelingRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->distributor = User::factory()->distributor()->create();
    $this->sales = User::factory()->sales()->create();
    $this->shift = User::factory()->shift()->create();
});

test('home page shows welcome for guests', function () {
    $response = $this->get('/');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
        ->missing('requests')
    );
});

test('home page shows requests for authenticated users', function () {
    $request = RefuelingRequest::factory()->create(['created_by' => $this->distributor->id]);
    
    $response = $this->actingAs($this->distributor)->get('/');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('welcome')
        ->has('requests.data', 1)
        ->where('userRole', 'distributor')
        ->where('canCreateRequest', true)
    );
});

test('distributor can create refueling request', function () {
    $data = [
        'no_do' => 'DO-2024-001',
        'nopol' => 'B 1234 ABC',
        'distributor_name' => 'PT Test Distribution',
    ];
    
    $response = $this->actingAs($this->distributor)
        ->post(route('refueling-requests.store'), $data);
    
    $response->assertRedirect(route('home'));
    
    $this->assertDatabaseHas('refueling_requests', [
        ...$data,
        'status' => 'pending',
        'created_by' => $this->distributor->id,
    ]);
});

test('sales can approve pending request', function () {
    $request = RefuelingRequest::factory()
        ->pending()
        ->create(['created_by' => $this->distributor->id]);
    
    $response = $this->actingAs($this->sales)
        ->patch(route('refueling-requests.update', $request), ['action' => 'approve']);
    
    $response->assertRedirect(route('home'));
    
    $request->refresh();
    expect($request->status)->toBe('approved');
    expect($request->approved_by)->toBe($this->sales->id);
    expect($request->approved_at)->not->toBeNull();
});

test('sales can reject pending request with reason', function () {
    $request = RefuelingRequest::factory()
        ->pending()
        ->create(['created_by' => $this->distributor->id]);
    
    $rejectionReason = 'Invalid vehicle registration number';
    
    $response = $this->actingAs($this->sales)
        ->patch(route('refueling-requests.update', $request), [
            'action' => 'reject',
            'rejection_reason' => $rejectionReason,
        ]);
    
    $response->assertRedirect(route('home'));
    
    $request->refresh();
    expect($request->status)->toBe('rejected');
    expect($request->approved_by)->toBe($this->sales->id);
    expect($request->rejection_reason)->toBe($rejectionReason);
});

test('shift can mark approved request as completed', function () {
    $request = RefuelingRequest::factory()
        ->approved()
        ->create([
            'created_by' => $this->distributor->id,
            'approved_by' => $this->sales->id,
        ]);
    
    $response = $this->actingAs($this->shift)
        ->patch(route('refueling-requests.update', $request), ['action' => 'complete']);
    
    $response->assertRedirect(route('home'));
    
    $request->refresh();
    expect($request->status)->toBe('completed');
});

test('distributors can only see their own requests', function () {
    $otherDistributor = User::factory()->distributor()->create();
    
    RefuelingRequest::factory()->create(['created_by' => $this->distributor->id]);
    RefuelingRequest::factory()->create(['created_by' => $otherDistributor->id]);
    
    $response = $this->actingAs($this->distributor)->get('/');
    
    $response->assertInertia(fn ($page) => $page
        ->has('requests.data', 1)
        ->where('requests.data.0.created_by', $this->distributor->id)
    );
});

test('sales can see all pending, approved, and rejected requests', function () {
    RefuelingRequest::factory()->pending()->create();
    RefuelingRequest::factory()->approved()->create();
    RefuelingRequest::factory()->rejected()->create();
    RefuelingRequest::factory()->completed()->create();
    
    $response = $this->actingAs($this->sales)->get('/');
    
    $response->assertInertia(fn ($page) => $page
        ->has('requests.data', 3) // pending, approved, rejected (not completed)
    );
});

test('shift can see approved and completed requests', function () {
    RefuelingRequest::factory()->pending()->create();
    RefuelingRequest::factory()->approved()->create();
    RefuelingRequest::factory()->rejected()->create();
    RefuelingRequest::factory()->completed()->create();
    
    $response = $this->actingAs($this->shift)->get('/');
    
    $response->assertInertia(fn ($page) => $page
        ->has('requests.data', 2) // approved, completed (not pending or rejected)
    );
});

test('unauthorized users cannot perform role-specific actions', function () {
    $request = RefuelingRequest::factory()
        ->pending()
        ->create(['created_by' => $this->distributor->id]);
    
    // Distributor cannot approve
    $this->actingAs($this->distributor)
        ->patch(route('refueling-requests.update', $request), ['action' => 'approve'])
        ->assertStatus(403);
    
    // Shift cannot approve
    $this->actingAs($this->shift)
        ->patch(route('refueling-requests.update', $request), ['action' => 'approve'])
        ->assertStatus(403);
    
    // Only sales can approve
    $this->actingAs($this->sales)
        ->patch(route('refueling-requests.update', $request), ['action' => 'approve'])
        ->assertRedirect();
});