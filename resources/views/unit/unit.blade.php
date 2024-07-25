@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Units </h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Products</li>
                                <li class="breadcrumb-item active">Unit</li>
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
                                        data-bs-target="#addUnitModal">
                                        <i class="fas fa-plus px-2"> </i>Add
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Short name</th>
                                        <th>Allow decimal</th>
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
    </div>

    @include('unit.unit_modal')
    
    <script>
        function toggleLoginFields() {
            var checkBox = document.getElementById("allowLoginCheckbox");
            var loginFields = document.querySelectorAll(".hidingclass");

            loginFields.forEach(function(field) {
                field.style.display = checkBox.checked ? "block" : "none";
            });
        }
    </script>
@endsection
