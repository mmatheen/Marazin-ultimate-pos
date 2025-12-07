<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">


    <!-- Dynamic Favicon -->
    <title>POS Page</title>
    <link rel="icon" href="{{ $activeSetting?->favicon_url }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ $activeSetting?->favicon_url }}" type="image/x-icon">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/icons/flags/flags.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toatr.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-bs4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
    <link rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.6/jquery.inputmask.min.css">

    <link rel="stylesheet" href="{{ asset('assets/css/pos_page_style/pos-main.css') }}">
</head>

<body>
    <!-- Include Sales Rep Vehicle/Route Selection Modal -->
    @include('components.sales-rep-modal')

    <div class="container-fluid p-1">
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-white p-1">
                    <!-- Mobile View: Single Row -->
                    <div class="d-md-none">
                        <div class="d-flex justify-content-between align-items-center">
                            <select id="locationSelect" class="form-select" style="max-width: calc(100% - 50px);">
                                <option value="" selected disabled>Select Location</option>
                            </select>
                            <button class="btn btn-primary ms-2" type="button" data-bs-toggle="modal"
                                data-bs-target="#mobileMenuModal">
                                <i class="fas fa-bars"></i>
                            </button>
                        </div>
                    </div>
                    <!-- Desktop View: Two Columns -->
                    <div class="row align-items-center d-none d-md-flex" style="margin: 0; padding: 4px 6px;">
                        <!-- Location and Date Section -->
                        <div class="col-md-6 d-flex align-items-center" style="padding: 0 6px;">
                            <div class="d-flex flex-row align-items-center" style="gap: 6px;">
                                <select id="locationSelectDesktop" class="form-select location-select-sync"
                                    style="min-width: 180px; max-width: 220px;">
                                    <option value="" selected disabled>Select Location</option>
                                </select>

                                <!-- Sales Rep Vehicle/Route Display (Desktop/Tablet Only) -->
                                <div id="salesRepDisplay" class="align-items-center" style="gap: 6px; display: none;">
                                    <div class="badge bg-success text-white p-2">
                                        <i class="fas fa-truck me-1"></i>
                                        <span id="selectedVehicleDisplay">-</span>
                                    </div>
                                    <div class="badge bg-info text-white p-2">
                                        <i class="fas fa-route me-1"></i>
                                        <span id="selectedRouteDisplay">-</span>
                                    </div>
                                    <div id="salesAccessBadge" class="badge p-2">
                                        <span id="salesAccessText">-</span>
                                    </div>
                                    <button class="btn btn-sm btn-outline-secondary" id="changeSalesRepSelection"
                                        title="Change Vehicle/Route">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>

                                <!-- Date and Time (Desktop Only) -->
                                <div class="d-flex align-items-center" style="gap: 6px;">
                                    <button class="btn btn-primary text-white border-1 px-3 py-1"
                                        style="font-size: 0.95rem;" id="currentDateButton">
                                        {{ \Carbon\Carbon::now('Asia/Colombo')->format('Y-m-d') }}
                                    </button>
                                    <span id="currentTimeText"
                                        style="color: #1e90ff; font-weight: 600; font-size: 1rem;">
                                        {{ \Carbon\Carbon::now('Asia/Colombo')->format('H:i:s') }}
                                    </span>
                                </div>

                                <!-- Keyboard Shortcuts Button (Desktop Only) -->
                                <button
                                    class="btn btn-info text-white border-1 px-2 py-1 d-flex align-items-center justify-content-center"
                                    id="shortcutButton" style="width: 38px; height: 38px;" data-bs-toggle="popover"
                                    data-bs-trigger="hover" data-bs-html="true"
                                    data-bs-content="
                                    <div class='row'>
                                        <div class='col-6'><strong>Operation</strong></div>
                                        <div class='col-6'><strong>Keyboard Shortcut</strong></div>
                                        <div class='col-6'>Go to next product quantity</div>
                                        <div class='col-6'>F2</div>
                                        <div class='col-6'>Add/Search new product</div>
                                        <div class='col-6'>F4</div>
                                        <div class='col-6'>Refresh the page</div>
                                        <div class='col-6'>F5</div>
                                        <div class='col-6'>Click Cash button</div>
                                        <div class='col-6'>F6</div>
                                        <div class='col-6'>Focus Amount Given</div>
                                        <div class='col-6'>F7</div>
                                        <div class='col-6'>Focus Discount Input</div>
                                        <div class='col-6'>F8</div>
                                        <div class='col-6'>Choose Customer</div>
                                        <div class='col-6'>F9</div>
                                    </div>">
                                    <i class="fas fa-keyboard fa-lg"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Action Buttons (Desktop) -->
                        <div class="col-md-6" style="padding: 0 6px;">
                            <div class="d-flex justify-content-end align-items-center" style="gap: 5px;">
                                <button class="btn btn-secondary btn-sm"
                                    onclick="handleGoHome()"
                                    data-bs-toggle="tooltip" title="Go home">
                                    <i class="fas fa-home"></i>
                                </button>

                                <button class="btn btn-light btn-sm" onclick="handleGoBack()"
                                    data-bs-toggle="tooltip" title="Go Back">
                                    <i class="fas fa-backward"></i>
                                </button>

                                <!-- Calculator Button with Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-warning btn-sm dropdown-toggle" id="calculatorButton"
                                        data-bs-toggle="dropdown" aria-expanded="false" data-bs-toggle="tooltip"
                                        title="Calculator">
                                        <i class="fas fa-calculator"></i>
                                    </button>
                                    <div class="dropdown-menu p-2 shadow" id="calculatorDropdown"
                                        style="width: 220px;">
                                        <div class="text-center">
                                            <input type="text" id="calcDisplay" class="form-control text-end mb-2"
                                                onkeydown="handleKeyboardInput(event)" autofocus>
                                        </div>
                                        <div class="d-grid gap-1">
                                            <div class="row g-1">
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('7')">7</button>
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('8')">8</button>
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('9')">9</button>
                                                <button class="btn btn-warning btn-sm col"
                                                    onclick="calcInput('/')">/</button>
                                            </div>
                                            <div class="row g-1">
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('4')">4</button>
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('5')">5</button>
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('6')">6</button>
                                                <button class="btn btn-warning btn-sm col"
                                                    onclick="calcInput('*')">×</button>
                                            </div>
                                            <div class="row g-1">
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('1')">1</button>
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('2')">2</button>
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('3')">3</button>
                                                <button class="btn btn-warning btn-sm col"
                                                    onclick="calcInput('-')">-</button>
                                            </div>
                                            <div class="row g-1">
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('0')">0</button>
                                                <button class="btn btn-light btn-sm col"
                                                    onclick="calcInput('.')">.</button>
                                                <button class="btn btn-danger btn-sm col"
                                                    onclick="clearCalc()">C</button>
                                                <button class="btn btn-warning btn-sm col"
                                                    onclick="calcInput('+')">+</button>
                                            </div>
                                            <div class="row g-1">
                                                <button class="btn btn-success btn-sm col"
                                                    onclick="calculateResult()">=</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button"
                                        id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                        title="Enter Invoice No">
                                        <i class="fas fa-redo-alt"></i>
                                    </button>
                                    <div class="dropdown-menu p-3">
                                        <label for="invoiceNo" class="form-label">Enter Invoice No</label>
                                        <input type="text" id="invoiceNo" class="form-control form-control-sm"
                                            placeholder="Invoice No">
                                        <button id="invoiceSubmitBtn"
                                            class="btn btn-primary btn-sm mt-2 w-100">Submit</button>
                                    </div>
                                </div>

                                <button class="btn btn-outline-danger btn-sm" id="pauseCircleButton"
                                    data-bs-toggle="modal" data-bs-target="#suspendSalesModal"
                                    data-bs-toggle="tooltip" title="Suspend Sales">
                                    <i class="fas fa-pause-circle"></i>
                                </button>

                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal"
                                    data-bs-target="#recentTransactionsModal" data-bs-toggle="tooltip"
                                    title="Recent Transactions">
                                    <i class="fas fa-clock"></i>
                                </button>

                                <button class="btn btn-gradient btn-sm" id="toggleProductList"
                                    data-bs-toggle="tooltip" title="Hide or Show Product list">
                                    <i class="fas fa-bars"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Menu Modal -->
        <div class="modal fade" id="mobileMenuModal" tabindex="-1" aria-labelledby="mobileMenuModalLabel">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-header bg-white border-bottom">
                        <h5 class="modal-title fw-bold text-dark" id="mobileMenuModalLabel">
                            <i class="fas fa-bars me-2"></i> Menu
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0 bg-white">
                        <!-- Sales Rep Vehicle/Route Display (If applicable) -->
                        <div id="salesRepDisplayMenu" style="display: none;" class="p-3 border-bottom bg-light">
                            <div class="d-flex flex-column gap-2">
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark"><i class="fas fa-truck me-2"></i>Vehicle:</strong>
                                    <span id="selectedVehicleDisplayMenu" class="badge bg-success">-</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark"><i class="fas fa-route me-2"></i>Route:</strong>
                                    <span id="selectedRouteDisplayMenu" class="badge bg-info">-</span>
                                </div>
                                <div class="d-flex align-items-center justify-content-between">
                                    <strong class="text-dark"><i class="fas fa-user-tag me-2"></i>Access:</strong>
                                    <span id="salesAccessBadgeMenu" class="badge">-</span>
                                </div>
                                <button class="btn btn-sm btn-outline-primary w-100 mt-2"
                                    id="changeSalesRepSelectionMenu">
                                    <i class="fas fa-edit me-2"></i>Change Vehicle/Route
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 p-3">
                            <!-- Row 1 -->
                            <div class="col-4">
                                <button type="button" class="menu-card w-100"
                                    data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#recentTransactionsModal">
                                    <div class="menu-icon bg-secondary text-white">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <span>Recent Transactions</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal"
                                    data-bs-toggle="modal" data-bs-target="#suspendSalesModal">
                                    <div class="menu-icon bg-secondary text-white">
                                        <i class="fas fa-pause-circle"></i>
                                    </div>
                                    <span>Suspended Sales</span>
                                </button>
                            </div>

                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal"
                                    onclick="handleGoBack()">
                                    <div class="menu-icon bg-secondary text-white">
                                        <i class="fas fa-backward"></i>
                                    </div>
                                    <span>Go Back</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal"
                                    onclick="handleGoHome()">
                                    <div class="menu-icon bg-info text-white">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <span>Go Home</span>
                                </button>
                            </div>

                            <!-- Row 3 -->
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-toggle="collapse"
                                    data-bs-target="#invoiceCollapse">
                                    <div class="menu-icon bg-danger text-white">
                                        <i class="fas fa-redo-alt"></i>
                                    </div>
                                    <span>Invoice No</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal"
                                    id="toggleProductListMobile"
                                    onclick="document.getElementById('toggleProductList').click()">
                                    <div class="menu-icon bg-secondary text-white">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <span>Product List</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal"
                                    onclick="toggleFullScreen()">
                                    <div class="menu-icon bg-warning text-white">
                                        <i class="fas fa-expand"></i>
                                    </div>
                                    <span>Full Screen</span>
                                </button>
                            </div>
                        </div>

                        <!-- Menu Card Styles -->
                        <style>
                            .menu-card {
                                background: white;
                                border: 1px solid #e0e0e0;
                                border-radius: 12px;
                                padding: 15px 10px;
                                display: flex;
                                flex-direction: column;
                                align-items: center;
                                gap: 8px;
                                transition: all 0.3s ease;
                                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                                min-height: 80px;
                            }

                            .menu-card:hover {
                                transform: translateY(-2px);
                                box-shadow: 0 4px 8px rgba(0,0,0,0.15);
                                border-color: #007bff;
                            }

                            .menu-icon {
                                width: 40px;
                                height: 40px;
                                border-radius: 50%;
                                display: flex;
                                align-items: center;
                                justify-content: center;
                                font-size: 16px;
                            }

                            .menu-icon i {
                                font-size: 18px;
                            }

                            .menu-card span {
                                font-size: 12px;
                                font-weight: 500;
                                text-align: center;
                                line-height: 1.2;
                                color: #333;
                            }

                            .menu-card:active {
                                transform: scale(0.98);
                            }

                            /* Recent Transactions Tab Styling */
                            #transactionTabs .nav-link {
                                color: #6c757d;
                                background-color: #f8f9fa;
                                border: 1px solid #dee2e6;
                                border-bottom: none;
                                border-radius: 0.375rem 0.375rem 0 0;
                                padding: 0.75rem 1rem;
                                margin-right: 2px;
                                transition: all 0.3s ease;
                            }

                            #transactionTabs .nav-link:hover {
                                background-color: #e9ecef;
                                color: #495057;
                            }

                            #transactionTabs .nav-link.active {
                                background-color: #007bff !important;
                                color: #ffffff !important;
                                border-color: #007bff;
                                font-weight: 600;
                            }

                            #transactionTabs .nav-link.active:hover {
                                background-color: #0056b3 !important;
                                color: #ffffff !important;
                            }
                        </style>

                        <!-- Invoice Number Input (Collapsible) -->
                        <div class="collapse" id="invoiceCollapse">
                            <div class="px-3 pb-3">
                                <div class="card border-0 bg-light" style="border-radius: 15px;">
                                    <div class="card-body">
                                        <label for="invoiceNoMobile" class="form-label fw-bold">Enter Invoice
                                            Number</label>
                                        <input type="text" id="invoiceNoMobile" class="form-control mb-2"
                                            placeholder="Invoice No">
                                        <button class="btn btn-primary w-100" onclick="submitInvoiceNo()">
                                            <i class="fas fa-check me-2"></i>Submit
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-1">

            <div class="container-fluid p-1">
                <div class="row">
                    <div class="col-md-12" id="mainContent">
                        <div class="card bg-white p-2"
                            style="height: calc(100vh - 215px); overflow: hidden; display: flex; flex-direction: column;">
                            <div class="row">
                                <div class="col-12">
                                    <p id="sale-invoice-no" class="text-info fw-bold mb-1"></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-5 pe-2">

                                    <div class="d-flex justify-content-center customer-select2">
                                        <select class="form-control selectBox" id="customer-id" style="flex: 1;">
                                            <option selected disabled>Please Select</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-info rounded-0"
                                            id="addCustomerButton">
                                            <i class="fas fa-plus-circle"></i>
                                        </button>
                                    </div>
                                    <!-- Customer Credit Information Row -->
                                    <div class="customer-credit-info mt-2" style="display: none;">
                                        <div class="d-flex border rounded">
                                            <div class="flex-fill border-end p-2 text-center">
                                                <small class="text-muted d-block">Total Due</small>
                                                <span id="total-due-amount" class="fw-bold text-danger d-block">
                                                    Rs. 0.00
                                                </span>
                                            </div>
                                            <div class="flex-fill border-end p-2 text-center">
                                                <small class="text-muted d-block">Credit Limit</small>
                                                <span id="credit-limit-amount" class="fw-bold text-info d-block">
                                                    Rs. 0.00
                                                </span>
                                            </div>
                                            <div class="flex-fill border-end p-2 text-center">
                                                <small class="text-muted d-block">Available</small>
                                                <span id="available-credit-amount"
                                                    class="fw-bold text-success d-block">
                                                    Rs. 0.00
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7 ps-2">
                                    <input type="text" class="form-control" id="productSearchInput"
                                        placeholder="Enter Product name / SKU / Scan bar code"
                                        style="height: 38px; font-size: 14px;">
                                </div>
                            </div>

                            <!-- Spacer for better separation -->
                            <div class="row mt-1" style="flex: 1; overflow: hidden;">
                                <div class="col-md-12 mt-1"
                                    style="height: 100%; display: flex; flex-direction: column;">
                                    <div class="table-responsive" style="flex: 1; overflow-y: auto;">
                                        <table class="table table-bordered">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th class="text-center" style="width: 50px;">#</th>
                                                    <th>Product</th>
                                                    <th class="text-center">Quantity</th>
                                                    <th class="text-center">Discount (Rs)</th>
                                                    <th class="text-center">Discount (%)</th>
                                                    <th class="text-center">Unit Price</th>
                                                    <th class="text-center">Subtotal</th>
                                                    <th class="text-center" style="color: red;">X</th>
                                                </tr>
                                            </thead>
                                            <tbody id="billing-body" class="bg-white">
                                                <!-- Dynamic rows go here -->
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- Item Counter Section - Fixed at bottom of billing card -->
                            <div class="row"
                                style="margin: 0; border-top: 2px solid #ddd; background-color: #f8f9fa;">
                                <div class="col-md-12" style="padding: 4px 8px;">
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-shopping-cart me-2 text-primary"></i>
                                            <span class="fw-bold">Total Items: </span>
                                            <span id="total-items-count" class="badge bg-secondary ms-2">0</span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Items in cart
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Total, Discount, Final Total Section - Fixed at bottom -->
                            <div class="row align-items-end"
                                style="margin: 0; border-top: 2px solid #ddd; background-color: #fff; padding: 10px;">
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Total</label>
                                        <p id="total-amount" class="form-control form-control-sm mb-0"
                                            style="height: 36px; line-height: 24px; font-size: 15px; font-weight: 600;">
                                            0.00</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Global
                                            Discount Type</label>
                                        <div class="btn-group w-100" role="group" aria-label="Discount Type"
                                            style="height: 36px; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <button type="button" class="btn btn-primary active"
                                                id="fixed-discount-btn"
                                                style="font-size: 12px; padding: 0; font-weight: 600; height: 36px; border: none; border-radius: 0; transition: all 0.3s ease;">Fixed</button>
                                            <button type="button" class="btn btn-outline-primary"
                                                id="percentage-discount-btn"
                                                style="font-size: 12px; padding: 0; font-weight: 600; height: 36px; border: none; border-radius: 0; background: white; transition: all 0.3s ease;">%</button>
                                        </div>
                                        <input type="hidden" id="discount-type" name="discount_type"
                                            value="fixed">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Discount</label>
                                        <div class="input-group input-group-sm" style="height: 36px;">
                                            <input type="text" id="global-discount" name="discount"
                                                class="form-control form-control-sm" placeholder="0.00"
                                                style="height: 36px; font-size: 15px; font-weight: 600;">
                                            <span class="input-group-text" id="discount-icon"
                                                style="height: 36px; font-size: 14px; font-weight: 600;">Rs</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Final
                                            Total</label>
                                        <p id="final-total-amount" class="form-control form-control-sm mb-0"
                                            style="height: 36px; line-height: 24px; font-size: 15px; font-weight: 600;">
                                            0.00</p>
                                    </div>
                                </div>
                                <!-- Shipping Button Column -->
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Shipping</label>
                                        <button class="btn btn-outline-info w-100" data-bs-toggle="modal"
                                            data-bs-target="#shippingModal" id="shippingButton"
                                            style="height: 36px; font-size: 14px; font-weight: 600;">
                                            <i class="fas fa-shipping-fast"></i> Shipping
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Amount
                                            Given</label>
                                        <input type="text" id="amount-given" class="form-control form-control-sm"
                                            placeholder="0.00" oninput="formatAmount(this)"
                                            style="height: 36px; font-size: 15px; font-weight: 600;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5 d-none" id="productListArea">
                        <div class="card bg-white p-2" style="height: calc(100vh - 215px); overflow: hidden;">
                            <!-- Buttons for Category and Brand -->
                            <div class="row mb-2">
                                <div class="d-flex justify-content-between w-100 mb-2">
                                    <button type="button" class="btn btn-gradient w-50 me-3" id="allProductsBtn">
                                        <i class="fas fa-box"></i> All Products
                                    </button>
                                    <button type="button" class="btn btn-gradient w-50 me-3" id="category-btn"
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategory"
                                        aria-controls="offcanvasCategory">
                                        <i class="fas fa-th-large"></i> Category
                                    </button>
                                    <button type="button" class="btn btn-gradient w-50" id="brand-btn"
                                        data-bs-toggle="offcanvas" data-bs-target="#offcanvasBrand"
                                        aria-controls="offcanvasBrand">
                                        <i class="fas fa-tags"></i> Brand
                                    </button>
                                </div>
                            </div>

                            <div class="row g-1 overflow-auto" id="posProduct"
                                style="height: calc(100vh - 315px); overflow-y: auto;">

                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

         <!-- Offcanvas Category Menu -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCategory"
            aria-labelledby="offcanvasCategoryLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasCategoryLabel">Categories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div id="categoryContainer" class="category-container">
                    <!-- Categories will be dynamically injected here -->
                </div>
            </div>
        </div>

        <!-- Offcanvas Subcategory Menu -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSubcategory"
            aria-labelledby="offcanvasSubcategoryLabel">
            <div class="offcanvas-header">
                <button type="button" class="btn btn-secondary" id="subcategoryBackBtn">Back</button>
                <h5 class="offcanvas-title" id="offcanvasSubcategoryLabel">Subcategories</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div id="subcategoryContainer" class="subcategory-container">
                    <!-- Subcategories will be dynamically injected here -->
                </div>
            </div>
        </div>

        <!-- Offcanvas Brand Menu -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasBrand"
            aria-labelledby="offcanvasBrandLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasBrandLabel">Brands</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <div id="brandContainer" class="category-container">
                    <!-- Brands will be dynamically injected here -->
                </div>
            </div>
        </div>

        <!-- Mobile/Tablet Bottom Fixed Button (Shows Final Total & Opens Payment Modal) -->
        <div class="mobile-bottom-fixed d-lg-none">
            <div class="mobile-bottom-container">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <small class="text-white d-block mb-1" style="font-size: 11px; opacity: 0.9;">Final
                            Total</small>
                        <h5 class="text-white mb-0 fw-bold" id="mobile-final-total">Rs 0.00</h5>
                        <small class="text-white" style="font-size: 10px; opacity: 0.8;">
                            <span id="mobile-items-count">0</span> item(s)
                        </small>
                    </div>
                    <button class="btn btn-light fw-bold px-3 py-2" data-bs-toggle="modal"
                        data-bs-target="#mobilePaymentModal">
                        <i class="fas fa-credit-card me-1"></i>
                        Pay Now
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Payment Methods Modal -->
        <div class="modal fade" id="mobilePaymentModal" tabindex="-1" aria-labelledby="mobilePaymentModalLabel">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <div class="w-100">
                            <h5 class="modal-title mb-1" id="mobilePaymentModalLabel">
                                <i class="fas fa-wallet me-2"></i>Select Payment Method
                            </h5>
                            <div class="d-flex align-items-center justify-content-between">
                                <small>Final Total:</small>
                                <h6 class="mb-0 fw-bold" id="modal-final-total">Rs 0.00</h6>
                            </div>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-0">
                        <div class="list-group list-group-flush">
                            @can('cash payment')
                                <button type="button"
                                    class="list-group-item list-group-item-action d-flex align-items-center p-3 mobile-payment-btn"
                                    data-payment="cash" data-bs-dismiss="modal">
                                    <div class="payment-icon bg-success text-white rounded-circle me-3">
                                        <i class="fas fa-money-bill-wave"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <h6 class="mb-0 fw-bold">Cash</h6>
                                        <small class="text-muted">Pay with cash</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </button>
                            @endcan

                            @can('card payment')
                                <button type="button"
                                    class="list-group-item list-group-item-action d-flex align-items-center p-3 mobile-payment-btn"
                                    data-payment="card" data-bs-dismiss="modal">
                                    <div class="payment-icon bg-primary text-white rounded-circle me-3">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <h6 class="mb-0 fw-bold">Card</h6>
                                        <small class="text-muted">Credit/Debit card</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </button>
                            @endcan

                            @can('cheque payment')
                                <button type="button"
                                    class="list-group-item list-group-item-action d-flex align-items-center p-3 mobile-payment-btn"
                                    data-payment="cheque" data-bs-dismiss="modal">
                                    <div class="payment-icon bg-warning text-white rounded-circle me-3">
                                        <i class="fas fa-money-check"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <h6 class="mb-0 fw-bold">Cheque</h6>
                                        <small class="text-muted">Pay by cheque</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </button>
                            @endcan

                            @can('credit sale')
                                <button type="button"
                                    class="list-group-item list-group-item-action d-flex align-items-center p-3 mobile-payment-btn"
                                    data-payment="credit" data-bs-dismiss="modal">
                                    <div class="payment-icon bg-info text-white rounded-circle me-3">
                                        <i class="fas fa-user-clock"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <h6 class="mb-0 fw-bold">Credit Sale</h6>
                                        <small class="text-muted">Pay later</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </button>
                            @endcan

                            @can('multiple payment methods')
                                <button type="button"
                                    class="list-group-item list-group-item-action d-flex align-items-center p-3 mobile-payment-btn"
                                    data-payment="multiple" data-bs-dismiss="modal" data-bs-toggle="modal"
                                    data-bs-target="#paymentModal">
                                    <div class="payment-icon bg-dark text-white rounded-circle me-3">
                                        <i class="fas fa-list"></i>
                                    </div>
                                    <div class="flex-fill">
                                        <h6 class="mb-0 fw-bold">Multiple Payment</h6>
                                        <small class="text-muted">Split payment</small>
                                    </div>
                                    <i class="fas fa-chevron-right text-muted"></i>
                                </button>
                            @endcan
                        </div>

                        <!-- Other Actions Section -->
                        <div class="border-top mt-2">
                            <div class="p-3 bg-light">
                                <h6 class="text-muted mb-3"><i class="fas fa-ellipsis-h me-2"></i>Other Actions</h6>
                                <div class="row g-2">
                                    @can('save draft')
                                        <div class="col-6">
                                            <button type="button" class="btn btn-outline-info w-100 mobile-action-btn"
                                                data-action="draft" data-bs-dismiss="modal">
                                                <i class="fas fa-edit d-block mb-1"></i>
                                                <small>Draft</small>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('create sale-order')
                                        <div class="col-6">
                                            <button type="button" class="btn btn-outline-success w-100 mobile-action-btn"
                                                data-action="sale-order" data-bs-dismiss="modal">
                                                <i class="fas fa-shopping-cart d-block mb-1"></i>
                                                <small>Sale Order</small>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('create quotation')
                                        <div class="col-6">
                                            <button type="button" class="btn btn-outline-warning w-100 mobile-action-btn"
                                                data-action="quotation" data-bs-dismiss="modal">
                                                <i class="fas fa-file-alt d-block mb-1"></i>
                                                <small>Quotation</small>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('create job-ticket')
                                        <div class="col-6">
                                            <button type="button"
                                                class="btn btn-outline-secondary w-100 mobile-action-btn"
                                                data-action="job-ticket" data-bs-dismiss="modal">
                                                <i class="fas fa-ticket-alt d-block mb-1"></i>
                                                <small>Job Ticket</small>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('suspend sale')
                                        <div class="col-6">
                                            <button type="button" class="btn btn-outline-danger w-100 mobile-action-btn"
                                                data-action="suspend" data-bs-dismiss="modal" data-bs-toggle="modal"
                                                data-bs-target="#suspendModal">
                                                <i class="fas fa-pause d-block mb-1"></i>
                                                <small>Suspend</small>
                                            </button>
                                        </div>
                                    @endcan

                                    <div class="col-12">
                                        <button type="button" class="btn btn-outline-danger w-100"
                                            id="mobile-cancel-button">
                                            <i class="fas fa-times me-2"></i>Cancel Order
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Bottom Fixed Section (Hidden on Mobile/Tablet) -->
        <div class="bottom-fixed d-none d-lg-block">
            <div class="row">
                <!-- Left Side: Total Payable and Cancel -->
                <div class="col-md-5 d-flex align-items-center justify-content-start gap-3">
                    <h4 class="mb-0">Total Payable:</h4>
                    <span id="total" class="text-success fw-bold ms-2" style="font-size: 24px;">Rs 0.00</span>
                    <span id="items-count" class="text-secondary ms-2" style="font-size: 16px;">(0)</span>
                    <button class="btn btn-danger ms-3 btn-sm" id="cancelButton"><i class="fas fa-times"></i>
                        Cancel</button>
                </div>

                <!-- Right Side: Actions (Aligned to Right) -->
                <div class="col-md-7 text-end">
                    <div class="d-flex justify-content-end gap-1 flex-wrap">

                        @can('create job-ticket')
                            {{-- job ticket --}}
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="jobTicketButton">
                                <i class="fas fa-ticket-alt"></i> Job Ticket
                            </button>
                        @endcan

                        @can('create quotation')
                            {{-- <!-- Quotation Button --> --}}
                            <button type="button" class="btn btn-outline-warning btn-sm" id="quotationButton">
                                <i class="fas fa-file-alt"></i> Quotation
                            </button>
                        @endcan

                        @can('save draft')
                            <!-- Draft Button -->
                            <button type="button" class="btn btn-outline-info btn-sm" id="draftButton">
                                <i class="fas fa-edit"></i> Draft
                            </button>
                        @endcan

                        @can('create sale-order')
                            <!-- Sale Order Button -->
                            <button type="button" class="btn btn-outline-success btn-sm" id="saleOrderButton">
                                <i class="fas fa-shopping-cart"></i> Sale Order
                            </button>
                        @endcan

                        @can('suspend sale')
                            <!-- Existing Buttons -->
                            <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal"
                                data-bs-target="#suspendModal">
                                <i class="fas fa-pause"></i> Suspend
                            </button>
                        @endcan

                        @can('credit sale')
                            <button class="btn btn-outline-success btn-sm" id="creditSaleButton">
                                <i class="fas fa-check"></i> Credit Sale
                            </button>
                        @endcan

                        @can('card payment')
                            <button class="btn btn-outline-primary btn-sm" id="cardButton">
                                <i class="fas fa-credit-card"></i> Card
                            </button>
                        @endcan

                        @can('cheque payment')
                            <button class="btn btn-outline-warning btn-sm" id="chequeButton">
                                <i class="fas fa-money-check"></i> Cheque
                            </button>
                        @endcan

                        @can('multiple payment methods')
                            <button class="btn btn-outline-dark btn-sm" data-bs-toggle="modal"
                                data-bs-target="#paymentModal">
                                <i class="fas fa-list"></i> Multiple Pay
                            </button>
                        @endcan
                        @can('cash payment')
                            <button class="btn btn-outline-secondary btn-sm" id="cashButton">
                                <i class="fas fa-cash-register"></i> Cash
                            </button>
                        @endcan

                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Bootstrap Modal with Tabs and Dynamic Table -->
    <div class="modal fade" id="recentTransactionsModal" tabindex="-1" aria-labelledby="recentTransactionsLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recentTransactionsLabel">Recent Transactions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <ul class="nav nav-tabs" id="transactionTabs">
                        <li class="nav-item">
                            <a class="nav-link active" data-bs-toggle="tab" href="#final">Final</a>
                        </li>
                        @can('create quotation')
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#quotation">Quotation</a>
                            </li>
                        @endcan

                        @can('save draft')
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#draft">Draft</a>
                            </li>
                        @endcan

                        @can('create job-ticket')
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#jobticket">Job Tickets</a>
                            </li>
                        @endcan
                    </ul>
                    <div class="tab-content mt-3">
                        <div class="table-responsive">
                            <table class="table table-bordered" id="transactionTable">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Invoice No</th>
                                        <th>Customer</th>
                                        <th>Sales Date</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="transactionTableBody">
                                    <!-- Dynamic Rows Will Be Injected Here -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productModalLabel">Product Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="productModalBody">
                    <!-- Product details will be injected here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="saveProductChanges">Save changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Suspend -->
    <div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="suspendModalLabel">Suspend Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <label for="suspendNote" class="form-label">Enter Suspension Note:</label>
                    <textarea class="form-control" id="suspendNote" rows="3" placeholder="Write reason..."></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmSuspend">Suspend</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Sale Order -->
    <div class="modal fade" id="saleOrderModal" tabindex="-1" aria-labelledby="saleOrderModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="saleOrderModalLabel">Create Sale Order</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="expectedDeliveryDate" class="form-label">Expected Delivery Date</label>
                        <input type="date" class="form-control" id="expectedDeliveryDate"
                            min="{{ date('Y-m-d') }}" required>
                    </div>
                    <div class="mb-3">
                        <label for="orderNotes" class="form-label">Order Notes (Optional)</label>
                        <textarea class="form-control" id="orderNotes" rows="3"
                            placeholder="Enter any special instructions or notes..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmSaleOrder">Create Sale Order</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Shipping Information -->
    <div class="modal fade" id="shippingModal" tabindex="-1" aria-labelledby="shippingModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="shippingModalLabel">
                        <i class="fas fa-shipping-fast me-2"></i>Shipping Information
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="shippingForm">
                        <div class="row">
                            <!-- Left Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shippingDetails" class="form-label">
                                        <strong>Shipping Details:</strong>
                                    </label>
                                    <textarea class="form-control" id="shippingDetails" name="shipping_details"
                                        rows="4" placeholder="Enter shipping details..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="shippingCharges" class="form-label">
                                        <strong>Shipping Charges:</strong>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            Rs.
                                        </span>
                                        <input type="number" class="form-control" id="shippingCharges"
                                            name="shipping_charges" min="0" step="0.01"
                                            placeholder="0.00">
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="deliveredTo" class="form-label">
                                        <strong>Delivered To:</strong>
                                    </label>
                                    <input type="text" class="form-control" id="deliveredTo" name="delivered_to"
                                        placeholder="Enter recipient name">
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="shippingAddress" class="form-label">
                                        <strong>Shipping Address:</strong>
                                    </label>
                                    <textarea class="form-control" id="shippingAddress" name="shipping_address"
                                        rows="4" placeholder="Enter shipping address..."></textarea>
                                </div>

                                <div class="mb-3">
                                    <label for="shippingStatus" class="form-label">
                                        <strong>Shipping Status:</strong>
                                    </label>
                                    <select class="form-select" id="shippingStatus" name="shipping_status">
                                        <option value="ordered" selected>Ordered</option>
                                        <option value="pending">Pending</option>
                                        <option value="shipped">Shipped</option>
                                        <option value="delivered">Delivered</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="deliveryPerson" class="form-label">
                                        <strong>Delivery Person:</strong>
                                    </label>
                                    <select class="form-select" id="deliveryPerson" name="delivery_person">
                                        <option value="Mr Admin" selected>Mr Admin</option>
                                        <option value="delivery_agent_1">Delivery Agent 1</option>
                                        <option value="delivery_agent_2">Delivery Agent 2</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>


                        <!-- Shipping Summary -->
                        <div class="row mt-3">
                            <div class="col-12">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">
                                        <i class="fas fa-info-circle me-2"></i>Shipping Summary
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Subtotal:</strong> Rs <span id="modalSubtotal">0.00</span></p>
                                            <p class="mb-1"><strong>Shipping Charges:</strong> Rs <span id="modalShippingCharges">33.75</span></p>
                                        </div>
                                        <div class="col-md-6">
                                            <p class="mb-1"><strong>Total with Shipping:</strong> Rs <span id="modalTotalWithShipping" class="text-success fw-bold">0.00</span></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" id="updateShipping">
                        <i class="fas fa-save me-1"></i>Update Shipping
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Suspended Sales -->
    <div class="modal fade" id="suspendSalesModal" tabindex="-1" aria-labelledby="suspendSalesModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="suspendSalesModalLabel">Suspended Sales</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Reference No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Total Items</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="suspendedSalesContainer">
                            <!-- Suspended sales will be dynamically inserted here -->
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="paymentModalLabel">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">Add Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <!-- Left Side (Scrollable) -->
                            <div class="col-12 col-md-8 pe-3" style="max-height: 70vh; overflow-y: auto;">
                                <div class="card shadow-sm mb-4">
                                    <div class="card-body">
                                        <form id="paymentForm">
                                            <input type="hidden" id="saleId" name="reference_id">
                                            <input type="hidden" id="payment_type" name="payment_type">
                                            <input type="hidden" id="customer_id" name="customer_id">
                                            <input type="hidden" id="reference_no" name="reference_no">
                                            <div class="row mb-3">
                                                <div class="col-md-12">
                                                    <label for="advanceBalance" class="form-label">Advance Balance :
                                                        Rs. 0.00</label>
                                                </div>
                                            </div>
                                            <div id="paymentRows">
                                                <div class="card mb-3 payment-row position-relative">
                                                    <div class="card-body">
                                                        <div class="row">
                                                            <div class="col-md-4">
                                                                <label for="paymentMethod" class="form-label">Payment
                                                                    Method</label>
                                                                <select class="form-select payment-method"
                                                                    name="payment_method"
                                                                    onchange="togglePaymentFields(this)">
                                                                    <option value="cash" selected>Cash</option>
                                                                    <option value="card">Credit Card</option>
                                                                    <option value="cheque">Cheque</option>
                                                                    <option value="bank_transfer">Bank Transfer
                                                                    </option>
                                                                </select>
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="paidOn" class="form-label">Paid
                                                                    On</label>
                                                                <input class="form-control datetimepicker payment-date"
                                                                    type="text" name="payment_date"
                                                                    placeholder="DD-MM-YYYY"
                                                                    value="${moment().format('DD-MM-YYYY')}">
                                                            </div>
                                                            <div class="col-md-4">
                                                                <label for="payAmount"
                                                                    class="form-label">Amount</label>
                                                                <input type="text"
                                                                    class="form-control payment-amount"
                                                                    id="payment-amount" name="amount"
                                                                    oninput="validateAmount()">
                                                                <div class="text-danger amount-error"
                                                                    style="display:none;"></div>
                                                            </div>
                                                        </div>
                                                        <div class="conditional-fields row mt-3"></div>
                                                    </div>
                                                    <button type="button"
                                                        class="btn-close position-absolute top-0 end-0 mt-2 me-2 remove-payment-row"
                                                        aria-label="Close"></button>
                                                </div>
                                            </div>
                                            <button type="button" class="btn btn-primary w-100 mb-3"
                                                id="addPaymentRow">Add Payment Row</button>
                                            <div class="mb-3">
                                                <label for="paymentNote" class="form-label">Payment Note</label>
                                                <textarea class="form-control" id="paymentNote" name="payment_note"></textarea>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Side (Sticky) -->
                            <div class="col-12 col-md-4">
                                <div class="card shadow-sm" style="position: sticky; top: 20px; height: fit-content;">
                                    <div style="background-color:#e3c1a6; padding: 20px;"
                                        class="card-body text-dark rounded" style="padding: 20px;">
                                        <div class="text-start">
                                            <p>Total Items:</p>
                                            <h5><strong id="modal-total-items">0.00</strong></h5>
                                            <hr>
                                            <p>Total Payable:</p>
                                            <h5><strong id="modal-total-payable">0.00</strong></h5>
                                            <hr>
                                            <p>Total Paying:</p>
                                            <h5><strong id="modal-total-paying">0.00</strong></h5>
                                            <hr>
                                            <p>Change Return:</p>
                                            <h5><strong id="modal-change-return">0.00</strong></h5>
                                            <hr>
                                            <p>Balance:</p>
                                            <h5><strong id="modal-balance">0.00</strong></h5>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary"
                            data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-outline-primary" id="finalize_payment">Finalize
                            Payment</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Payment Modal -->
    <div class="modal fade" id="cardModal" tabindex="-1" aria-labelledby="cardModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cardModalLabel">Card Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="cardNumber" class="form-label">Card Number</label>
                            <input type="text" class="form-control" name="card_number" id="card_number">
                            <div id="cardNumberError" class="text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="cardHolderName" class="form-label">Card Holder Name</label>
                            <input type="text" class="form-control" name="card_holder_name"
                                id="card_holder_name">
                        </div>
                        <div class="col-md-6">
                            <label for="expiryMonth" class="form-label">Expiry Month</label>
                            <input type="text" class="form-control" name="card_expiry_month"
                                id="card_expiry_month">
                        </div>
                        <div class="col-md-6">
                            <label for="expiryYear" class="form-label">Expiry Year</label>
                            <input type="text" class="form-control" name="card_expiry_year"
                                id="card_expiry_year">
                        </div>
                        <div class="col-md-6">
                            <label for="securityCode" class="form-label">Security Code</label>
                            <input type="text" class="form-control" name="card_security_code"
                                id="card_security_code">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="confirmCardPayment">Confirm
                        Payment</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Cheque Payment Modal -->
    <div class="modal fade" id="chequeModal" tabindex="-1" aria-labelledby="chequeModalLabel">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chequeModalLabel">Cheque Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="chequeNumber" class="form-label">Cheque Number</label>
                            <input type="text" class="form-control" name="cheque_number" id="cheque_number">
                            <div id="chequeNumberError" class="text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="bankBranch" class="form-label">Bank Branch</label>
                            <input type="text" class="form-control" name="cheque_bank_branch"
                                id="cheque_bank_branch">
                            <div id="bankBranchError" class="text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="cheque_received_date" class="form-label">Cheque Received Date</label>
                            <input type="text" class="form-control datetimepicker cheque-received-date"
                                name="cheque_received_date" id="cheque_received_date">
                            <div id="chequeReceivedDateError" class="text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="cheque_valid_date" class="form-label">Cheque Valid Date</label>
                            <input type="text" class="form-control datetimepicker cheque-valid-date"
                                name="cheque_valid_date" id="cheque_valid_date">
                            <div id="chequeValidDateError" class="text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="cheque_given_by" class="form-label">Cheque Given by</label>
                            <input type="text" class="form-control" name="cheque_given_by"
                                id="cheque_given_by">
                            <div id="chequeGivenByError" class="text-danger"></div>
                        </div>
                        <div class="col-md-6">
                            <label for="cheque_status" class="form-label">Cheque Status</label>
                            <select class="form-control" name="cheque_status" id="cheque_status">
                                <option value="pending">Pending</option>
                                <option value="deposited">Deposited</option>
                                <option value="cleared">Cleared</option>
                            </select>
                            <div id="chequeStatusError" class="text-danger"></div>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Pending: Cheque received but not yet deposited<br>
                                Deposited: Cheque deposited but not yet cleared<br>
                                Cleared: Cheque amount successfully credited
                            </small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="confirmChequePayment">Confirm
                        Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- IMEI Selection Modal -->
    <div class="modal fade" id="imeiModal" tabindex="-1" aria-labelledby="imeiModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imeiModalLabel">Select IMEI Numbers</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="imeiSearch" class="form-control"
                                placeholder="Search IMEI...">
                        </div>
                        <div class="col-md-4">
                            <select id="checkboxFilter" class="form-select">
                                <option value="all">All</option>
                                <option value="checked">Checked</option>
                                <option value="unchecked">Unchecked</option>
                            </select>
                        </div>
                    </div>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Select</th>
                                <th>IMEI Number</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="imei-table-body">
                            <!-- Rows will be inserted here -->
                        </tbody>
                    </table>
                    <div id="imeiModalFooter"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmImeiSelection">Confirm</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel">
        <div class="modal-dialog modal-dialog-top">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmDeleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this IMEI?
                </div>
                <div class="modal-footer">
                    <button type="button" id="confirmDeleteBtn" class="btn btn-danger">Delete</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </div>
        </div>
    </div>


    <!-- Modal -->
    <div class="modal fade" id="jobTicketModal" tabindex="-1" aria-labelledby="jobTicketModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="font-family: 'Roboto', sans-serif;">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobTicketModalLabel" style="font-weight: bold;">JOB-TICKET</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <form id="jobTicketForm">
                        <div class="row mb-2">
                            <div class="col-6">
                                <label class="form-label mb-1" style="font-size: 13px;">Ticket ID</label>
                                <input type="text" class="form-control form-control-sm" id="ticketId"
                                    readonly>
                            </div>
                            <div class="col-6 text-end">
                                <label class="form-label mb-1" style="font-size: 13px;">Date</label>
                                <input type="text" class="form-control form-control-sm text-end" id="jobDate"
                                    readonly>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-12">
                                <label class="form-label mb-1" style="font-size: 13px;">Description</label>
                                <textarea class="form-control form-control-sm" id="description" rows="2"></textarea>
                            </div>
                        </div>

                        <div class="row mb-2">
                            <div class="col-4">
                                <label class="form-label mb-1" style="font-size: 13px;">Advance</label>
                                <input type="number" class="form-control form-control-sm"
                                    id="advanceAmountInput">
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1" style="font-size: 13px;">Balance</label>
                                <input type="number" class="form-control form-control-sm" id="balanceAmountInput"
                                    readonly>
                            </div>
                            <div class="col-4">
                                <label class="form-label mb-1" style="font-size: 13px;">Total</label>
                                <input type="number" class="form-control form-control-sm" id="totalAmountInput"
                                    readonly>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row mb-2 mt-2">
                            <div class="col-12">
                                <div
                                    style="border: 1px solid #198754; border-radius: 6px; padding: 10px; background: #f8fff8;">
                                    <div style="font-size: 13px; color: #198754; font-weight: 500;">
                                        I Certify that the above informations correct &amp; Complete.<br>
                                        மேலே என்னால் கொடுக்கப்பட்ட தகவல் யாவும் சரியானதும், முற்றும் பெற்றதும் என
                                        உறுதிப்படுத்துகிறேன்.
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <span style="font-size: 12px;">Date</span>
                                            <div
                                                style="border-bottom: 1px dotted #888; width: 100px; display: inline-block; margin-left: 10px;">
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <span style="font-size: 12px;">Signature/ஒப்பம்</span>
                                            <div
                                                style="border-bottom: 1px dotted #888; width: 120px; display: inline-block; margin-left: 10px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr class="my-2">
                        <div class="row mb-2">
                            <div class="col-6">
                                <label class="form-label mb-1" style="font-size: 13px;">Name</label>
                                <input type="text" class="form-control form-control-sm" id="customerName"
                                    readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1" style="font-size: 13px;">Address</label>
                                <input type="text" class="form-control form-control-sm" id="customerAddress"
                                    readonly>
                            </div>
                        </div>
                        <div class="row mb-2">
                            <div class="col-6">
                                <label class="form-label mb-1" style="font-size: 13px;">Mobile</label>
                                <input type="text" class="form-control form-control-sm" id="customerMobile"
                                    readonly>
                            </div>
                            <div class="col-6">
                                <label class="form-label mb-1" style="font-size: 13px;">E-mail</label>
                                <input type="email" class="form-control form-control-sm" id="customerEmail"
                                    readonly>
                            </div>
                        </div>

                    </form>
                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary btn-sm" id="submitJobTicket">Submit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Batch Price Selection Modal -->
    <div class="modal fade" id="batchPriceModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Retail Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Batch No</th>
                                <th>Retail Price</th>
                                <th>Quantity</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="batch-price-list">
                            <!-- Rows will be injected here -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- JavaScript for Calculator Functionality -->
        <script>
            function calcInput(value) {
                document.getElementById('calcDisplay').value += value;
            }

            function clearCalc() {
                document.getElementById('calcDisplay').value = '';
            }

            function calculateResult() {
                try {
                    document.getElementById('calcDisplay').value = eval(document.getElementById('calcDisplay').value);
                } catch (e) {
                    document.getElementById('calcDisplay').value = 'Error';
                }
            }
            // Prevent dropdown from closing when clicking inside
            document.getElementById('calculatorDropdown').addEventListener('click', function(event) {
                event.stopPropagation();
            });
            // Handle keyboard input, allowing only numbers and operators
            function handleKeyboardInput(event) {
                const allowedKeys = "0123456789+-*/.";
                if (!allowedKeys.includes(event.key) && event.key !== "Backspace" && event.key !== "Enter") {
                    event.preventDefault();
                }
                if (event.key === "Enter") {
                    calculateResult();
                }
            }
            document.addEventListener('DOMContentLoaded', function() {
                console.log('DOM Content Loaded - Invoice functionality initializing');

                // Check if required libraries are loaded
                if (typeof $ === 'undefined') {
                    console.error('jQuery is not loaded');
                }
                if (typeof toastr === 'undefined') {
                    console.error('Toastr is not loaded');
                }

                function handleInvoiceSubmission() {
                    console.log('handleInvoiceSubmission called');
                    const invoiceInput = document.getElementById('invoiceNo');
                    if (!invoiceInput) {
                        console.error('Invoice input not found');
                        return;
                    }

                    const invoiceNo = invoiceInput.value.trim();
                    console.log('Invoice number entered:', invoiceNo);

                    if (invoiceNo) {
                        // Fetch sales data from the API
                        $.ajax({
                            url: '/sales',
                            method: 'GET',
                            success: function(data) {
                                console.log('Sales data received:', data);

                                // Check if data has the expected structure
                                if (!data.sales || !Array.isArray(data.sales)) {
                                    console.error('Invalid sales data structure:', data);
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error('Invalid sales data received.');
                                    } else {
                                        alert('Invalid sales data received.');
                                    }
                                    return;
                                }

                                // Check if the entered invoice number matches any sales data
                                const sale = data.sales.find(sale => sale.invoice_no.toLowerCase() ===
                                    invoiceNo.toLowerCase());
                                console.log('Found sale:', sale);

                                if (sale) {
                                    console.log('Redirecting to sale return page');
                                    // Redirect to the sale return page with the invoice number as a query parameter
                                    window.location.href =
                                        `/sale-return/add?invoiceNo=${encodeURIComponent(invoiceNo)}`;
                                } else {
                                    console.log('Sale not found for invoice:', invoiceNo);
                                    // Show toastr message indicating sale not found
                                    if (typeof toastr !== 'undefined') {
                                        toastr.error(
                                            'Sale not found. Please enter a valid invoice number.');
                                    } else {
                                        alert('Sale not found. Please enter a valid invoice number.');
                                    }
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error('Error fetching sales data:', xhr.responseText);
                                toastr.error('An error occurred while fetching sales data.');
                            }
                        });
                    } else {
                        toastr.warning('Please enter an invoice number');
                    }
                }

                // Function to handle invoice submission from mobile menu
                window.submitInvoiceNo = function() {
                    const mobileInvoiceInput = document.getElementById('invoiceNoMobile');
                    if (mobileInvoiceInput && mobileInvoiceInput.value.trim() !== '') {
                        document.getElementById('invoiceNo').value = mobileInvoiceInput.value;
                        handleInvoiceSubmission();
                        // Close the modal and collapse
                        const modal = bootstrap.Modal.getInstance(document.getElementById('mobileMenuModal'));
                        if (modal) modal.hide();
                        const collapse = bootstrap.Collapse.getInstance(document.getElementById('invoiceCollapse'));
                        if (collapse) collapse.hide();
                    }
                };

                // Capture the Submit button click using specific ID
                const submitButton = document.getElementById('invoiceSubmitBtn');
                if (submitButton) {
                    submitButton.addEventListener('click', function(event) {
                        event.preventDefault();
                        handleInvoiceSubmission();
                    });
                    console.log('Submit button event listener attached');
                } else {
                    console.error('Submit button for invoice not found');
                }

                // Capture the Enter key press in the input field
                const invoiceInput = document.getElementById('invoiceNo');
                if (invoiceInput) {
                    invoiceInput.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            handleInvoiceSubmission();
                        }
                    });
                }

                // Mobile invoice input Enter key
                const mobileInvoiceInput = document.getElementById('invoiceNoMobile');
                if (mobileInvoiceInput) {
                    mobileInvoiceInput.addEventListener('keydown', function(event) {
                        if (event.key === 'Enter') {
                            event.preventDefault();
                            window.submitInvoiceNo();
                        }
                    });
                }
            });
        </script>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Sync location selects between mobile and desktop
                const mobileSelect = document.getElementById('locationSelect');
                const desktopSelect = document.getElementById('locationSelectDesktop');

                if (mobileSelect && desktopSelect) {
                    mobileSelect.addEventListener('change', function() {
                        desktopSelect.value = this.value;
                        $(desktopSelect).trigger('change');
                    });

                    desktopSelect.addEventListener('change', function() {
                        mobileSelect.value = this.value;
                        $(mobileSelect).trigger('change');
                    });
                }

                function updateDateTime() {
                    const now = new Date();
                    const dateStr = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + ('0' + now
                        .getDate()).slice(-2);
                    const timeStr = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' +
                        ('0' + now.getSeconds()).slice(-2);
                    const dateBtn = document.getElementById('currentDateButton');
                    const timeText = document.getElementById('currentTimeText');
                    if (dateBtn) dateBtn.innerText = dateStr;
                    if (timeText) timeText.innerText = timeStr;
                }
                setInterval(updateDateTime, 1000);
                updateDateTime();

                // Initialize popovers
                const popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
                const popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                    return new bootstrap.Popover(popoverTriggerEl);
                });
            });
        </script>
        <script>
                // Handle back navigation for POS system: go to previous page or dashboard if no history
                function handleGoBack() {
                    // Check if there's a previous page in browser history
                    if (window.history.length > 1 && document.referrer) {
                        // Go back to previous page
                        window.history.back();
                    } else {
                        // No previous page, redirect to dashboard as fallback
                        window.location.href = "{{ route('dashboard') }}";
                    }
                }

                // Function to go directly to dashboard (for Go Home button)
                function handleGoHome() {
                    window.location.href = "{{ route('dashboard') }}";
                }
            </script>
            <script>
                    document.addEventListener('DOMContentLoaded', function() {
                        const fixedDiscountBtn = document.getElementById('fixed-discount-btn');
                        const percentageDiscountBtn = document.getElementById('percentage-discount-btn');
                        const discountInput = document.getElementById('global-discount');
                        const discountIcon = document.getElementById('discount-icon');
                        const discountTypeInput = document.getElementById('discount-type');
                        const toggleProductListBtn = document.getElementById('toggleProductList');
                        const productListArea = document.getElementById('productListArea');
                        const mainContent = document.getElementById('mainContent');
                        // Set default to fixed discount
                        fixedDiscountBtn.classList.add('active');
                        discountIcon.textContent = 'Rs';
                        fixedDiscountBtn.addEventListener('click', function() {
                            fixedDiscountBtn.classList.add('active');
                            percentageDiscountBtn.classList.remove('active');
                            discountIcon.textContent = 'Rs';
                            discountTypeInput.value = 'fixed';
                            // Clear the discount value when switching discount type
                            discountInput.value = '0';
                            // Trigger input event to ensure proper recalculation
                            discountInput.dispatchEvent(new Event('input', { bubbles: true }));
                            discountInput.dispatchEvent(new Event('change', { bubbles: true }));
                            // Trigger calculation update if updateTotals exists
                            if (typeof updateTotals === 'function') {
                                updateTotals();
                            }
                        });
                        percentageDiscountBtn.addEventListener('click', function() {
                            percentageDiscountBtn.classList.add('active');
                            fixedDiscountBtn.classList.remove('active');
                            discountIcon.textContent = '%';
                            discountTypeInput.value = 'percentage';
                            // Clear the discount value when switching discount type
                            discountInput.value = '0';
                            // Trigger input event to ensure proper recalculation
                            discountInput.dispatchEvent(new Event('input', { bubbles: true }));
                            discountInput.dispatchEvent(new Event('change', { bubbles: true }));
                            // Trigger calculation update if updateTotals exists
                            if (typeof updateTotals === 'function') {
                                updateTotals();
                            }
                        });
                        // Toggle product list area visibility
                        toggleProductListBtn.addEventListener('click', function() {
                            if (productListArea.classList.contains('show')) {
                                productListArea.classList.remove('show');
                                productListArea.classList.add('d-none');
                                mainContent.classList.remove('col-md-7');
                                mainContent.classList.add('col-md-12');
                            } else {
                                productListArea.classList.remove('d-none');
                                productListArea.classList.add('show');
                                mainContent.classList.remove('col-md-12');
                                mainContent.classList.add('col-md-7');
                            }
                        });
                    });
                </script>

                <script>
            // Format amounts with commas
            function formatAmountWithSeparators(amount) {
                return new Intl.NumberFormat().format(amount);
            }
            // Parse formatted numbers back to float
            function parseFormattedAmount(formattedAmount) {
                return parseFloat(formattedAmount.replace(/,/g, ''));
            }

            // Format input field value with commas for better readability
            function formatAmount(input) {
                // Get the current value and remove any existing commas
                let value = input.value.replace(/,/g, '');

                // Only format if it's a valid number
                if (value && !isNaN(value)) {
                    // Format with commas and update the input
                    input.value = formatAmountWithSeparators(parseFloat(value));
                }
            }

            let totalPayable = 0;
            document.getElementById('addPaymentRow').addEventListener('click', function() {
                const paymentRows = document.getElementById('paymentRows');
                const usedMethods = Array.from(document.querySelectorAll('.payment-method')).map(el => el.value);
                const newPaymentRow = document.createElement('div');
                newPaymentRow.className = 'card mb-3 payment-row position-relative';
                newPaymentRow.innerHTML = `
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="paymentMethod" class="form-label">Payment Method</label>
                            <select class="form-select payment-method" name="payment_method" onchange="togglePaymentFields(this)">
                                <option value="cash" ${!usedMethods.includes('cash') ? '' : 'disabled'}>Cash</option>
                                <option value="card" ${!usedMethods.includes('card') ? '' : 'disabled'}>Credit Card</option>
                                <option value="cheque" ${!usedMethods.includes('cheque') ? '' : 'disabled'}>Cheque</option>
                                <option value="bank_transfer" ${!usedMethods.includes('bank_transfer') ? '' : 'disabled'}>Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="paidOn" class="form-label">Paid On</label>
                            <input class="form-control datetimepicker payment-date" type="text" name="payment_date"
                                placeholder="DD-MM-YYYY" value="${moment().format('DD-MM-YYYY')}">
                        </div>
                        <div class="col-md-4">
                            <label for="payAmount" class="form-label">Amount</label>
                            <input type="text" class="form-control payment-amount" name="amount" oninput="validateAmount()">
                            <div class="text-danger amount-error" style="display:none;">Enter valid amount</div>
                        </div>
                    </div>
                    <div class="conditional-fields row mt-3"></div>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2 remove-payment-row" aria-label="Close"></button>
            `;
                paymentRows.appendChild(newPaymentRow);
                // Initialize date pickers
                initializeDateTimePickers();
                // Remove button handler
                newPaymentRow.querySelector('.remove-payment-row').addEventListener('click', function() {
                    this.closest('.payment-row').remove();
                    updatePaymentSummary();
                });
                togglePaymentFields(newPaymentRow.querySelector('.payment-method'));
                updatePaymentSummary();
            });

            function togglePaymentFields(selectElement) {
                const paymentMethod = selectElement.value;
                const conditionalFields = selectElement.closest('.payment-row').querySelector('.conditional-fields');
                conditionalFields.innerHTML = '';
                if (paymentMethod === 'card') {
                    conditionalFields.innerHTML =
                        `
                    <div class="col-md-6"><label class="form-label">Card Number</label><input class="form-control" name="card_number" required></div>
                    <div class="col-md-6"><label class="form-label">Card Holder Name</label><input class="form-control" name="card_holder_name"></div>
                    <div class="col-md-6"><label class="form-label">Card Type</label><select class="form-select" name="card_type">
                        <option value="visa">Visa</option><option value="mastercard">MasterCard</option><option value="amex">American Express</option>
                    </select></div>
                    <div class="col-md-6"><label class="form-label">Expiry Month</label><input class="form-control" name="card_expiry_month"></div>
                    <div class="col-md-6"><label class="form-label">Expiry Year</label><input class="form-control" name="card_expiry_year"></div>
                    <div class="col-md-6"><label class="form-label">Security Code</label><input class="form-control" name="card_security_code"></div>`;
                } else if (paymentMethod === 'cheque') {
                    conditionalFields.innerHTML =
                        `
                    <div class="col-md-6"><label class="form-label">Cheque Number</label><input class="form-control" name="cheque_number" required></div>
                    <div class="col-md-6"><label class="form-label">Bank Branch</label><input class="form-control" name="cheque_bank_branch"></div>
                    <div class="col-md-6"><label class="form-label">Received Date</label><input class="form-control datetimepicker cheque-received-date" name="cheque_received_date"></div>
                    <div class="col-md-6"><label class="form-label">Valid Date</label><input class="form-control datetimepicker cheque-valid-date" name="cheque_valid_date"></div>
                    <div class="col-md-6"><label class="form-label">Given By</label><input class="form-control" name="cheque_given_by"></div>`;
                    initializeDateTimePickers();
                } else if (paymentMethod === 'bank_transfer') {
                    conditionalFields.innerHTML =
                        `
                    <div class="col-md-12"><label class="form-label">Bank Account Number</label><input class="form-control" name="bank_account_number"></div>`;
                }
            }

            function validateAmount() {
                const amountInputs = document.querySelectorAll('.payment-amount');
                amountInputs.forEach(input => {
                    const amountError = input.nextElementSibling;
                    const val = parseFloat(input.value);
                    if (isNaN(val) || val <= 0) {
                        amountError.style.display = 'block';
                    } else {
                        amountError.style.display = 'none';
                    }
                });
                updatePaymentSummary();
            }

            function updatePaymentSummary() {
                const totalItems = fetchTotalItems();
                const totalAmount = fetchTotalAmount();
                // Apply discount if any
                const discount = parseFloat((document.getElementById('global-discount') && document.getElementById(
                        'global-discount')
                    .value) || 0);
                const discountType = (document.getElementById('discount-type') && document.getElementById('discount-type')
                    .value) || 'fixed';
                totalPayable = discountType === 'percentage' ?
                    totalAmount - (totalAmount * discount / 100) :
                    totalAmount - discount;
                let totalPaying = 0;
                document.querySelectorAll('.payment-amount').forEach(input => {
                    totalPaying += parseFloat(input.value) || 0;
                });
                let changeReturn = Math.max(totalPaying - totalPayable, 0);
                let balance = Math.max(totalPayable - totalPaying, 0);
                document.getElementById('modal-total-items').textContent = totalItems.toFixed(2);
                document.getElementById('modal-total-payable').textContent = formatAmountWithSeparators(totalPayable
                    .toFixed(
                        2));
                document.getElementById('modal-total-paying').textContent = formatAmountWithSeparators(totalPaying.toFixed(
                    2));
                document.getElementById('modal-change-return').textContent = formatAmountWithSeparators(changeReturn
                    .toFixed(
                        2));
                document.getElementById('modal-balance').textContent = formatAmountWithSeparators(balance.toFixed(2));
                // Disable add button if balance is zero
                document.getElementById('addPaymentRow').disabled = (balance === 0 && totalPaying >= totalPayable);
            }

            function fetchTotalAmount() {
                let total = 0;
                document.querySelectorAll('#billing-body tr .subtotal').forEach(cell => {
                    total += parseFloat(cell.textContent.replace(/,/g, ''));
                });
                return total;
            }

            function fetchTotalItems() {
                let count = 0;
                document.querySelectorAll('#billing-body tr .quantity-input').forEach(input => {
                    count += parseInt(input.value) || 0;
                });
                return count;
            }

            function initializeDateTimePickers() {
                $('.datetimepicker').datetimepicker({
                    format: 'DD-MM-YYYY',
                    useCurrent: false,
                    minDate: moment().startOf('day')
                });
                $('.cheque-received-date').datetimepicker({
                    format: 'DD-MM-YYYY'
                });
                $('.cheque-valid-date').datetimepicker({
                    format: 'DD-MM-YYYY',
                    minDate: moment().add(1, 'days')
                });
            }
            document.getElementById('paymentModal').addEventListener('show.bs.modal', function() {
                // Reset form
                document.getElementById('paymentForm').reset();
                document.getElementById('paymentRows').innerHTML = ''; // Clear all rows
                // Set default first cash row
                const totalAmount = fetchTotalAmount();
                // Apply global discount to get the actual payable amount
                const discount = parseFloat((document.getElementById('global-discount') && document.getElementById(
                        'global-discount')
                    .value) || 0);
                const discountType = (document.getElementById('discount-type') && document.getElementById(
                        'discount-type')
                    .value) || 'fixed';
                const defaultAmount = discountType === 'percentage' ?
                    totalAmount - (totalAmount * discount / 100) :
                    totalAmount - discount;
                const defaultRow = document.createElement('div');
                defaultRow.className = 'card mb-3 payment-row position-relative';
                defaultRow.innerHTML = `
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select payment-method" name="payment_method" disabled onchange="togglePaymentFields(this)">
                                <option value="cash" selected>Cash</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Paid On</label>
                            <input class="form-control datetimepicker payment-date" type="text" name="payment_date"
                                placeholder="DD-MM-YYYY" value="${moment().format('DD-MM-YYYY')}">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Amount</label>
                            <input type="text" class="form-control payment-amount" name="amount"
                                value="${defaultAmount.toFixed(2)}" oninput="validateAmount()">
                            <div class="text-danger amount-error" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="conditional-fields row mt-3"></div>
                </div>
            `;
                document.getElementById('paymentRows').appendChild(defaultRow);
                initializeDateTimePickers();
                updatePaymentSummary();
            });
        </script>

         <script>
            // Mobile payment button handlers
            document.addEventListener('DOMContentLoaded', function() {
                // Update mobile totals when main totals change
                function updateMobileTotals() {
                    const finalTotal = document.getElementById('final-total-amount')?.textContent || '0.00';
                    const itemsCount = document.getElementById('total-items-count')?.textContent || '0';

                    // Update mobile final total with null check
                    const mobileFinalTotalEl = document.getElementById('mobile-final-total');
                    if (mobileFinalTotalEl) {
                        mobileFinalTotalEl.textContent = 'Rs ' + finalTotal;
                    }

                    // Update modal final total with null check
                    const modalFinalTotalEl = document.getElementById('modal-final-total');
                    if (modalFinalTotalEl) {
                        modalFinalTotalEl.textContent = 'Rs ' + finalTotal;
                    }

                    // Update mobile items count with null check
                    const mobileItemsCountEl = document.getElementById('mobile-items-count');
                    if (mobileItemsCountEl) {
                        mobileItemsCountEl.textContent = itemsCount;
                    }
                }

                // Call update function periodically
                setInterval(updateMobileTotals, 500);

                // Mobile payment method buttons
                document.querySelectorAll('.mobile-payment-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const paymentType = this.getAttribute('data-payment');

                        switch (paymentType) {
                            case 'cash':
                                document.getElementById('cashButton')?.click();
                                break;
                            case 'card':
                                document.getElementById('cardButton')?.click();
                                break;
                            case 'cheque':
                                document.getElementById('chequeButton')?.click();
                                break;
                            case 'credit':
                                document.getElementById('creditSaleButton')?.click();
                                break;
                        }
                    });
                });

                // Mobile action buttons
                document.querySelectorAll('.mobile-action-btn').forEach(button => {
                    button.addEventListener('click', function() {
                        const actionType = this.getAttribute('data-action');

                        switch (actionType) {
                            case 'draft':
                                document.getElementById('draftButton')?.click();
                                break;
                            case 'sale-order':
                                document.getElementById('saleOrderButton')?.click();
                                break;
                            case 'quotation':
                                document.getElementById('quotationButton')?.click();
                                break;
                            case 'job-ticket':
                                document.getElementById('jobTicketButton')?.click();
                                break;
                        }
                    });
                });

                // Mobile cancel button
                document.getElementById('mobile-cancel-button')?.addEventListener('click', function() {
                    document.getElementById('cancelButton')?.click();
                });
            });
        </script>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <!-- Include Bootstrap JS -->
    @include('sell.pos_ajax')
    @include('contact.customer.customer_ajax')
    @include('contact.customer.cities_ajax')
    @include('contact.customer.add_customer_modal')
    @include('contact.customer.city_modal')

</body>

</html>
