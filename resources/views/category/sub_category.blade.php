@extends('layout.layout')
@section('content')

    <style>
         .hidden {
                display: none;
            }
    </style>
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Categories</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item">Products</li>
                                <li class="breadcrumb-item active">Sub Categories</li>
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
                                    <button type="button" class="btn btn-outline-info " id="addSubCategoryButton">
                                        New  <i class="fas fa-plus px-2"> </i>
                                      </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table  id="SubCategory" class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Main Catergory</th>
                                        <th>Sub Catergory</th>
                                        <th>Catergory Code</th>
                                        <th>Description</th>
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

        @include('category.main_category_modal')

    </div>

    <script>
        function toggleLoginFields() {
            var checkBox = document.getElementById("allowLoginCheckbox");
            var loginFields = document.querySelectorAll(".hidingclass");

            loginFields.forEach(function(field) {
                field.style.display = checkBox.checked ? "block" : "none";
            });
        }
    </script>
     @include('category.sub_category_ajax')
@endsection
