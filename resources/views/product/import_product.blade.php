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
                                <form action="#" id="importProductForm" method="POST" enctype="multipart/form-data">
                                    @csrf
                                    <div class="row">

                                        <div class="col-md-6 mt-4">
                                            <a class="btn btn-outline-success mt-2" id="export_btn" href="{{ route('excel-product-blank-template-export') }}"><i class="fas fa-download"></i> &nbsp; Download template file</a>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-start">
                                                <div class="mb-3">
                                                    <label>File To Import</label>
                                                    <div class="invoices-upload-btn">
                                                        <input type="file" name="file" id="file" class="hide-input">
                                                        <label for="file" class="upload"><i class="far fa-folder-open">
                                                                &nbsp;</i> Browse..</label>
                                                    </div>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="submit" id="import_btn" class="btn btn-outline-primary ms-4 mt-3">Upload</button>
                                                </div>
                                            </div>
                                        </div>
                                         <div class="col-md-6 mt-3">
                                            <div class="progress mt-3" style="display: none;">
                                                <div class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="text-danger" id="file_error"></span>
                                        </div>


                                    </div>


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
                                                        <td>Product Name (Required)</td>
                                                        <td>Name of the brand
                                                            (If not found new brand with the given name will be created)
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">2</th>
                                                        <td>Brand (Optional)</td>
                                                        <td>Thornton</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">3</th>
                                                        <td>Unit (Required)</td>
                                                        <td>Name of the unit</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">4</th>
                                                        <td>Category (Optional)</td>
                                                        <td>Name of the Category
                                                            (If not found new category with the given name will be created)
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">5</th>
                                                        <td>Sub category (Optional)</td>
                                                        <td>Name of the Sub-Category
                                                            (If not found new sub-category with the given name under the
                                                            parent Category will be created)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">6</th>
                                                        <td>SKU (Optional)</td>
                                                        <td>Product SKU. If blank an SKU will be automatically generated
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">7</th>
                                                        <td>Barcode Type (Optional, Default: C128)</td>
                                                        <td>Barcode Type for the product.
                                                            <b>Currently supported: C128, C39, EAN-13, EAN-8, UPC-A, UPC-E,
                                                                ITF-14</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">8</th>
                                                        <td>Manage Stock? (Required)</td>
                                                        <td>Enable or disable stock managemant
                                                            <b>1 = Yes
                                                                0 = No</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">9</th>
                                                        <td>Alert quantity (Optional)</td>
                                                        <td>Alert quantity</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">10</th>
                                                        <td>Expires in (Optional)</td>
                                                        <td>Product expiry period (Only in numbers)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">11</th>
                                                        <td>Expiry Period Unit (Optional)</td>
                                                        <td>Unit for the expiry period
                                                            <b>Available Options: days, months</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">12</th>
                                                        <td>Applicable Tax (Optional)</td>
                                                        <td>Name of the Tax Rate
                                                            If purchase Price (Excluding Tax) is not same as
                                                            Purchase Price (Including Tax)
                                                            then you must supply the tax rate name.</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">13</th>
                                                        <td>Selling Price Tax Type (Required)</td>
                                                        <td>Selling Price Tax Type
                                                            <b>Available Options: inclusive, exclusive</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">14</th>
                                                        <td>Product Type (Required)</td>
                                                        <td>Product Type
                                                            <b>Available Options: single, variable</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">15</th>
                                                        <td>Variation Name (Required if product type is variable)</td>
                                                        <td>Name of the variation (Ex: Size, Color etc )</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">16</th>
                                                        <td>Variation Values (Required if product type is variable)</td>
                                                        <td>Values for the variation separated with '|'
                                                            (Ex: Red|Blue|Green)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">17</th>
                                                        <td>Variation SKUs (Optional)</td>
                                                        <td>SKUs of each variations separated by "|" if product type is
                                                            variable</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">18</th>
                                                        <td>Purchase Price (Including Tax)
                                                            (Required if Purchase Price Excluding Tax is not given)</td>
                                                        <td>Purchase Price (Including Tax) (Only in numbers)
                                                            For variable products '|' separated values with
                                                            the same order as Variation Values
                                                            (Ex: 84|85|88)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">19</th>
                                                        <td>Purchase Price (Excluding Tax)
                                                            (Required if Purchase Price Including Tax is not given)</td>
                                                        <td>Purchase Price (Excluding Tax) (Only in numbers)
                                                            For variable products '|' separated values with
                                                            the same order as Variation Values
                                                            (Ex: 84|85|88)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">20</th>
                                                        <td>Profit Margin % (Optional)</td>
                                                        <td>Profit Margin (Only in numbers)
                                                            If blank default profit margin for the
                                                            business will be used</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">21</th>
                                                        <td>Selling Price (Optional)</td>
                                                        <td>Selling Price (Only in numbers)
                                                            If blank selling price will be calculated
                                                            with the given Purchase Price
                                                            and Applicable Tax</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">22</th>
                                                        <td>Opening Stock (Optional)</td>
                                                        <td>Opening Stock (Only in numbers)
                                                            For variable products separate stock quantities with '|'
                                                            (Ex: 100|150|200)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">23</th>
                                                        <td>Opening stock location (Optional)
                                                            If blank first business location will be used</td>
                                                        <td>Name of the business location</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">24</th>
                                                        <td>Expiry Date (Optional)</td>
                                                        <td>Stock Expiry Date
                                                            <b>Format: mm-dd-yyyy; Ex: 11-25-2018</b>
                                                        </td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">25</th>
                                                        <td>Enable Product description, IMEI or Serial Number (Optional,
                                                            Default: 0)</td>
                                                        <td><b>1 = Yes
                                                                0 = No</b></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">26</th>
                                                        <td>Weight (Optional)</td>
                                                        <td>Optional</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">27</th>
                                                        <td>Rack (Optional)</td>
                                                        <td>Rack details seperated by '|' for different business locations
                                                            serially.
                                                            (Ex: R1|R5|R12)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">28</th>
                                                        <td>Row (Optional)</td>
                                                        <td>Row details seperated by '|' for different business locations
                                                            serially.
                                                            (Ex: ROW1|ROW2|ROW3)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">29</th>
                                                        <td>Position (Optional)</td>
                                                        <td>Position details seperated by '|' for different business
                                                            locations serially.
                                                            (Ex: POS1|POS2|POS3)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">30</th>
                                                        <td>Image (Optional)</td>
                                                        <td>Image name with extension.
                                                            (Image name must be uploaded to the server public/uploads/img )
                                                            Or URL of the image</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">31</th>
                                                        <td>Product Description (Optional)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">32</th>
                                                        <td>Custom Field1 (Optional)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">33</th>
                                                        <td>Custom Field2 (Optional)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">34</th>
                                                        <td>Custom Field3 (Optional)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">35</th>
                                                        <td>Custom Field4 (Optional)</td>
                                                        <td></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">36</th>
                                                        <td>Not for selling (Optional)</td>
                                                        <td><b>1 = Yes
                                                                0 = No</b></td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">37</th>
                                                        <td>Product locations (Optional)</td>
                                                        <td>Comma separated string of business location names where product
                                                            will be available</td>
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

    @include('product.product_ajax')
@endsection
