@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Import Products</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Product</a></li>
                                <li class="breadcrumb-item active">Import Products</li>
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
                                                <p>File To Import</p>
                                                <input type="file" />
                                                <button type="button" class="btn btn-primary mt-2">Submit</button>
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <button type="button" class="btn btn-outline-success mt-2"><i
                                                        class="fas fa-download"></i> &nbsp; Download template file</button>
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

        {{-- table row --}}
        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <h5>Instructions</h5>
                                            <b>Follow the instructions carefully before importing the file.</b>
                                            <p>The columns of the file should be in the following order.</p>
                                        </div>
                                    </div>

                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="table-responsive">
                                            <table class="table table-borderless table-hover">
                                                <thead>
                                                    <tr>
                                                        <th scope="col">Column Number</th>
                                                        <th scope="col">Column Name</th>
                                                        <th scope="col">Instruction</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <th scope="row">1</th>
                                                        <td>SKU(Required)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">2</th>
                                                        <td>Location (Optional)
                                                            If blank first business location will be used</td>
                                                        <td>Name of the business location</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">3</th>
                                                        <td>Quantity (Required)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">4</th>
                                                        <td>Unit Cost (Before Tax) (Required)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">5</th>
                                                        <td>Lot Number (Optional)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">6</th>
                                                        <td>Expiry Date (Optional)</td>
                                                        <td>Stock expiry date in Business date format
                                                            <b>mm/dd/yyyy, Type: text, Example: 07/15/2024</b></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>



    </div>
@endsection
