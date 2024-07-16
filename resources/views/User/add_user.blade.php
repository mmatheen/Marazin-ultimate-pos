@extends('layout.layout')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add new User</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Users</a></li>
                                <li class="breadcrumb-item active">Add new User</li>
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
                                <form class="px-3" action="#">
                                    <div class="row">
                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
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

                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>First Name<span class="login-danger">*</span></label>
                                                    <input class="form-control" type="text" placeholder="First Name">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Last Name<span class="login-danger">*</span></label>
                                                    <input class="form-control" type="text" placeholder="Last Name">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-5">
                                            <div class="mb-3">
                                                <div class="form-group local-forms">
                                                    <label>Email<span class="login-danger">*</span></label>
                                                    <input class="form-control" type="text" placeholder="Email">
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-2">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="isActive">
                                                    <label class="form-check-label" for="isActive">
                                                        Is active?
                                                    </label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" value="" id="enableServiceStaffPin">
                                                    <label class="form-check-label" for="enableServiceStaffPin">
                                                        Enable service staff pin
                                                    </label>
                                                </div>
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
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="mb-4">Roles and Permissions</h5>
                                        <div class="col-md-4">
                                            <div class="mb-3">
                                                <div class="form-group">
                                                    <input class="form-check-input" type="checkbox" value="" id="allowLoginCheckbox">
                                                    <label class="form-check-label" for="allowLoginCheckbox" onclick="toggleLoginFields()">
                                                        Allow login
                                                    </label>

                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 login-fields">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Username<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Username">
                                                <span>Leave blank to auto generate username</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 login-fields">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Password<span class="login-danger">*</span></label>
                                                <input class="form-control" type="password" placeholder="Password">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4 login-fields">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Confirm Password<span class="login-danger">*</span></label>
                                                <input class="form-control" type="password" placeholder="Confirm Password">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group local-forms days">
                                            <label>Role<span class="login-danger">*</span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select</option>
                                                <option>Admin</option>
                                                <option>Super User</option>
                                                <option>Cashier</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        Access locations
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input class="form-check-input" type="checkbox" value="" id="allowLogin">
                                            <label class="form-check-label" for="allowLogin">
                                                All Locations
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input class="form-check-input" type="checkbox" value="" id="awesomeShop">
                                            <label class="form-check-label" for="awesomeShop">
                                                Awesome Shop
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
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
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="mb-4">Sales</h5>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Sales Commission Percentage (%)<span
                                                        class="login-danger"></span></label>
                                                <input class="form-control" type="text"
                                                    placeholder="Sales Commission Percentage (%)">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Max sales discount percent<span class="login-danger"></span></label>
                                                <input class="form-control" type="text"
                                                    placeholder="Max sales discount percent">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        Access locations
                                    </div>
                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input class="form-check-input" type="checkbox" value="" id="allowLogin1">
                                            <label class="form-check-label" for="allowLogin1">
                                                All Locations
                                            </label>
                                        </div>
                                    </div>

                                    <div class="col-md-2">
                                        <div class="form-group">
                                            <input class="form-check-input" type="checkbox" value="" id="awesomeShop2">
                                            <label class="form-check-label" for="awesomeShop2">
                                                Awesome Shop
                                            </label>
                                        </div>
                                    </div>
                                </div>

                            </div>
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
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="mb-4">More Informations</h5>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms calendar-icon">
                                            <label>Date Of Birth <span class="login-danger">*</span></label>
                                            <input class="form-control datetimepicker" type="text"
                                                placeholder="DD-MM-YYYY">
                                        </div>
                                    </div>


                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Gender<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select</option>
                                                <option>Male</option>
                                                <option>Femal</option>
                                                <option>Other</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="form-group local-forms">
                                            <label>Marital Status<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Marital Status</option>
                                                <option>Married</option>
                                                <option>Unmarried</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Blood Group<span class="login-danger"></span></label>
                                                <select class="form-control form-select select">
                                                    <option selected disabled>A/B/O/AB</option>
                                                    <option>A+</option>
                                                    <option>A-</option>
                                                    <option>B+</option>
                                                    <option>B-</option>
                                                    <option>O+</option>
                                                    <option>O-</option>
                                                    <option>AB+</option>
                                                    <option>AB-</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Mobile Number<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Mobile Number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Alternate contact number<span class="login-danger"></span></label>
                                                <input class="form-control" type="text"
                                                    placeholder="Alternate contact number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Family contact number<span class="login-danger"></span></label>
                                                <input class="form-control" type="text"
                                                    placeholder="Family contact number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Facebook Link<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Facebook Link">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Twitter Link<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Twitter Link">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Social Media 1<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Social Media 1">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Social Media 2<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Social Media 2">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Custom field 1<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Custom field 1">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Custom field 2<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Custom field 2">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Custom field 3<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Custom field 3">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Custom field 4<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Custom field 4">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Guardian Name<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="Guardian Name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>ID proof name<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="ID proof name">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-3">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>ID proof number<span class="login-danger"></span></label>
                                                <input class="form-control" type="text" placeholder="ID proof number">
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Permanent Address<span class="login-danger"></span></label>
                                                <textarea class="form-control" type="text" placeholder="Permanent Address"></textarea>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <div class="form-group local-forms">
                                                <label>Current Address<span class="login-danger"></span></label>
                                                <textarea class="form-control" type="text" placeholder="Current Address"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="col-md-12">
                                    <h5 class="mb-4">Bank Details</h5>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Account Holder's Name<span class="login-danger"></span></label>
                                            <input class="form-control" type="text"
                                                placeholder="Account Holder's Name">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Account Number<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Account Number">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Bank Name<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Bank Name">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Bank Identifier Code<span class="login-danger"></span></label>
                                            <input class="form-control" type="text"
                                                placeholder="Bank Identifier Code">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Branch<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Branch">
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Tax Payer ID<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Tax Payer ID">
                                        </div>
                                    </div>
                                </div>
                            </div>
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
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="mb-4">HRM Details</h5>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group local-forms days">
                                            <label>Department<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="form-group local-forms days">
                                            <label>Designatio<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                            </div>
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
                            <div class="row align-items-center">
                                <div class="row">
                                    <div class="col-md-12">
                                        <h5 class="mb-4">Payroll</h5>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group local-forms days">
                                            <label>Primary work location<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select</option>
                                                <option>Awesome Shop</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-md-5">
                                        <div class="row g-0 text-center">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <div class="form-group local-forms">
                                                        <label>Basic salary<span class="login-danger"></span></label>
                                                        <input class="form-control" type="text"
                                                            placeholder="Basic salary">
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

                                    <div class="col-md-4">
                                        <div class="form-group local-forms days">
                                            <label>Primary work location<span class="login-danger"></span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select</option>
                                                <option>Awesome Shop</option>
                                            </select>
                                        </div>
                                    </div>

                                </div>

                            </div>
                            <!-- Add other elements if needed -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">Save</button>
        </form>
    </div>

    </div>

    <script>
        function toggleLoginFields() {
            var checkBox = document.getElementById("allowLoginCheckbox");
            var loginFields = document.querySelectorAll(".login-fields");

            loginFields.forEach(function(field) {
                field.style.display = checkBox.checked ? "none" : "block";
            });
        }
    </script>
@endsection
