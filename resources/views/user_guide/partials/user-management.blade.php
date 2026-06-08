{{-- ═══ ADMIN · USER MANAGEMENT ═══ --}}
<x-guide.section id="user-management" number="★" title="User Management" roles="admin" color="indigo" tag="Admin Only">

    <p class="text-sm text-gray-600">Go to <strong>Admin → User Management</strong> to create, edit, and manage system users.</p>

    <h3 class="mt-5 text-sm font-semibold text-gray-900">Adding a User</h3>
    <x-guide.steps :items="[
        'Click <strong>Add User</strong>',
        'Fill in Name, Email, Password, Role, and Location',
        'Click <strong>Add User</strong> to create',
    ]" />

    <h3 class="mt-6 text-sm font-semibold text-gray-900">Roles</h3>
    <x-guide.table :headers="['Role', 'Access']" :rows="[
        ['<span class=\'font-medium text-gray-800\'>Super Admin</span>', 'Full system access — all stores, products, users, settings, and logs'],
        ['<span class=\'font-medium text-gray-800\'>Store Manager</span>', 'Approve / reject orders and view reports for their assigned region'],
        ['<span class=\'font-medium text-gray-800\'>Store Personnel</span>', 'Create orders, view their store\'s products'],
        ['<span class=\'font-medium text-gray-800\'>Warehouse Manager</span>', 'Read-only orders + depot-filtered product view for their warehouse'],
        ['<span class=\'font-medium text-gray-800\'>Warehouse Personnel</span>', 'Read-only orders + depot-filtered product view for their warehouse'],
    ]" />

    <h3 class="mt-6 text-sm font-semibold text-gray-900">Editing</h3>
    <p class="mt-1 text-sm text-gray-500">Click <strong>Edit</strong> → update fields → leave Password blank to keep the current one → <strong>Update User</strong>.</p>
    <x-guide.callout type="tip">Role changes take effect immediately on the user's next page load.</x-guide.callout>
</x-guide.section>
