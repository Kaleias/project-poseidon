<?php

use App\Models\User;
use App\Models\Member;
use Livewire\Livewire;
use App\Livewire\MemberManagement;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

// Test 1: Member Management page can be accessed
test('member management page can be accessed by an authenticated user', function () {
    $response = $this->get('/');

    $response->assertOk();
});

// Test 2: Add Member
test('a member can be added successfully', function () {
    Livewire::test(MemberManagement::class)
        ->set('member_id', 'LIB100')
        ->set('name', 'Test Member')
        ->set('email', 'testmember@gmail.com')
        ->set('phone', '0123456789')
        ->set('address', 'Bangi, Selangor')
        ->call('saveMember')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('members', [
        'member_id' => 'LIB100',
        'name' => 'Test Member',
    ]);
});

// Test 3: Member name is required
test('member name is required', function () {
    Livewire::test(MemberManagement::class)
        ->set('member_id', 'LIB101')
        ->set('name', '')
        ->set('email', 'test101@gmail.com')
        ->call('saveMember')
        ->assertHasErrors(['name' => 'required']);
});

// Test 4: Edit Member
test('a member can be edited successfully', function () {
    $member = Member::create([
        'member_id' => 'LIB200',
        'name' => 'Old Name',
        'email' => 'old@gmail.com',
    ]);

    Livewire::test(MemberManagement::class)
        ->call('editMember', $member->id)
        ->set('name', 'New Name')
        ->call('updateMember')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('members', [
        'id' => $member->id,
        'name' => 'New Name',
    ]);
});

// Test 5: Delete Member
test('a member can be deleted successfully', function () {
    $member = Member::create([
        'member_id' => 'LIB300',
        'name' => 'Delete Test',
        'email' => 'delete@gmail.com',
    ]);

    Livewire::test(MemberManagement::class)
        ->call('deleteMember', $member->id);

    $this->assertDatabaseMissing('members', [
        'id' => $member->id,
    ]);
});

// Test 6: Duplicate Member ID is not allowed
test('duplicate member ID is not allowed', function () {
    Member::create([
        'member_id' => 'LIB400',
        'name' => 'First Member',
        'email' => 'first@gmail.com',
    ]);

    Livewire::test(MemberManagement::class)
        ->set('member_id', 'LIB400')
        ->set('name', 'Second Member')
        ->set('email', 'second@gmail.com')
        ->call('saveMember')
        ->assertHasErrors(['member_id' => 'unique']);
});

// Test 7: Duplicate Email is not allowed
test('duplicate email is not allowed', function () {
    Member::create([
        'member_id' => 'LIB500',
        'name' => 'First Member',
        'email' => 'same@gmail.com',
    ]);

    Livewire::test(MemberManagement::class)
        ->set('member_id', 'LIB501')
        ->set('name', 'Second Member')
        ->set('email', 'same@gmail.com')
        ->call('saveMember')
        ->assertHasErrors(['email' => 'unique']);
});

// Test 8: Member Management component can render
test('member management component can render', function () {
    Livewire::test(MemberManagement::class)
        ->assertStatus(200);
});
