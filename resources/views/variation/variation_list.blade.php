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
                                <li class="breadcrumb-item active">Variations</li>
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
                                    <button type="button" class="btn btn-outline-info" data-bs-toggle="modal"
                                        data-bs-target="#addModal">
                                        <i class="fas fa-plus px-2"></i>Add
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
            <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Add Variation</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        {{-- <form id="addAndEditForm" method="POST" action="">  --}}
                        @csrf
                        <div class="modal-body">
                            <div class="row mt-4">
                                <div class="col col-md-12">
                                    <div class="mb-4">
                                        <div class="form-group local-forms">
                                            <label>Variation Name <span class="login-danger">*</span></label>
                                            <input class="form-control" type="text" name="variation_name"
                                                placeholder="Variation Name"> <!-- Add name attribute -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                        <div class="settings-form">
                                            <div class="links-info">
                                                <div class="row">
                                                    <div class="col-8 col-md-8">
                                                        <div class="form-group local-forms">
                                                            <label>Variation Name <span class="login-danger">*</span></label>
                                                            <input class="form-control" type="text" name="variation_name"
                                                                placeholder="Variation Name"> <!-- Add name attribute -->
                                                        </div>
                                                    </div>
                                                    <div class="col col-md-2">
                                                        <button type="button" class="btn add-links"><i
                                                                class="fas fa-plus px-2"></i></button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Save changes</button>
                                    <button type="reset" class="btn btn-secondary" id="close"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        {{-- Edit modal row --}}
    </div>
@endsection
