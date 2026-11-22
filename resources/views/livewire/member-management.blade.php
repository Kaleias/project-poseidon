<div
    class="p-6 space-y-6 text-gray-900 dark:text-white"
    x-data="{
        showDeleteModal: false,
        memberToDelete: null,
        memberName: ''
    }"
>

    <!-- Page Heading -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Member Management</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Manage your library members here.
            </p>
        </div>

        <button
            wire:click="openAddModal"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
            + Add Member
        </button>
    </div>

    <!-- Success Message -->
    @if (session()->has('success'))
        <div class="p-3 bg-green-100 text-green-800 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <!-- Search -->
    <div>
        <input
            type="text"
            wire:model.live="search"
            placeholder="Search by member name or ID..."
            class="w-full sm:w-1/2 p-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white placeholder-gray-400">
    </div>

    <!-- Members Table -->
    <div class="overflow-x-auto bg-white dark:bg-zinc-900 rounded-lg shadow border border-gray-200 dark:border-gray-700">
        <table class="w-full text-left">
            <thead class="bg-gray-100 dark:bg-zinc-800">
                <tr>
                    <th class="p-3">Member ID</th>
                    <th class="p-3">Name</th>
                    <th class="p-3">Email</th>
                    <th class="p-3">Phone</th>
                    <th class="p-3">Actions</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($members as $member)
                    <tr class="border-t border-gray-200 dark:border-gray-700">
                        <td class="p-3">{{ $member->member_id }}</td>
                        <td class="p-3">{{ $member->name }}</td>
                        <td class="p-3">{{ $member->email }}</td>
                        <td class="p-3">{{ $member->phone ?? '-' }}</td>

                        <td class="p-3">
                            <div class="flex gap-3">
                                <button
                                    wire:click="editMember({{ $member->id }})"
                                    class="text-blue-600 hover:underline">
                                    Edit
                                </button>

                                <!-- Custom Delete Button -->
                                <button
                                    type="button"
                                    x-on:click="
                                        memberToDelete = {{ $member->id }};
                                        memberName = @js($member->name);
                                        showDeleteModal = true;
                                    "
                                    class="text-red-600 hover:underline">
                                    Delete
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="p-6 text-center text-gray-500 dark:text-gray-400">
                            No members found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div>
        {{ $members->links() }}
    </div>

    <!-- Add / Edit Member Modal -->
    @if ($showModal)
        <div class="fixed inset-0 z-[100] flex items-center justify-center bg-black/60 p-4">

            <div class="bg-white dark:bg-zinc-800 text-gray-900 dark:text-white rounded-xl shadow-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">

                <h2 class="text-xl font-bold mb-5">
                    {{ $editingMemberId ? 'Edit Member' : 'Add New Member' }}
                </h2>

                <form wire:submit="{{ $editingMemberId ? 'updateMember' : 'saveMember' }}">

                    <div class="space-y-4">

                        <!-- Member ID -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Member ID</label>
                            <input
                                type="text"
                                wire:model="member_id"
                                placeholder="Example: LIB001"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white rounded-lg p-2">
                            @error('member_id')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Name -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Full Name</label>
                            <input
                                type="text"
                                wire:model="name"
                                placeholder="Enter full name"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white rounded-lg p-2">
                            @error('name')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Email</label>
                            <input
                                type="email"
                                wire:model="email"
                                placeholder="example@gmail.com"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white rounded-lg p-2">
                            @error('email')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Phone -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Phone Number</label>
                            <input
                                type="text"
                                wire:model="phone"
                                placeholder="Enter phone number"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white rounded-lg p-2">
                            @error('phone')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                        <!-- Address -->
                        <div>
                            <label class="block text-sm font-medium mb-1">Address</label>
                            <textarea
                                wire:model="address"
                                placeholder="Enter address"
                                rows="3"
                                class="w-full border border-gray-300 dark:border-gray-600 bg-white dark:bg-zinc-900 text-gray-900 dark:text-white rounded-lg p-2"></textarea>
                            @error('address')
                                <span class="text-red-500 text-sm">{{ $message }}</span>
                            @enderror
                        </div>

                    </div>

                    <!-- Buttons -->
                    <div class="flex justify-end gap-3 mt-6">
                        <button
                            type="button"
                            wire:click="$set('showModal', false)"
                            class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                            Cancel
                        </button>

                        <button
                            type="submit"
                            wire:loading.attr="disabled"
                            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 disabled:opacity-50">
                            <span wire:loading.remove>
                                {{ $editingMemberId ? 'Update Member' : 'Save Member' }}
                            </span>
                            <span wire:loading>Saving...</span>
                        </button>
                    </div>

                </form>
            </div>
        </div>
    @endif

    <!-- Custom Delete Confirmation Modal -->
    <div
        x-cloak
        x-show="showDeleteModal"
        x-transition.opacity
        class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 p-4"
    >
        <div
            x-show="showDeleteModal"
            x-transition
            x-on:click.outside="showDeleteModal = false"
            class="bg-white dark:bg-zinc-800 text-gray-900 dark:text-white rounded-xl shadow-2xl w-full max-w-md p-6"
        >

            <div class="text-center">

                <div class="mx-auto mb-4 flex items-center justify-center w-14 h-14 rounded-full bg-red-100">
                    <span class="text-3xl">!</span>
                </div>

                <h2 class="text-xl font-bold mb-2">
                    Delete Member?
                </h2>

                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    Are you sure you want to delete
                    <strong x-text="memberName"></strong>?
                    This action cannot be undone.
                </p>

                <div class="flex justify-center gap-3">

                    <!-- Cancel -->
                    <button
                        type="button"
                        x-on:click="showDeleteModal = false"
                        class="px-5 py-2 rounded-lg border border-gray-300 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-zinc-700">
                        Cancel
                    </button>

                    <!-- Confirm Delete -->
                    <button
                        type="button"
                        x-on:click="
                            $wire.deleteMember(memberToDelete);
                            showDeleteModal = false;
                            memberToDelete = null;
                            memberName = '';
                        "
                        class="px-5 py-2 rounded-lg bg-red-600 text-white hover:bg-red-700">
                        Yes, Delete
                    </button>

                </div>
            </div>
        </div>
    </div>

</div>
