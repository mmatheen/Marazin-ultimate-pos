<script>
    $(document).ready(function() {
        let currentTableId = null;

        // Load all tables on page load
        function loadTables() {
            $.ajax({
                url: "/tables",
                method: "GET",
                success: function(response) {
                    const tbody = $('#tablesTable tbody');
                    tbody.empty();
                    response.data.forEach(table => {
                        const waiterNames = table.waiters.map(w => w.name).join(', ') ||
                            'None';
                        tbody.append(`
                        <tr>
                            <td>${table.id}</td>
                            <td>${table.name}</td>
                            <td>${table.capacity || '-'}</td>
                            <td>${table.is_available ? 'Yes' : 'No'}</td>
                            <td>
                                <button class="btn btn-sm btn-outline-info edit-btn" data-id="${table.id}">Edit</button>
                                <button class="btn btn-sm btn-outline-danger delete-btn" data-id="${table.id}">Delete</button>
                            </td>
                        </tr>
                    `);
                    });
                }
            });
        }

        // // Load waiters into Select2 dropdown
        // function loadWaitersForSelect(selected = []) {
        //     $.ajax({
        //         url: "{{ route('api.restaurant.waiters') }}",
        //         method: "GET",
        //         success: function(response) {
        //             $('#waiter_ids').empty();
        //             response.data.forEach(waiter => {
        //                 $('#waiter_ids').append(new Option(waiter.name, waiter.id));
        //             });

        //             $('#waiter_ids').val(selected).trigger('change');
        //         }
        //     });
        // }

        // // Initialize Select2
        // $('#waiter_ids').select2({
        //     placeholder: "Select Waiters",
        //     width: '100%'
        // });

        // Show Add Modal
        $('#addTableBtn').on('click', function() {
            $('#tableForm')[0].reset();
            $('#tableId').val('');
            $('#tableModalLabel').text('Add Table');
            $('#saveBtn').text('Save');
            $('#error-name').text('');
            $('#error-waiter_ids').text('');
            loadWaitersForSelect([]);
            $('#tableModal').modal('show');
        });

        // Edit Table
        $(document).on('click', '.edit-btn', function() {
            const id = $(this).data('id');
            $.ajax({
                url: `/api/restaurant/tables/${id}`,
                method: "GET",
                success: function(response) {
                    const table = response.data;
                    $('#tableId').val(table.id);
                    $('#name').val(table.name);
                    $('#capacity').val(table.capacity);
                    $('#is_available').prop('checked', table.is_available);
                    $('#tableModalLabel').text('Edit Table');
                    $('#saveBtn').text('Update');
                    $('#tableModal').modal('show');
                }
            });
        });

        // Save or Update Table
        $('#tableForm').on('submit', function(e) {
            e.preventDefault();
            const id = $('#tableId').val();
            const url = id ? `/tables/${id}` :
                "/tables";
            const method = id ? 'PUT' : 'POST';

            const formData = {
                name: $('#name').val(),
                capacity: $('#capacity').val(),
                is_available: $('#is_available').is(':checked') ? 1 : 0,
                waiter_ids: $('#waiter_ids').val()
            };

            $.ajax({
                url: url,
                method: method,
                data: formData,
                success: function(response) {
                    $('#tableModal').modal('hide');
                    toastr.success(response.message);
                    loadTables();
                },
                error: function(xhr) {
                    const errors = xhr.responseJSON.errors;
                    $('#error-name').text(errors?.name?.[0] || '');
                    $('#error-waiter_ids').text(errors?.waiter_ids?.[0] || '');
                    toastr.error("Please fix the errors.");
                }
            });
        });

        // Delete Table
        let deleteId = null;
        $(document).on('click', '.delete-btn', function() {
            deleteId = $(this).data('id');
            $('#deleteModal').modal('show');
        });

        $('#confirmDeleteBtn').on('click', function() {
            if (!deleteId) return;
            $.ajax({
                url: `/api/restaurant/tables/${deleteId}`,
                method: "DELETE",
                success: function(response) {
                    $('#deleteModal').modal('hide');
                    toastr.success(response.message);
                    loadTables();
                },
                error: function() {
                    toastr.error("Failed to delete table.");
                }
            });
        });

        // Initial Load
        loadTables();
    });
</script>
