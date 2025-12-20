@extends('layout.layout')
@push('styles')
    <style>
        .error-container {
            position: relative;
        }

        .error-container .alert {
            border-left: 5px solid #dc3545;
            box-shadow: 0 2px 10px rgba(220, 53, 69, 0.1);
        }

        .error-container .bg-light {
            background-color: #f8f9fa !important;
            border: 1px solid #e9ecef;
        }

        .error-container li {
            font-size: 0.9em;
            margin-bottom: 2px;
        }

        .error-container .text-danger {
            color: #dc3545 !important;
            font-weight: 500;
        }

        .error-container h5,
        .error-container h6 {
            color: #721c24;
            margin-bottom: 15px;
        }

        .error-container .fas {
            margin-right: 8px;
        }

        /* Progress bar styling */
        .progress {
            height: 25px;
            border-radius: 15px;
            background-color: #e9ecef;
        }

        .progress-bar {
            border-radius: 15px;
            font-weight: 500;
            line-height: 25px;
        }
    </style>
@endpush
@section('content')
    <div class="content container-fluid">
        <div class="row">
            <div class="page-header">
                <div class="row align-items-center">
                    <div class="col-sm-12">
                        <div class="page-sub-header">
                            <h3 class="page-title">Import Customers</h3>
                            <ul class="breadcrumb">
                                <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
                                <li class="breadcrumb-item"><a href="#">Contacts</a></li>
                                <li class="breadcrumb-item active">Import Customers</li>
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
                                <form action="#" id="importCustomerForm" method="POST" enctype="multipart/form-data"
                                    class="skip-global-handler" data-skip-global="true">
                                    @csrf
                                    <div class="row">

                                        <div class="col-md-6 mt-4">
                                            <a class="btn btn-outline-success mt-2" id="export_btn"
                                                href="{{ route('excel-customer-blank-template-export') }}"><i
                                                    class="fas fa-download"></i> &nbsp; Download template file</a>
                                        </div>

                                        <!-- City Selection (Optional) -->
                                        <div class="col-md-12 mt-3">
                                            <div class="mb-3">
                                                <label for="import_city" class="form-label">
                                                    <i class="fas fa-city"></i> Select City (Optional)
                                                </label>
                                                <select name="import_city" id="import_city" class="form-control selectBox">
                                                    <option value="">No City - Use Excel City Names or Import Without City</option>
                                                </select>
                                                <small class="text-info">
                                                    <i class="fas fa-info-circle"></i>
                                                    <strong>Note:</strong> If you select a city here, ALL imported customers will be assigned to this city (ignoring Excel city names).
                                                    Leave blank to use city names from Excel file or import without city.
                                                </small>
                                            </div>
                                        </div>

                                        <div class="col-md-6">
                                            <div class="d-flex justify-content-start">
                                                <div class="mb-3">
                                                    <label>File To Import</label>
                                                    <div class="invoices-upload-btn">
                                                        <input type="file" name="file" id="file"
                                                            class="hide-input" accept=".xlsx,.xls">
                                                        <label for="file" class="upload"><i class="far fa-folder-open">
                                                                &nbsp;</i> Browse..</label>
                                                    </div>
                                                    <small class="text-muted" id="file_name_display">No file chosen</small>
                                                </div>
                                                <div class="mt-3">
                                                    <button type="submit" id="import_btn"
                                                        class="btn btn-outline-primary ms-4 mt-3">Upload</button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-6 mt-3">
                                            <div class="progress mt-3" style="display: none;">
                                                <div class="progress-bar" role="progressbar" style="width: 0%;"
                                                    aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                            </div>
                                            <span class="text-danger" id="file_error"></span>
                                        </div>

                                        <!-- Error Display Area -->
                                        <div class="col-md-12 mt-3">
                                            <div id="error-display-area" class="error-container"></div>
                                        </div>

                                    </div>

                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Instructions table --}}
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
                                                        <td>ID (Optional)</td>
                                                        <td>Leave blank for new customers</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">2</th>
                                                        <td>Prefix (Optional)</td>
                                                        <td>Customer prefix (e.g., Mr., Mrs., Dr.)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">3</th>
                                                        <td>First Name (Required)</td>
                                                        <td>Customer's first name</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">4</th>
                                                        <td>Last Name (Optional)</td>
                                                        <td>Customer's last name</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">5</th>
                                                        <td>Mobile No (Required)</td>
                                                        <td>Must be unique - no duplicates allowed</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">6</th>
                                                        <td>Email (Optional)</td>
                                                        <td>Must be valid email format and unique if provided</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">7</th>
                                                        <td>Address (Optional)</td>
                                                        <td>Customer's address</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">8</th>
                                                        <td>Opening Balance (Optional)</td>
                                                        <td>Customer's opening balance (numeric value)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">9</th>
                                                        <td>Credit Limit (Optional)</td>
                                                        <td>Customer's credit limit (numeric value)</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">10</th>
                                                        <td>City Name (Optional)</td>
                                                        <td>Must match existing city name in your database</td>
                                                    </tr>
                                                    <tr>
                                                        <th scope="row">11</th>
                                                        <td>Customer Type (Optional)</td>
                                                        <td>wholesaler or retailer (default: retailer)</td>
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

 <script>
            $(document).ready(function() {
                // Load cities when import customer page is loaded
                function loadCities() {
                    $.ajax({
                        url: '/api/cities',
                        type: 'GET',
                        success: function(response) {
                            if (response.status === true && response.data) {
                                var cities = response.data;
                                var options =
                                    '<option value="">No City - Use Excel City Names or Import Without City</option>';
                                cities.forEach(function(city) {
                                    options += '<option value="' + city.id + '">' + city.name + '</option>';
                                });
                                $('#import_city').html(options);
                            }
                        },
                        error: function(xhr) {
                            console.error('Error loading cities:', xhr);
                        }
                    });
                }

                // Load cities on page load
                loadCities();

                // Display selected file name
                $('#file').on('change', function() {
                    var fileName = $(this).val().split('\\').pop();
                    if (fileName) {
                        $('#file_name_display').text(fileName).removeClass('text-muted').addClass('text-success');
                    } else {
                        $('#file_name_display').text('No file chosen').removeClass('text-success').addClass('text-muted');
                    }
                });

                $('#importCustomerForm').on('submit', function(e) {
                    e.preventDefault();

                    var formData = new FormData(this);

                    // Show progress bar
                    $('.progress').show();
                    $('.progress-bar').css('width', '0%').text('0%');

                    $.ajax({
                        url: '/import-customer-excel-store',
                        type: 'POST',
                        data: formData,
                        processData: false,
                        contentType: false,
                        xhr: function() {
                            var xhr = new window.XMLHttpRequest();
                            xhr.upload.addEventListener("progress", function(evt) {
                                if (evt.lengthComputable) {
                                    var percentComplete = Math.round((evt.loaded / evt.total) *
                                        100);
                                    $('.progress-bar').css('width', percentComplete + '%').text(
                                        percentComplete + '%');
                                }
                            }, false);
                            return xhr;
                        },
                        success: function(response) {
                            $('.progress').hide();

                            if (response.status === 200) {
                                // Clear error display
                                $('#error-display-area').html('');

                                // Clear form
                                $('#importCustomerForm')[0].reset();
                                $('#file_name_display').text('No file chosen').removeClass('text-success').addClass('text-muted');

                                // Show success toastr
                                toastr.success('Customers imported successfully! Total: ' + (response.data ? response.data.length : 0) + ' customers', 'Success', {
                                    timeOut: 3000,
                                    closeButton: true,
                                    progressBar: true
                                });

                                // Redirect after a short delay to show the toastr
                                setTimeout(function() {
                                    window.location.href = '{{ route('customer') }}';
                                }, 1500);
                            } else if (response.status === 401 && response.validation_errors) {
                                // Display validation errors
                                displayValidationErrors(response.validation_errors);
                            }
                        },
                        error: function(xhr) {
                            $('.progress').hide();

                            var errorMessage = 'An error occurred while importing customers.';
                            if (xhr.responseJSON && xhr.responseJSON.message) {
                                errorMessage = xhr.responseJSON.message;
                            }

                            toastr.error(errorMessage, 'Error', {
                                timeOut: 5000,
                                closeButton: true,
                                progressBar: true
                            });
                        }
                    });
                });

                function displayValidationErrors(errors) {
                    var errorHtml = `
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            <h5 class="alert-heading">
                                <i class="fas fa-exclamation-triangle"></i> Import Failed - Validation Errors
                            </h5>
                            <hr>
                            <div class="bg-light p-3 rounded">
                                <h6 class="mb-3">Please fix the following errors and try again:</h6>
                                <ul class="mb-0">
                    `;

                    errors.forEach(function(error) {
                        errorHtml += '<li class="text-danger">' + error + '</li>';
                    });

                    errorHtml += `
                                </ul>
                            </div>
                        </div>
                    `;

                    $('#error-display-area').html(errorHtml);

                    // Scroll to error display
                    $('html, body').animate({
                        scrollTop: $('#error-display-area').offset().top - 100
                    }, 500);
                }
            });
        </script>
@endsection
