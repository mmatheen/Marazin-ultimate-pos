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
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-outline-info " id="addVariationButton">
                                New  <i class="fas fa-plus px-2"> </i>
                              </button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="variation" class="datatable table table-stripped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
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
        <div id="addAndEditVariationModal" class="modal fade" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-body">
                        <div class="text-center mt-2 mb-4">
                            <h5 id="modalTitle"></h5>
                        </div>
                        <form id="addAndUpdateForm">

                            <input type="hidden" name="edit_id" id="edit_id">

                            <div class="mb-3">
                                <div class="form-group local-forms">
                                    <label>Variation Title<span class="login-danger">*</span></label>
                                    <select id="edit_variation_title" name="variation_title_id[]" class="form-control form-select">
                                        <option selected disabled>Please Select </option>
                                        @foreach($variationTitles as $variationTitle)
                                        <option value="{{ $variationTitle->id }}">{{ $variationTitle->variation_title }}</option>
                                        @endforeach
                                    </select>
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
                                                        <input class="form-control" id="edit_variation_value"type="text" name="variation_value[]" placeholder="Variation Name"> <!-- Add name attribute -->
                                                    </div>
                                                </div>
                                                <div class="col col-md-2">
                                                    <button type="button" class="btn add-links"><i class="fas fa-plus px-2"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div id="edit_variations_container"></div> <!-- Container for dynamic fields -->

                            <div class="modal-footer">
                                <button type="submit" id="modalButton" class="btn btn-outline-primary">Save</button>
                                <button type="button" class="btn btn-outline-danger" data-bs-dismiss="modal">Close</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
     {{-- Delete modal --}}
     <div id="deleteModal" class="modal custom-modal fade" role="dialog">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="form-header">
                        <h3 id="deleteName"></h3>
                        <p>Are you sure want to delete?</p>
                    </div>
                    <div class="modal-btn delete-action">
                        <div class="row">
                            <input type="hidden" id="deleting_id">
                            <div class="row">
                                <div class="col-6">
                                    <button type="submit" class="confirm_delete_btn btn btn-primary paid-continue-btn" style="width: 100%;">Delete</button>
                                </div>
                                <div class="col-6">
                                    <a data-bs-dismiss="modal" class="btn btn-primary paid-cancel-btn">Cancel
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

{{-- this code for add new row and remove --}}
<script>
    $(".settings-form").on("click", ".trash", function() {
        $(this).closest(".links-cont").remove();
        return false;
    });
    $(document).on("click", ".add-links", function() {
        var experiencecontent =
            `<div class="row form-row links-cont">
                    <div class="form-group d-flex">
                    <input type="text" name="variation_value[]" class="form-control" placeholder="">
                    <div><a href="#" class="btn trash"><i class="feather-trash-2"></i></a></div>
                    </div>
                    </div>`
        $(".settings-form").append(experiencecontent);
        return false;
    });

</script>

@include('variation.variation.variation_ajax')
@endsection
