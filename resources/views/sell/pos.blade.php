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
    <link rel="stylesheet" href="{{ asset('vendor/font-awesome/css/all.min.css') }}">
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
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert/css/sweetalert.min.css') }}">

    <link rel="stylesheet" href="{{ asset('assets/css/pos_page_style/pos-main.css') }}">

    <!-- Sales Rep Payment Button Control CSS -->
    <style>
        /* Force hide payment buttons for sales reps on parent location */
        .sales-rep-hide-payment {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        /* Force show sale order button */
        .sales-rep-show-sale-order {
            display: block !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* Ensure offcanvas appears above mobile product modal */
        #offcanvasCategory,
        #offcanvasBrand,
        #offcanvasSubcategory {
            z-index: 10500 !important;
        }

        .offcanvas-backdrop {
            z-index: 10499 !important;
        }

        #mobileProductModal {
            z-index: 10400 !important;
        }

        /* Mobile Quantity Modal - highest z-index */
        #mobileQuantityModal {
            z-index: 10600 !important;
        }

        #mobileQuantityModal .modal-backdrop {
            z-index: 10599 !important;
        }

        #mobileQuantityModal .modal-dialog {
            z-index: 10601 !important;
        }

        /* Calculator & sale return (invoice) dropdowns above main content (no clipping/hiding) */
        #posHeaderCard {
            position: relative;
            z-index: 1020;
            overflow: visible;
        }
        #posHeaderCard .dropdown-menu {
            z-index: 1030 !important;
        }
        #invoiceDropdown {
            min-width: 320px;
        }
    </style>
</head>

<body>
    <div class="pos-mobile-scroll-wrapper">
    <!-- Include Sales Rep Vehicle/Route Selection Modal -->
    @include('components.sales-rep-modal')

    <div class="container-fluid p-1">
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-white p-1" id="posHeaderCard">
                    <!-- Mobile/Tablet View: blue cart icon, location, time, trash & menu (full mobile UI below 992px) -->
                    <div class="d-lg-none pos-mobile-header">
                        <div class="d-flex align-items-center w-100">
                            <div class="pos-mobile-brand d-flex align-items-center gap-2 flex-grow-1 min-width-0">
                                <div class="pos-mobile-brand-icon rounded-circle d-flex align-items-center justify-content-center flex-shrink-0">
                                    <i class="fas fa-shopping-cart text-white"></i>
                                </div>
                                <div class="pos-mobile-brand-content min-width-0 flex-grow-1">
                                    <select id="locationSelect" class="form-select form-select-sm selectBox pos-mobile-location-select border-0 bg-transparent p-0 text-dark fw-semibold" style="font-size: 0.8rem; max-width: 100%;">
                                        <option value="" selected disabled>Select Location</option>
                                    </select>
                                    <span id="currentTimeTextMobile" class="pos-mobile-time text-muted d-block" style="font-size: 0.7rem;">{{ \Carbon\Carbon::now('Asia/Colombo')->format('H:i:s') }}</span>
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <button type="button" class="btn pos-mobile-header-btn rounded-pill p-0" id="mobileClearCartBtn" title="Clear cart" aria-label="Clear cart">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                                <button type="button" class="btn pos-mobile-header-btn rounded-pill p-0" data-bs-toggle="modal" data-bs-target="#mobileMenuModal" title="Menu" aria-label="Menu">
                                    <i class="fas fa-bars"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Desktop View: Two Columns (992px and up) -->
                    <div class="row align-items-center d-none d-lg-flex" style="margin: 0; padding: 4px 6px;">
                        <!-- Location and Date Section -->
                        <div class="col-md-6 d-flex align-items-center" style="padding: 0 6px;">
                            <div class="d-flex flex-row align-items-center" style="gap: 6px;">
                                <select id="locationSelectDesktop" class="form-select selectBox location-select-sync"
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
                                        <div class='col-6'>Quick entry (Price)</div>
                                        <div class='col-6'>Ctrl+Q</div>
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
                        <div class="col-md-6" style="padding: 0 10px;">
                            <div class="d-flex justify-content-end align-items-center flex-wrap" style="gap: 10px;">
                                <button class="btn btn-secondary btn-sm"
                                    onclick="handleGoHome()"
                                    data-bs-toggle="tooltip" title="Go home">
                                    <i class="fas fa-home"></i>
                                </button>

                                <button class="btn btn-light btn-sm" onclick="handleGoBack()"
                                    data-bs-toggle="tooltip" title="Go Back">
                                    <i class="fas fa-backward"></i>
                                </button>

                                <!-- Cash Register: Drawer balance + actions -->
                                <div class="pos-cash-drawer-wrap d-none d-md-flex align-items-center gap-2 me-2">
                                    <span class="text-nowrap fw-semibold" style="font-size: 0.9rem;">
                                        <i class="fas fa-cash-register me-1"></i> Drawer: <span id="posCashDrawerBalance">Rs. 0.00</span>
                                    </span>
                                    <div class="dropdown">
                                        <button class="btn btn-success btn-sm dropdown-toggle" id="posCashRegisterDropdown" data-bs-toggle="dropdown" aria-expanded="false" title="Cash register">
                                            <i class="fas fa-wallet"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#posPayInModal"><i class="fas fa-plus-circle text-success me-2"></i> Pay In</button></li>
                                            <li><button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#posPayOutModal"><i class="fas fa-minus-circle text-warning me-2"></i> Pay Out</button></li>
                                            <li><button type="button" class="dropdown-item" id="posAddExpenseBtn"><i class="fas fa-receipt text-info me-2"></i> Add Expense</button></li>
                                            <li><hr class="dropdown-divider"></li>
                                            <li><button type="button" class="dropdown-item text-danger" id="posCloseRegisterBtn"><i class="fas fa-lock me-2"></i> Close Register</button></li>
                                        </ul>
                                    </div>
                                </div>

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
                                        title="Enter Invoice No (Sale Return)">
                                        <i class="fas fa-redo-alt"></i>
                                    </button>
                                    <div class="dropdown-menu p-3" id="invoiceDropdown">
                                        <label for="invoiceNo" class="form-label mb-0">Enter Invoice No</label>
                                        <small class="text-muted d-block mb-2">For Sale Return</small>
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
                                    data-bs-toggle="modal" data-bs-target="#mobileProductModal">
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
                            <div class="row pos-mobile-top-section">
                                <div class="col-md-5 pe-2">
                                    <div class="d-flex align-items-center customer-select2 pos-customer-row">
                                        <span class="d-inline d-md-none me-2 text-muted" aria-hidden="true"><i class="fas fa-user"></i></span>
                                        <select class="form-control selectBox" id="customer-id" style="flex: 1;">
                                            <option selected disabled>Please Select</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-info rounded-0 pos-add-customer-btn" id="addCustomerButton" title="Add customer">
                                            <i class="fas fa-plus-circle d-none d-md-inline-block"></i>
                                            <i class="fas fa-plus d-md-none"></i>
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
                                    <div class="input-group pos-search-row">
                                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                        <input type="text" class="form-control" id="productSearchInput"
                                            placeholder="Enter Product name / SKU / Scan...">
                                        <!-- Product list button: show only on mobile + tablet (< 992px), hide on desktop -->
                                        <button type="button" class="btn btn-outline-secondary d-lg-none pos-search-grid-btn"
                                            data-bs-toggle="modal" data-bs-target="#mobileProductModal"
                                            title="Product list" aria-label="Product list">
                                            <i class="fas fa-th-large text-muted"></i>
                                        </button>
                                        @if($canUseQuickPriceEntry)
                                        <button id="cashEntryToggle" type="button"
                                            class="btn btn-outline-secondary d-none d-md-inline-flex"
                                            title="Quick price entry">
                                            <i class="fas fa-tag"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Quick Price Entry bar — shown/hidden via the tag button in billing header -->
                            @if($canUseQuickPriceEntry)
                            <div id="cashEntryBar" class="collapse mt-1">
                                <div class="d-flex align-items-center gap-2 px-2 py-1 rounded"
                                     style="background:#fffbea;">
                                    <i class="fas fa-tag text-warning"></i>
                                    <input type="number" id="cashPriceInput" class="form-control form-control-sm"
                                        placeholder="Price" step="0.01" min="0.01"
                                        style="width:110px;" autocomplete="off">
                                    <input type="number" id="cashQtyInput" class="form-control form-control-sm"
                                        placeholder="Qty" value="1" min="0.01" step="0.01"
                                        style="width:70px;" autocomplete="off">
                                    <span class="text-muted small">↵ Enter to add</span>
                                    <button id="cashEntryClose" type="button"
                                        class="btn btn-sm btn-outline-secondary ms-auto"
                                        title="Hide quick entry" style="padding:1px 7px;">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            @endif

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
                                                    @if($freeQtyEnabled && $canUseFreeQty)
                                                    <th class="text-center" id="freeQtyTh">Free Qty</th>
                                                    @endif
                                                    <th class="text-center" colspan="2">Discount</th>
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

                            <!-- Item Counter Section - Fixed at bottom of billing card (desktop) -->
                            <div class="row d-none d-md-flex pos-desktop-item-counter"
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

                            <!-- Total, Discount, Final Total Section - Desktop only (hidden below 992px; use Order Summary card on mobile/tablet) -->
                            <div class="row align-items-end pos-order-summary-row d-none d-lg-flex"
                                style="margin: 0; border-top: 2px solid #ddd; background-color: #fff; padding: 10px;">
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 11px; font-weight: 600; margin-bottom: 2px; display: block;">Total</label>
                                        <p id="total-amount" class="form-control form-control-sm mb-0"
                                            style="height: 30px; line-height: 20px; font-size: 13px; font-weight: 600;">
                                            0.00</p>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 11px; font-weight: 600; margin-bottom: 2px; display: block;">Global
                                            Discount Type</label>
                                        <div class="btn-group w-100" role="group" aria-label="Discount Type"
                                            style="height: 30px; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <button type="button" class="btn btn-primary active"
                                                id="fixed-discount-btn"
                                                style="font-size: 11px; padding: 0; font-weight: 600; height: 30px; border: none; border-radius: 0; transition: all 0.3s ease;">Fixed</button>
                                            <button type="button" class="btn btn-outline-primary"
                                                id="percentage-discount-btn"
                                                style="font-size: 11px; padding: 0; font-weight: 600; height: 30px; border: none; border-radius: 0; background: white; transition: all 0.3s ease;">%</button>
                                        </div>
                                        <input type="hidden" id="discount-type" name="discount_type"
                                            value="fixed">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 11px; font-weight: 600; margin-bottom: 2px; display: block;">Discount</label>
                                        <div class="input-group input-group-sm" style="height: 30px;">
                                            <input type="text" id="global-discount" name="discount"
                                                class="form-control form-control-sm" placeholder="0.00"
                                                style="height: 30px; font-size: 13px; font-weight: 600;">
                                            <span class="input-group-text" id="discount-icon"
                                                style="height: 30px; font-size: 12px; font-weight: 600;">Rs</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 11px; font-weight: 600; margin-bottom: 2px; display: block;">Final
                                            Total</label>
                                        <p id="final-total-amount" class="form-control form-control-sm mb-0"
                                            style="height: 30px; line-height: 20px; font-size: 13px; font-weight: 600;">
                                            0.00</p>
                                    </div>
                                </div>
                                <!-- Shipping Button Column -->
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 11px; font-weight: 600; margin-bottom: 2px; display: block;">Shipping</label>
                                        <button class="btn btn-outline-info w-100 pos-shipping-btn" data-bs-toggle="modal"
                                            data-bs-target="#shippingModal" id="shippingButton"
                                            style="height: 30px; font-size: 12px; font-weight: 600; padding: 4px 8px;">
                                            <i class="fas fa-truck d-md-none"></i><i class="fas fa-shipping-fast d-none d-md-inline-block"></i>
                                            <span class="d-md-none">Add Shipping Details</span><span class="d-none d-md-inline">Shipping</span>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label
                                            style="font-size: 11px; font-weight: 600; margin-bottom: 2px; display: block;">Amount
                                            Given</label>
                                        <input type="text" id="amount-given" class="form-control form-control-sm"
                                            placeholder="0.00" oninput="formatAmount(this)"
                                            style="height: 30px; font-size: 13px; font-weight: 600;">
                                    </div>
                                </div>
                            </div>

                            <!-- Sale Notes Section (desktop only; on mobile/tablet use Internal Notes in Order Summary card) -->
                            <div class="row mt-1 pos-desktop-sale-notes-row d-none d-lg-flex" style="margin: 0; background-color: #fff; padding: 4px 8px;">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <label for="sale-notes-textarea"
                                            style="font-size: 12px; font-weight: 600; margin-bottom: 2px; display: block;">
                                            <i class="fas fa-sticky-note me-1"></i>Sale Notes / Description (Optional)
                                        </label>
                                        <textarea id="sale-notes-textarea" class="form-control form-control-sm"
                                            placeholder="Add notes, reference, or description for this sale..."
                                            rows="1"
                                            style="font-size: 12px; resize: vertical; min-height: 32px !important; max-height: 50px !important; padding: 4px 6px !important;"></textarea>
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

                            <div class="row g-2 g-md-2 overflow-auto" id="posProduct"
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
                <div class="mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control pos-offcanvas-search" id="categorySearchInput" placeholder="Search category..." autocomplete="off">
                    </div>
                </div>
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
                <div class="mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control pos-offcanvas-search" id="subcategorySearchInput" placeholder="Search subcategory..." autocomplete="off">
                    </div>
                </div>
                <div id="subcategoryContainer" class="subcategory-container">
                    <!-- Subcategories will be dynamically injected here -->
                </div>
            </div>
        </div>

        <!-- Mobile Product View Modal -->
        <div class="modal fade" id="mobileProductModal" tabindex="-1" aria-labelledby="mobileProductModalLabel">
            <div class="modal-dialog modal-fullscreen">
                <div class="modal-content">
                    <div class="modal-header bg-white border-bottom sticky-top">
                        <h5 class="modal-title" id="mobileProductModalLabel">
                            <i class="fas fa-box me-2"></i>Products
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-2" id="mobileProductModalBody" style="overflow-y: auto; max-height: calc(100vh - 120px); min-height: 0;">
                        <!-- Filter Buttons -->
                        <div class="mb-3 d-flex gap-2 flex-wrap sticky-top bg-white pb-2" style="z-index: 1;">
                            <button type="button" class="btn btn-primary btn-sm" id="mobileAllProductsBtn">
                                <i class="fas fa-box"></i> All Products
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="mobileCategoryBtn">
                                <i class="fas fa-th-large"></i> Category
                            </button>
                            <button type="button" class="btn btn-primary btn-sm" id="mobileBrandBtn">
                                <i class="fas fa-tags"></i> Brand
                            </button>
                        </div>
                        <!-- Product Grid -->
                        <div class="row g-2" id="mobileProductGrid">
                            <!-- Products will be populated here -->
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Quantity Input Modal -->
        <div class="modal fade" id="mobileQuantityModal" tabindex="-1" aria-labelledby="mobileQuantityModalLabel">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header border-0 pb-0">
                        <h6 class="modal-title" id="mobileQtyProductName">Product Name</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center py-3">
                        <small class="text-muted d-block mb-2" id="mobileQtyAvailable">Available: 0</small>
                        <input type="number" class="form-control form-control-lg text-center fw-bold" id="mobileQtyInput"
                               placeholder="0" min="1" step="1" autofocus style="font-size: 24px; height: 60px;">
                        <small class="text-danger d-block mt-2" id="mobileQtyError" style="display: none;"></small>
                    </div>
                    <div class="modal-footer border-0 pt-0">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="mobileQtyConfirm">Add</button>
                    </div>
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
                <div class="mb-2">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        <input type="text" class="form-control pos-offcanvas-search" id="brandSearchInput" placeholder="Search brand..." autocomplete="off">
                    </div>
                </div>
                <div id="brandContainer" class="category-container">
                    <!-- Brands will be dynamically injected here -->
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
                                <button type="button" id="mobileCashBtn"
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
                                <button type="button" id="mobileCardBtn"
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
                                <button type="button" id="mobileChequeBtn"
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
                                <button type="button" id="mobileCreditBtn"
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
                                <button type="button" id="mobileMultiplePayBtn"
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

                        <!-- Other Actions Section - Enhanced Beautiful Design -->
                        <div class="mt-3">
                            <div class="px-3 py-2">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="flex-grow-1">
                                        <hr class="my-0">
                                    </div>
                                    <span class="px-3 text-muted small fw-semibold">OR</span>
                                    <div class="flex-grow-1">
                                        <hr class="my-0">
                                    </div>
                                </div>

                                <div class="row g-2">
                                    @can('save draft')
                                        <div class="col-6" id="mobileDraftBtnCol">
                                            <button type="button" class="btn btn-light border w-100 mobile-action-btn shadow-sm"
                                                data-action="draft" data-bs-dismiss="modal"
                                                style="border-radius: 12px;">
                                                <i class="fas fa-edit text-info d-block mb-1"></i>
                                                <small class="fw-semibold text-dark">Draft</small>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('create sale-order')
                                        <div class="col-12" id="mobileSaleOrderBtnCol">
                                            <button type="button" class="btn btn-success w-100 mobile-action-btn shadow-sm"
                                                data-action="sale-order" data-bs-dismiss="modal"
                                                style="border-radius: 12px;">
                                                <i class="fas fa-shopping-cart me-2"></i>
                                                <span class="fw-semibold">Create Sale Order</span>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('create quotation')
                                        <div class="col-6" id="mobileQuotationBtnCol">
                                            <button type="button" class="btn btn-light border w-100 mobile-action-btn shadow-sm"
                                                data-action="quotation" data-bs-dismiss="modal"
                                                style="border-radius: 12px;">
                                                <i class="fas fa-file-alt text-warning d-block mb-1"></i>
                                                <small class="fw-semibold text-dark">Quotation</small>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('create job-ticket')
                                        <div class="col-6" id="mobileJobTicketBtnCol">
                                            <button type="button"
                                                class="btn btn-light border w-100 mobile-action-btn shadow-sm"
                                                data-action="job-ticket" data-bs-dismiss="modal"
                                                style="border-radius: 12px;">
                                                <i class="fas fa-ticket-alt text-secondary d-block mb-1"></i>
                                                <small class="fw-semibold text-dark">Job Ticket</small>
                                            </button>
                                        </div>
                                    @endcan

                                    @can('suspend sale')
                                        <div class="col-6" id="mobileSuspendBtnCol">
                                            <button type="button" class="btn btn-light border w-100 mobile-action-btn shadow-sm"
                                                data-action="suspend" data-bs-dismiss="modal" data-bs-toggle="modal"
                                                data-bs-target="#suspendModal"
                                                style="border-radius: 12px;">
                                                <i class="fas fa-pause text-danger d-block mb-1"></i>
                                                <small class="fw-semibold text-dark">Suspend</small>
                                            </button>
                                        </div>
                                    @endcan
                                </div>

                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-secondary w-100"
                                        id="mobile-cancel-button"
                                        style="border-radius: 12px; padding: 12px 16px; border-width: 2px;">
                                        <i class="fas fa-times-circle me-2"></i>
                                        <span class="fw-semibold">Cancel Order</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Modern POS Footer: desktop only (992px and up) -->
        <div class="pos-footer-modern bottom-fixed d-none d-lg-block">
            <div class="pos-footer-inner">
                <!-- Left: More + separator + Cancel -->
                <div class="pos-footer-left">
                    <div class="dropdown dropup pos-footer-more-dropdown">
                        <button class="btn pos-footer-more-btn dropdown-toggle" type="button" id="posFooterMoreDropdown" data-bs-toggle="dropdown" aria-expanded="false" aria-haspopup="true">
                            <i class="fas fa-ellipsis-h"></i> More
                        </button>
                        <ul class="dropdown-menu pos-footer-more-menu" aria-labelledby="posFooterMoreDropdown">
                            @can('create job-ticket')
                                <li><button type="button" class="dropdown-item pos-footer-trigger-item" data-trigger-id="jobTicketButton"><i class="fas fa-ticket-alt me-2"></i>Job Ticket</button></li>
                            @endcan
                            @can('create quotation')
                                <li><button type="button" class="dropdown-item pos-footer-trigger-item" data-trigger-id="quotationButton"><i class="fas fa-file-alt me-2"></i>Quotation</button></li>
                            @endcan
                            @can('save draft')
                                <li><button type="button" class="dropdown-item pos-footer-trigger-item" data-trigger-id="draftButton"><i class="fas fa-edit me-2"></i>Draft</button></li>
                            @endcan
                            @can('create sale-order')
                                <li><button type="button" class="dropdown-item pos-footer-trigger-item" data-trigger-id="saleOrderButton"><i class="fas fa-shopping-cart me-2"></i>Sale Order</button></li>
                            @endcan
                            @can('suspend sale')
                                <li><button type="button" class="dropdown-item pos-footer-trigger-item" data-trigger-id="suspendTriggerBtn"><i class="fas fa-pause me-2"></i>Suspend</button></li>
                            @endcan
                            @can('cheque payment')
                                <li><button type="button" class="dropdown-item pos-footer-trigger-item" data-trigger-id="chequeButton"><i class="fas fa-money-check me-2"></i>Cheque</button></li>
                            @endcan
                        </ul>
                    </div>
                    <span class="pos-footer-sep" aria-hidden="true"></span>
                    <button type="button" class="btn pos-footer-btn pos-footer-btn-cancel" id="cancelButton">
                        <i class="fas fa-times"></i> <span>CANCEL</span>
                    </button>
                    <div class="d-none pos-footer-hidden-triggers">
                        @can('create job-ticket') <button type="button" id="jobTicketButton"></button> @endcan
                        @can('create quotation') <button type="button" id="quotationButton"></button> @endcan
                        @can('save draft') <button type="button" id="draftButton"></button> @endcan
                        @can('create sale-order') <button type="button" id="saleOrderButton"></button> @endcan
                        @can('suspend sale') <button type="button" id="suspendTriggerBtn" data-bs-toggle="modal" data-bs-target="#suspendModal"></button> @endcan
                        @can('cheque payment') <button type="button" id="chequeButton"></button> @endcan
                    </div>
                </div>

                <!-- Center: Total Payable + amount -->
                <div class="pos-footer-center">
                    <div class="pos-footer-total-wrap">
                        <span class="pos-footer-total-label">TOTAL PAYABLE</span>
                        <span id="total" class="pos-footer-total-amount"><span class="pos-footer-currency">Rs</span> <span class="pos-footer-amount-value">0.00</span></span>
                        <span id="items-count" class="pos-footer-items-count">(0)</span>
                    </div>
                </div>

                <!-- Right: Payment method buttons -->
                <div class="pos-footer-right">
                    @can('cash payment')
                    <button type="button" class="btn pos-footer-btn pos-footer-btn-cash" id="cashButton">
                        <i class="fas fa-money-bill-wave"></i> <span>CASH</span>
                    </button>
                    @endcan
                    @can('card payment')
                    <button type="button" class="btn pos-footer-btn pos-footer-btn-card" id="cardButton">
                        <i class="fas fa-credit-card"></i> <span>CARD</span>
                    </button>
                    @endcan
                    @can('credit sale')
                    <button type="button" class="btn pos-footer-btn pos-footer-btn-credit" id="creditSaleButton">
                        <i class="fas fa-check-circle"></i> <span>CREDIT</span>
                    </button>
                    @endcan
                    @can('multiple payment methods')
                    <button type="button" class="btn pos-footer-btn pos-footer-btn-multiple" data-bs-toggle="modal" data-bs-target="#paymentModal">
                        <i class="fas fa-list"></i> <span>MULTIPLE</span>
                    </button>
                    @endcan
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
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Select Batch Price</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Batch No</th>
                                @can('select retail price')
                                <th>Retail Price</th>
                                @endcan
                                @can('select wholesale price')
                                <th>Wholesale Price</th>
                                @endcan
                                @can('select special price')
                                <th>Special Price</th>
                                @endcan
                                @can('select max retail price')
                                <th>Max Retail Price</th>
                                @endcan
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

    <!-- Cash Register Modals -->
    <div class="modal fade" id="posOpenRegisterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Open Register</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="text-muted small">Enter the opening cash amount in the drawer.</p>
                    <label class="form-label">Opening amount (Rs.)</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="posOpeningAmount" placeholder="0.00">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="posOpenRegisterBtn">Open Register</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="posPayInModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Pay In</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Amount (Rs.)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control mb-2" id="posPayInAmount" placeholder="0.00">
                    <label class="form-label">Notes (optional)</label>
                    <input type="text" class="form-control" id="posPayInNotes" placeholder="e.g. Change fund">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="posPayInConfirmBtn">Record Pay In</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="posPayOutModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Pay Out</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <label class="form-label">Amount (Rs.)</label>
                    <input type="number" step="0.01" min="0.01" class="form-control mb-2" id="posPayOutAmount" placeholder="0.00">
                    <label class="form-label">Notes (optional)</label>
                    <input type="text" class="form-control" id="posPayOutNotes" placeholder="e.g. Bank deposit">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" id="posPayOutConfirmBtn">Record Pay Out</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="posCloseRegisterModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Close Register</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <p class="mb-2">Expected balance (system): <strong id="posCloseExpectedBalance">Rs. 0.00</strong></p>
                    <label class="form-label">Counted cash in drawer (Rs.)</label>
                    <input type="number" step="0.01" min="0" class="form-control mb-2" id="posClosingAmount" placeholder="0.00">
                    <label class="form-label">Notes (optional)</label>
                    <input type="text" class="form-control" id="posCloseNotes" placeholder="e.g. Over/short reason">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="posCloseRegisterConfirmBtn">Close Register</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal fade" id="posExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title">Add Expense (from drawer)</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body">
                    <input type="hidden" id="posExpenseRegisterId">
                    <input type="hidden" id="posExpenseLocationId">
                    <div class="mb-2">
                        <label class="form-label">Category</label>
                        <select class="form-select" id="posExpenseParentCategory"><option value="">Select category</option></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Subcategory (optional)</label>
                        <select class="form-select" id="posExpenseSubCategory"><option value="">Select subcategory</option></select>
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Amount (Rs.) *</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="posExpenseAmount" placeholder="0.00">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Paid to (optional)</label>
                        <input type="text" class="form-control" id="posExpensePaidTo" placeholder="Name or description">
                    </div>
                    <div class="mb-2">
                        <label class="form-label">Note (optional)</label>
                        <input type="text" class="form-control" id="posExpenseNote" placeholder="Note">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="posExpenseSubmitBtn">Add Expense</button>
                </div>
            </div>
        </div>
    </div>

    <!-- POS Configuration (Blade-to-JS bridge) is in @include('sell.partials.pos-config') below -->

    @include('sell.partials.pos-vendor-scripts')
    @include('sell.partials.pos-notifications')
    @include('sell.partials.pos-config')

    {{--
        POS JavaScript load order (do not change lightly):
        1) Vendor scripts      → @include('sell.partials.pos-vendor-scripts')
        2) Notifications       → @include('sell.partials.pos-notifications')
        3) Config bridge       → @include('sell.partials.pos-config') exposes window.PosConfig + globals
        4) POS modules (Vite)  → @vite('resources/js/pos/*.js') – all page logic lives here
        5) Customer includes   → contact.* partials (AJAX + modals)
    --}}

    <!-- POS JS Modules -->
    @vite('resources/js/pos/pos-utils.js')
    @vite('resources/js/pos/pos-cache.js')
    @vite('resources/js/pos/pos-ui.js')
    @vite('resources/js/pos/pos-customer.js')
    @vite('resources/js/pos/pos-salesrep.js')
    @vite('resources/js/pos/pos-location.js')
    @vite('resources/js/pos/pos-cash-register.js')
    @vite('resources/js/pos/pos-product-grid.js')
    @vite('resources/js/pos/pos-autocomplete.js')
    @vite('resources/js/pos/pos-cart.js')
    @vite('resources/js/pos/pos-product-display.js')
    @vite('resources/js/pos/pos-billing.js')
    @vite('resources/js/pos/pos-product-select.js')
    @vite('resources/js/pos/pos-sale.js')
    @vite('resources/js/pos/pos-sales-list.js')
    @vite('resources/js/pos/pos-salesrep-display.js')
    @vite('resources/js/pos/pos-receipt.js')
    @vite('resources/js/pos/pos-helpers.js')
    @vite('resources/js/pos/pos-hotkeys.js')
    @vite('resources/js/pos/pos-init.js')
    @vite('resources/js/pos/pos-page.js')
    @vite('resources/js/pos/pos-payment.js')
    @include('contact.customer.customer_ajax')
    @include('contact.customer.cities_ajax')
    @include('contact.customer.add_customer_modal')
    @include('contact.customer.city_modal')

    <!-- Modern footer: dropdown items trigger hidden buttons (keep existing JS bindings) -->
    <script>
    document.addEventListener('DOMContentLoaded', function () {
        document.querySelector('.pos-footer-modern')?.addEventListener('click', function (e) {
            var item = e.target.closest('.pos-footer-trigger-item');
            if (item) {
                var id = item.getAttribute('data-trigger-id');
                if (id) { var el = document.getElementById(id); if (el) el.click(); }
            }
        });
    });
    </script>

    </div><!-- /.pos-mobile-scroll-wrapper -->

    <!-- Mobile/Tablet Order Summary – fixed above PAY TOTAL bar (outside scroll so "X Items" / totals always visible) -->
    <div class="d-lg-none pos-mobile-order-summary-fixed">
        <div class="pos-mobile-order-summary-card card border-0 shadow-sm mb-0" style="border-radius: 12px; overflow: hidden;">
            <div class="card-body p-3 pos-mobile-summary-body">
                <!-- Top row: X Items | Details tabs + Fixed | Percent toggle -->
                <div class="d-flex align-items-center justify-content-between mb-3 pos-mobile-summary-tabs">
                    <div class="d-flex align-items-center gap-2">
                        <span class="pos-mobile-tab pos-mobile-tab-active">
                            <i class="fas fa-circle text-primary me-1" style="font-size: 0.4rem; vertical-align: middle;"></i>
                            <span id="mobile-summary-items-count">0</span> Items
                        </span>
                        <span class="pos-mobile-tab" data-bs-toggle="collapse" data-bs-target="#posMobileDetailsCollapse" aria-expanded="false">
                            <i class="fas fa-info-circle text-muted me-1"></i>Details
                        </span>
                    </div>
                    <div class="btn-group btn-group-sm pos-mobile-discount-toggle" role="group">
                        <button type="button" class="btn btn-primary btn-sm active" id="mobile-fixed-discount-btn" style="font-size: 10px; padding: 4px 10px;">Fixed</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="mobile-percentage-discount-btn" style="font-size: 10px; padding: 4px 10px;">Percent (%)</button>
                    </div>
                </div>
                <!-- Total Subtotal | Discount same row -->
                <div class="row g-2 mb-2 pos-mobile-summary-row">
                    <div class="col-6">
                        <div class="text-muted small">Total Subtotal</div>
                        <div id="mobile-total-amount-text" class="fw-bold text-dark" style="font-size: 1.1rem;">Rs. 0.00</div>
                    </div>
                    <div class="col-6 text-end">
                        <div class="text-muted small">Discount</div>
                        <div id="mobile-total-discount-text" class="fw-semibold text-danger" style="font-size: 1rem;">Rs. 0.00</div>
                    </div>
                </div>
                <!-- Global discount: label + input inline (no detached floating field) -->
                <div class="d-flex align-items-center gap-2 mb-2 pos-mobile-discount-row">
                    <span class="text-muted small">Discount amount</span>
                    <input type="text" id="mobile-global-discount" class="form-control form-control-sm" placeholder="0" value="0" style="max-width: 72px; height: 30px; font-weight: 600;">
                </div>
                <!-- Add Shipping: white button blue border + edit -->
                <div class="d-flex align-items-center gap-2 mb-2">
                    <button type="button" class="btn pos-mobile-add-shipping-btn flex-grow-1" data-bs-toggle="modal" data-bs-target="#shippingModal" id="shippingButtonMobile" title="Add shipping">
                        <i class="fas fa-truck me-1"></i>Add Shipping
                    </button>
                    <button type="button" class="btn pos-mobile-shipping-edit-btn" data-bs-toggle="modal" data-bs-target="#shippingModal" title="Edit shipping"><i class="fas fa-edit"></i></button>
                </div>
                <!-- Final Total -->
                <div class="d-flex justify-content-between align-items-center pt-2 border-top pos-mobile-final-row">
                    <span class="fw-bold">Final Total</span>
                    <span id="mobile-final-total-inline" class="fw-bold text-primary" style="font-size: 1.1rem;">Rs. 0.00</span>
                </div>
                <!-- Details collapse: Total Items + Internal Notes only (Add Shipping is single button above) -->
                <div class="collapse mt-2" id="posMobileDetailsCollapse">
                    <div class="small text-muted mb-2"><span class="fw-semibold">Total Items:</span> <span id="mobile-total-items-text">0 (0 units)</span></div>
                    <div class="accordion accordion-flush" id="posMobileAccordion">
                        <div class="accordion-item border-0 border-top">
                            <h2 class="accordion-header">
                                <button class="accordion-button collapsed py-2 bg-white" type="button" data-bs-toggle="collapse" data-bs-target="#collapseNotesMobile" aria-expanded="false" style="font-size: 0.85rem;">
                                    <i class="fas fa-sticky-note me-2 text-muted"></i>Internal Notes
                                </button>
                            </h2>
                            <div id="collapseNotesMobile" class="accordion-collapse collapse" data-bs-parent="#posMobileAccordion">
                                <div class="accordion-body py-2">
                                    <textarea id="sale-notes-textarea-mobile" class="form-control form-control-sm" placeholder="Add notes..." rows="2" style="font-size: 12px;"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile/Tablet Bottom Bar – fixed to viewport bottom (outside scrollable content) -->
    <div class="mobile-bottom-fixed d-lg-none">
        <div class="mobile-bottom-container">
            <button type="button" class="btn pos-mobile-pay-full-btn w-100 d-flex align-items-center justify-content-between px-2 py-2" data-bs-toggle="modal" data-bs-target="#mobilePaymentModal">
                <div class="d-flex align-items-center gap-2 text-start">
                    <span class="pos-mobile-cart-badge rounded-circle d-flex align-items-center justify-content-center flex-shrink-0" id="mobile-cart-badge">
                        <span id="mobile-items-count">0</span>
                    </span>
                    <div class="min-width-0">
                        <div class="text-white text-uppercase fw-semibold mb-0 pos-mobile-final-label">PAY TOTAL</div>
                        <div class="text-white fw-bold lh-1 mt-0 pos-mobile-final-amount" id="mobile-final-total">Rs. 0.00</div>
                    </div>
                </div>
                <span class="text-white fw-semibold d-flex align-items-center gap-1" style="font-size: 0.9rem;">
                    Proceed <i class="fas fa-chevron-right"></i>
                </span>
            </button>
            <p class="text-white mb-0 text-center small pos-mobile-pay-footer">Select payment method on next step</p>
        </div>
    </div>

</body>

</html>
