<?php

namespace App\Livewire;

use App\Models\Member;
use App\Actions\Members\CreateMemberAction;
use App\Actions\Members\UpdateMemberAction;
use App\Actions\Members\DeleteMemberAction;
use Livewire\Component;
use Livewire\WithPagination;

class MemberManagement extends Component
{
    use WithPagination;

    public $member_id = '';
    public $name = '';
    public $email = '';
    public $phone = '';
    public $address = '';

    public $search = '';
    public $editingMemberId = null;
    public $showModal = false;

    // Check permission when the component loads
    public function mount()
    {
        $this->authorize('viewAny', Member::class);
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function openAddModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    // Add Member using Action class and Policy
    public function saveMember(CreateMemberAction $action)
    {
        $this->authorize('create', Member::class);

        $validated = $this->validate([
            'member_id' => 'required|unique:members,member_id',
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $action->execute($validated);

        $this->resetForm();
        $this->showModal = false;

        session()->flash('success', 'Member added successfully!');
    }

    // Edit Member using Policy
    public function editMember($id)
    {
        $member = Member::findOrFail($id);

        $this->authorize('view', $member);

        $this->editingMemberId = $member->id;
        $this->member_id = $member->member_id;
        $this->name = $member->name;
        $this->email = $member->email;
        $this->phone = $member->phone;
        $this->address = $member->address;

        $this->resetValidation();
        $this->showModal = true;
    }

    // Update Member using Action class and Policy
    public function updateMember(UpdateMemberAction $action)
    {
        $validated = $this->validate([
            'member_id' => 'required|unique:members,member_id,' . $this->editingMemberId,
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:members,email,' . $this->editingMemberId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
        ]);

        $member = Member::findOrFail($this->editingMemberId);

        $this->authorize('update', $member);

        $action->execute($member, $validated);

        $this->resetForm();
        $this->showModal = false;

        session()->flash('success', 'Member updated successfully!');
    }

    // Delete Member using Action class and Policy
    public function deleteMember($id, DeleteMemberAction $action)
    {
        $member = Member::findOrFail($id);

        $this->authorize('delete', $member);

        $action->execute($member);

        session()->flash('success', 'Member deleted successfully!');
    }

    // Reset Form
    public function resetForm()
    {
        $this->reset([
            'member_id',
            'name',
            'email',
            'phone',
            'address',
            'editingMemberId',
        ]);

        $this->resetValidation();
    }

    // Display Members
    public function render()
    {
        $members = Member::where('name', 'like', '%' . $this->search . '%')
            ->orWhere('member_id', 'like', '%' . $this->search . '%')
            ->orderBy('created_at', 'desc')
            ->paginate(5);

        return view('livewire.member-management', [
            'members' => $members,
        ]);
    }
}
