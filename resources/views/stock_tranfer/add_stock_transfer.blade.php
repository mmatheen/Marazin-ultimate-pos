@extends('layout.layout')
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Add Stock Transfer</h3>

                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="students.html">Stock Transfer</a></li>
                                <li class="breadcrumb-item active">Add Stock Transfer</li>
                            </ul>
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
                                    <div class="form-group local-forms calendar-icon">
                                        <label>Date<span class="login-danger">*</span></label>
                                        <input class="form-control datetimepicker" type="text" placeholder="DD-MM-YYYY">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Reference No<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="Reference No">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-5">
                                        <div class="form-group local-forms">
                                            <label>Status<span class="login-danger">*</span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select </option>
                                                <option>Pending</option>
                                                <option>In Transit</option>
                                                <option>Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Location (From)<span class="login-danger">*</span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select </option>
                                                <option>Awesome Shop1</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Location (To)<span class="login-danger">*</span></label>
                                            <select class="form-control form-select select">
                                                <option selected disabled>Please Select </option>
                                                <option>Awesome Shop2</option>
                                            </select>
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
                            <div class="row align-items-center">
                                <form class="px-3" action="#">
                                    <div class="row d-flex justify-content-center">
                                        <div class="col-md-8">
                                            <div class="input-group flex-nowrap my-5">
                                                <button class="btn btn-outline-primary add-btn" type="button"
                                                    id="basic-addon1"><i class="fas fa-search"></i></button>
                                                <select class="form-control select2Box form-select"
                                                    aria-describedby="basic-addon1">
                                                    <option selected disabled>Category</option>
                                                    <option>Books</option>
                                                    <option>Electronics</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-12">
                                            <div class="table-responsive">
                                                <table class="table table-bordered">
                                                    <thead class="table-success">
                                                        <tr>
                                                            <th scope="col">Product</th>
                                                            <th scope="col">Quantity</th>
                                                            <th scope="col">Unit Price</th>
                                                            <th scope="col">Subtotal</th>
                                                            <th scope="col"><i class="fas fa-trash"></i></th>
                                                        </tr>
                                                    </thead>
                                                    <tbody class="add-table-items">
                                                        <!-- Initial empty total row -->
                                                        <tr>
                                                            <td colspan="2"></td>
                                                            <td></td>
                                                            <td colspan="2">Total : 0.00</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </form>
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
                                        <div class="form-group local-forms hidden">
                                            <label>Shipping Charges<span class="login-danger"></span></label>
                                            <input class="form-control" type="text" placeholder="0">
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <div class="form-group local-forms">
                                            <label>Additional Notes<span class="login-danger"></span></label>
                                            <textarea class="form-control" type="text" placeholder="Additional Notes"></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4">
            <div class="gap-4 d-flex justify-content-center">
                <div>
                    <button class="btn btn-primary btn-lg">Save</button>
                </div>
            </div>
        </div>
    </div>

    <script type="module">
        $(document).on("click", ".remove-btn", function() {
            $(this).closest(".add-row").remove();
            updateTotalRow();
            return false;
        });

        $(document).on("click", ".add-btn", function() {
            var experiencecontent =
                `<tr class="add-row">
                    <td>
                        <div class="row">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label>Acer Aspire E 15 (Color:Black) AS0017-1</label>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="col-12">
                            <div class="row">
                                <div class="col">
                                    <div class="form-group">
                                        <input type="text" class="form-control">
                                    </div>
                                </div>
                                <div class="col">
                                    <div class="input-group local-forms mb-3">
                                        <select class="form-control form-select select" aria-label="Example text with button addon" aria-describedby="button-addon1">
                                            <option selected disabled>Pieces</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="3,850.00">
                        </div>
                    </td>
                    <td>
                        <div class="form-group">
                            <input type="text" class="form-control" placeholder="3,850.00">
                        </div>
                    </td>
                    <td class="add-remove text-end">
                        <a href="javascript:void(0);" class="remove-btn"><i class="fas fa-trash"></i></a>
                    </td>
                </tr>`;

            $(".add-table-items").find("tr:last").remove(); // Remove the existing total row
            $(".add-table-items").append(experiencecontent); // Append the new row
            addTotalRow(); // Add the total row again at the end
            deleteTotalRow(); // delete the total row again at the end
            return false;
        });

        function addTotalRow() {
            var totalRow =
                `<tr>
                    <td colspan="2"></td>
                    <td></td>
                    <td colspan="2">Total : 0.00</td>
                </tr>`;
            $(".add-table-items").append(totalRow);
        }

        function deleteTotalRow() {
            var totalRow =
                `<tr>
                    <td colspan="2"></td>
                    <td></td>
                    <td colspan="2">Total : 0.00</td>
                </tr>`;
            $(".remove-btn").remove(totalRow);
        }
    </script>
@endsection
