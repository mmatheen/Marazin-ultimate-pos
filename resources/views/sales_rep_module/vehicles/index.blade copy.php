@extends('layout.layout')

@section('content')
    <div class="container">
        <h3>Vehicles</h3>
        <button class="btn btn-primary mb-2" id="addVehicleBtn">Add Vehicle</button>
        <table id="vehiclesTable" class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Number</th>
                    <th>Type</th>
                    <th>Description</th>
                    <th>Action</th>
                </tr>
            </thead>
        </table>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="vehicleModal" tabindex="-1">
        <div class="modal-dialog">
            <form id="vehicleForm">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Vehicle</h5>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="vehicle_id">
                        <div class="mb-3">
                            <label>Vehicle Number</label>
                            <input type="text" class="form-control" id="vehicle_number">
                        </div>
                        <div class="mb-3">
                            <label>Vehicle Type</label>
                            <select id="vehicle_type" class="form-control">
                                <option value="van">Van</option>
                                <option value="bike">Bike</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label>Description</label>
                            <textarea class="form-control" id="description"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success">Save</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
        $(function() {
            // Load DataTable
            var table = $('#vehiclesTable').DataTable({
                ajax: '/api/vehicles',
                columns: [{
                        data: 'id'
                    },
                    {
                        data: 'vehicle_number'
                    },
                    {
                        data: 'vehicle_type'
                    },
                    {
                        data: 'description'
                    },
                    {
                        data: null,
                        render: function(data) {
                            return `<button class='btn btn-sm btn-info editBtn' data-id='${data.id}'>Edit</button>
                        <button class='btn btn-sm btn-danger deleteBtn' data-id='${data.id}'>Delete</button>`;
                        }
                    }
                ]
            });

            // Add Vehicle
            $('#addVehicleBtn').click(function() {
                $('#vehicle_id').val('');
                $('#vehicleForm')[0].reset();
                $('#vehicleModal').modal('show');
            });

            // Save Vehicle
            $('#vehicleForm').submit(function(e) {
                e.preventDefault();
                var id = $('#vehicle_id').val();
                var method = id ? 'PUT' : 'POST';
                var url = id ? '/api/vehicles/' + id : '/api/vehicles';
                $.ajax({
                    url: url,
                    method: method,
                    data: {
                        vehicle_number: $('#vehicle_number').val(),
                        vehicle_type: $('#vehicle_type').val(),
                        description: $('#description').val()
                    },
                    success: function() {
                        $('#vehicleModal').modal('hide');
                        table.ajax.reload();
                    }
                });
            });

            // Edit Vehicle
            $('#vehiclesTable').on('click', '.editBtn', function() {
                var id = $(this).data('id');
                $.get('/api/vehicles/' + id, function(data) {
                    $('#vehicle_id').val(data.id);
                    $('#vehicle_number').val(data.vehicle_number);
                    $('#vehicle_type').val(data.vehicle_type);
                    $('#description').val(data.description);
                    $('#vehicleModal').modal('show');
                });
            });

            // Delete Vehicle
            $('#vehiclesTable').on('click', '.deleteBtn', function() {
                var id = $(this).data('id');
                if (confirm('Are you sure?')) {
                    $.ajax({
                        url: '/api/vehicles/' + id,
                        method: 'DELETE',
                        success: function() {
                            table.ajax.reload();
                        }
                    });
                }
            });
        });
    </script>
@endsection
