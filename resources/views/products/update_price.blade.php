@extends('layout.layout')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Update Price</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Product</a></li>
                                <li class="breadcrumb-item active">Add new product</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- table row --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <form action="#" method="">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                               <h5>Import Export Product Price</h5>
                                               <button type="button" class="btn btn-primary mt-2">Export Product Price</button>
                                            </div>
                                        </div>
    
                                        <div class="col-md-6">
                                            <label>File To Import</label>
                                            <div class="form-group local-forms days">
                                                <input type="file"/>
                                                <button type="button" class="btn btn-primary mt-2">Save</button>
                                            </div>
                                        </div>
                                        
                                    </div>
    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <h4>Instructions:</h4>
                                                <span>Export product prices by clicking on above button</span>
                                                <span>Make changes in product price including tax & selling price groups.</span>
                                                <span>Do not change any product name, sku & headers</span>
                                                <span>After making changes import the file</span>
                                            </div>
                                        </div>
                                    </div>
                                    <!-- Add other elements if needed -->
                                </form>
                              
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


    </div>
@endsection
