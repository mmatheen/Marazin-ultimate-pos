<div class="row">
    <div class="modal fade" id="new_purchase_product" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h1 class="modal-title fs-5" id="exampleModalLabel">Add new product</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addForm">
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
                                                                <input class="form-control" id="edit_product_name"
                                                                    name="product_name" type="text"
                                                                    placeholder="Product Name" autocomplete="off">
                                                                <span class="text-danger"
                                                                    id="product_name_error"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="form-group local-forms">
                                                                <label>Product SKU</label>
                                                                <input class="form-control" id="edit_sku"
                                                                    name="sku" type="text"
                                                                    placeholder="Product SKU" autocomplete="off">
                                                                <span class="text-danger" id="sku_error"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3 ">
                                                            <div class="input-group local-forms d-flex ">
                                                                <label>Product Unit <span
                                                                        class="login-danger">*</span></label>
                                                                <select class="form-control form-select select"
                                                                    id="edit_unit_id" name="unit_id">
                                                                    <option>Select Unit</option>
                                                                </select>
                                                                <button type="button" class="btn btn-outline-info"
                                                                    id="addUnitButton">
                                                                    <i class="fas fa-plus-circle"></i>
                                                                </button>
                                                            </div>
                                                            <span class="text-danger" id="unit_id_error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3 ">
                                                            <div class="input-group local-forms d-flex">
                                                                <label>Product Brand <span
                                                                        class="login-danger">*</span></label>
                                                                <select class="form-control form-select select"
                                                                    id="edit_brand_id" name="brand_id">
                                                                    <option>Select Brand</option>
                                                                </select>
                                                                <button type="button" class="btn btn-outline-info "
                                                                    id="addBrandButton">
                                                                    <i class="fas fa-plus-circle"></i>
                                                                </button>
                                                            </div>
                                                            <span class="text-danger" id="brand_id_error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3">
                                                            <div class="input-group local-forms d-flex">
                                                                <label>Main Category <span
                                                                        class="login-danger">*</span></label>
                                                                <select class="form-control form-select select"
                                                                    id="edit_main_category_id" name="main_category_id">
                                                                </select>
                                                                <button type="button" class="btn btn-outline-info"
                                                                    id="addMainCategoryButton">
                                                                    <i class="fas fa-plus-circle"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mb-3 ">
                                                            <div class="input-group local-forms d-flex">
                                                                <label>Sub Category</label>
                                                                <select class="form-control form-select select"
                                                                    id="edit_sub_category_id" name="sub_category_id">
                                                                </select>
                                                                <button type="button" class="btn btn-outline-info"
                                                                    id="addSubCategoryButton">
                                                                    <i class="fas fa-plus-circle"></i>
                                                                </button>
                                                            </div>
                                                            <span class="text-danger" id="sub_category_id_error"></span>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4">
                                                        <div class="mt-4 mb-3">
                                                            <div class="input-group local-forms">
                                                                <label>Business Locations<span
                                                                        class="login-danger">*</span></label>
                                                                <select
                                                                    class="form-control form-select multiple-location"
                                                                    id="edit_location_id" name="locations[]"
                                                                    multiple="multiple">
                                                                </select>
                                                                <span class="text-danger" id="locations_error"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <script>
                                                        $(document).ready(function() {
                                                            $('.multiple-location').select2({
                                                                placeholder: "Select Business Locations",
                                                                allowClear: true,
                                                                dropdownParent: $('#new_purchase_product'),
                                                                width: '100%'
                                                            });
                                                        });
                                                    </script>
                                                </div>

                                                <div class="mt-3 row">
                                                    <div class="col-md-4">
                                                        <div class="mb-5">
                                                            <div class="form-check">
                                                                <!-- Hidden input to submit 0 when checkbox is unchecked -->
                                                                <input type="hidden" name="stock_alert"
                                                                    value="1">
                                                                <input class="form-check-input" name="stock_alert"
                                                                    type="checkbox" id="edit_stock_alert"
                                                                    value="1" checked disabled>
                                                                <label class="form-check-label" for="edit_stock_alert"
                                                                    id="stock_alert_label">
                                                                    Manage Stock?
                                                                </label>
                                                                <span class="text-danger"
                                                                    id="stock_alert_error"></span>
                                                            </div>
                                                        </div>
                                                    </div>

                                                    <div class="col-md-4" id="alert_quantity_container">
                                                        <div class="mb-5">
                                                            <div class="form-group local-forms">
                                                                <label>Alert Quantity<span
                                                                        class="login-danger"></span></label>
                                                                <input class="form-control" type="number"
                                                                    id="edit_alert_quantity" name="alert_quantity"
                                                                    placeholder="0">
                                                                <span class="text-danger"
                                                                    id="alert_quantity_error"></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <script>
                                                    $(document).ready(function() {
                                                        // Since checkbox is disabled and always checked, show alert quantity container
                                                        $('#alert_quantity_container').show();
                                                        $('#stock_alert_label').text('Manage Stock?');
                                                        $('#openingStockAndProduct').prop('disabled', false);
                                                    });
                                                </script>


                                                <div class="row">
                                                    <div class="col-md-8">
                                                        {{-- <div id="summernote" name="description"></div> --}}
                                                        <textarea id="summernote" name="description"></textarea>
                                                    </div>
                                                    <div class="col-md-4">
                                                        <div class="row">
                                                            <div class="col-md-12">
                                                                <label>Product image</label>
                                                                <div class="invoices-upload-btn">
                                                                    <input type="file" accept="image/*"
                                                                        name="product_image" id="file"
                                                                        class="hide-input show-picture">
                                                                    <label for="file" class="upload"><i
                                                                            class="far fa-folder-open"></i>&nbsp;Browse..</label>
                                                                </div>
                                                                <span>Max File size: 5MB</span>
                                                            </div>

                                                            <div class="my-4 col-md-12 d-flex justify-content-center">
                                                                <img id="product-selectedImage"
                                                                    src="/assets/images/No Product Image Available.png"
                                                                    alt="Selected Image" width="200px"
                                                                    class="img-thumbnail" height="200px">
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
                                                    <div class="mt-2 row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-check ms-3">
                                                                    <input type="hidden" name="is_imei_or_serial_no"
                                                                        value="0">
                                                                    <input class="form-check-input"
                                                                        name="is_imei_or_serial_no" type="checkbox"
                                                                        value="1"
                                                                        id="Enable_Product_description">
                                                                    <label class="form-check-label"
                                                                        for="Enable_Product_description">
                                                                        Enable Product description, IMEI or Serial
                                                                        Number
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="form-check ms-3">
                                                                    <input type="hidden" name="is_for_selling"
                                                                        value="0">
                                                                    <input class="form-check-input"
                                                                        name="is_for_selling" type="checkbox"
                                                                        value="1" id="Not_for_selling">
                                                                    <label class="form-check-label"
                                                                        for="Not_for_selling">
                                                                        Not for selling
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="mt-4 row">
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="input-group local-forms">
                                                                    <select class="form-control form-select select"
                                                                        id="edit_product_type" name="product_type"
                                                                        aria-label="Example text with button addon"
                                                                        aria-describedby="button-addon1">
                                                                        <option selected disabled>Product Type</option>
                                                                        <option value="Box">Box</option>
                                                                        <option value="Bundle">Bundle</option>
                                                                        <option value="Case">Case</option>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="mb-3">
                                                                <div class="mb-3">
                                                                    <div class="form-group local-forms">
                                                                        <label>Pax<span
                                                                                class="login-danger"></span></label>
                                                                        <input class="form-control" id="edit_pax"
                                                                            name="pax" type="number"
                                                                            placeholder="0">
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
                                                <div class="row align-items-center">

                                                    <div class="row">
                                                        <div class="col-md-12">
                                                            <div class="table-responsive">
                                                                <table class="table table-bordered">
                                                                    <thead class="table-success">
                                                                        <tr>
                                                                            <th scope="col">Cost Price</th>
                                                                            <th scope="col">Special Price</th>
                                                                            <th scope="col">Whole Sale Price</th>
                                                                            <th scope="col">Retail Price</th>
                                                                            <th scope="col">Max Retail Price</th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr>
                                                                            <td>
                                                                                <div class="row">
                                                                                    <div class="col-md-12">
                                                                                        <input type="number"
                                                                                            id="edit_original_price"
                                                                                            name="original_price"
                                                                                            class="form-control"
                                                                                            placeholder="Rs .00">
                                                                                        <span class="text-danger"
                                                                                            id="original_price_error"></span>
                                                                                    </div>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="form-group">
                                                                                    <input type="text"
                                                                                        id="edit_special_price"
                                                                                        name="special_price"
                                                                                        class="form-control"
                                                                                        placeholder="Rs .00">
                                                                                    <span class="text-danger"
                                                                                        id="special_price_error"></span>
                                                                                </div>
                                                                            </td>
                                                                            <td>
                                                                                <div class="form-group">
                                                                                    <input type="number"
                                                                                        id="edit_whole_sale_price"
                                                                                        name="whole_sale_price"
                                                                                        class="form-control"
                                                                                        placeholder="Rs .00">
                                                                                    <span class="text-danger"
                                                                                        id="whole_sale_price_error"></span>
                                                                                </div>
                                                                            </td>

                                                                            <td>
                                                                                <div class="form-group">
                                                                                    <input type="number"
                                                                                        id="edit_retail_price"
                                                                                        name="retail_price"
                                                                                        class="form-control"
                                                                                        placeholder="Rs .00">
                                                                                    <span class="text-danger"
                                                                                        id="retail_price_error"></span>
                                                                                </div>
                                                                            </td>

                                                                            <td>
                                                                                <div class="form-group">
                                                                                    <input type="text"
                                                                                        id="edit_max_retail_price"
                                                                                        name="max_retail_price"
                                                                                        class="form-control"
                                                                                        placeholder="Rs .00">
                                                                                    <span class="text-danger"
                                                                                        id="max_retail_price_error"></span>
                                                                                </div>
                                                                            </td>
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

                            <div class="mb-4 row">
                                <div class="d-flex justify-content-center">
                                    <div>
                                        <button type="submit" class="btn btn-outline-primary"
                                            id="onlySaveProductButton">Save</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).ready(function() {
        // Initialize Select2 for static elements on page load
        $('.select').select2(); // Use .select class as per your modal HTML

        // When modal opens, re-initialize Select2 inside the modal
        $('#new_purchase_product').on('shown.bs.modal', function() {
            // Destroy any previous Select2 instance to avoid duplication
            $(this).find('.select').each(function() {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
            });

            // Re-apply Select2 to dropdowns inside the modal
            $(this).find('.select').select2({
                dropdownParent: $('#new_purchase_product'), // Important for modals
                width: '100%'
            });

            // Optional: Focus search input after opening dropdown
            $(this).find('.select').on('select2:open', function() {
                setTimeout(() => {
                    const allDropdowns = document.querySelectorAll(
                        '.select2-container--open');
                    const lastOpenedDropdown = allDropdowns[allDropdowns.length - 1];
                    if (lastOpenedDropdown) {
                        const searchInput = lastOpenedDropdown.querySelector(
                            '.select2-search__field');
                        if (searchInput) {
                            searchInput.focus();
                            searchInput.select();
                        }
                    }
                }, 10);
            });
        });

        // Destroy Select2 when modal is closed to prevent duplication on next open
        $('#new_purchase_product').on('hidden.bs.modal', function() {
            $(this).find('.select').each(function() {
                if ($(this).data('select2')) {
                    $(this).select2('destroy');
                }
            });
        });
    });
</script>
