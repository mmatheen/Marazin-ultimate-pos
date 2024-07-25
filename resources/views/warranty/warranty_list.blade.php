@extends('layout.layout')
@section('content')
    <div class="content container-fluid">

        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Warranties</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Warranties</a></li>
                                <li class="breadcrumb-item active">List Warranties</li>
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
                                        <i class="fas fa-plus px-2"> </i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="datatable table table-stripped" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>name</th>
                                        <th>Description</th>
                                        <th>Duration</th>
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
                <div class="modal-dialog  modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body">
                            <div class="text-center mt-2 mb-4">
                                <h5>Add Warranty</h5>
                            </div>
                            <form class="px-3" action="#">
                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Name <span class="login-danger">*</span></label>
                                        <input class="form-control" type="text" placeholder="Enter Name">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="form-group local-forms">
                                        <label>Description <span class="login-danger">*</span></label>
                                        <textarea class="form-control" type="text" placeholder="Enter Description"></textarea>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <div class="row">
                                        <div class="col">
                                            <div class="form-group local-forms">
                                                <label>Duration <span class="login-danger">*</span></label>
                                                <input class="form-control" type="number" placeholder="Enter Duration">
                                            </div>
                                        </div>
                                        <div class="col">
                                            <div class="form-group local-forms days">
                                                <label>Period<span class="login-danger">*</span></label>
                                                <select class="form-control form-select">
                                                    <option selected disabled>Please Select </option>
                                                    <option>Days</option>
                                                    <option>Months</option>
                                                    <option>Years</option>
                                                </select>
                                            </div>
                                        </div>

                                    </div>

                                </div>

                                <div class="modal-footer">
                                    <button type="button" class="btn btn-primary">Save changes</button>
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
@endsection



{{--

<div class="row">
    <div class="col-sm-12">
        <div class="card comman-shadow">
            <div class="card-body">
                <form>
                    <div class="row">
                        <div class="col-12">
                            <h5 class="form-title student-info">Student Information <span><a
                                        href="javascript:;"><i
                                            class="feather-more-vertical"></i></a></span></h5>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>First Name <span class="login-danger">*</span></label>
                                <input class="form-control" type="text" placeholder="Enter First Name">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Last Name <span class="login-danger">*</span></label>
                                <input class="form-control" type="text" placeholder="Enter First Name">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Gender <span class="login-danger">*</span></label>
                                <select class="form-control select">
                                    <option>Select Gender</option>
                                    <option>Female</option>
                                    <option>Male</option>
                                    <option>Others</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms calendar-icon">
                                <label>Date Of Birth <span class="login-danger">*</span></label>
                                <input class="form-control datetimepicker" type="text"
                                    placeholder="DD-MM-YYYY">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Roll </label>
                                <input class="form-control" type="text" placeholder="Enter Roll Number">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Blood Group <span class="login-danger">*</span></label>
                                <select class="form-control select">
                                    <option>Please Select Group </option>
                                    <option>B+</option>
                                    <option>A+</option>
                                    <option>O+</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Religion <span class="login-danger">*</span></label>
                                <select class="form-control select">
                                    <option>Please Select Religion </option>
                                    <option>Hindu</option>
                                    <option>Christian</option>
                                    <option>Others</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>E-Mail <span class="login-danger">*</span></label>
                                <input class="form-control" type="text"
                                    placeholder="Enter Email Address">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Class <span class="login-danger">*</span></label>
                                <select class="form-control select">
                                    <option>Please Select Class </option>
                                    <option>12</option>
                                    <option>11</option>
                                    <option>10</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Section <span class="login-danger">*</span></label>
                                <select class="form-control select">
                                    <option>Please Select Section </option>
                                    <option>B</option>
                                    <option>A</option>
                                    <option>C</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Admission ID </label>
                                <input class="form-control" type="text"
                                    placeholder="Enter Admission ID">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group local-forms">
                                <label>Phone </label>
                                <input class="form-control" type="text"
                                    placeholder="Enter Phone Number">
                            </div>
                        </div>
                        <div class="col-12 col-sm-4">
                            <div class="form-group students-up-files">
                                <label>Upload Student Photo (150px X 150px)</label>
                                <div class="uplod">
                                    <label class="file-upload image-upbtn mb-0">
                                        Choose File <input type="file">
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="col-12">
                            <div class="student-submit">
                                <button type="submit" class="btn btn-primary">Submit</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div> --}}
