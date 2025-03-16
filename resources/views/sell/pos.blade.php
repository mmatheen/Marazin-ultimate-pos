<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Page</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ URL::to('assets/img/ARB Logo.png') }}">
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


    <style>
        /* General Styles */
        .is-invalidRed {
            border-color: #e61414 !important;
        }

        .is-validGreen {
            border-color: rgb(3, 105, 54) !important;
        }

        /* Scrollable Content */
        .scrollable-content {
            max-height: 470px;
            overflow-y: auto;
        }

        /* Total Payable Section */
        .total-payable-section {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .total-payable-section h1 {
            font-size: 28px;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
        }

        .total-payable-section #total {
            font-size: 24px;
            font-weight: 800;
            color: #d9534f;
            margin: 0;
        }

        .total-payable-section button {
            margin-top: 20px;
        }


                    /* Hide number input arrows in Chrome, Safari, Edge, and Opera */
            .quantity-input::-webkit-outer-spin-button,
            .quantity-input::-webkit-inner-spin-button {
                -webkit-appearance: none;
                margin: 0;
            }

            /* Hide number input arrows in Firefox */
            .quantity-input {
                -moz-appearance: textfield;
            }




        /* Button Styles */
        .btn-outline-teal {
            border-color: #20c997;
            color: #20c997;
            background: none;
        }

        .btn-outline-teal:hover {
            background-color: #20c997;
            color: white;
        }

        .btn-outline-purple {
            border-color: #6f42c1;
            color: #6f42c1;
            background: none;
        }

        .btn-outline-purple:hover {
            background-color: #6f42c1;
            color: white;
        }

        /* Button Sizes */
        .btn-sm {
            padding: 8px 12px !important;
            font-size: 14px !important;
            border-radius: 5px !important;
        }

        .row .col-auto {
            margin-right: 20px !important;
        }

        /* Add Expense Button */
        .btn-primary {
            background-color: #4f46e5 !important;
            border: none !important;
            color: white !important;
        }

        .btn-primary:hover {
            background-color: #3c3aad !important;
        }

        /* Location and Date Section */
        .d-flex.align-items-center p {
            font-size: 16px !important;
            font-weight: 600 !important;
        }

        .d-flex.align-items-center h6 {
            font-size: 16px !important;
            color: #333 !important;
        }

        /* Bottom Fixed Section */
        .bottom-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #fff;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 10px 20px;
        }

        .bottom-fixed .btn {
            font-size: 14px;
            font-weight: bold;
            padding: 10px 15px;
            border-radius: 5px;
        }

        .bottom-fixed .btn-primary {
            background-color: #4f46e5;
            border: none;
            color: #fff;
        }

        .bottom-fixed .btn-primary:hover {
            background-color: #3c3aad;
        }

        .bottom-fixed .btn-warning {
            background-color: #f59e0b;
            border: none;
            color: #fff;
        }

        .bottom-fixed .btn-warning:hover {
            background-color: #d97706;
        }

        .bottom-fixed .btn-danger {
            background-color: #dc2626;
            border: none;
            color: #fff;
        }

        .bottom-fixed .btn-danger:hover {
            background-color: #b91c1c !important;
        }

        .bottom-fixed .total-payable-section h4,
        .bottom-fixed .total-payable-section h5 {
            margin: 0 !important;
            font-weight: bold !important;
        }

        .bottom-fixed .total-payable-section h5 {
            color: #16a34a !important;
            text-align: left !important;
        }

        .product-card {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-bottom: 10px;
            /* Uniform margin bottom */
        }

        .product-card img {
            max-width: 100%;
            height: auto;
            max-height: 80px;
            object-fit: contain;
        }

        .product-card h6 {
            font-size: 14px;
            margin: 6px 0;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .product-card .product-card-body {
            display: flex;
            flex-direction: column;
            align-items: left;
        }

        .product-card .badge {
            font-size: 12px;
            margin-top: 5px;
        }

        /* Responsive grid layout */
        @media (min-width: 1400px) {
            #posProduct .col-xxl-3 {
                flex: 0 0 25%;
                max-width: 25%;
            }
        }

        @media (min-width: 1200px) and (max-width: 1399px) {
            #posProduct .col-xl-4 {
                flex: 0 0 33.3333%;
                max-width: 33.3333%;
            }
        }

        @media (min-width: 992px) and (max-width: 1199px) {
            #posProduct .col-lg-4 {
                flex: 0 0 33.3333%;
                max-width: 33.3333%;
            }
        }

        @media (min-width: 768px) and (max-width: 991px) {
            #posProduct .col-md-6 {
                flex: 0 0 50%;
                max-width: 50%;
            }
        }

        @media (max-width: 767px) {
            #posProduct .col-sm-12 {
                flex: 0 0 100%;
                max-width: 100%;
            }
        }

        .btn-add-to-cart {
            background-color: #007bff;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .btn-add-to-cart:hover {
            background-color: #0056b3;
        }

        /* Offcanvas Styles */
        .offcanvas {
            background-color: #f8f9fa;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 40%
        }

        .offcanvas-body {
            padding: 15px;
        }

        .offcanvas-body button {
            background-color: transparent;
            /* Remove the background color */
            color: #007bff;
            /* Set the text color to blue */
            border: 2px solid #007bff;
            /* Add a blue outline border */
            padding: 8px 16px;
            margin-bottom: 10px;
            width: 100%;
            text-align: left;
            border-radius: 4px;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .offcanvas-body button:hover {
            background-color: #007bff;
            /* Add blue background on hover */
            color: #fff;
            /* Change text color to white on hover */
        }

        /* Category Styles */
        .category-container {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .category-card,
        .brand-card {
            width: calc(33.33% - 10px);
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            padding: 10px;
            background-color: #fff;
            box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease-in-out;
        }

        .category-card:hover,
        .brand-card:hover {
            transform: translateY(-10px);
        }

        .category-card h6,
        .brand-card h6 {
            font-size: 14px;
            margin: 10px 0;
            color: #333;
        }

        .category-footer,
        .brand-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        /* Category Button Styles */
        .category-footer button {
            background-color: transparent;
            color: #28a745;
            border: 2px solid #28a745;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .category-footer button:hover {
            background-color: #28a745;
            color: #fff;
        }

        /* Green Outline Button */
        .btn-outline-green {
            background-color: transparent;
            color: #28a745;
            border: 2px solid #28a745;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .btn-outline-green:hover {
            background-color: #28a745;
            color: white;
        }

        /* Blue Outline Button */
        .btn-outline-blue {
            background-color: transparent;
            color: #007bff;
            border: 2px solid #007bff;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .btn-outline-blue:hover {
            background-color: #007bff;
            color: white;
        }

        /* Responsive Styles */
        @media (max-width: 1200px) {

            .category-card,
            .brand-card {
                width: calc(50% - 10px);
            }
        }

        @media (max-width: 768px) {

            .category-card,
            .brand-card {
                width: calc(100% - 10px);
            }

            .bottom-fixed {
                padding: 5px 10px;
            }

            .bottom-fixed .btn {
                font-size: 12px;
                padding: 8px 10px;
            }
        }

        @media (max-width: 576px) {
            .bottom-fixed {
                padding: 5px 5px;
            }

            .bottom-fixed .btn {
                font-size: 10px;
                padding: 5px 8px;
            }
        }

        /* Updated Loader Styles */
        .loader-container {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            /* Use viewport height to ensure full coverage */
            width: 100vw;
            /* Use viewport width to ensure full coverage */
            position: fixed;
            /* Ensure loader is on top of everything */
            top: 0;
            left: 0;
            z-index: 1;
            background: rgba(255, 255, 255, 0.8);
            /* Optional background overlay */
        }

        .loader {
            --dim: 3rem;
            width: var(--dim);
            height: var(--dim);
            position: relative;
            animation: spin988 2s linear infinite;
        }

        .loader .circle {
            --color: #333;
            --dim: 1.2rem;
            width: var(--dim);
            height: var(--dim);
            background-color: var(--color);
            border-radius: 50%;
            position: absolute;
        }

        .loader .circle:nth-child(1) {
            top: 0;
            left: 0;
        }

        .loader .circle:nth-child(2) {
            top: 0;
            right: 0;
        }

        .loader .circle:nth-child(3) {
            bottom: 0;
            left: 0;
        }

        .loader .circle:nth-child(4) {
            bottom: 0;
            right: 0;
        }

        @keyframes spin988 {
            0% {
                transform: scale(1) rotate(0);
            }

            20%,
            25% {
                transform: scale(1.3) rotate(90deg);
            }

            45%,
            50% {
                transform: scale(1) rotate(180deg);
            }

            70%,
            75% {
                transform: scale(1.3) rotate(270deg);
            }

            95%,
            100% {
                transform: scale(1) rotate(360deg);
            }
        }

        /* Style for the dropdown container */
        .ui-autocomplete {
            position: absolute;
            top: 100%;
            left: 0;
            z-index: 1000;
            float: left;
            display: none;
            min-width: 160px;
            padding: 4px 0;
            margin: 2px 0 0 0;
            list-style: none;
            background-color: #ffffff;
            border: 1px solid #ccc;
            border: 1px solid rgba(0, 0, 0, .15);
            border-radius: 4px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, .175);
        }

        /* Style for each dropdown item */
        .ui-menu-item {
            padding: 3px 20px;
            line-height: 1.5;
            cursor: pointer;
        }

        /* Hover effect for dropdown items */
        .ui-menu-item:hover {
            background-color: #f1f1f1;
        }

        /* Style for the dropdown input */
        .ui-autocomplete-input {
            margin-bottom: 0;
            padding: 10px;
            width: 100%;
            box-sizing: border-box;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        .card-body::-webkit-scrollbar {
            width: 8px;
        }

        .card-body::-webkit-scrollbar-thumb {
            background-color: #6c757d;
            border-radius: 4px;
        }

        .card-body::-webkit-scrollbar-track {
            background-color: #f1f1f1;
        }

        .quantity-container {
            display: flex;
            align-items: center;
        }

        .quantity-input {
            width: 60px;
            text-align: center;
        }

        .batch-dropdown {
            width: 100%;
            margin-top: 5px;
        }

        .price-input,
        .subtotal {
            text-align: right;
        }

        .product-name {
            font-size: 16px;
            font-weight: bold;
        }

        /* .remove-btn {
            display: flex;
            align-items: center;
            justify-content: center;
        } */

        .star {
            color: gold;
            font-size: 20px;
            margin-left: 5px;
        }
    </style>
</head>

<body>
    <div class="container-fluid p-3">
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-white p-1">
                    <div class="row align-items-center">
                        <!-- Location and Date Section -->
                        <div class="col-md-6 d-flex align-items-center">
                            <h6 class="me-3 mb-0">Location: <strong>{{ $location->name ?? 'N/A' }}</strong></h6>
                            <button class="btn btn-primary text-white border-1 px-2 py-1"
                                style="width: auto; height: 30px;" id="currentTimeButton"
                                disabled>{{ now()->format('Y-m-d H:i:s') }}</button>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const currentTimeButton = document.getElementById('currentTimeButton');
                                    setInterval(() => {
                                        const now = new Date();
                                        const formattedTime = now.getFullYear() + '-' +
                                            ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
                                            ('0' + now.getDate()).slice(-2) + ' ' +
                                            ('0' + now.getHours()).slice(-2) + ':' +
                                            ('0' + now.getMinutes()).slice(-2) + ':' +
                                            ('0' + now.getSeconds()).slice(-2);
                                        currentTimeButton.textContent = formattedTime;
                                    }, 1000);
                                });
                            </script>
                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-3 d-none d-md-flex">
                                <button class="btn btn-light btn-sm" onclick="history.back()"><i
                                        class="fas fa-backward"></i></button>
                                <button class="btn btn-danger btn-sm"><i class="fas fa-window-close"></i></button>
                                <button class="btn btn-success btn-sm"><i class="fas fa-briefcase"></i></button>

                                <!-- Calculator Button with Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-warning btn-sm dropdown-toggle" id="calculatorButton"
                                        data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fas fa-calculator"></i>
                                    </button>
                                    <div class="dropdown-menu p-2 shadow" id="calculatorDropdown" style="width: 220px;">
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

                                <button class="btn btn-danger btn-sm"><i class="fas fa-redo-alt"></i></button>

                                <button class="btn btn-outline-danger" id="pauseCircleButton" data-bs-toggle="modal"
                                    data-bs-target="#suspendSalesModal">
                                    <i class="fas fa-pause-circle"></i> Suspended Sales
                                </button>

                                <!-- Add Expense Button -->
                                <button class="btn btn-dark btn-sm d-flex align-items-center">
                                    <i class="fas fa-minus-circle me-2"></i> Add Expense
                                </button>
                            </div>

                            <!-- Hamburger Button for Mobile and Tablet -->
                            <div class="d-flex d-lg-none justify-content-center mb-3">
                                <button class="btn btn-primary" type="button" data-bs-toggle="offcanvas"
                                    data-bs-target="#offcanvasResponsive" aria-controls="offcanvasResponsive">
                                    <i class="fas fa-bars"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offcanvas for Mobile and Tablet -->
        <div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasResponsive"
            aria-labelledby="offcanvasResponsiveLabel">
            <div class="offcanvas-header">
                <h5 class="offcanvas-title" id="offcanvasResponsiveLabel">Actions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
            </div>
            <div class="offcanvas-body">
                <button class="btn btn-light btn-sm w-100 mb-2" onclick="history.back()"><i
                        class="fas fa-backward"></i> Back</button>
                <button class="btn btn-danger btn-sm w-100 mb-2"><i class="fas fa-window-close"></i> Close</button>
                <button class="btn btn-success btn-sm w-100 mb-2"><i class="fas fa-briefcase"></i> Briefcase</button>
                <button class="btn btn-warning btn-sm w-100 mb-2 dropdown-toggle" id="calculatorButtonMobile"
                    data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-calculator"></i> Calculator
                </button>
                <div class="dropdown-menu p-2 shadow" id="calculatorDropdownMobile" style="width: 220px;">
                    <div class="text-center">
                        <input type="text" id="calcDisplayMobile" class="form-control text-end mb-2"
                            onkeydown="handleKeyboardInput(event)" autofocus>
                    </div>
                    <div class="d-grid gap-1">
                        <div class="row g-1">
                            <button class="btn btn-light btn-sm col" onclick="calcInput('7')">7</button>
                            <button class="btn btn-light btn-sm col" onclick="calcInput('8')">8</button>
                            <button class="btn btn-light btn-sm col" onclick="calcInput('9')">9</button>
                            <button class="btn btn-warning btn-sm col" onclick="calcInput('/')">/</button>
                        </div>
                        <div class="row g-1">
                            <button class="btn btn-light btn-sm col" onclick="calcInput('4')">4</button>
                            <button class="btn btn-light btn-sm col" onclick="calcInput('5')">5</button>
                            <button class="btn btn-light btn-sm col" onclick="calcInput('6')">6</button>
                            <button class="btn btn-warning btn-sm col" onclick="calcInput('*')">×</button>
                        </div>
                        <div class="row g-1">
                            <button class="btn btn-light btn-sm col" onclick="calcInput('1')">1</button>
                            <button class="btn btn-light btn-sm col" onclick="calcInput('2')">2</button>
                            <button class="btn btn-light btn-sm col" onclick="calcInput('3')">3</button>
                            <button class="btn btn-warning btn-sm col" onclick="calcInput('-')">-</button>
                        </div>
                        <div class="row g-1">
                            <button class="btn btn-light btn-sm col" onclick="calcInput('0')">0</button>
                            <button class="btn btn-light btn-sm col" onclick="calcInput('.')">.</button>
                            <button class="btn btn-danger btn-sm col" onclick="clearCalc()">C</button>
                            <button class="btn btn-warning btn-sm col" onclick="calcInput('+')">+</button>
                        </div>
                        <div class="row g-1">
                            <button class="btn btn-success btn-sm col" onclick="calculateResult()">=</button>
                        </div>
                    </div>
                </div>
                <button class="btn btn-danger btn-sm w-100 mb-2"><i class="fas fa-redo-alt"></i> Redo</button>
                <button class="btn btn-outline-danger w-100 mb-2" id="pauseCircleButtonMobile" data-bs-toggle="modal"
                    data-bs-target="#suspendSalesModal">
                    <i class="fas fa-pause-circle"></i> Suspended Sales
                </button>
                <button class="btn btn-dark btn-sm w-100 mb-2 d-flex align-items-center">
                    <i class="fas fa-minus-circle me-2"></i> Add Expense
                </button>
            </div>
        </div>


        <div class="row mt-2">
            <div class="col-md-7">
                <div class="card bg-white p-3">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="d-flex justify-content-center">
                                <select class="form-control selectBox rounded-start" id="customer-id">
                                    <option selected disabled>Please Select</option>
                                    <!-- Options will be populated dynamically -->
                                </select>
                                <button type="button" class="btn btn-outline-info rounded-0" id="addCustomerButton">
                                    <i class="fas fa-plus-circle"></i>
                                </button>
                            </div>
                            <p id="total-due-amount" class="text-danger mt-1">Total due amount: Rs. 0.00</p>
                        </div>
                        <div class="col-md-7">
                            <input type="text" class="form-control" id="productSearchInput"
                                placeholder="Enter Product name / SKU / Scan bar code">
                        </div>


                        <div class="col-md-12 mt-3">
                            <div class="table-responsive" style="height: 300px; overflow-y: auto;">
                                <table class="table table-bordered">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Product</th>
                                            <th class="text-center">Quantity</th>
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
                </div>

                <!-- Billing Summary (Positioned at the Bottom) -->
                <div class="card bg-white mt-3 p-3">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Items</label>
                                <p id="items-count" class="form-control">0</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Total</label>
                                <p id="total-amount" class="form-control">0.00</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="discount-type">Discount Type</label>
                                <select id="discount-type" class="form-control">
                                    <option value="fixed">Fixed</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Discount</label>
                                <input type="text" id="discount" class="form-control" placeholder="0.00">
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Final Total</label>
                                <p id="final-total-amount" class="form-control">0.00</p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Amount Given</label>
                                <input type="text" id="amount-given" class="form-control" placeholder="0.00"
                                    oninput="formatAmount(this)">
                            </div>
                        </div>
                    </div>
                </div>
            </div>



            <!-- Product List -->
            <div class="col-md-5">
                <div class="card bg-white p-3" style="height: 560px;">
                    <!-- Buttons for Category and Brand -->
                    <div class="row mb-3">
                        <div class="d-flex justify-content-between w-100 mb-2">
                            <button type="button" class="btn btn-primary w-50 me-3" id="allProductsBtn">All
                                Products</button>
                            <button type="button" class="btn btn-primary w-50 me-3" id="category-btn"
                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategory"
                                aria-controls="offcanvasCategory">Category</button>
                            <button type="button" class="btn btn-primary w-50" id="brand-btn"
                                data-bs-toggle="offcanvas" data-bs-target="#offcanvasBrand"
                                aria-controls="offcanvasBrand">Brand</button>
                        </div>
                    </div>

                    <div class="row g-3 overflow-auto" id="posProduct">

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

        <!-- Bottom Fixed Section -->
        <div class="bottom-fixed mt-3">
            <div class="row align-items-center">
                <!-- Left Side: Actions -->
                <div class="col-md-7">
                    <div class="d-flex gap-2 flex-wrap">
                        {{-- <button class="btn btn-outline-primary"><i class="fas fa-edit"></i> Draft</button>
                        <button class="btn btn-outline-warning"><i class="fas fa-file-alt"></i> Quotation</button> --}}
                        <button class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#suspendModal">
                            <i class="fas fa-pause"></i> Suspend
                        </button>
                        <button class="btn btn-outline-success" id="creditSaleButton"><i class="fas fa-check"></i>
                            Credit Sale</button>
                        <button class="btn btn-outline-pink" id="cardButton">
                            <i class="fas fa-credit-card"></i> Card
                        </button>
                        <button class="btn btn-outline-pink" id="chequeButton">
                            <i class="fas fa-money-check"></i> Cheque
                        </button>
                        <button class="btn btn-outline-dark" data-bs-toggle="modal" data-bs-target="#paymentModal">
                            <i class="fas fa-list"></i> Multiple Pay
                        </button>
                        <button class="btn btn-outline-success" id="cashButton"><i class="fas fa-money-bill-wave"></i>
                            Cash</button>
                        <button class="btn btn-danger" id="cancelButton"><i class="fas fa-times"></i> Cancel</button>
                    </div>
                </div>

                <!-- Right Side: Total Payable -->
                <div class="col-md-5 text-end">
                    <h4 class="d-inline">Total Payable:</h4>
                    <span id="total" class="text-success fs-4 fw-bold">Rs 0.00</span>
                    <button class="btn btn-primary ms-3" data-bs-toggle="modal" data-bs-target="#recentTransactionsModal"><i class="fas fa-clock"></i> Recent Transactions</button>
                </div>
            </div>
        </div>

     <!-- Bootstrap Modal with Tabs and Dynamic Table -->
<div class="modal fade" id="recentTransactionsModal" tabindex="-1" aria-labelledby="recentTransactionsLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="recentTransactionsLabel">Recent Transactions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <ul class="nav nav-tabs" id="transactionTabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#completed" onclick="loadTableData('completed')">Completed</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#quotation" onclick="loadTableData('quotation')">Quotation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#draft" onclick="loadTableData('draft')">Draft</a>
                    </li>
                </ul>
                <div class="tab-content mt-3">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Invoice No</th>
                                    <th>Customer</th>
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

<script>
    let sales = [];

    async function fetchSalesData() {
        try {
            const response = await fetch('/sales');
            const data = await response.json();
            if (Array.isArray(data)) {
                sales = data;
            } else if (data.sales && Array.isArray(data.sales)) {
                sales = data.sales;
            } else {
                console.error('Unexpected data format:', data);
            }
            // Load the default tab data
            loadTableData('completed');
        } catch (error) {
            console.error('Error fetching sales data:', error);
        }
    }

    function loadTableData(status) {
        const tableBody = document.getElementById('transactionTableBody');
        tableBody.innerHTML = '';

        const filteredSales = sales
            .filter(sale => sale.status === status)
            .sort((a, b) => parseInt(b.invoice_no.split('-')[1]) - parseInt(a.invoice_no.split('-')[1]));

        if (filteredSales.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5" class="text-center">No records found</td></tr>';
        } else {
            filteredSales.forEach((sale, index) => {
                const row = `<tr>
                    <td>${index + 1}</td>
                    <td>${sale.invoice_no}</td>
                    <td>${sale.customer.prefix} ${sale.customer.first_name} ${sale.customer.last_name}</td>
                    <td>${sale.final_total}</td>
                    <td>
                        <button class='btn btn-primary btn-sm'>Edit</button>
                        <button class='btn btn-success btn-sm' onclick="printReceipt(${sale.id})">Print</button>
                        <button class='btn btn-danger btn-sm'>Delete</button>
                    </td>
                </tr>`;
                tableBody.innerHTML += row;
            });
        }
    }

    document.addEventListener('DOMContentLoaded', fetchSalesData);

    // Add this JavaScript function to your view or a separate JS file
    function printReceipt(saleId) {
        fetch(`/sales/print-recent-transaction/${saleId}`)
            .then(response => response.json())
            .then(data => {
                if (data.invoice_html) {
                    const receiptWindow = window.open('', '_blank');
                    receiptWindow.document.write(data.invoice_html);
                    receiptWindow.document.close();
                    receiptWindow.print();
                } else {
                    alert('Failed to fetch the receipt. Please try again.');
                }
            })
            .catch(error => {
                console.error('Error fetching the receipt:', error);
                alert('An error occurred while fetching the receipt. Please try again.');
            });
    }
</script>

<style>
    .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }

    .table-responsive {
        overflow-x: auto;
    }
</style>



        <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="productModalLabel">Product Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
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
        <div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="suspendModalLabel">Suspend Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
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
        <!-- Bootstrap Modal for Suspended Sales -->
        <div class="modal fade" id="suspendSalesModal" tabindex="-1" aria-labelledby="suspendSalesModalLabel"
            aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="suspendSalesModalLabel">Suspended Sales</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
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
    </div>


    <div class="container mt-5">
        <div class="modal fade" id="paymentModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1"
            aria-labelledby="paymentModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="paymentModalLabel">Add Payment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-12 col-md-8">
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
                            <div class="col-12 col-md-4">
                                <div class="card shadow-sm" style="height: 100%;">
                                    <div class="card-body bg-warning text-dark rounded" style="padding: 20px;">
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
                        <div class="row mt-4">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-success" id="finalize_payment">Finalize
                                    Payment</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Card Payment Modal -->
    <div class="modal fade" id="cardModal" tabindex="-1" aria-labelledby="cardModalLabel" aria-hidden="true">
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
                        {{-- <div class="col-md-6">
                            <label for="cardType" class="form-label">Card Type</label>
                            <select class="form-select" name="card_type" id="card_type">
                                <option value="visa">Visa</option>
                                <option value="mastercard">MasterCard</option>
                                <option value="amex">American Express</option>
                            </select>
                        </div> --}}
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
                    <button type="button" class="btn btn-primary" id="confirmCardPayment">Confirm Payment</button>
                </div>
            </div>
        </div>
    </div>
    <!-- Cheque Payment Modal -->
    <div class="modal fade" id="chequeModal" tabindex="-1" aria-labelledby="chequeModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="chequeModalLabel">Cheque Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                            <input type="text" class="form-control" name="cheque_given_by" id="cheque_given_by">
                            <div id="chequeGivenByError" class="text-danger"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="confirmChequePayment">Confirm Payment</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let locationId;
        let totalAmount = 0;

        document.getElementById('addPaymentRow').addEventListener('click', function() {
            const paymentRows = document.getElementById('paymentRows');
            const newPaymentRow = document.createElement('div');
            newPaymentRow.className = 'card mb-3 payment-row position-relative';
            newPaymentRow.innerHTML = `
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <label for="paymentMethod" class="form-label">Payment Method</label>
                            <select class="form-select payment-method" name="payment_method" onchange="togglePaymentFields(this)">
                                <option value="cash" selected>Cash</option>
                                <option value="card">Credit Card</option>
                                <option value="cheque">Cheque</option>
                                <option value="bank_transfer">Bank Transfer</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="paidOn" class="form-label">Paid On</label>
                            <input class="form-control datetimepicker payment-date" type="text" name="payment_date" placeholder="DD-MM-YYYY" value="${moment().format('DD-MM-YYYY')}">
                        </div>
                        <div class="col-md-4">
                            <label for="payAmount" class="form-label">Amount</label>
                            <input type="text" class="form-control payment-amount" id="payment-amount" name="amount" oninput="validateAmount()">
                            <div class="text-danger amount-error" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="conditional-fields row mt-3"></div>
                </div>
                <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2 remove-payment-row" aria-label="Close"></button>
            `;
            paymentRows.appendChild(newPaymentRow);

            newPaymentRow.querySelector('.remove-payment-row').addEventListener('click', function() {
                this.closest('.payment-row').remove();
                updatePaymentSummary();
            });

            updatePaymentSummary();
            initializeDateTimePickers();
        });

        function togglePaymentFields(selectElement) {
            const paymentMethod = selectElement.value;
            const conditionalFields = selectElement.closest('.payment-row').querySelector('.conditional-fields');

            conditionalFields.innerHTML = '';

            if (paymentMethod === 'card') {
                conditionalFields.innerHTML = `
                    <div class="col-md-4">
                        <label for="cardNumber" class="form-label">Card Number</label>
                        <input type="text" class="form-control" name="card_number" required>
                    </div>
                    <div class="col-md-4">
                        <label for="cardHolderName" class="form-label">Card Holder Name</label>
                        <input type="text" class="form-control" name="card_holder_name">
                    </div>
                    <div class="col-md-4">
                        <label for="cardType" class="form-label">Card Type</label>
                        <select class="form-select" name="card_type">
                            <option value="visa">Visa</option>
                            <option value="mastercard">MasterCard</option>
                            <option value="amex">American Express</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="expiryMonth" class="form-label">Expiry Month</label>
                        <input type="text" class="form-control" name="card_expiry_month">
                    </div>
                    <div class="col-md-4">
                        <label for="expiryYear" class="form-label">Expiry Year</label>
                        <input type="text" class="form-control" name="card_expiry_year">
                    </div>
                    <div class="col-md-4">
                        <label for="securityCode" class="form-label">Security Code</label>
                        <input type="text" class="form-control" name="card_security_code">
                    </div>
                `;
            } else if (paymentMethod === 'cheque') {
                conditionalFields.innerHTML = `
                    <div class="col-md-6">
                        <label for="chequeNumber" class="form-label">Cheque Number</label>
                        <input type="text" class="form-control" name="cheque_number" required>
                    </div>
                    <div class="col-md-6">
                        <label for="bankBranch" class="form-label">Bank Branch</label>
                        <input type="text" class="form-control" name="cheque_bank_branch">
                    </div>
                    <div class="col-md-6">
                        <label for="cheque_received_date" class="form-label">Cheque Received Date</label>
                        <input type="text" class="form-control datetimepicker cheque-received-date" name="cheque_received_date" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cheque_valid_date" class="form-label">Cheque Valid Date</label>
                        <input type="text" class="form-control datetimepicker cheque-valid-date" name="cheque_valid_date" required>
                    </div>
                    <div class="col-md-6">
                        <label for="cheque_given_by" class="form-label">Cheque Given by</label>
                        <input type="text" class="form-control" name="cheque_given_by">
                    </div>
                `;
                initializeDateTimePickers();
            } else if (paymentMethod === 'bank_transfer') {
                conditionalFields.innerHTML = `
                    <div class="col-md-12">
                        <label for="bankAccountNumber" class="form-label">Bank Account Number</label>
                        <input type="text" class="form-control" name="bank_account_number">
                    </div>
                `;
            }
        }

        function validateAmount() {
            const amountInputs = document.querySelectorAll('.payment-amount');
            amountInputs.forEach(input => {
                const amountError = input.nextElementSibling;
                if (isNaN(input.value) || input.value <= 0) {
                    amountError.style.display = 'block';
                    amountError.textContent = 'Please enter a valid amount.';
                } else {
                    amountError.style.display = 'none';
                }
            });
            updatePaymentSummary();
        }

        document.querySelectorAll('.remove-payment-row').forEach(button => {
            button.addEventListener('click', function() {
                this.closest('.payment-row').remove();
                updatePaymentSummary();
            });
        });

        document.getElementById('paymentModal').addEventListener('hidden.bs.modal', function() {
            document.getElementById('paymentForm').reset();
            document.getElementById('paymentRows').innerHTML = `
                <div class="card mb-3 payment-row position-relative">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <label for="paymentMethod" class="form-label">Payment Method</label>
                                <select class="form-select payment-method" name="payment_method" onchange="togglePaymentFields(this)">
                                    <option value="cash" selected>Cash</option>
                                    <option value="card">Credit Card</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="paidOn" class="form-label">Paid On</label>
                                <input class="form-control datetimepicker payment-date" type="text" name="payment_date" placeholder="DD-MM-YYYY" value="${moment().format('DD-MM-YYYY')}">
                            </div>
                            <div class="col-md-4">
                                <label for="payAmount" class="form-label">Amount</label>
                                <input type="text" class="form-control payment-amount" id="payment-amount" name="amount" oninput="validateAmount()">
                                <div class="text-danger amount-error" style="display:none;"></div>
                            </div>
                        </div>
                        <div class="conditional-fields row mt-3"></div>
                    </div>
                    <button type="button" class="btn-close position-absolute top-0 end-0 mt-2 me-2 remove-payment-row" aria-label="Close"></button>
                </div>
            `;
            updatePaymentSummary();
            initializeDateTimePickers();
        });

        function initializeDateTimePickers() {
            $('.datetimepicker').datetimepicker({
                format: 'DD-MM-YYYY',
                useCurrent: false,
                minDate: moment().startOf('day')
            });

            $('.cheque-received-date').datetimepicker({
                format: 'DD-MM-YYYY',
                useCurrent: false,
                minDate: moment().startOf('day')
            });

            $('.cheque-valid-date').datetimepicker({
                format: 'DD-MM-YYYY',
                useCurrent: false,
                minDate: moment().add(1, 'days').startOf('day')
            });
        }

        function updatePaymentSummary() {
            let totalItems = fetchTotalItems();
            let totalPayable = totalAmount;
            let totalPaying = 0;
            let changeReturn = 0;
            let balance = 0;

            document.querySelectorAll('.payment-amount').forEach(input => {
                totalPaying += parseFloat(input.value) || 0;
            });

            if (totalPaying > totalPayable) {
                changeReturn = totalPaying - totalPayable;
            } else {
                balance = totalPayable - totalPaying;
            }

            document.getElementById('modal-total-items').textContent = totalItems.toFixed(2);
            document.getElementById('modal-total-payable').textContent = totalPayable.toFixed(2);
            document.getElementById('modal-total-paying').textContent = totalPaying.toFixed(2);
            document.getElementById('modal-change-return').textContent = changeReturn.toFixed(2);
            document.getElementById('modal-balance').textContent = balance.toFixed(2);
        }

        function fetchTotalAmount() {
            let totalAmount = 0;
            document.querySelectorAll('#billing-body tr').forEach(row => {
                const subtotal = parseFloat(row.querySelector('.subtotal').textContent);
                totalAmount += subtotal;
            });
            return totalAmount;
        }

        function fetchTotalItems() {
            let totalItems = 0;
            document.querySelectorAll('#billing-body tr').forEach(row => {
                const quantity = parseInt(row.querySelector('.quantity-input').value);
                totalItems += quantity;
            });
            return totalItems;
        }

        document.getElementById('paymentModal').addEventListener('show.bs.modal', function() {
            totalAmount = fetchTotalAmount();
            updatePaymentSummary();
            initializeDateTimePickers();
        });
    </script>

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
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <!-- Include Bootstrap JS -->
    @include('sell.pos_ajax')
    @include('contact.customer.customer_ajax')
    @include('contact.customer.add_customer_modal')

</body>

</html>
