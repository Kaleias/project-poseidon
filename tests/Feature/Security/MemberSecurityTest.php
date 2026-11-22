<?php

use App\Models\Member;
use App\Models\User;
use Livewire\Livewire;
use App\Livewire\MemberManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('students cannot access the member management component', function () {
    $student = User::factory()->create(['role' => 'student']);
    
    Livewire::actingAs($student)
        ->test(MemberManagement::class)
        ->assertForbidden(); // Tests the mount() $this->authorize()
});

test('students cannot delete members', function () {
    // 1. Create users and a member
    $admin = User::factory()->create(['role' => 'admin']);
    $student = User::factory()->create(['role' => 'student']);
    $member = Member::factory()->create();

    // 2. Prove the policy blocks the student directly
    $policy = new \App\Policies\MemberPolicy();
    
    expect($policy->delete($student, $member))->toBeFalse();
    expect($policy->delete($admin, $member))->toBeTrue();
        
    $this->assertDatabaseHas('members', ['id' => $member->id]);
});

test('admins can delete members', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $member = Member::factory()->create();

    Livewire::actingAs($admin)
        ->test(MemberManagement::class)
        ->call('deleteMember', $member->id)
        ->assertHasNoErrors(); // Admin passes the policy, no 403
        
    $this->assertDatabaseMissing('members', ['id' => $member->id]);
});