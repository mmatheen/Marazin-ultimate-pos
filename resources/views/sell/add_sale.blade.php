@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <style>
            .login-fields1 {
                display: none;
            }

            .login-fields2 {
                display: none;
            }

            .login-fields3 {
                display: none;
            }

            .hidden+.hidden2 {
                display: none;
            }

            .hiddenway_two_action {
                display: none;
            }
        </style>
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Sale</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Sell</a></li>
                                <li class="breadcrumb-item active">Add Sale</li>
                            </ul>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="mb-3">
                            <div class="input-group local-forms">
                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                <select class="form-control form-select" aria-label="Example text with button addon"
                                    aria-describedby="button-addon1">
                                    <option selected disabled>Awesome Shop</option>
                                    <option>Awesome Shop</option>
                                </select>
                                <button class="btn btn-outline-primary" type="button" data-bs-toggle="modal"
                                    data-bs-target="#addModal" id="button-addon1"><i
                                        class="fas fa-plus-circle"></i></button>
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
                                <form class="px-3" action="#">
                                    <div class="row">
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <div class="input-group local-forms">
                                                    <span class="input-group-text" id="basic-addon1"><i
                                                            class="fas fa-user"></i></span>
                                                    <select class="form-control form-select"
                                                        aria-label="Example text with button addon"
                                                        aria-describedby="button-addon1">
                                                        <option selected disabled>Customer*</option>
                                                    </select>
                                                    <button class="btn btn-outline-primary" type="button"
                                                        data-bs-toggle="modal" data-bs-target="#addModal"
                                                        id="button-addon1"><i class="fas fa-plus-circle"></i></button>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="row g-0 text-center">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Pay term<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Pay term">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms days">
                                                            <label>Department<span class="login-danger"></span></label>
                                                            <select class="form-control form-select select">
                                                                <option selected disabled>Please Select</option>
                                                                <option>Per Month</option>
                                                                <option>Per Week</option>
                                                                <option>Per Day</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-3">
                                            <div class="form-group local-forms calendar-icon">
                                                <label>Purchase Date<span class="login-danger">*</span></label>
                                                <input class="form-control datetimepicker" type="text"
                                                    placeholder="DD-MM-YYYY">
                                            </div>
                                        </div>

                                    </div>

                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <b>Billing Address</b>
                                                <p>
                                                    Walk-In Customer,
                                                    Linking Street,
                                                    Phoenix, Arizona, USA
                                                </p>
                                                <b>Shipping Address</b>
                                                <p>
                                                    Walk-In Customer,
                                                </p>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group local-forms days">
                                                    <label>Status<span class="login-danger">*</span></label>
                                                    <select class="form-control form-select select">
                                                        <option selected disabled>Please Select </option>
                                                        <option>Final</option>
                                                        <option>Draft</option>
                                                        <option>Quatation</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group local-forms days">
                                                    <label>Invoice scheme<span class="login-danger">*</span></label>
                                                    <select class="form-control form-select select">
                                                        <option selected disabled>Default</option>
                                                        <option>Please Select</option>
                                                        <option>Draft</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                            </div>

                            <div class="row justify-content-end">
                                <div class="col-md-3">
                                    <div class="mb-3 mt-4">
                                        <div class="form-group local-forms">
                                            <label>Invoice No<span class="login-danger"></span></label>
                                            <input class="form-control" type="text"
                                                placeholder="Pay term">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <label>Attach Document</label>
                                    <div class="invoices-upload-btn">
                                        <input type="file" accept="image/*" name="image" id="file"
                                            class="hide-input">
                                        <label for="file" class="upload"><i class="far fa-folder-open">
                                                &nbsp;</i> Browse..</label>
                                    </div>
                                    <span>Max File size: 5MB
                                        Allowed File: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png</span>
                                </div>
                            </div>
                            </form>
                            <!-- Add other elements if needed -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row d-flex justify-content-center">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <div class="input-group">
                                            <span class="input-group-text" id="basic-addon1"><i
                                                    class="fas fa-search"></i></span>
                                            <input type="text" class="form-control"
                                                placeholder="Enter Product Name / SKU / Scan bar code" aria-label="Username"
                                                aria-describedby="basic-addon1">
                                                <button class="btn btn-outline-primary" type="button"
                                                id="button-addon1"><i class="fas fa-plus-circle"></i></button>
                                        </div>
                                    </div>
                                </div>
    
                                <!-- Add other elements if needed -->
                            </div>
                        </div>
    
                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%" id="example1">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Discount</th>
                                        <th>Tax</th>
                                        <th>Price inc.Tax</th>
                                        <th>Subtotoal</th>
                                        <th><i class="fas fa-window-close"></i></th>
                                    </tr>
                                </thead>
                                <tbody>
    
                                </tbody>
                            </table>
                        </div>
                        <hr>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="input-group local-forms">
                                            <span class="input-group-text" id="basic-addon1"><i class="fas fa-exclamation"></i></span>
                                            <select class="form-control form-select" aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                                <option selected disabled>Discount Type*</option>
                                                <option>Pecentage</option>
                                                <option>Fixed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="input-group local-forms">
                                            <span class="input-group-text" id="basic-addon1"><i class="fas fa-exclamation"></i></span>
                                            <select class="form-control form-select" aria-label="Example text with button addon"
                                                aria-describedby="button-addon1">
                                                <option selected disabled>Discount Amount</option>
                                                <option>Pecentage</option>
                                                <option>Fixed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                              
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <b>Discount Amount:</b>
                                        <p>(-) $ 0.00</p>
                                    </div>
                                </div>

                                <div class="row">
                                     <div class="col-md-4">
                                        <div class="mb-3">
                                            <div class="input-group local-forms">
                                                <span class="input-group-text" id="basic-addon1"><i class="fas fa-exclamation"></i></span>
                                                <select class="form-control form-select" aria-label="Example text with button addon"
                                                    aria-describedby="button-addon1">
                                                    <option selected disabled>Order Tax</option>
                                                    <option>None</option>
                                                    <option>Vat</option>
                                                </select>
                                            </div>
                                        </div>
                                     </div>
                                     <div class="col-md-4">
                                        <div class="mb-3">
                                            <b>DiscOrder Tax</b>
                                            <p>(+) $ 0.00</p>
                                        </div>
                                     </div>
                                </div>
                                <!-- Add other elements if needed -->
                            </div>
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mt-3">
                                        <div class="form-group local-forms">
                                            <label>Additional Notes <span class="login-danger"></span></label>
                                            <textarea class="form-control" id="edit_description" name="description" type="text"
                                                placeholder="Additional Notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <!-- Add other elements if needed -->
                            </div>
    
                        </div>
    
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card card-table">
                    <div class="card-body">
                        <div class="page-header">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Shipping Details <span class="login-danger"></span></label>
                                            <textarea class="form-control" id="edit_description" name="description" type="text"
                                                placeholder="Shipping Details"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Shipping Address <span class="login-danger"></span></label>
                                            <textarea class="form-control" id="edit_description" name="description" type="text"
                                                placeholder="Shipping Address"></textarea>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-money-bill-alt"></i></span>
                                        <input type="text" class="form-control" placeholder="Shipping Charges"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms days">
                                            <label>Shipping Status<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select </option>
                                                <option>Ordered</option>
                                                <option>Packed</option>
                                                <option>Shipped</option>
                                                <option>Delivered</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Delivered To<span class="login-danger"></span></label>
                                            <input class="form-control" type="text"
                                                placeholder="Delivered To">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms days">
                                            <label>Delivery Person<span class="login-danger"></span></label>
                                            <select class="form-control form-select select select2">
                                                <option selected disabled>Please Select </option>
                                                <option>Mr Admin</option>
                                                <option>Mr Demo Cashier</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <label>Shipping Documents</label>
                                    <div class="invoices-upload-btn">
                                        <input type="file" accept="image/*" name="image" id="file"
                                            class="hide-input">
                                        <label for="file" class="upload"><i class="far fa-folder-open">
                                                &nbsp;</i> Browse..</label>
                                    </div>
                                    <span>Max File size: 5MB
                                        Allowed File: .pdf, .csv, .zip, .doc, .docx, .jpeg, .jpg, .png</span>
                                </div>
                            </div>
                            <div class="row">
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-primary mt-xs-2px" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#moreinformation1" aria-expanded="false"
                                        aria-controls="collapseExample">
                                        Add additional expenses <i class="fas fa-sort-down "></i>
                                    </button>
                                </div>
    
                                <div>
                                    <div class="collapse" id="moreinformation1">
                                        <div class="student-group-form">
                                            <div class="row justify-content-end mt-5">
                                                <div class="col-8">
                                                    <div class="row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <label>Additional expense name<span
                                                                            class="login-danger"></span></label>
                                                                    <input class="form-control" type="text">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <label>Amount<span class="login-danger"></span></label>
                                                                    <input class="form-control" type="text"
                                                                        placeholder="0">
                                                                </div>
                                                            </div>
                                                        </div>
    
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <input class="form-control" type="text">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <input class="form-control" type="text"
                                                                        placeholder="0">
                                                                </div>
                                                            </div>
                                                        </div>
    
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <input class="form-control" type="text">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <input class="form-control" type="text"
                                                                        placeholder="0">
                                                                </div>
                                                            </div>
                                                        </div>
    
    
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <input class="form-control" type="text">
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-group local-forms">
                                                                    <input class="form-control" type="text"
                                                                        placeholder="0">
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row justify-content-end">
                                                <div class="col-3">
                                                    <b>Purchase Total:</b>
                                                    <p>$ 0.00</p>
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
        </div>

    </div>

    

    


  

    <div class="row">
        <div class="col-md-12">
            <div class="card card-table">
                <div class="card-body">
                    <div class="page-header">
                        <h5>Add Payment</h5>
                        <div class="row">
                            <div class="col-md-4">
                                <div class="input-group mb-3">
                                    <span class="input-group-text" id="basic-addon1"><i
                                            class="fas fa-money-bill-alt"></i></span>
                                    <input type="text" class="form-control" placeholder="Advance Balance"
                                        aria-label="Example text with button addon" aria-describedby="button-addon1">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group local-forms calendar-icon">
                                    <label>Purchase Date<span class="login-danger">*</span></label>
                                    <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <div class="input-group local-forms">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-user"></i></span>
                                        <select class="form-control form-select"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                            <option selected disabled>Payment Method</option>
                                            <option>Cash</option>
                                            <option>Advance</option>
                                            <option>Cheque</option>
                                            <option>Bank Transfer</option>
                                            <option>Other</option>
                                            <option>Custom Payment 1</option>
                                            <option>Custome Payment 2</option>
                                            <option>Custome Payment 3</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group local-forms">
                                    <span class="input-group-text" id="basic-addon1"><i class="fas fa-user"></i></span>
                                    <select class="form-control form-select" aria-label="Example text with button addon"
                                        aria-describedby="button-addon1">
                                        <option selected disabled>Payment Account</option>
                                        <option>None</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="mt-4">
                                    <div class="form-group local-forms">
                                        <label>Payment note<span class="login-danger"></span></label>
                                        <textarea class="form-control" id="edit_description" name="description" type="text" placeholder="Payment note"></textarea>
                                    </div>
                                </div>
                            </div>
                            <hr>
                        </div>
                        <div class="row justify-content-start">
                            <div class="col-4">
                                <b>Change Return</b>
                                <p>0.00</p>
                            </div>
                        </div>
                        <hr>
                        <div class="row justify-content-end">
                            <div class="col-4">
                                <b>Balance</b>
                                <p>0.00</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row justify-content-center gap-3">
                <div class="col-md-4 mb-3">
                    <button class="btn btn-primary btn-lg" type="button">Save</button>
                    <button class="btn btn-success btn-lg" type="button">Save</button>
                </div>
            </div>
        </div>
    </div>


    {{-- Add modal row --}}
    <div class="row">
        <div class="modal fade" id="addModal" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add a new contact</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="addAndEditForm" method="POST" action="">
                            <div class="row">

                                <div class="col-md-4 mt-xs-2px">
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                            id="inlineRadio1" value="option1"
                                            onclick="toggleLoginFields('inlineRadio1','.hidden','.hiddenway_two_action')">
                                        <label class="form-check-label" for="inlineRadio1">Individual</label>
                                    </div>
                                    <div class="form-check form-check-inline">
                                        <input class="form-check-input" type="radio" name="inlineRadioOptions"
                                            id="inlineRadio2" value="option2"
                                            onclick="toggleLoginFields2('inlineRadio2','.hidden','.hiddenway_two_action')">
                                        <label class="form-check-label" for="inlineRadio2">Business</label>
                                    </div>
                                    <div class="col"></div>
                                    <div class="col"></div>
                                </div>

                                <div class="col-md-4">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-address-book"></i></span>
                                        <input type="text" class="form-control" placeholder="Contact ID"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    </div>
                                    <span>Leave empty to autogenerate</span>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-md-3 hidden">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Prefix<span class="login-danger">*</span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Mr / Mrs / Miss</option>
                                                <option>Mr</option>
                                                <option>Mrs</option>
                                                <option>Ms</option>
                                                <option>Miss</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 hidden">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>First Name<span class="login-danger">*</span></label>
                                            <input class="form-control" type="text" placeholder="First Name">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 hidden">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Middle name<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Middle name">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3 hidden">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Last Name<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Last Name">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3 hiddenway_two_action">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-mobile-alt"></i></span>
                                        <input type="text" class="form-control" placeholder="Bussiness Name"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-mobile-alt"></i></span>
                                        <input type="text" class="form-control" placeholder="Mobile"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" placeholder="Alternate contact number"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-phone"></i></span>
                                        <input type="text" class="form-control" placeholder="Landline"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group mb-3">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-envelope"></i></span>
                                        <input type="text" class="form-control" placeholder="Email"
                                            aria-label="Example text with button addon" aria-describedby="button-addon1">
                                    </div>
                                </div>
                            </div>

                            <div class="row mt-4">
                                <div class="col-md-4 hidden">
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date Of Birth <span class="login-danger">*</span></label>
                                        <input class="form-control datetimepicker" type="text"
                                            placeholder="DD-MM-YYYY">
                                    </div>
                                </div>

                                <div class="col-md-5">
                                    <div class="input-group local-forms">
                                        <span class="input-group-text" id="basic-addon1"><i
                                                class="fas fa-user"></i></span>
                                        <select class="form-control form-select"
                                            aria-label="Example text with button addon" aria-describedby="basic-addon1">
                                            <option selected disabled>Assigned to</option>
                                            <option>Mr SuperUser</option>
                                            <option>Mr Ahshan</option>
                                            <option>Mr Afshan</option>
                                        </select>
                                    </div>
                                </div>


                            </div>

                            <div class="row">
                                <div class="d-flex justify-content-center">
                                    <button class="btn btn-primary mt-xs-2px" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#moreinformation1" aria-expanded="false"
                                        aria-controls="collapseExample">
                                        More Infomation <i class="fas fa-sort-down "></i>
                                    </button>
                                </div>

                                <div>
                                    <div class="collapse" id="moreinformation1">
                                        <div class="student-group-form">
                                            <hr>
                                            <div class="row mt-4">
                                                <div class="col-md-4">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="basic-addon1"><i
                                                                class="fas fa-address-book"></i></span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Tax number"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                    </div>
                                                </div>
                                                <div class="col-md-4">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="basic-addon1"><i
                                                                class="fas fa-address-book"></i></span>
                                                        <input type="text" class="form-control"
                                                            placeholder="Opening Balance"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                    </div>
                                                </div>

                                                <div class="col-md-4">
                                                    <div class="d-flex justify-content-between">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Pay term<span class="login-danger"></span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Pay term">
                                                            </div>
                                                        </div>

                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>Please Select</option>
                                                                    <option>Per Month</option>
                                                                    <option>Per Week</option>
                                                                    <option>Per Day</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr />
                                            <div class="row">
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Address line 1<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Address line 1">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Address line 2<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Address line 2">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="basic-addon1"><i
                                                                class="fas fa-address-book"></i></span>
                                                        <input type="text" class="form-control" placeholder="City"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="basic-addon1"><i
                                                                class="fas fa-address-book"></i></span>
                                                        <input type="text" class="form-control" placeholder="State"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="basic-addon1"><i
                                                                class="fas fa-address-book"></i></span>
                                                        <input type="text" class="form-control" placeholder="Country"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="input-group mb-3">
                                                        <span class="input-group-text" id="basic-addon1"><i
                                                                class="fas fa-address-book"></i></span>
                                                        <input type="text" class="form-control" placeholder="Zip Code"
                                                            aria-label="Example text with button addon"
                                                            aria-describedby="button-addon1">
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row">
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 1<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 1">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 2<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 2">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 3<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 3">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 4<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 4">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 5<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 5">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 6<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 6">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 7<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 7">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 8<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 8">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 9<span class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 9">
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-3">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Custom Field 10<span
                                                                    class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Custom Field 10">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <hr>
                                            <div class="row justify-content-center">
                                                <div class="col-md-8">
                                                    <div class="mb-3">
                                                        <div class="form-group local-forms">
                                                            <label>Shipping Address<span
                                                                    class="login-danger"></span></label>
                                                            <input class="form-control" type="text"
                                                                placeholder="Shipping Address">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary">Save changes</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>

                </div>
            </div>
        </div>
    </div>
    {{-- Edit modal row --}}

    {{-- Add modal row --}}
    <div class="row">
        <div class="modal fade" id="new_product" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h1 class="modal-title fs-5" id="exampleModalLabel">Add new product</h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card card-table">
                                    <div class="card-body">
                                        <div class="page-header">
                                            <div class="row align-items-center">
                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Product Name <span
                                                                        class="login-danger">*</span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="Product Name">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>SKU <span class="login-danger">*</span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="SKU">
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms days">
                                                                <label>Barcode Type<span
                                                                        class="login-danger">*</span></label>
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>Please Select
                                                                    </option>
                                                                    <option>Code 128(C128)</option>
                                                                    <option>Code 39(C39)</option>
                                                                    <option>EAN -8</option>
                                                                    <option>EAN -A</option>
                                                                    <option>EAN -E</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="input-group local-forms">
                                                                <label>Unit<span class="login-danger">*</span></label>
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>Unit</option>
                                                                    <option>Pieces(Pcs)</option>
                                                                    <option>Packets(pck)</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="input-group local-forms">
                                                                <label>Brand<span class="login-danger"></span></label>
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>Brand</option>
                                                                    <option>Acer</option>
                                                                    <option>Apple</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms days">
                                                                <label>Category<span class="login-danger"></span></label>
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>Please Select
                                                                    </option>
                                                                    <option>Books</option>
                                                                    <option>Electronics</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms days">
                                                                <label>Sub category<span
                                                                        class="login-danger"></span></label>
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>Please Select
                                                                    </option>
                                                                    <option>...</option>
                                                                    <option>...</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group">
                                                                <label>Business Locations <span
                                                                        class="star-red">*</span></label>
                                                                <input type="text" data-role="tagsinput"
                                                                    class="input-tags form-control"
                                                                    placeholder="Meta Keywords" name="services"
                                                                    value="Lorem,Ipsum" id="services">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group">
                                                                <label>Business Locations <span
                                                                        class="star-red">*</span></label>
                                                                <select class="form-control input-tags form-select select"
                                                                    data-role="tagsinput" id="services">
                                                                    <option selected disabled>Please Select Business
                                                                        Locations </option>
                                                                    <option>Kalmunai</option>
                                                                    <option>Colombo</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-check ms-3">
                                                                <input class="form-check-input" type="checkbox" checked
                                                                    id="isActive"
                                                                    onclick="toggleLoginFields2(id,null,'.hidden2')">
                                                                <label class="form-check-label" for="isActive">
                                                                    Enable stock management at product level
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms hidden2">
                                                                <label>Alert Quantity<span
                                                                        class="login-danger">*</span></label>
                                                                <input class="form-control" type="text"
                                                                    placeholder="0">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-8">
                                                        <div id="summernote"></div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-check ms-3">
                                                                <input class="form-check-input" type="checkbox"
                                                                    value="" id="Enable_Product_description?">
                                                                <label class="form-check-label"
                                                                    for="Enable_Product_description?">
                                                                    Enable Product description, IMEI or Serial Number
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-check ms-3">
                                                                <input class="form-check-input" type="checkbox"
                                                                    value="" id="Not_for_selling?">
                                                                <label class="form-check-label" for="Not_for_selling?">
                                                                    Not for selling
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-check ms-3">
                                                                <input class="form-check-input" type="checkbox"
                                                                    value="" id="Disable_Woocommerce_Sync?">
                                                                <label class="form-check-label"
                                                                    for="Disable_Woocommerce_Sync?">
                                                                    Disable Woocommerce Sync
                                                                </label>
                                                            </div>
                                                        </div>

                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="input-group local-forms">
                                                                <label>Applicable Tax<span
                                                                        class="login-danger"></span></label>
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>None</option>
                                                                    <option>VAT@10%</option>
                                                                    <option>CGST@10%</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="input-group local-forms">
                                                                <label>Selling Price Tax Type<span
                                                                        class="login-danger">*</span></label>
                                                                <select class="form-control form-select select">
                                                                    <option selected disabled>Exclusive</option>
                                                                    <option>Inclusive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="row">
                                                    <div class="col-md-12">
                                                        <div class="table-responsive">
                                                            <table class="table table-bordered">
                                                                <thead class="table-success">
                                                                    <tr>
                                                                        <th scope="col">Default Purchase Price</th>
                                                                        <th scope="col">x Margin(%) </th>
                                                                        <th scope="col">Default Selling Price</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <tr>
                                                                        <td>
                                                                            <div class="row">
                                                                                <div class="col-sm-6">
                                                                                    <div class="form-group">
                                                                                        <label>Exc. tax:*</label>
                                                                                        <input type="text"
                                                                                            class="form-control">
                                                                                    </div>
                                                                                </div>
                                                                                <div class="col-sm-6">
                                                                                    <div class="form-group">
                                                                                        <label>Inc. tax:*</label>
                                                                                        <input type="text"
                                                                                            class="form-control">
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="form-group">
                                                                                <label>&nbsp;</label>
                                                                                <input type="text"
                                                                                    class="form-control">
                                                                            </div>
                                                                        </td>
                                                                        <td>
                                                                            <div class="form-group">
                                                                                <label>Exc. Tax</label>
                                                                                <input type="text"
                                                                                    class="form-control">
                                                                            </div>
                                                                        </td>
                                                                    </tr>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Add other elements if needed -->
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Save changes</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
        {{-- Edit modal row --}}

        {{-- modal --}}
        <div class="row">
            <div class="modal fade" id="ImportProduct" tabindex="-1" aria-labelledby="exampleModalLabel"
                aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h1 class="modal-title fs-5" id="exampleModalLabel">Import Products</h1>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>

                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-12">
                                    <label>Product image</label>
                                    <div class="invoices-upload-btn">
                                        <input type="file" accept="image/*" name="image" id="file"
                                            class="hide-input">
                                        <label for="file" class="upload"><i class="far fa-folder-open">
                                                &nbsp;</i> Browse..</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <button type="button" class="btn btn-outline-success mt-2"><i
                                                class="fas fa-download"></i> &nbsp; Download template file</button>
                                    </div>
                                </div>
                            </div>

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
                                                                <b>Follow the instructions carefully before importing
                                                                    the
                                                                    file.</b>
                                                                <p>The columns of the file should be in the following
                                                                    order.
                                                                </p>
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
                                                                            <td>SKU (Required)</td>
                                                                            <td></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th scope="row">2</th>
                                                                            <td>Purchase Quantity (Required)</td>
                                                                            <td></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th scope="row">3</th>
                                                                            <td>Unit Cost (Before Discount) (Optional)
                                                                            </td>
                                                                            <td></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th scope="row">4</th>
                                                                            <td>Discount Percent (Optional)</td>
                                                                            <td></td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th scope="row">5</th>
                                                                            <td>Product Tax (Optional)</td>
                                                                            <td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th scope="row">6</th>
                                                                            <td>Lot Number (Optional)</td>
                                                                            <td>Only if Lot number is enabled. You can
                                                                                enable Lot number from
                                                                                Business Settings > Purchases > Enable
                                                                                Lot
                                                                                number</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th scope="row">7</th>
                                                                            <td>MFG Date (Optional)</td>
                                                                            <td>Only if Product Expiry is enabled. You
                                                                                can
                                                                                enable Product expiry from
                                                                                Business Settings > Product > Enable
                                                                                Product
                                                                                Expiry
                                                                                Format: yyyy-mm-dd; Ex: 2021-11-25</td>
                                                                        </tr>
                                                                        <tr>
                                                                            <th scope="row">8</th>
                                                                            <td>EXP Date (Optional)</td>
                                                                            <td>Only if Product Expiry is enabled. You
                                                                                can
                                                                                enable Product expiry from
                                                                                Business Settings > Product > Enable
                                                                                Product
                                                                                Expiry
                                                                                Format: yyyy-mm-dd; Ex: 2021-11-25</td>
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

                            <div class="row">

                                <div class="modal-footer">
                                    <button type="submit" class="btn btn-primary">Import</button>
                                    <button type="button" class="btn btn-secondary"
                                        data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>


        <script>
            function toggleLoginFields(propertyId, actionClass, HidingClass) {
                var checkBox = document.getElementById(propertyId);
                var loginFields = document.querySelectorAll(actionClass);
                var hideclassFiled = document.querySelectorAll(HidingClass);

                loginFields.forEach(function(field) {
                    field.style.display = checkBox.checked ? "block" : "none";

                });

                hideclassFiled.forEach(function(field) {
                    fiel.style.display = 'none';
                })
            }

            function toggleLoginFields2(propertyId, actionClass, displayClass) {
                var checkBox = document.getElementById(propertyId);
                var loginFields = document.querySelectorAll(actionClass);
                var specificFieldVisible = document.querySelectorAll(displayClass);

                loginFields.forEach(function(field) {
                    field.style.display = checkBox.checked ? "none" : "block";
                });

                specificFieldVisible.forEach(function(field) {
                    field.style.display = checkBox.checked ? "block" : "none";
                })
            }
        </script>
    @endsection
