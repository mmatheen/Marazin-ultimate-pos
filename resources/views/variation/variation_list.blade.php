@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Variations</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Products</li>
                                <li class="breadcrumb-item active">Sales Commission Agents</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table row --}}
        <div class="row">
            <div class="col-sm-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="col-auto text-end float-end ms-auto download-grp">
                                    <!-- Button trigger modal -->
                                    <button type="button" class="btn btn-outline-info " data-bs-toggle="modal"
                                        data-bs-target="#addModal">
                                        <i class="fas fa-plus px-2"> </i>Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Variations</th>
                                        <th>Values</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Add modal row --}}
        <div class="row">
            <div id="addModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
                <div class="modal-dialog modal-lg  modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="text-center mt-2 mb-4">
                                <h5>Add Variation</h5>
                            </div>
                            <form class="px-3" action="#">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="mb-12">
                                            <div class="form-group local-forms">
                                                <label>Variation Name <span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="Variation Name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-10">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Add variation values <span class="login-danger">*</span></label>
                                                <input class="form-control" type="text" placeholder="">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-2">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <button class="btn btn-primary" id="add-variation-btn"><i class="fas fa-plus"></i></button>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="variation-container"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary">Save</button>
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        {{-- Edit modal row --}}
    </div>
    

    <script>
        document.getElementById('add-variation-btn').addEventListener('click', function(event) {
            event.preventDefault();

            // Create a new row for the variation fields
            const newRow = document.createElement('div');
            newRow.classList.add('row', 'variation-group');

            newRow.innerHTML = `
                <div class="col-md-10">
                    <div class="mb-3">
                        <div class="form-group local-forms">
                            <label>Add variation values <span class="login-danger">*</span></label>
                            <input class="form-control" type="text" placeholder="">
                        </div>
                    </div>
                </div> 
                <div class="col-md-2">
                    <div class="mb-3">
                        <div class="form-group local-forms">
                            <button class="btn btn-danger remove-variation-btn"><i class="fas fa-minus"></i></button>
                        </div>
                    </div>
                </div>
            `;

            // Add the new row to the container
            document.getElementById('variation-container').appendChild(newRow);
        });

        document.addEventListener('click', function(event) {
            if (event.target && event.target.classList.contains('remove-variation-btn')) {
                event.preventDefault();
                event.target.closest('.variation-group').remove();
            }
        });
    </script>

@endsection
