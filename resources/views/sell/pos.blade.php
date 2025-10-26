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

    <style>
        /* Sales Rep Display - Hidden by default until confirmed as sales rep */
        #salesRepDisplay {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
        }
        
        /* Only show when explicitly marked as visible */
        #salesRepDisplay.sales-rep-visible {
            display: flex !important;
            visibility: visible !important;
            opacity: 1 !important;
        }

        /* General Styles */
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            background-color: #dedede;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .container-fluid {
            padding-left: 15px;
            padding-right: 15px;
            margin-left: auto;
            margin-right: auto;
            width: 100%;
            max-width: 100vw;
            overflow-x: hidden;
        }

        /* Prevent horizontal scroll on all elements */
        * {
            box-sizing: border-box;
        }

        /* Prevent page scrolling - single view only */
        html, body {
            overflow: hidden !important;
            height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* Container should fit viewport exactly */
        .container-fluid {
            height: 100vh;
            overflow: hidden;
            padding-bottom: 60px !important; /* Space for bottom fixed */
        }


        /* Fix button text wrapping */
        .bottom-fixed .btn {
            word-break: keep-all;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Ensure cards don't overflow */
        .card {
            overflow: hidden;
            word-wrap: break-word;
        }

        .is-invalidRed {
            border-color: #e61414 !important;
        }

        .is-validGreen {
            border-color: rgb(3, 105, 54) !important;
        }

        .toast-error {
            animation: fadeIn 0.3s ease-in-out;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
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

        /* Button Sizes */
        .btn-sm {
            padding: 8px 12px !important;
            font-size: 14px !important;
            border-radius: 5px !important;
        }

        /* Bottom Fixed Section */
        .bottom-fixed {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            background-color: #fff;
            box-shadow: 0 -2px 5px rgba(0, 0, 0, 0.1);
            z-index: 1000;
            padding: 2px 5px;
            border-top: 1px solid #ddd;
        }

        .bottom-fixed .btn {
            font-size: 12px;
            font-weight: bold;
            padding: 3px 8px;
            border-radius: 4px;
            margin: 1px;
        }

        .bottom-fixed .row {
            margin: 0;
            align-items: center;
            padding: 2px 0;
        }

        .bottom-fixed .col-md-5,
        .bottom-fixed .col-md-5,
        .bottom-fixed .col-md-7 {
            padding: 0 8px;
        }

        .bottom-fixed h4 {
            font-size: 20px;
            margin-bottom: 0;
            font-weight: 700;
        }

        .bottom-fixed #total {
            font-size: 24px;
            font-weight: 800;
        }

        .bottom-fixed #items-count {
            font-size: 16px;
            font-weight: 600;
        }

        /* Ensure Total Payable section stays on left */
        .bottom-fixed .col-md-5 {
            justify-content: flex-start !important;
            gap: 10px !important;
        }

        .bottom-fixed .col-md-5 .d-flex {
            gap: 10px !important;
        }
        /* Header card styling */
        .card.bg-white.p-1 {
            margin-bottom: 0 !important;
        }

        /* Desktop header row */
        .row.align-items-center.d-none.d-md-flex {
            padding: 4px 8px;
            min-height: 50px;
            flex-wrap: nowrap !important;
        }

        /* Left section - prevent wrapping */
        .col-md-6.d-flex.align-items-center .d-flex.flex-row {
            flex-wrap: nowrap !important;
        }

        /* Location select styling */
        .form-select.location-select-sync {
            height: 38px;
            font-size: 12px;
            padding: 6px 10px;
            min-width: 160px;
            max-width: 180px;
        }

        /* Date and time buttons */
        #currentDateButton {
            height: 38px;
            font-size: 12px !important;
            padding: 4px 10px !important;
            white-space: nowrap;
        }

        #currentTimeText {
            font-size: 13px;
            font-weight: 600;
            white-space: nowrap;
        }

        /* Keyboard shortcut button */
        #shortcutButton {
            height: 38px;
            width: 38px !important;
            min-width: 38px !important;
            padding: 0 !important;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Sales rep badges */
        #salesRepDisplay .badge {
            font-size: 10px;
            padding: 4px 8px !important;
            height: 30px;
            display: inline-flex;
            align-items: center;
            white-space: nowrap;
        }

        #salesRepDisplay .btn-sm {
            height: 30px;
            padding: 4px 8px !important;
        }

        #salesRepDisplay {
            flex-shrink: 0;
        }

        /* Header action buttons alignment */
        .col-md-6 .d-flex.justify-content-end {
            gap: 5px !important;
            flex-wrap: nowrap !important;
        }

        .col-md-6 .d-flex.justify-content-end .btn-sm {
            height: 36px;
            min-width: 36px;
            padding: 5px 8px !important;
            font-size: 12px !important;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        /* Dropdown buttons */
        .dropdown .btn {
            height: 36px;
            min-width: 36px;
        }

        /* Ensure all header buttons same height */
        .row.align-items-center.d-none.d-md-flex .btn {
            height: 36px !important;
        }

        /* Left section alignment */
        .col-md-6.d-flex.align-items-center {
            gap: 6px;
            overflow: visible;
        }

        .col-md-6.d-flex.align-items-center .d-flex {
            gap: 6px !important;
        }

        /* Prevent button text wrapping */
        .row.align-items-center.d-none.d-md-flex .btn {
            white-space: nowrap;
            flex-shrink: 0;
        }

        /* Ensure columns don't wrap */
        .col-md-6 {
            flex-shrink: 0;
        }


        .product-card {
            border: 1px solid #e0e0e0;
            padding: 8px 8px;
            text-align: center;
            min-height: 135px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            margin-bottom: 6px;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.08);
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .product-card:hover {
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
            transform: translateY(-2px);
            border-color: #007bff;
        }

        .product-card img {
            max-width: 100%;
            height: 55px;
            max-height: 55px;
            object-fit: contain;
            margin-bottom: 8px;
        }

        .product-card h6 {
            font-size: 12px;
            font-weight: 600;
            margin: 5px 0 7px 0;
            color: #333;
            line-height: 1.4;
            min-height: 34px;
            max-height: 34px;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
            word-break: break-word;
        }

        .product-card .product-card-body {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .product-card .badge {
            font-size: 11px;
            font-weight: 600;
            margin-top: 4px;
            padding: 3px 8px;
            border-radius: 4px;
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

        /* Offcanvas Styles */
        .offcanvas {
            background-color: white;
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
            width: calc(33.33% - 15px);
            border: 1px solid #ddd;
            border-radius: 8px;
            text-align: center;
            padding: 15px;
            background-color: #fff;
            transition: transform 0.3s ease-in-out;
            box-shadow: rgba(50, 50, 105, 0.15) 0px 2px 5px 0px, rgba(0, 0, 0, 0.05) 0px 1px 1px 0px;
        }

        .category-card:hover,
        .brand-card:hover {
            transform: translateY(-10px);
        }

        .category-card h6,
        .brand-card h6 {
            font-size: 16px;
            margin: 10px 0;
            color: #333;
            align-items: center;
            justify-content: center;
        }

        .category-footer,
        .brand-footer {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            text-align: center;

        }

        /* Category Button Styles */
        .category-footer button {
            background-color: transparent;
            /* color: #28a745;
                border: 2px solid #28a745; */
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        .category-footer button:hover {
            /* background-color: #28a745; */
            color: #fff;
        }

        /* Green Outline Button */
        .btn-outline-green {
            background-color: transparent !important;
            color: #28a745 !important;
            border: 2px solid #28a745 !important;
            transition: background-color 0.3s ease, color 0.3s ease !important;
            padding: 5px 10px !important;
            text-align: center !important;

        }

        .btn-outline-green:hover {
            background-color: #28a745 !important;
            color: white !important;
        }

        /* Blue Outline Button */
        .btn-outline-purple {
            background-color: transparent !important;
            color: #6f42c1 !important;
            border: 2px solid #6f42c1 !important;
            transition: background-color 0.3s ease, color 0.3s ease !important;
            text-align: center !important;
        }

        .btn-outline-purple:hover {
            background-color: #6f42c1 !important;
            color: white !important;
        }


        /* Responsive Styles */
        @media (min-width: 1024px) {

            .category-card,
            .brand-card {
                width: calc(33.33% - 10px);
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {

            .category-card,
            .brand-card {
                width: calc(50% - 10px);
            }

            .bottom-fixed {
                padding: 5px 10px;
            }

            .bottom-fixed .btn {
                font-size: 12px;
                padding: 8px 10px;
            }
        }

        @media (min-width: 576px) and (max-width: 767px) {

            .category-card,
            .brand-card {
                width: calc(100% - 10px);
            }

            .bottom-fixed {
                padding: 5px 5px;
            }

            .bottom-fixed .btn {
                font-size: 10px;
                padding: 5px 8px;
            }
        }

        @media (max-width: 575px) {

            .category-card,
            .brand-card {
                width: calc(100% - 10px);
            }

            .bottom-fixed {
                padding: 5px 5px;
            }

            .bottom-fixed .btn {
                font-size: 8px;
                padding: 5px 6px;
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
            border-radius: 0 !important;
        }

        .quantity-minus {
            border-radius: 3px 0 0 3px !important;

        }

        .quantity-plus {
            border-radius: 0 3px 3px 0 !important;

        }

        .customer-select2 .select2-container--default .select2-selection--single {
            border-radius: 5px 0 0 5px !important;
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
            padding: 0 12px !important;
        }

        .customer-select2 .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 38px !important;
            padding-left: 0 !important;
        }

        .customer-select2 .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 38px !important;
        }

        .customer-select2 #addCustomerButton {
            height: 38px !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            padding: 0 15px !important;
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



        .star {
            color: gold;
            font-size: 20px;
            margin-left: 5px;
        }

        .ui-autocomplete {
            max-height: 200px;
            overflow-y: auto;
            overflow-x: hidden;
            background: #fff;
            border: 1px solid #ccc;
            padding: 5px 0;
            font-size: 14px;
        }

        /* Mobile/Tablet: Fix autocomplete overflow */
        @media (max-width: 991px) {
            .ui-autocomplete {
                position: fixed !important;
                left: 5px !important;
                right: 5px !important;
                width: calc(100vw - 10px) !important;
                max-width: calc(100vw - 10px) !important;
                max-height: 250px !important;
                overflow-y: auto !important;
                overflow-x: hidden !important;
                z-index: 10000 !important;
                box-shadow: 0 3px 10px rgba(0, 0, 0, 0.15) !important;
                border-radius: 5px !important;
                margin-top: 2px !important;
                padding: 0 !important;
                box-sizing: border-box !important;
            }

            .ui-menu .ui-menu-item {
                padding: 6px 10px !important;
                font-size: 12px !important;
                border-bottom: 1px solid #f0f0f0;
                line-height: 1.3 !important;
                min-height: auto !important;
                box-sizing: border-box !important;
            }

            .ui-menu .ui-menu-item a {
                padding: 2px 0 !important;
                display: block !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
                font-size: 12px !important;
                line-height: 1.3 !important;
                max-width: 100% !important;
            }

            .ui-menu .ui-menu-item:last-child {
                border-bottom: none;
            }

            .ui-menu .ui-menu-item:hover,
            .ui-menu .ui-menu-item.ui-state-focus,
            .ui-menu .ui-menu-item.ui-state-active {
                background-color: #e3f2fd !important;
            }

            /* Prevent text overflow in autocomplete items */
            .ui-autocomplete .ui-menu-item-wrapper {
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                white-space: nowrap !important;
                max-width: 100% !important;
                font-size: 12px !important;
                padding: 2px 0 !important;
                box-sizing: border-box !important;
            }

            /* Smaller font for stock info */
            .ui-autocomplete .ui-menu-item-wrapper small,
            .ui-autocomplete .ui-menu-item-wrapper .text-muted {
                font-size: 10px !important;
            }
        }

        /* Ensure autocomplete items are properly styled */
        .ui-menu .ui-menu-item {
            padding: 8px 12px;
            cursor: pointer;
        }

        .ui-menu .ui-menu-item a {
            overflow: hidden;
            text-overflow: ellipsis;
            display: block;
        }

        /* Ensure hover and keyboard focus both show highlight */
        .ui-menu .ui-menu-item:hover,
        .ui-menu .ui-menu-item.ui-state-focus,
        .ui-menu .ui-menu-item.ui-state-active {
            /* Fix for keyboard navigation */
            background-color: #007bff !important;
            /* Highlight color */
            color: #fff !important;
            /* Text color */
            border-radius: 4px;
        }


        /* //new styles */
        /* --- Product Info, Name, and Badge --- */
        .product-info {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            gap: 8px !important;
            min-width: 0 !important;
            flex-wrap: wrap !important;
        }

        .product-name {
            font-weight: bold !important;
            display: flex !important;
            align-items: center !important;
            gap: 6px !important;
            flex-wrap: wrap !important;
            word-break: break-word !important;
            min-width: 0 !important;
        }

        .product-name .badge {
            flex-shrink: 0 !important;
            max-width: 100% !important;
            white-space: nowrap !important;
            margin-left: 4px !important;
        }



        /* Laptop/Tablet fixes (768px-1199px) */
        @media (max-width: 1199px) and (min-width: 768px) {

            .category-card,
            .brand-card {
                min-height: 72px;
                padding: 14px 18px;
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 16px;
                justify-content: flex-start;
            }

            .category-card h6,
            .brand-card h6 {
                font-size: 1.08rem;
                margin-bottom: 5px;
                white-space: normal;
                overflow: visible;
            }

            /* Better bottom-fixed for tablet */
            .bottom-fixed {
                padding: 12px 15px;
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                background: #fff;
                box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.1);
                z-index: 1000;
            }

            .bottom-fixed .row {
                align-items: center;
                margin: 0;
            }

            /* Total Payable Section - Better spacing */
            .bottom-fixed .col-md-5 {
                padding: 0 10px;
                display: flex;
                align-items: center;
                justify-content: flex-start;
                gap: 15px;
            }

            .bottom-fixed .col-md-5 h4 {
                font-size: 1.0rem;
                margin: 0;
                white-space: nowrap;
            }

            .bottom-fixed .col-md-5 #total {
                font-size: 1.1rem;
                font-weight: bold;
            }

            .bottom-fixed .col-md-5 #items-count {
                font-size: 0.9rem;
            }

            .bottom-fixed .col-md-5 #cancelButton {
                font-size: 11px;
                padding: 6px 12px;
                margin-left: 10px;
            }

            /* Payment Buttons Section - Better grid layout */
            .bottom-fixed .col-md-7 {
                padding: 0 10px;
            }

            .bottom-fixed .col-md-7 .d-flex {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
                gap: 6px;
                justify-content: center;
                align-items: stretch;
            }

            .bottom-fixed .btn {
                font-size: 10px;
                padding: 8px 6px;
                margin: 0;
                border-radius: 4px;
                min-height: 38px;
                display: flex;
                align-items: center;
                justify-content: center;
                text-align: center;
                white-space: nowrap;
                overflow: hidden;
                text-overflow: ellipsis;
                line-height: 1.2;
            }

            .bottom-fixed .btn i {
                font-size: 10px;
                margin-right: 3px;
            }
        }

        /* Small tablet specific adjustments (768px-991px) */
        @media (max-width: 991px) and (min-width: 768px) {
            .bottom-fixed .col-md-7 .d-flex {
                display: grid !important;
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 6px !important;
                width: 100% !important;
                justify-content: center !important;
                align-items: stretch !important;
            }

            .bottom-fixed .btn {
                font-size: 10px !important;
                padding: 8px 4px !important;
                margin: 0 !important;
                border-radius: 4px !important;
                min-height: 40px !important;
                display: flex !important;
                flex-direction: column !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                line-height: 1.1 !important;
            }

            .bottom-fixed .btn i {
                font-size: 12px !important;
                margin-right: 0 !important;
                margin-bottom: 2px !important;
            }

            /* Adjust total payable section for tablet */
            .bottom-fixed .col-md-5 {
                padding: 5px 10px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: flex-start !important;
                gap: 10px !important;
            }

            .bottom-fixed .col-md-5 h4 {
                font-size: 14px !important;
                margin: 0 !important;
            }

            .bottom-fixed .col-md-5 #total {
                font-size: 16px !important;
                font-weight: bold !important;
            }

            .bottom-fixed .col-md-5 #cancelButton {
                font-size: 10px !important;
                padding: 6px 10px !important;
            }
        }

        /* 600px–767px: Small tablet, landscape mobile */
        /* Medium Mobile/Small Tablet */
        @media (max-width: 767px) and (min-width: 600px) {

            .category-card,
            .brand-card {
                min-height: 68px;
                padding: 12px 13px;
                gap: 13px;
            }

            .category-card h6,
            .brand-card h6 {
                font-size: 1.01rem;
                margin-bottom: 4px;
            }

            /* Fix main content area */
            .col-md-12 .card {
                margin-bottom: 10px;
            }

            .table-responsive {
                overflow-x: auto;
                max-width: 100%;
            }

            /* Header buttons */
            .col-md-6 .d-flex {
                flex-wrap: wrap;
                gap: 5px;
            }

            .col-md-6 .btn {
                font-size: 12px;
                padding: 6px 8px;
            }
        }

        /* Small Mobile Screens */
        @media (max-width: 599px) {

            .category-card,
            .brand-card {
                min-height: 62px;
                padding: 10px 8px;
                flex-direction: row;
                align-items: center;
                gap: 10px;
            }

            .category-card h6,
            .brand-card h6 {
                font-size: 0.98rem;
                margin-bottom: 3px;
            }

            /* Fix header layout for small screens */
            .row.align-items-center .col-md-6 {
                margin-bottom: 10px;
            }

            .d-flex.flex-row.align-items-center.gap-3.flex-wrap {
                flex-direction: column;
                align-items: flex-start;
                gap: 8px;
            }

            #locationSelect {
                max-width: 100%;
                width: 100%;
            }

            /* Fix customer section */
            .customer-select2 {
                margin-bottom: 10px;
            }

            /* Fix product search */
            #productSearchInput {
                margin-bottom: 10px;
            }

            /* Fix table responsiveness */
            .table-responsive {
                font-size: 12px;
            }

            .table th,
            .table td {
                padding: 6px 4px;
                font-size: 11px;
            }

            /* Fix discount section */
            .card.bg-white.mt-3.p-2 .row .col-md-2,
            .card.bg-white.mt-3.p-2 .row .col-md-3 {
                margin-bottom: 10px;
            }
        }

        /* Fix for all: remove excessive whitespace on product cards
        .product-card,
        .category-card,
        .brand-card {
            box-sizing: border-box;
            background: #fff !important;
            border: 1px solid #e2e2e2 !important;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.03) !important;
        } */

        /* Mobile/Tablet adjustments */
        @media (max-width: 991px) {
            .bottom-fixed {
                position: static !important;
                width: 100% !important;
                max-width: 100% !important;
                padding: 15px 10px !important;
                margin: 0 !important;
                background: #fff !important;
                box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08) !important;
                border-top: 1px solid #ddd !important;
                overflow: hidden !important;
            }

            .bottom-fixed .row {
                margin: 0 !important;
                width: 100% !important;
                display: flex !important;
                flex-direction: column !important;
            }

            /* Total Payable Section */
            .bottom-fixed .col-md-5 {
                width: 100% !important;
                max-width: 100% !important;
                padding: 10px 0 !important;
                text-align: center !important;
                order: 1 !important;
                margin-bottom: 15px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                gap: 10px !important;
                flex-wrap: wrap !important;
            }

            .bottom-fixed .col-md-5 h4 {
                font-size: 16px !important;
                margin: 0 !important;
                white-space: nowrap !important;
            }

            .bottom-fixed .col-md-5 #total {
                font-size: 16px !important;
                font-weight: bold !important;
            }

            .bottom-fixed .col-md-5 #items-count {
                font-size: 14px !important;
            }

            .bottom-fixed .col-md-5 #cancelButton {
                font-size: 12px !important;
                padding: 8px 16px !important;
                border-radius: 6px !important;
            }

            /* Payment Buttons Section */
            .bottom-fixed .col-md-7 {
                width: 100% !important;
                max-width: 100% !important;
                padding: 0 !important;
                order: 2 !important;
            }

            .bottom-fixed .col-md-7 .d-flex {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 8px !important;
                width: 100% !important;
                justify-content: center !important;
                align-items: stretch !important;
            }

            .bottom-fixed .btn {
                font-size: 11px !important;
                padding: 10px 6px !important;
                margin: 0 !important;
                border-radius: 6px !important;
                min-height: 44px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                text-align: center !important;
                white-space: nowrap !important;
                overflow: hidden !important;
                text-overflow: ellipsis !important;
                line-height: 1.2 !important;
            }

            .bottom-fixed .btn i {
                font-size: 10px !important;
                margin-right: 3px !important;
            }

            /* Ensure body has proper spacing */
            body {
                padding-bottom: 20px !important;
                overflow-x: hidden !important;
            }

            /* Fix container width */
            .container-fluid {
                max-width: 100vw !important;
                overflow-x: hidden !important;
                padding-left: 10px !important;
                padding-right: 10px !important;
            }
        }

        /* Small Mobile Screens */
        @media (max-width: 576px) {
            .bottom-fixed {
                padding: 12px 8px !important;
            }

            .bottom-fixed .col-md-5 {
                margin-bottom: 12px !important;
                gap: 8px !important;
            }

            .bottom-fixed .col-md-5 h4 {
                font-size: 14px !important;
            }

            .bottom-fixed .col-md-5 #total {
                font-size: 14px !important;
            }

            .bottom-fixed .col-md-5 #items-count {
                font-size: 12px !important;
            }

            .bottom-fixed .col-md-5 #cancelButton {
                font-size: 10px !important;
                padding: 6px 10px !important;
            }

            .bottom-fixed .btn {
                font-size: 9px !important;
                padding: 8px 4px !important;
                min-height: 40px !important;
                line-height: 1.1 !important;
            }

            .bottom-fixed .btn i {
                font-size: 8px !important;
                margin-right: 2px !important;
            }

            .bottom-fixed .col-md-7 .d-flex {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 6px !important;
            }

            /* Make sure container fits properly */
            .container-fluid {
                padding-left: 8px !important;
                padding-right: 8px !important;
            }

            /* Fix card padding for mobile */
            .card.bg-white.p-3,
            .card.bg-white.p-2 {
                padding: 15px 10px !important;
            }
        }

        /* Extra Small Mobile Screens */
        @media (max-width: 400px) {
            .bottom-fixed .btn {
                font-size: 10px !important;
                padding: 6px 4px !important;
                min-height: 36px !important;
            }

            .bottom-fixed .col-md-5 h4,
            .bottom-fixed .col-md-5 #total {
                font-size: 14px !important;
            }

            .bottom-fixed .col-md-7 .d-flex {
                gap: 4px !important;
            }
        }

        /* Mobile Menu Offcanvas Styles */
        #mobileMenuOffcanvas .list-group-item {
            border-left: none;
            border-right: none;
            transition: all 0.3s ease;
            font-size: 15px;
        }

        #mobileMenuOffcanvas .list-group-item:hover {
            background-color: #f8f9fa;
            padding-left: 1.5rem;
        }

        #mobileMenuOffcanvas .list-group-item:first-child {
            border-top: none;
        }

        #mobileMenuOffcanvas .offcanvas-body {
            padding: 0;
        }

        /* Mobile menu - make it beautiful like your reference image */
        #mobileMenuOffcanvas .offcanvas {
            width: 280px;
        }

        #mobileMenuOffcanvas .list-group-item {
            border-bottom: 1px solid #e9ecef;
        }

        #mobileMenuOffcanvas .list-group-item .fw-medium {
            font-size: 14px;
            color: #212529;
        }

        /* Collapse invoice section styling */
        #invoiceCollapse .card-body {
            margin: 0 1rem 1rem 1rem;
            border-radius: 8px;
        }

        /* Mobile Menu Modal Card Styles */
        .menu-card {
            background: white;
            border: none;
            border-radius: 15px;
            padding: 15px 10px;
            text-align: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            min-height: 100px;
        }

        .menu-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.15);
        }

        .menu-card:active {
            transform: translateY(-2px);
        }

        .menu-card .menu-icon {
            width: 45px;
            height: 45px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 20px;
            margin-bottom: 5px;
        }

        .menu-card span {
            font-size: 11px;
            font-weight: 500;
            color: #333;
            line-height: 1.2;
            text-align: center;
        }

        /* Modal styling */
        #mobileMenuModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        /* Responsive adjustments */
        @media (max-width: 576px) {
            .menu-card {
                padding: 12px 8px;
                min-height: 90px;
            }

            .menu-card .menu-icon {
                width: 40px;
                height: 40px;
                font-size: 18px;
            }

            .menu-card span {
                font-size: 10px;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .offcanvas {
                width: 70vw !important;
                /* wider for tablet */
                min-width: 360px !important;
                max-width: 90vw !important;
                border-radius: 0 12px 12px 0 !important;
                box-shadow: 0 2px 24px rgba(0, 0, 0, 0.06) !important;
            }

            .offcanvas-header,
            .offcanvas-body {
                padding-left: 22px !important;
                padding-right: 22px !important;
            }

            .offcanvas-title {
                font-size: 1.32rem !important;
                font-weight: 500 !important;
            }

            .offcanvas-body {
                padding-top: 18px !important;
                padding-bottom: 18px !important;
                overflow-y: auto !important;
                max-height: 85vh !important;
            }

            .category-container,
            .brand-container {
                gap: 16px !important;
                padding-bottom: 20px !important;
            }

            .category-card,
            .brand-card {
                font-size: 1.06rem !important;
                padding: 17px !important;
            }
        }

        /* Desktop screens */
        @media (min-width: 1200px) {

            html,
            body {
                height: 100%;
                min-height: 100vh;
                overflow-x: hidden;
                overflow-y: auto;
            }

            body {
                padding-bottom: 120px;
                /* Height of .bottom-fixed + margin */
            }

            .bottom-fixed {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                padding: 20px;
                z-index: 1000;
            }
        }

        /* Laptop screen fix (1024px-1199px) */
        @media (min-width: 1024px) and (max-width: 1199px) {
            body {
                padding-bottom: 140px;
            }

            .bottom-fixed {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                padding: 15px 20px;
                z-index: 1000;
                background: #fff;
                border-top: 1px solid #ddd;
                box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.1);
            }

            .bottom-fixed .btn {
                font-size: 13px;
                padding: 8px 10px;
                margin: 2px 3px;
            }

            .bottom-fixed h4 {
                font-size: 1.1rem;
            }

            .bottom-fixed .row {
                align-items: center;
                margin: 0;
            }
        }

        /* Header Mobile Styles */
        @media (max-width: 767px) {
            #locationSelect {
                font-size: 13px;
                min-width: 150px !important;
            }
            
            .card.bg-white.p-2 {
                padding: 0.5rem !important;
            }

            /* Hide all vehicle/route display elements on mobile */
            #salesRepDisplay,
            #changeSalesRepSelection,
            .badge.bg-success,
            .badge.bg-info,
            #salesAccessBadge {
                display: none !important;
            }

            /* Only show location and hamburger on mobile */
            .col-md-6:first-child .d-flex > *:not(label):not(#locationSelect) {
                display: none !important;
            }
        }

        /* Vehicle/Route badges responsive */
        #salesRepDisplay .badge {
            font-size: 12px;
        }

        /* Force hide on mobile - IMPORTANT */
        @media (max-width: 767px) {
            #salesRepDisplay {
                display: none !important;
            }
        }

        @media (max-width: 991px) {
            #salesRepDisplay .badge {
                font-size: 11px;
                padding: 4px 8px !important;
            }
        }

        /* Customer Credit Information Styling */
        .customer-credit-info {
            margin-top: 8px;
        }

        .customer-credit-info .border {
            border-color: #e0e6ed !important;
            background-color: #f8f9fa;
        }

        .customer-credit-info .col-4 {
            transition: background-color 0.2s ease;
        }

        .customer-credit-info .col-4:hover {
            background-color: #e9ecef;
        }

        .customer-credit-info small {
            font-size: 11px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .customer-credit-info .fw-bold {
            font-size: 14px;
            font-weight: 600;
        }

        /* Better spacing between customer section and product search */
        #productSearchInput {
            margin-top: 0;
        }

        @media (max-width: 767px) {
            #productSearchInput {
                margin-top: 10px;
            }

            .customer-credit-info {
                margin-bottom: 10px;
            }
        }

        /* Responsive adjustments for mobile */
        @media (max-width: 768px) {
            .customer-credit-info .p-2 {
                padding: 8px !important;
            }

            .customer-credit-info small {
                font-size: 10px;
            }

            .customer-credit-info .fw-bold {
                font-size: 12px;
            }
        }

        @media (max-width: 576px) {
            .customer-credit-info .row {
                flex-direction: column;
            }

            .customer-credit-info .col-4 {
                border-right: none !important;
                border-bottom: 1px solid #e0e6ed;
            }

            .customer-credit-info .col-4:last-child {
                border-bottom: none;
            }
        }

        /* Mobile & Tablet Billing Table Card Styles */
        @media (max-width: 991px) {
            /* Allow page scrolling on mobile/tablet */
            html, body {
                overflow: auto !important;
                padding-bottom: 200px !important; /* Space for bottom-fixed section */
            }

            /* Ensure bottom-fixed is visible and properly positioned */
            .bottom-fixed {
                position: fixed !important;
                bottom: 0 !important;
                left: 0 !important;
                right: 0 !important;
                width: 100% !important;
                z-index: 9999 !important;
                background-color: #fff !important;
                box-shadow: 0 -3px 10px rgba(0, 0, 0, 0.15) !important;
                padding: 8px 10px !important;
                display: block !important;
                visibility: visible !important;
                opacity: 1 !important;
            }

            /* Mobile layout for bottom-fixed */
            .bottom-fixed .row {
                display: flex !important;
                flex-direction: column !important;
                gap: 8px;
            }

            .bottom-fixed .col-md-5,
            .bottom-fixed .col-md-7 {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
                padding: 0 !important;
            }

            /* Total Payable section on mobile */
            .bottom-fixed .col-md-5 {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 8px !important;
                align-items: center !important;
                justify-content: space-between !important;
            }

            .bottom-fixed h4 {
                font-size: 14px !important;
                margin: 0 !important;
            }

            .bottom-fixed #total {
                font-size: 18px !important;
                font-weight: 700 !important;
            }

            .bottom-fixed #items-count {
                font-size: 13px !important;
            }

            /* Buttons container on mobile */
            .bottom-fixed .col-md-7 .d-flex {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 5px !important;
                justify-content: center !important;
            }

            .bottom-fixed .btn {
                font-size: 11px !important;
                padding: 6px 10px !important;
                white-space: nowrap !important;
                flex: 0 1 auto !important;
            }

            .bottom-fixed .btn i {
                font-size: 10px !important;
            }

            /* Make billing card scrollable */
            .card.bg-white.p-2 {
                height: auto !important;
                overflow: visible !important;
                margin-bottom: 200px !important; /* Space for bottom-fixed */
            }

            /* Total Items Section - Mobile/Tablet Responsive */
            .row[style*="border-top: 2px solid #ddd"][style*="background-color: #f8f9fa"] {
                display: flex !important;
                flex-direction: column;
                padding: 10px 12px !important;
                gap: 8px;
            }

            .row[style*="border-top: 2px solid #ddd"][style*="background-color: #f8f9fa"] .d-flex {
                flex-direction: column !important;
                align-items: flex-start !important;
                gap: 8px !important;
            }

            /* Total/Discount/Final Total Section - Mobile/Tablet Stacked Layout */
            .row.align-items-end[style*="border-top: 2px solid #ddd"] {
                display: flex !important;
                flex-direction: column !important;
                padding: 12px !important;
                gap: 12px;
            }

            .row.align-items-end[style*="border-top: 2px solid #ddd"] > div[class*="col-md"] {
                width: 100% !important;
                max-width: 100% !important;
                flex: 0 0 100% !important;
            }

            .row.align-items-end[style*="border-top: 2px solid #ddd"] .form-group label {
                font-size: 12px !important;
                font-weight: 700 !important;
                margin-bottom: 4px !important;
            }

            .row.align-items-end[style*="border-top: 2px solid #ddd"] .form-control,
            .row.align-items-end[style*="border-top: 2px solid #ddd"] .input-group,
            .row.align-items-end[style*="border-top: 2px solid #ddd"] .btn-group {
                height: 42px !important;
                font-size: 15px !important;
            }

            .row.align-items-end[style*="border-top: 2px solid #ddd"] .btn-group .btn {
                height: 42px !important;
                font-size: 13px !important;
                padding: 8px 12px !important;
            }

            .row.align-items-end[style*="border-top: 2px solid #ddd"] .input-group input {
                height: 42px !important;
                font-size: 15px !important;
            }

            .row.align-items-end[style*="border-top: 2px solid #ddd"] .input-group-text {
                height: 42px !important;
                font-size: 15px !important;
            }

            /* Hide table headers on mobile/tablet */
            .table-responsive table thead {
                display: none !important;
            }

            /* Make each table row a compact card */
            #billing-body tr {
                display: block;
                position: relative;
                margin-bottom: 12px;
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 10px;
                padding-top: 35px;
                background: white;
                box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            }

            /* Make each cell a block */
            #billing-body tr td {
                display: block;
                border: none !important;
                padding: 0 !important;
                text-align: left !important;
            }

            /* Counter Badge - Top Left (Small) */
            #billing-body tr td.counter-cell {
                position: absolute;
                top: 8px;
                left: 8px;
                font-size: 12px;
                font-weight: 600;
                color: #ffffff;
                background: #6c757d;
                padding: 3px 10px !important;
                border-radius: 4px;
                z-index: 10;
            }

            /* Remove Button - Top Right (Small) */
            #billing-body tr td:nth-child(8) {
                position: absolute;
                top: 8px;
                right: 8px;
                width: auto !important;
                padding: 0 !important;
            }

            #billing-body tr td:nth-child(8) .remove-btn {
                width: 28px;
                height: 28px;
                border-radius: 4px;
                font-size: 16px;
                padding: 0;
                display: flex;
                align-items: center;
                justify-content: center;
                background: #dc3545;
                border: none;
                color: white;
            }

            /* Product Header: Image + Name + SKU */
            #billing-body tr td:nth-child(2) {
                margin-bottom: 10px;
                padding-bottom: 10px !important;
                border-bottom: 1px solid #eee !important;
            }

            #billing-body tr td:nth-child(2) .d-flex {
                flex-direction: row !important;
                gap: 10px;
                align-items: flex-start;
            }

            #billing-body tr td:nth-child(2) img {
                width: 50px !important;
                height: 50px !important;
                border-radius: 4px !important;
                border: 1px solid #dee2e6;
                flex-shrink: 0;
            }

            #billing-body tr td:nth-child(2) .product-info {
                flex: 1;
                min-width: 0;
            }

            #billing-body tr td:nth-child(2) .product-name {
                font-size: 14px;
                font-weight: 600;
                color: #212529;
                line-height: 1.3;
                margin-bottom: 4px;
                display: block;
            }

            #billing-body tr td:nth-child(2) .product-sku {
                font-size: 12px;
                color: #6c757d;
                display: inline-block;
                margin-right: 10px;
            }

            #billing-body tr td:nth-child(2) .quantity-display {
                font-size: 12px;
                color: #198754;
                font-weight: 600;
                display: inline-block;
            }

            #billing-body tr td:nth-child(2) .badge {
                font-size: 10px;
                padding: 2px 6px;
            }

            /* Quantity Section - Compact, No Label */
            #billing-body tr td:nth-child(3) {
                padding: 0 !important;
                margin-bottom: 8px;
            }

            /* Hide the QUANTITY label */
            #billing-body tr td:nth-child(3)::before {
                display: none !important;
            }

            #billing-body tr td:nth-child(3) .d-flex {
                justify-content: center;
                gap: 0;
            }

            #billing-body tr td:nth-child(3) .quantity-minus {
                width: 45px;
                height: 40px;
                border-radius: 4px 0 0 4px !important;
                font-size: 20px;
                font-weight: 600;
                padding: 0 !important;
                background: #dc3545;
                border: none;
                color: white;
            }

            #billing-body tr td:nth-child(3) .quantity-plus {
                width: 45px;
                height: 40px;
                border-radius: 0 4px 4px 0 !important;
                font-size: 20px;
                font-weight: 600;
                padding: 0 !important;
                background: #198754;
                border: none;
                color: white;
            }

            #billing-body tr td:nth-child(3) .quantity-input {
                width: 70px !important;
                height: 40px;
                font-size: 16px;
                font-weight: 600;
                text-align: center;
                border: 1px solid #ced4da;
                border-left: none;
                border-right: none;
                border-radius: 0 !important;
                background: #ffffff;
            }

            /* Hide the Pc(s) text below quantity */
            #billing-body tr td:nth-child(3) > div:last-child {
                display: none !important;
            }

            /* All Price Fields in Single Row */
            #billing-body tr td:nth-child(4),
            #billing-body tr td:nth-child(5),
            #billing-body tr td:nth-child(6) {
                display: inline-block;
                width: 32%;
                margin: 0 0.5% 8px 0;
                padding: 0 !important;
                vertical-align: top;
            }

            #billing-body tr td:nth-child(6) {
                margin-right: 0;
            }

            #billing-body tr td:nth-child(4)::before,
            #billing-body tr td:nth-child(5)::before,
            #billing-body tr td:nth-child(6)::before {
                display: block;
                font-size: 10px;
                font-weight: 600;
                color: #495057;
                margin-bottom: 4px;
            }

            #billing-body tr td:nth-child(4)::before {
                content: 'DISCOUNT (RS)';
            }

            #billing-body tr td:nth-child(5)::before {
                content: 'DISCOUNT (%)';
            }

            #billing-body tr td:nth-child(6)::before {
                content: 'UNIT PRICE';
            }

            #billing-body tr td:nth-child(4) input,
            #billing-body tr td:nth-child(5) input,
            #billing-body tr td:nth-child(6) input {
                width: 100%;
                font-size: 13px;
                font-weight: 600;
                padding: 8px 4px;
                text-align: center;
                border: 1px solid #ced4da;
                border-radius: 4px;
                background: #ffffff;
            }

            /* Subtotal - Left Label, Center Value */
            #billing-body tr td:nth-child(7) {
                width: 100%;
                margin: 8px 0 0 0;
                padding: 10px 12px !important;
                background: #198754;
                border-radius: 4px;
                text-align: left;
                display: flex;
                justify-content: space-between;
                align-items: center;
            }

            #billing-body tr td:nth-child(7)::before {
                content: 'SUBTOTAL';
                color: #ffffff;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
            }

            #billing-body tr td.subtotal {
                font-size: 20px;
                font-weight: 700;
                color: #ffffff !important;
                text-align: right;
                flex: 1;
            }

            /* Hide hidden cells */
            #billing-body tr td.d-none {
                display: none !important;
            }

            /* Adjust table container */
            .table-responsive {
                height: auto !important;
                max-height: calc(100vh - 350px) !important;
                padding: 8px;
                background: #f5f5f5;
            }

            #billing-body {
                padding: 0;
            }

            /* Tablet specific adjustments (768px-991px) */
            @media (min-width: 768px) {
                /* Larger, more spacious cards for tablets */
                #billing-body tr {
                    margin-bottom: 20px;
                    padding: 20px;
                    padding-top: 50px;
                    border-radius: 12px;
                    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.12);
                    border: 2px solid #e0e0e0;
                }

                /* Larger counter badge */
                #billing-body tr td.counter-cell {
                    top: 12px;
                    left: 12px;
                    font-size: 15px;
                    padding: 5px 14px !important;
                    border-radius: 6px;
                }

                /* Larger remove button */
                #billing-body tr td:nth-child(8) {
                    top: 12px;
                    right: 12px;
                }

                #billing-body tr td:nth-child(8) .remove-btn {
                    width: 40px;
                    height: 40px;
                    font-size: 20px;
                    border-radius: 6px;
                }

                /* Product section with more space */
                #billing-body tr td:nth-child(2) {
                    margin-bottom: 16px;
                    padding-bottom: 16px !important;
                }

                #billing-body tr td:nth-child(2) .d-flex {
                    gap: 16px;
                }

                #billing-body tr td:nth-child(2) img {
                    width: 75px !important;
                    height: 75px !important;
                    border-radius: 8px !important;
                    border: 2px solid #dee2e6;
                }

                #billing-body tr td:nth-child(2) .product-name {
                    font-size: 17px;
                    margin-bottom: 7px;
                    line-height: 1.4;
                }

                #billing-body tr td:nth-child(2) .product-sku {
                    font-size: 14px;
                    font-weight: 500;
                }

                #billing-body tr td:nth-child(2) .quantity-display {
                    font-size: 14px;
                    font-weight: 700;
                }

                #billing-body tr td:nth-child(2) .badge {
                    font-size: 12px;
                    padding: 4px 10px;
                }

                /* Larger quantity controls */
                #billing-body tr td:nth-child(3) {
                    margin-bottom: 16px;
                }

                #billing-body tr td:nth-child(3) .quantity-minus,
                #billing-body tr td:nth-child(3) .quantity-plus {
                    width: 60px;
                    height: 52px;
                    font-size: 24px;
                    border-radius: 6px 0 0 6px !important;
                }

                #billing-body tr td:nth-child(3) .quantity-plus {
                    border-radius: 0 6px 6px 0 !important;
                }

                #billing-body tr td:nth-child(3) .quantity-input {
                    width: 100px !important;
                    height: 52px;
                    font-size: 20px;
                    font-weight: 700;
                }

                /* Larger discount and price fields */
                #billing-body tr td:nth-child(4),
                #billing-body tr td:nth-child(5),
                #billing-body tr td:nth-child(6) {
                    width: 31.5%;
                    margin: 0 1% 16px 0;
                }

                #billing-body tr td:nth-child(6) {
                    margin-right: 0;
                }

                #billing-body tr td:nth-child(4)::before,
                #billing-body tr td:nth-child(5)::before,
                #billing-body tr td:nth-child(6)::before {
                    font-size: 12px;
                    margin-bottom: 8px;
                    font-weight: 700;
                }

                #billing-body tr td:nth-child(4) input,
                #billing-body tr td:nth-child(5) input,
                #billing-body tr td:nth-child(6) input {
                    font-size: 16px;
                    padding: 12px 8px;
                    border-radius: 6px;
                    border: 2px solid #ced4da;
                    font-weight: 600;
                }

                #billing-body tr td:nth-child(4) input:focus,
                #billing-body tr td:nth-child(5) input:focus,
                #billing-body tr td:nth-child(6) input:focus {
                    border-color: #198754;
                    box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
                }

                /* Larger subtotal section */
                #billing-body tr td:nth-child(7) {
                    margin-top: 16px;
                    padding: 16px 20px !important;
                    border-radius: 8px;
                }

                #billing-body tr td:nth-child(7)::before {
                    font-size: 14px;
                    font-weight: 700;
                    letter-spacing: 0.5px;
                }

                #billing-body tr td.subtotal {
                    font-size: 26px;
                    font-weight: 800;
                }

                /* More spacious table container */
                .table-responsive {
                    padding: 16px;
                    background: #fafafa;
                }
            }
        }

        /* ============================================
           OPTIMIZED BILLING TABLE STYLES FOR DESKTOP
           ============================================ */
        
        /* Desktop: Reduce table cell padding for compact display */
        @media (min-width: 992px) {
            #billing-body tr td {
                padding: 4px 6px !important;
                vertical-align: middle !important;
            }

            /* Counter cell - centered alignment */
            #billing-body tr td:nth-child(1) {
                text-align: center !important;
                vertical-align: middle !important;
                padding: 4px !important;
            }

            /* Product cell - optimize spacing */
            #billing-body tr td:nth-child(2) {
                padding: 6px 8px !important;
                vertical-align: middle !important;
            }

            #billing-body tr td:nth-child(2) .d-flex {
                align-items: center;
                gap: 8px;
            }

            /* Product image - reduce size for compactness */
            #billing-body tr td:nth-child(2) img {
                width: 40px !important;
                height: 40px !important;
                margin-right: 6px !important;
            }

            /* Product info section */
            #billing-body tr td:nth-child(2) .product-info {
                line-height: 1.2;
            }

            /* Product name - increase font size more */
            #billing-body tr td:nth-child(2) .product-name {
                font-size: 15px;
                line-height: 1.4;
                margin-bottom: 3px;
                font-weight: 600;
            }

            /* Increase badge sizes more */
            #billing-body tr td:nth-child(2) .badge {
                font-size: 11px;
                padding: 3px 7px;
                margin: 0 3px;
            }

            /* SKU and quantity display - even larger text */
            #billing-body tr td:nth-child(2) .product-sku,
            #billing-body tr td:nth-child(2) .quantity-display {
                font-size: 13px;
            }

            /* IMEI section - optimize spacing */
            #billing-body tr td:nth-child(2) .d-flex.flex-wrap {
                gap: 4px !important;
                margin-top: 2px !important;
            }

            /* IMEI badge - larger size */
            #billing-body tr td:nth-child(2) .badge.bg-info {
                font-size: 10px;
                padding: 3px 6px;
            }

            /* IMEI info icon - larger */
            #billing-body tr td:nth-child(2) .show-imei-btn {
                font-size: 13px;
                margin-left: 2px !important;
            }

            /* Quantity controls - larger size */
            #billing-body tr td:nth-child(3) {
                text-align: center;
                vertical-align: middle !important;
            }

            #billing-body tr td:nth-child(3) .d-flex {
                gap: 0;
            }

            #billing-body tr td:nth-child(3) .quantity-minus,
            #billing-body tr td:nth-child(3) .quantity-plus {
                width: 34px;
                height: 34px;
                font-size: 17px;
                padding: 0;
            }

            #billing-body tr td:nth-child(3) .quantity-input {
                width: 60px;
                height: 34px;
                font-size: 15px;
                padding: 0 5px;
                font-weight: 600;
            }

            /* Unit name below quantity - larger */
            #billing-body tr td:nth-child(3) > div:last-child {
                font-size: 12px;
                margin-top: 2px;
            }

            /* Discount and Price columns - centered */
            #billing-body tr td:nth-child(4),
            #billing-body tr td:nth-child(5),
            #billing-body tr td:nth-child(6),
            #billing-body tr td:nth-child(7) {
                text-align: center;
                vertical-align: middle !important;
            }

            /* Input fields - even larger height and font */
            #billing-body tr td input.form-control {
                height: 34px;
                font-size: 14px;
                padding: 4px 7px;
                text-align: center;
                font-weight: 500;
            }

            /* Subtotal column - even larger and bold */
            #billing-body tr td.subtotal {
                font-weight: 700;
                font-size: 15px;
                text-align: center;
                vertical-align: middle !important;
            }

            /* Remove button - larger */
            #billing-body tr td:nth-child(8) {
                text-align: center;
                vertical-align: middle !important;
            }

            #billing-body tr td:nth-child(8) .remove-btn {
                width: 30px;
                height: 30px;
                font-size: 17px;
                padding: 0;
                display: inline-flex;
                align-items: center;
                justify-content: center;
                line-height: 1;
            }

            /* Table header - even larger font */
            .table thead th {
                padding: 7px 9px !important;
                font-size: 14px;
                font-weight: 600;
            }

            /* Compact form groups for single-screen view */
            .form-group {
                margin-bottom: 4px !important;
            }

            .form-group label {
                font-size: 13px;
                margin-bottom: 2px;
                font-weight: 600;
            }

            .form-group .form-control,
            .form-group p.form-control {
                height: 36px;
                font-size: 15px;
                padding: 5px 8px;
                font-weight: 600;
            }

            /* Compact card sections */
            .card.bg-light,
            .card.bg-white {
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }

            /* Beautiful Button Group for Discount Type */
            .btn-group .btn {
                padding: 0;
                font-size: 14px;
                height: 36px;
                font-weight: 600;
                transition: all 0.3s ease;
                border: none !important;
            }

            .btn-group .btn:hover {
                transform: translateY(-1px);
                box-shadow: 0 2px 6px rgba(0,0,0,0.15);
            }

            .btn-group .btn.active {
                background-color: #0d6efd !important;
                color: white !important;
                box-shadow: 0 2px 6px rgba(13, 110, 253, 0.3);
            }

            .btn-group .btn:not(.active) {
                background-color: white;
                color: #0d6efd;
            }

            .btn-group .btn:not(.active):hover {
                background-color: #e7f1ff;
            }

            /* Input group text sizing */
            .input-group-text {
                font-size: 14px;
                padding: 5px 10px;
                height: 36px;
                font-weight: 600;
            }

            /* Reduce input group margins */
            .input-group {
                height: 36px;
            }

            .input-group .form-control {
                height: 36px;
                font-size: 15px;
                font-weight: 600;
            }
        }

        /* Additional compact adjustments for standard desktop screens */
        @media (min-width: 992px) and (max-width: 1399px) {
            #billing-body tr td {
                padding: 3px 4px !important;
            }

            #billing-body tr td:nth-child(2) img {
                width: 35px !important;
                height: 35px !important;
            }

            #billing-body tr td:nth-child(2) .product-name {
                font-size: 12px;
            }
        }
    </style>
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
                            <button class="btn btn-primary ms-2" type="button" data-bs-toggle="modal" data-bs-target="#mobileMenuModal">
                                <i class="fas fa-bars"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Desktop View: Two Columns -->
                    <div class="row align-items-center d-none d-md-flex" style="margin: 0; padding: 4px 6px;">
                        <!-- Location and Date Section -->
                        <div class="col-md-6 d-flex align-items-center" style="padding: 0 6px;">
                            <div class="d-flex flex-row align-items-center" style="gap: 6px;">
                                <select id="locationSelectDesktop" class="form-select location-select-sync" style="min-width: 180px; max-width: 220px;">
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
                                    <button class="btn btn-sm btn-outline-secondary" id="changeSalesRepSelection" title="Change Vehicle/Route">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                </div>

                                <!-- Date and Time (Desktop Only) -->
                                <div class="d-flex align-items-center" style="gap: 6px;">
                                    <button class="btn btn-primary text-white border-1 px-3 py-1" style="font-size: 0.95rem;" id="currentDateButton">
                                        {{ \Carbon\Carbon::now('Asia/Colombo')->format('Y-m-d') }}
                                    </button>
                                    <span id="currentTimeText" style="color: #1e90ff; font-weight: 600; font-size: 1rem;">
                                        {{ \Carbon\Carbon::now('Asia/Colombo')->format('H:i:s') }}
                                    </span>
                                </div>

                                <!-- Keyboard Shortcuts Button (Desktop Only) -->
                                <button class="btn btn-info text-white border-1 px-2 py-1 d-flex align-items-center justify-content-center"
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
                                <button class="btn btn-secondary btn-sm" onclick="window.location.href='{{ route('dashboard') }}'" data-bs-toggle="tooltip" title="Go home">
                                    <i class="fas fa-home"></i>
                                </button>

                                <button class="btn btn-light btn-sm" onclick="handleGoBack()" data-bs-toggle="tooltip" title="Go Back">
                                    <i class="fas fa-backward"></i>
                                </button>

                                <!-- Calculator Button with Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-warning btn-sm dropdown-toggle" id="calculatorButton" data-bs-toggle="dropdown" aria-expanded="false" data-bs-toggle="tooltip" title="Calculator">
                                        <i class="fas fa-calculator"></i>
                                    </button>
                                    <div class="dropdown-menu p-2 shadow" id="calculatorDropdown" style="width: 220px;">
                                        <div class="text-center">
                                            <input type="text" id="calcDisplay" class="form-control text-end mb-2" onkeydown="handleKeyboardInput(event)" autofocus>
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
                                </div>

                                <div class="dropdown">
                                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false" data-bs-toggle="tooltip" title="Enter Invoice No">
                                        <i class="fas fa-redo-alt"></i>
                                    </button>
                                    <div class="dropdown-menu p-3" style="min-width: 250px;">
                                        <label for="invoiceNo" class="form-label">Enter Invoice No</label>
                                        <input type="text" id="invoiceNo" class="form-control form-control-sm" placeholder="Invoice No">
                                        <button id="invoiceSubmitBtn" class="btn btn-primary btn-sm mt-2 w-100">Submit</button>
                                    </div>
                                </div>

                                <button class="btn btn-outline-danger btn-sm" id="pauseCircleButton" data-bs-toggle="modal" data-bs-target="#suspendSalesModal" data-bs-toggle="tooltip" title="Suspend Sales">
                                    <i class="fas fa-pause-circle"></i>
                                </button>

                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#recentTransactionsModal" data-bs-toggle="tooltip" title="Recent Transactions">
                                    <i class="fas fa-clock"></i>
                                </button>

                                <button class="btn btn-gradient btn-sm" id="toggleProductList" data-bs-toggle="tooltip" title="Hide or Show Product list">
                                    <i class="fas fa-bars"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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
                    const dateStr = now.getFullYear() + '-' + ('0' + (now.getMonth() + 1)).slice(-2) + '-' + ('0' + now.getDate()).slice(-2);
                    const timeStr = ('0' + now.getHours()).slice(-2) + ':' + ('0' + now.getMinutes()).slice(-2) + ':' + ('0' + now.getSeconds()).slice(-2);
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

        <!-- Mobile Menu Modal -->
        <div class="modal fade" id="mobileMenuModal" tabindex="-1" aria-labelledby="mobileMenuModalLabel">
            <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content" style="border-radius: 20px; overflow: hidden;">
                    <div class="modal-header bg-white border-bottom">
                        <h5 class="modal-title fw-bold text-dark" id="mobileMenuModalLabel">
                            <i class="fas fa-bars me-2"></i> Menu
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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
                                <button class="btn btn-sm btn-outline-primary w-100 mt-2" id="changeSalesRepSelectionMenu">
                                    <i class="fas fa-edit me-2"></i>Change Vehicle/Route
                                </button>
                            </div>
                        </div>

                        <div class="row g-3 p-3">
                            <!-- Row 1 -->
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#recentTransactionsModal">
                                    <div class="menu-icon bg-primary">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <span>Recent Transactions</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" onclick="window.location.href='{{ url('sale-return') }}'">
                                    <div class="menu-icon bg-success">
                                        <i class="fas fa-undo"></i>
                                    </div>
                                    <span>Sell Return</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" data-bs-toggle="modal" data-bs-target="#suspendSalesModal">
                                    <div class="menu-icon bg-secondary">
                                        <i class="fas fa-pause-circle"></i>
                                    </div>
                                    <span>Suspended Sales</span>
                                </button>
                            </div>

                            <!-- Row 2 -->
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" onclick="window.location.href='{{ url('expense-add') }}'">
                                    <div class="menu-icon bg-success">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <span>Add Expense</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" onclick="handleGoBack()">
                                    <div class="menu-icon bg-secondary">
                                        <i class="fas fa-backward"></i>
                                    </div>
                                    <span>Go Back</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" onclick="window.location.href='{{ route('dashboard') }}'">
                                    <div class="menu-icon bg-info">
                                        <i class="fas fa-home"></i>
                                    </div>
                                    <span>Go Home</span>
                                </button>
                            </div>

                            <!-- Row 3 -->
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-toggle="collapse" data-bs-target="#invoiceCollapse">
                                    <div class="menu-icon bg-danger">
                                        <i class="fas fa-redo-alt"></i>
                                    </div>
                                    <span>Invoice No</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" id="toggleProductListMobile" onclick="document.getElementById('toggleProductList').click()">
                                    <div class="menu-icon bg-primary">
                                        <i class="fas fa-box-open"></i>
                                    </div>
                                    <span>Product List</span>
                                </button>
                            </div>
                            <div class="col-4">
                                <button type="button" class="menu-card w-100" data-bs-dismiss="modal" onclick="toggleFullScreen()">
                                    <div class="menu-icon bg-warning">
                                        <i class="fas fa-expand"></i>
                                    </div>
                                    <span>Full Screen</span>
                                </button>
                            </div>
                        </div>

                        <!-- Invoice Number Input (Collapsible) -->
                        <div class="collapse" id="invoiceCollapse">
                            <div class="px-3 pb-3">
                                <div class="card border-0 bg-light" style="border-radius: 15px;">
                                    <div class="card-body">
                                        <label for="invoiceNoMobile" class="form-label fw-bold">Enter Invoice Number</label>
                                        <input type="text" id="invoiceNoMobile" class="form-control mb-2" placeholder="Invoice No">
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

        <script>
            // Handle back navigation for POS system: always redirect to dashboard if no history
            function handleGoBack() {
                window.location.href = "{{ route('dashboard') }}";
            }
        </script>

        <div class="row mt-1">

            <div class="container-fluid p-1">
                <div class="row">
                    <div class="col-md-12" id="mainContent">
                        <div class="card bg-white p-2" style="height: calc(100vh - 215px); overflow: hidden; display: flex; flex-direction: column;">
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
                                            <div class="flex-fill p-2 text-center">
                                                <small class="text-muted d-block">Available</small>
                                                <span id="available-credit-amount" class="fw-bold text-success d-block">
                                                    Rs. 0.00
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-7 ps-2">
                                    <input type="text" class="form-control" id="productSearchInput"
                                        placeholder="Enter Product name / SKU / Scan bar code" style="height: 38px; font-size: 14px;">
                                </div>
                            </div>

                            <!-- Spacer for better separation -->
                            <div class="row mt-1" style="flex: 1; overflow: hidden;">
                                <div class="col-md-12 mt-1" style="height: 100%; display: flex; flex-direction: column;">
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
                            <div class="row" style="margin: 0; border-top: 2px solid #ddd; background-color: #f8f9fa;">
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
                            <div class="row align-items-end" style="margin: 0; border-top: 2px solid #ddd; background-color: #fff; padding: 10px;">
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Total</label>
                                        <p id="total-amount" class="form-control form-control-sm mb-0" style="height: 36px; line-height: 24px; font-size: 15px; font-weight: 600;">0.00</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Global Discount Type</label>
                                        <div class="btn-group w-100" role="group" aria-label="Discount Type" style="height: 36px; border-radius: 6px; overflow: hidden; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                                            <button type="button" class="btn btn-primary active"
                                                id="fixed-discount-btn" style="font-size: 14px; padding: 0; font-weight: 600; height: 36px; border: none; border-radius: 0; transition: all 0.3s ease;">Fixed</button>
                                            <button type="button" class="btn btn-outline-primary"
                                                id="percentage-discount-btn" style="font-size: 14px; padding: 0; font-weight: 600; height: 36px; border: none; border-radius: 0; background: white; transition: all 0.3s ease;">Percentage</button>
                                        </div>
                                        <input type="hidden" id="discount-type" name="discount_type" value="fixed">
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Discount</label>
                                        <div class="input-group input-group-sm" style="height: 36px;">
                                            <input type="text" id="global-discount" name="discount"
                                                class="form-control form-control-sm" placeholder="0.00" style="height: 36px; font-size: 15px; font-weight: 600;">
                                            <span class="input-group-text" id="discount-icon" style="height: 36px; font-size: 14px; font-weight: 600;">Rs</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group mb-0">
                                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Final Total</label>
                                        <p id="final-total-amount" class="form-control form-control-sm mb-0" style="height: 36px; line-height: 24px; font-size: 15px; font-weight: 600;">0.00</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group mb-0">
                                        <label style="font-size: 13px; font-weight: 600; margin-bottom: 4px; display: block;">Amount Given</label>
                                        <input type="text" id="amount-given" class="form-control form-control-sm"
                                            placeholder="0.00" oninput="formatAmount(this)" style="height: 36px; font-size: 15px; font-weight: 600;">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5 collapse" id="productListArea">
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

                            <div class="row g-1 overflow-auto" id="posProduct" style="height: calc(100vh - 315px); overflow-y: auto;">

                            </div>
                        </div>
                    </div>
                </div>
            </div>

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
                        // Don't reset the discount value - keep existing value
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
                        // Don't reset the discount value - keep existing value
                        // Trigger calculation update if updateTotals exists
                        if (typeof updateTotals === 'function') {
                            updateTotals();
                        }
                    });
                    // Toggle product list area visibility
                    toggleProductListBtn.addEventListener('click', function() {
                        if (productListArea.classList.contains('show')) {
                            productListArea.classList.remove('show');
                            mainContent.classList.remove('col-md-7');
                            mainContent.classList.add('col-md-12');
                        } else {
                            productListArea.classList.add('show');
                            mainContent.classList.remove('col-md-12');
                            mainContent.classList.add('col-md-7');
                        }
                    });
                });
            </script>
            <style>
                .btn-gradient {
                    background: linear-gradient(85deg, #1e90ff, #1e90ff, #87cefa);
                    border: none;
                    color: white;
                    transition: background 0.3s ease;
                }

                .btn-gradient:hover {
                    background: linear-gradient(85deg, #1e90ff, #1e90ff);
                    color: white;
                }
            </style>

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
        <div class="bottom-fixed">
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

                        @can('save draft')
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
                            <a class="nav-link active" data-bs-toggle="tab" href="#final"
                                onclick="loadTableData('final')">Final</a>
                        </li>
                        @can('create quotation')
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#quotation"
                                    onclick="loadTableData('quotation')">Quotation</a>
                            </li>
                        @endcan

                        @can('save draft')
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#draft"
                                    onclick="loadTableData('draft')">Draft</a>
                            </li>
                        @endcan

                        @can('create job-ticket')
                            <li class="nav-item">
                                <a class="nav-link" data-bs-toggle="tab" href="#jobticket"
                                    onclick="loadTableData('jobticket')">Job Tickets</a>
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

    <style>
        .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        .table-responsive {
            overflow-x: auto;
        }
    </style>

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
    <div class="modal fade" id="chequeModal" tabindex="-1" aria-labelledby="chequeModalLabel">
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
                    <button type="button" class="btn btn-primary" id="confirmChequePayment">Confirm Payment</button>
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <input type="text" id="imeiSearch" class="form-control" placeholder="Search IMEI...">
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
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
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

    <!-- Modal -->
    <div class="modal fade" id="jobTicketModal" tabindex="-1" aria-labelledby="jobTicketModalLabel">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="font-family: 'Roboto', sans-serif;">
                <div class="modal-header">
                    <h5 class="modal-title" id="jobTicketModalLabel" style="font-weight: bold;">JOB-TICKET</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body px-4 py-3">
                    <form id="jobTicketForm">
                        <div class="row mb-2">
                            <div class="col-6">
                                <label class="form-label mb-1" style="font-size: 13px;">Ticket ID</label>
                                <input type="text" class="form-control form-control-sm" id="ticketId" readonly>
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
                                <input type="number" class="form-control form-control-sm" id="advanceAmountInput">
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
    <style>
        #jobTicketModal .form-label {
            font-weight: 500;
        }

        #jobTicketModal input[readonly],
        #jobTicketModal textarea[readonly] {
            background: #f8f9fa;
        }

        #jobTicketModal input,
        #jobTicketModal textarea {
            font-size: 13px;
        }

        #jobTicketModal .modal-content {
            border-radius: 10px;
        }
    </style>
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
                            const sale = data.sales.find(sale => sale.invoice_no.toLowerCase() === invoiceNo.toLowerCase());
                            console.log('Found sale:', sale);
                            
                            if (sale) {
                                console.log('Redirecting to sale return page');
                                // Redirect to the sale return page with the invoice number as a query parameter
                                window.location.href = `/sale-return/add?invoiceNo=${encodeURIComponent(invoiceNo)}`;
                            } else {
                                console.log('Sale not found for invoice:', invoiceNo);
                                // Show toastr message indicating sale not found
                                if (typeof toastr !== 'undefined') {
                                    toastr.error('Sale not found. Please enter a valid invoice number.');
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
   
   
   <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <!-- Include Bootstrap JS -->
    @include('sell.pos_ajax')
    @include('contact.customer.customer_ajax')
    @include('contact.customer.cities_ajax')
    @include('contact.customer.add_customer_modal')
    @include('contact.customer.city_modal')

</body>

</html>
