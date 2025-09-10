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
        /* General Styles */
        body {
            font-family: 'Roboto', sans-serif;
            font-size: 14px;
            line-height: 1.5;
            background-color: #dedede;
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

        /* Ensure autocomplete items are properly styled */
        .ui-menu .ui-menu-item {
            padding: 8px 12px;
            cursor: pointer;
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



        /* 768px–1199px: Common fixes for all laptop/tablet */
        @media (max-width: 1199px) and (min-width: 768px) {

            /* .product-card, */
            .category-card,
            .brand-card {
                min-height: 72px !important;
                padding: 14px 18px !important;
                display: flex !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 16px !important;
                justify-content: flex-start !important;
            }

            /* .product-card img {
                max-width: 54px !important;
                max-height: 54px !important;
                min-width: 36px !important;
                object-fit: contain !important;
            } */

            /* .product-card h6, */
            .category-card h6,
            .brand-card h6 {
                font-size: 1.08rem !important;
                margin-bottom: 5px !important;
                white-space: normal !important;
                overflow: visible !important;
            }

            /* .product-card .badge {
                font-size: 1rem !important;
                margin: 5px 0 !important;
            } */

            .row.g-3.overflow-auto,
            #posProduct {
                gap: 0 !important;
                padding: 0 !important;
            }

            /* Bottom-fixed: 3-column button grid for tablet */
            .bottom-fixed {
                display: flex !important;
                flex-wrap: wrap !important;
                justify-content: space-between !important;
                align-items: flex-start !important;
                gap: 16px !important;
                padding: 16px 3vw !important;
                width: 100vw !important;
                background: #fff !important;
                box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.04) !important;
                z-index: 100;
            }

            .bottom-fixed .row {
                flex-wrap: wrap !important;
                width: 100vw !important;
                gap: 0 !important;
            }

            .bottom-fixed .col-md-5,
            .bottom-fixed .col-md-7 {
                width: 100vw !important;
                max-width: 100vw !important;
                flex: 0 0 100vw !important;
                padding: 0 !important;
                margin: 0 !important;
            }

            /* Button grid: 3 per row for tablet */
            .bottom-fixed .d-flex,
            .bottom-fixed .justify-content-end,
            .bottom-fixed .gap-2,
            .bottom-fixed .flex-wrap {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 0 !important;
                justify-content: center !important;
                width: 100vw !important;
                margin: 0 !important;
            }

            .bottom-fixed .btn {
                width: 31vw !important;
                max-width: 32vw !important;
                min-width: 110px !important;
                flex: 1 1 31vw !important;
                margin: 10px 1vw !important;
                font-size: 1.08rem !important;
                padding: 14px 0 !important;
                border-radius: 12px !important;
                text-align: center !important;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05) !important;
            }

            .bottom-fixed h4,
            .bottom-fixed span {
                font-size: 1.18rem !important;
                margin-right: 8px !important;
            }

            .bottom-fixed #cancelButton {
                margin-left: 10px !important;
            }

            html,
            body {
                overflow-x: hidden !important;
            }
        }

        /* 600px–767px: Small tablet, landscape mobile */
        @media (max-width: 767px) and (min-width: 600px) {

            /* #posProduct>div[class*="col-"],
            .product-card {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 auto 14px auto !important;
                box-sizing: border-box;
            } */

            /* .product-card, */
            .category-card,
            .brand-card {
                min-height: 68px !important;
                padding: 12px 13px !important;
                gap: 13px !important;
            }

            /* .product-card img {
                max-width: 48px !important;
                max-height: 48px !important;
                min-width: 28px !important;
            } */

            /* .product-card h6, */
            .category-card h6,
            .brand-card h6 {
                font-size: 1.01rem !important;
                margin-bottom: 4px !important;
            }

            /* Bottom-fixed: 2-column button grid for mobile/tablet */
            .bottom-fixed .d-flex,
            .bottom-fixed .justify-content-end,
            .bottom-fixed .gap-2,
            .bottom-fixed .flex-wrap {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 0 !important;
                justify-content: center !important;
                width: 100vw !important;
                margin: 0 !important;
            }

            .bottom-fixed .btn {
                width: 48vw !important;
                max-width: 48vw !important;
                min-width: 90px !important;
                font-size: 1.01rem !important;
                padding: 11px 0 !important;
                border-radius: 10px !important;
                margin: 8px 1vw !important;
            }
        }

        /* 320px–599px: Mobile, portrait tablet */
        @media (max-width: 599px) {
            /*
            #posProduct>div[class*="col-"],
            .product-card {
                flex: 0 0 100% !important;
                max-width: 100% !important;
                min-width: 0 !important;
                margin: 0 auto 12px auto !important;
                box-sizing: border-box;
            }

            .product-card,
            .category-card,
            .brand-card {
                min-height: 62px !important;
                padding: 10px 8px !important;
                flex-direction: row !important;
                align-items: center !important;
                gap: 10px !important;
            }

            .product-card img {
                max-width: 40px !important;
                max-height: 40px !important;
                min-width: 20px !important;
                object-fit: contain !important;
            }

            .product-card h6,
            .category-card h6,
            .brand-card h6 {
                font-size: 0.98rem !important;
                margin-bottom: 3px !important;
            } */

            /* --- UX improvement: Make bottom-fixed NOT sticky on mobile --- */
            .bottom-fixed {
                position: static !important;
                box-shadow: none !important;
                border-top: 1px solid #eee !important;
                padding-bottom: 12px !important;
                margin-bottom: 0 !important;
                background: #fff !important;
            }

            html,
            body {
                padding-bottom: 0 !important;
            }

            /* Button grid for mobile */
            .bottom-fixed .d-flex,
            .bottom-fixed .justify-content-end,
            .bottom-fixed .gap-2,
            .bottom-fixed .flex-wrap {
                display: flex !important;
                flex-wrap: wrap !important;
                gap: 0 !important;
                justify-content: center !important;
                width: 100vw !important;
                margin: 0 !important;
            }

            .bottom-fixed .btn {
                width: 48vw !important;
                max-width: 48vw !important;
                min-width: 80px !important;
                font-size: 0.95rem !important;
                padding: 10px 0 !important;
                border-radius: 9px !important;
                margin: 7px 1vw !important;
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

        @media (max-width: 991px) {

            .bottom-fixed {
                position: static !important;
                left: 0 !important;
                bottom: 0 !important;
                right: 0 !important;
                width: 100vw !important;
                /* padding: 4px 2vw !important; */
                margin: 0 auto !important;
                background: #fff !important;
                box-shadow: 0 -2px 8px rgba(0, 0, 0, 0.08) !important;
                display: flex !important;
                flex-direction: column !important;
                /* align-items: stretch !important; */
                z-index: 1000 !important;
                overflow-x: auto !important;
            }

            /* Button grid: 2-per-row, wrap if odd number */
            .bottom-fixed .button-grid {
                display: flex;
                flex-wrap: wrap;
                gap: 10px 2vw;
                width: 100%;
                justify-content: center;
                margin: 0 auto;
            }

            .bottom-fixed .button-grid .btn {
                flex: 1 1 45%;
                max-width: 48vw;
                min-width: 80px;
                margin: 0;
                font-size: 0.95rem !important;
                padding: 8px 0 !important;
                border-radius: 8px !important;
                text-align: center;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.03);
                margin-bottom: 7px !important;
            }

            /* Remove extra margin from last row if odd */
            .bottom-fixed .button-grid .btn:last-child:nth-child(odd) {
                margin-left: 25vw;
            }

            /* Total Payable and Cancel should be a row above, large and clear */
            .bottom-fixed .total-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 12px;
                gap: 8px;
                width: 100%;
                flex-wrap: wrap;
            }

            .bottom-fixed .total-row h4,
            .bottom-fixed .total-row span {
                font-size: 1.11rem !important;
                margin: 0 5px 0 0;
            }

            .bottom-fixed .total-row .btn {
                font-size: 0.95rem !important;
                padding: 7px 0 !important;
                min-width: 70px !important;
                border-radius: 8px !important;
                margin: 0;
                white-space: nowrap;
            }

            /* Scroll area fix for mobile: make whole content scrollable */
            html,
            body,
            .container-fluid {
                overflow-x: hidden !important;
                height: 100vh !important;
                padding-bottom: 100px !important;
            }
        }

        /* For very small screens, shrink button font a bit */
        @media (max-width: 500px) {

            .bottom-fixed .button-grid .btn,
            .bottom-fixed .total-row .btn,
            .bottom-fixed .btn {
                font-size: 0.82rem !important;
                min-width: 60px !important;
                padding: 6px 0 !important;
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

        @media (min-width: 1200px) {

            html,
            body {
                height: 100%;
                min-height: 100vh;
                overflow-x: hidden;
                overflow-y: auto;
            }

            body {
                padding-bottom: 90px;
                /* Height of .bottom-fixed + some margin */
            }
        }
    </style>
</head>

<body>
    <!-- Include Sales Rep Vehicle/Route Selection Modal -->
    @include('components.sales-rep-modal')

    <div class="container-fluid p-2">
        <div class="row">
            <div class="col-md-12">
                <div class="card bg-white p-2">
                    <div class="row align-items-center">
                        <!-- Location and Date Section -->
                        <div class="col-md-6 d-flex align-items-center">
                            {{-- <h6 class="me-3 mb-0">Location: <strong>{{ $location->name ?? 'N/A' }}</strong></h6>
                            --}}

                            <div class="d-flex flex-row align-items-center gap-3 flex-wrap">
                                <select id="locationSelect" class="form-control selectBox rounded-start" style="max-width: 220px;">
                                    <option value="" selected disabled>Select Location</option>
                                </select>
                                
                                <!-- Sales Rep Vehicle/Route Display -->
                                <div id="salesRepDisplay" style="display: none;" class="align-items-center gap-2">
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
                                
                                <button class="btn btn-primary text-white border-1 px-3 py-1" style="width: 130px; font-size: 1.15rem;" id="currentDateButton">
                                    {{ \Carbon\Carbon::now('Asia/Colombo')->format('Y-m-d') }}
                                </button>
                                <span id="currentTimeText" style="color: #1e90ff; font-weight: 600; font-size: 1.08rem;">
                                    {{ \Carbon\Carbon::now('Asia/Colombo')->format('H:i:s') }}
                                </span>
                                <button class="btn btn-info text-white border-1 px-2 py-1 d-flex align-items-center justify-content-center"
                                    id="shortcutButton"
                                    style="width: 40px; height: 40px;"
                                    data-bs-toggle="popover" data-bs-trigger="hover" data-bs-html="true"
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
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        function updateDateTime() {
                                            const now = new Date();
                                            const dateStr = now.getFullYear() + '-' +
                                                ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
                                                ('0' + now.getDate()).slice(-2);
                                            const timeStr = ('0' + now.getHours()).slice(-2) + ':' +
                                                ('0' + now.getMinutes()).slice(-2) + ':' +
                                                ('0' + now.getSeconds()).slice(-2);
                                            document.getElementById('currentDateButton').innerText = dateStr;
                                            document.getElementById('currentTimeText').innerText = timeStr;
                                        }
                                        setInterval(updateDateTime, 1000);
                                        updateDateTime();
                                    });
                                </script>
                            </div>

                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    const currentDateButton = document.getElementById('currentDateButton');
                                    setInterval(() => {
                                        const now = new Date();
                                        const formattedDate = now.getFullYear() + '-' +
                                            ('0' + (now.getMonth() + 1)).slice(-2) + '-' +
                                            ('0' + now.getDate()).slice(-2);
                                        currentDateButton.innerText = formattedDate;
                                    }, 1000);
                                    const popoverTriggerList = [].slice.call(document.querySelectorAll(
                                        '[data-bs-toggle="popover"]'))
                                    const popoverList = popoverTriggerList.map(function(popoverTriggerEl) {
                                        return new bootstrap.Popover(popoverTriggerEl)
                                    })
                                });
                            </script>

                        </div>

                        <!-- Action Buttons -->
                        <div class="col-md-6">
                            <div class="d-flex justify-content-end gap-3">
                                <button class="btn btn-light btn-sm" onclick="history.back()" data-bs-toggle="tooltip"
                                    title="Go Back"><i class="fas fa-backward"></i></button>

                                <!-- Calculator Button with Dropdown -->
                                <div class="dropdown">
                                    <button class="btn btn-warning btn-sm dropdown-toggle" id="calculatorButton"
                                        data-bs-toggle="dropdown" aria-expanded="false" data-bs-toggle="tooltip"
                                        title="Calculator">
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

                                <div class="dropdown">
                                    <button class="btn btn-danger btn-sm dropdown-toggle" type="button"
                                        id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false"
                                        data-bs-toggle="tooltip" title="Enter Invoice No">
                                        <i class="fas fa-redo-alt"></i>
                                    </button>

                                    <!-- Dropdown menu -->
                                    <div class="dropdown-menu p-3" style="min-width: 250px;">
                                        <label for="invoiceNo" class="form-label">Enter Invoice No</label>
                                        <input type="text" id="invoiceNo" class="form-control form-control-sm"
                                            placeholder="Invoice No">
                                        <button class="btn btn-primary btn-sm mt-2 w-100">Submit</button>
                                    </div>
                                </div>
                                <button class="btn btn-outline-danger" id="pauseCircleButton" data-bs-toggle="modal"
                                    data-bs-target="#suspendSalesModal" data-bs-toggle="tooltip"
                                    title="Suspend Sales">
                                    <i class="fas fa-pause-circle"></i>
                                </button>

                                <button class="btn btn-primary" data-bs-toggle="modal"
                                    data-bs-target="#recentTransactionsModal" data-bs-toggle="tooltip"
                                    title="Recent Transactions"><i class="fas fa-clock"></i></button>
                                <!-- Hamburger button to toggle the product list area -->
                                <button class="btn btn-gradient" id="toggleProductList" data-bs-toggle="tooltip"
                                    title="Hide or Show Product list"><i class="fas fa-bars"></i></i></button>

                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-2">

            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-12" id="mainContent">
                        <div class="card bg-white p-3">
                            <div class="row">
                                <div class="col-12">
                                    <p id="sale-invoice-no" class="text-info fw-bold"></p>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-5">

                                    <div class="d-flex justify-content-center customer-select2">
                                        <select class="form-control selectBox" id="customer-id">
                                            <option selected disabled>Please Select</option>
                                        </select>
                                        <button type="button" class="btn btn-outline-info rounded-0"
                                            id="addCustomerButton">
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
                                    <div class="table-responsive"
                                        style="height: calc(100vh - 445px); overflow-y: auto;">
                                        <table class="table table-bordered">
                                            <thead class="thead-light">
                                                <tr>
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
                        </div>

                        <div class="card bg-white mt-3 p-2">
                            <div class="row align-items-center">
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Total</label>
                                        <p id="total-amount" class="form-control">0.00</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label for="discount-type">Global Discount Type</label>
                                        <div class="btn-group w-100" role="group" aria-label="Discount Type">
                                            <button type="button" class="btn btn-outline-primary active"
                                                id="fixed-discount-btn">Fixed</button>
                                            <button type="button" class="btn btn-outline-primary"
                                                id="percentage-discount-btn">Percentage</button>
                                        </div>
                                        <input type="hidden" id="discount-type" name="discount_type"
                                            value="fixed">
                                    </div>
                                </div>

                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Discount</label>
                                        <div class="input-group">
                                            <input type="text" id="global-discount" name="discount"
                                                class="form-control" placeholder="0.00">
                                            <span class="input-group-text" id="discount-icon">Rs</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-2">
                                    <div class="form-group">
                                        <label>Final Total</label>
                                        <p id="final-total-amount" class="form-control">0.00</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Amount Given</label>
                                        <input type="text" id="amount-given" class="form-control"
                                            placeholder="0.00" oninput="formatAmount(this)">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5 collapse" id="productListArea">
                        <div class="card bg-white p-3" style="height: calc(100vh - 180px);">
                            <!-- Buttons for Category and Brand -->
                            <div class="row mb-3">
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

                            <div class="row g-3 overflow-auto" id="posProduct" style="height: calc(100vh - 300px);">

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
                        discountInput.value = ''; // Reset the discount value
                    });
                    percentageDiscountBtn.addEventListener('click', function() {
                        percentageDiscountBtn.classList.add('active');
                        fixedDiscountBtn.classList.remove('active');
                        discountIcon.textContent = '%';
                        discountTypeInput.value = 'percentage';
                        discountInput.value = ''; // Reset the discount value
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
        <div class="bottom-fixed mt-3">
            <div class="row">
                <!-- Right Side: Total Payable -->
                <div class="col-md-5 d-flex align-items-center justify-content-end gap-4">
                    <h4 class="mb-0">Total Payable:</h4>
                    <span id="total" class="text-success fs-4 fw-bold ms-2">Rs 0.00</span>
                    <span id="items-count" class="text-secondary fs-5 ms-2">(0)</span>
                    <button class="btn btn-danger ms-2" id="cancelButton"><i class="fas fa-times"></i>
                        Cancel</button>
                </div>

                <!-- Left Side: Actions (Aligned to Right) -->
                <div class="col-md-7 text-end">
                    <div class="d-flex justify-content-end gap-2 flex-wrap">

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
    <div class="modal fade" id="recentTransactionsModal" tabindex="-1" aria-labelledby="recentTransactionsLabel"
        aria-hidden="true">
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

    <div class="modal fade" id="productModal" tabindex="-1" aria-labelledby="productModalLabel"
        aria-hidden="true">
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
    <div class="modal fade" id="suspendModal" tabindex="-1" aria-labelledby="suspendModalLabel"
        aria-hidden="true">
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
    <!-- Bootstrap Modal for Suspended Sales -->
    <div class="modal fade" id="suspendSalesModal" tabindex="-1" aria-labelledby="suspendSalesModalLabel"
        aria-hidden="true">
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
    <div class="modal fade" id="imeiModal" tabindex="-1" aria-labelledby="imeiModalLabel" aria-hidden="true">
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
    <div class="modal fade" id="confirmDeleteModal" tabindex="-1" aria-labelledby="confirmDeleteModalLabel"
        aria-hidden="true">
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
    {{-- <script>
        // Function to format amounts with separators for display
        function formatAmountWithSeparators(amount) {
            return new Intl.NumberFormat().format(amount);
        }

        // Function to parse formatted amounts back to numbers
        function parseFormattedAmount(formattedAmount) {
            return parseFloat(formattedAmount.replace(/,/g, ''));
        }

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
            let totalPayable = fetchTotalAmount();
            let totalPaying = 0;
            let changeReturn = 0;
            let balance = 0;

            // Apply discount
            const discountElement = document.getElementById('discount');
            const discountTypeElement = document.getElementById('discount-type');
            const discount = discountElement ? parseFloat(discountElement.value) || 0 : 0;
            const discountType = discountTypeElement ? discountTypeElement.value : 'fixed';

            if (discountType === 'percentage') {
                totalPayable = totalPayable - (totalPayable * discount / 100);
            } else {
                totalPayable = totalPayable - discount;
            }

            document.querySelectorAll('.payment-amount').forEach(input => {
                totalPaying += parseFloat(input.value) || 0;
            });

            if (totalPaying > totalPayable) {
                changeReturn = totalPaying - totalPayable;
            } else {
                balance = totalPayable - totalPaying;
            }

            document.getElementById('modal-total-items').textContent = totalItems.toFixed(2);
            document.getElementById('modal-total-payable').textContent = formatAmountWithSeparators(totalPayable.toFixed(
                2));
            document.getElementById('modal-total-paying').textContent = formatAmountWithSeparators(totalPaying.toFixed(2));
            document.getElementById('modal-change-return').textContent = formatAmountWithSeparators(changeReturn.toFixed(
                2));
            document.getElementById('modal-balance').textContent = formatAmountWithSeparators(balance.toFixed(2));
        }

        function fetchTotalAmount() {
            let totalAmount = 0;
            document.querySelectorAll('#billing-body tr').forEach(row => {
                const subtotal = parseFloat(row.querySelector('.subtotal').textContent.replace(/,/g, ''));
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
    </script> --}}

    <script>
        // Format amounts with commas
        function formatAmountWithSeparators(amount) {
            return new Intl.NumberFormat().format(amount);
        }
        // Parse formatted numbers back to float
        function parseFormattedAmount(formattedAmount) {
            return parseFloat(formattedAmount.replace(/,/g, ''));
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
            const discount = parseFloat((document.getElementById('global-discount') && document.getElementById('global-discount')
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
            const discount = parseFloat((document.getElementById('global-discount') && document.getElementById('global-discount')
                .value) || 0);
            const discountType = (document.getElementById('discount-type') && document.getElementById('discount-type')
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
    <div class="modal fade" id="jobTicketModal" tabindex="-1" aria-labelledby="jobTicketModalLabel"
        aria-hidden="true">
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
            function handleInvoiceSubmission() {
                const invoiceNo = document.getElementById('invoiceNo').value.trim().toLowerCase();
                if (invoiceNo) {
                    // Fetch sales data from the API
                    $.ajax({
                        url: '/sales', // Update this URL if necessary
                        method: 'GET',
                        success: function(data) {
                            // Check if the entered invoice number matches any sales data
                            const sale = data.sales.find(sale => sale.invoice_no.toLowerCase() ===
                                invoiceNo);
                            if (sale) {
                                // Redirect to the sale return page with the invoice number as a query parameter
                                window.location.href = `/sale-return/add?invoiceNo=${invoiceNo}`;
                            } else {
                                // Show toastr message indicating sale not found
                                toastr.error(
                                    'Sale not found. Please enter a valid invoice number.');
                            }
                        },
                        error: function(error) {
                            console.error('Error fetching sales data:', error);
                            toastr.error('An error occurred while fetching sales data.');
                        }
                    });
                } else {
                    alert('Please enter an invoice number');
                }
            }
            // Capture the Submit button click
            document.querySelector('.dropdown-menu .btn-primary').addEventListener('click',
                handleInvoiceSubmission);
            // Capture the Enter key press in the input field
            document.getElementById('invoiceNo').addEventListener('keydown', function(event) {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    handleInvoiceSubmission();
                }
            });
        });
    </script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

    <!-- Include Bootstrap JS -->
    @include('sell.pos_ajax')
    @include('contact.customer.customer_ajax')
    @include('contact.customer.add_customer_modal')

</body>

</html>
