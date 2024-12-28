<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Document</title>
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


    <style>
        @media (max-width: 575.98px) {
            .mt-xs-2px {
                margin-top: 14px !important;
            }
        }

        @media (min-width: 576px) {
            .mt-xs-2px {
                margin-top: 14px !important;
            }
        }

        .is-invalidRed {
            border-color: #e61414 !important;
        }

        .is-validGreen {
            border-color: rgb(3, 105, 54) !important;
        }



        /* this style for POS page  Start*/

        .scrollable-content {
            max-height: 470px;
            /* Set a max height */
            overflow-y: auto;
            /* Enable vertical scrolling */
        }

        .total-payable-section {
            background-color: #f8f9fa;
            /* Light gray background */
            border-radius: 10px;
            /* Rounded corners */
            padding: 20px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            /* Soft shadow */
            text-align: center;
            /* Center the text */
        }

        .total-payable-section h1 {
            font-size: 28px;
            /* Larger font for the title */
            font-weight: 700;
            /* Bold title */
            color: #333;
            /* Darker text color */
            margin-bottom: 10px;
        }

        .total-payable-section #total {
            font-size: px;

            font-weight: 800;
            /* Extra bold for emphasis */
            color: #d9534f;
            /* Red color to attract attention */
            margin: 0;
            /* Remove extra margins */
        }

        .total-payable-section button {
            margin-top: 20px;
            /* Add space above the buttons */
        }

        .quantity-container {
            display: flex;
            align-items: center;
        }

        .quantity-container {
            display: flex;
            align-items: center;
        }

        .quantity-container input {
            width: 60px;
            text-align: center;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin: 0 5px;
            height: 35px;
        }

        .quantity-container button {
            background-color: #007bff;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }

        .quantity-container button:hover {
            background-color: #0056b3 !important;
        }

        .quantity-minus {
            background-color: red !important;
        }

        .quantity-plus {
            background-color: green !important;
        }



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

        /* General styling for buttons */
        .btn-sm {
            padding: 8px 12px !important;
            font-size: 14px !important;
            border-radius: 5px !important;
        }

        /* Add spacing between buttons */
        .row .col-auto {
            margin-right: 20px !important;
        }

        /* Styling for the "Add Expense" button */
        .btn-primary {
            background-color: #4f46e5 !important;
            border: none !important;
            color: white !important;
        }

        .btn-primary:hover {
            background-color: #3c3aad !important;
        }

        /* Adjustments for the location and date section */
        .d-flex.align-items-center p {
            font-size: 16px !important;
            font-weight: 600 !important;
        }

        .d-flex.align-items-center h6 {
            font-size: 16px !important;
            color: #333 !important;
        }

        /* General styling for the bottom-fixed section */
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

         /* Product Card Styles */
    .product-card {
      border: 1px solid #ddd;
      border-radius: 8px;
      overflow: hidden;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease-in-out;
      text-align: center;
      background-color: #fff;
    }
    .product-card:hover {
      transform: translateY(-10px); /* Hover effect */
    }
    .product-card img {
      width: 100%;
      height: 80px; /* Smaller image height */
      object-fit: cover;
    }
    .product-card-body {
      padding: 8px; /* Smaller padding */
      font-family: Arial, sans-serif;
    }
    .product-card-body h6 {
      font-size: 12px; /* Smaller font size */
      font-weight: bold;
      margin-bottom: 5px; /* Smaller margin */
      color: #333;
    }
    .product-card-body p {
      font-size: 10px; /* Smaller font size */
      margin-bottom: 5px; /* Smaller margin */
      color: #777;
    }
    .btn-add-to-cart {
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 6px 12px; /* Smaller padding */
      border-radius: 5px;
      font-size: 12px; /* Smaller font size */
      cursor: pointer;
      transition: background-color 0.3s ease;
    }
    .btn-add-to-cart:hover {
      background-color: #0056b3;
    }

    /* Offcanvas Styles */
    .offcanvas {
      background-color: #f8f9fa; /* Light background color */
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }
    .offcanvas-header {
      background-color: #007bff; /* Header background color */
      color: #fff; /* Header text color */
    }
    .offcanvas-body {
      padding: 15px; /* Adjust padding */
    }
    .offcanvas-body button {
      background-color: #007bff; /* Button background color */
      color: #fff; /* Button text color */
      border: none;
      padding: 8px 16px; /* Adjust padding */
      margin-bottom: 10px; /* Adjust margin */
      width: 100%;
      text-align: left;
      border-radius: 4px;
      transition: background-color 0.3s ease;
    }
    .offcanvas-body button:hover {
      background-color: #0056b3; /* Hover effect */
    }

    /* Category Styles */
    .category-container {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      justify-content: center;
    }
    .category-card {
      width: calc(33.33% - 10px);
      border: 1px solid #ddd;
      border-radius: 8px;
      text-align: center;
      padding: 10px;
      background-color: #fff;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease-in-out;
    }
    .category-card:hover {
      transform: translateY(-10px);
    }
    .category-card h6 {
      font-size: 14px;
      margin: 10px 0;
      color: #333;
    }
    .category-card button {
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 12px;
      cursor: pointer;
      margin-top: 5px;
    }
    .category-card button:hover {
      background-color: #0056b3;
    }
    .category-footer {
      display: flex;
      justify-content: space-between;
      margin-top: 15px;
    }
    .category-footer button {
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 12px;
      cursor: pointer;
    }
    .category-footer button:hover {
      background-color: #0056b3;
    }

    /* Brand Styles */
    .brand-card {
      width: calc(33.33% - 10px);
      border: 1px solid #ddd;
      border-radius: 8px;
      text-align: center;
      padding: 5px;
      background-color: #fff;
      box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s ease-in-out;
    }
    .brand-card:hover {
      transform: translateY(-10px);
    }
    .brand-card h6 {
      font-size: 14px;
      margin: 10px 0;
      color: #333;
    }
    .brand-card button {
      background-color: #007bff;
      color: #fff;
      border: none;
      padding: 5px 10px;
      border-radius: 5px;
      font-size: 12px;
      cursor: pointer;
      margin-top: 5px;
    }
    .brand-card button:hover {
      background-color: #0056b3;
    }

        .scrollable-content {
            max-height: 400px;
            overflow-y: auto;
        }
        .product-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            transition: transform 0.2s;
        }
        .product-card:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .billing-summary {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .form-control[readonly] {
            background-color: #fff;
        }
   
    </style>
</head>

<body>

    <div class="container-fluid p-3">


        <div class="row mt-2">
            <div class="col-md-12">
                <div class="card flex-fill bg-white" style="padding: 10px; min-height: auto;">
                    <div class="card-body" style="padding: 10px;">
                        <div class="row">
                            <!-- Location and Date Section -->
                            <div class="col-md-5">
                                <div class="row d-flex justify-content-end align-items-center">
                                    <div class="col-auto">
                                        <div class="form-group local-forms days">
                                            {{-- <div class="d-flex align-items-center">
                                                <p class="me-2 mb-0" style="font-size: 14px; font-weight: bold;">
                                                    <strong>Location:</strong>
                                                </p>
                                                @if ($location)
                                                    <h6 class="mb-0" style="font-size: 14px;">{{ $location->name }}
                                                    </h6>
                                                @endif
                                            </div> --}}

                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="me-2">Location: <strong>@if ($location)
                                                    {{ $location->name }}
                                                @endif</strong></h5>
                                                <div>
                                                  <input type="datetime-local" class="form-control d-inline-block w-auto" />
                                                </div>
                                                {{-- <button class="btn btn-success">Add Expense</button> --}}
                                              </div>
                                        </div>
                                    </div>
                                    {{-- <div class="col mb-4">
                                        <button class="btn btn-primary d-flex align-items-center"
                                            style="padding: 5px 10px;">
                                            11/20/2024 01:42 <i class="fas fa-calendar-alt ms-2"></i>
                                        </button>
                                    </div> --}}
                                </div>
                            </div>

                            <!-- Buttons Section -->
                            <div class="col-md-7">
                                <div class="row d-flex justify-content-end align-items-center">
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm" onclick="window.history.back();"><i
                                                class="fas fa-backward"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i
                                                class="fas fa-window-close"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i
                                                class="fas fa-business-time"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i class="fas fa-calculator"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i class="fas fa-redo-alt"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i class="fas fa-wallet"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i
                                                class="fas fa-pause-circle"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary btn-sm d-flex align-items-center"
                                            style="width: 140px; padding: 5px 10px;">
                                            <i class="fas fa-minus-circle me-2"></i> Add Expense
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <div class="row mt-2">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-7 scrollable-content">
                        <div class="blog grid-blog flex-fill">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group local-forms days">
                                        <label>Customer Type<span class="login-danger">*</span></label>
                                        <select class="form-control form-select select select" id="customer-id">
                                            <option selected disabled>Please Select</option>
                                            <!-- Options will be populated dynamically -->
                                        </select>
                                    </div>

                                </div>
                                <div class="col-md-8">
                                    <input type="text" class="form-control" id="productSearchInput" placeholder="Enter Product name / SKU / Scan bar code">
                                    <input type="hidden" id="payment-mode" value="cash"/>
                                    <input type="hidden" id="payment-status" value="paid"/>
                                    <input type="hidden" id="invoice-no" value="inv-00256"/>
                                </div>
                                <div class="col-md-12">
                                    <table class="table table-bordered">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Product</th>
                                                <th>Quantity</th>
                                                <th>Price inc. tax</th>
                                                <th>Subtotal</th>
                                                <th>Remove</th>
                                            </tr>
                                        </thead>
                                        <tbody id="billing-body">
                                            <!-- Dynamic rows go here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Summary (Positioned at the Bottom) -->
                        <div class="billing-summary mt-auto">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Items</label>
                                        <p id="items-count" class="form-control">0</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Discount</label>
                                        <input type="text" id="discount" class="form-control" placeholder="0.00" >
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Order Tax</label>
                                        <input type="text" id="order-tax" class="form-control" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="form-group">
                                        <label>Shipping</label>
                                        <input type="text" id="shipping" class="form-control" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-3 offset-md-9">
                                    <div class="form-group">
                                        <label>Total</label>
                                        <p id="total-amount" class="form-control">0.00</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                    <!-- Product List -->
                    <div class="col-md-5">
                        <!-- Buttons for Category and Brand -->
                        <!-- Buttons for Category and Brand -->
                        <div class="row mb-3">
                            <div class="d-flex justify-content-between w-100 mb-2">
                                <button type="button" class="btn btn-primary text-center w-100  me-3 "
                                    id="category-btn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategory"
                                    aria-controls="offcanvasCategory">Category</button>
                                <button type="button" class="btn btn-primary text-center w-100 " id="brand-btn"
                                    data-bs-toggle="offcanvas" data-bs-target="#offcanvasBrand"
                                    aria-controls="offcanvasBrand">Brand</button>


                            </div>
                        </div>

                        <div class="row mt-2 scrollable-content" id="product-container">
                            <div id="productContainer" class="row g-3">

                            </div>

                        </div>
                    </div>


                </div>
            </div>

        </div>
<!-- Offcanvas Category Menu -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasCategory" aria-labelledby="offcanvasCategoryLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasCategoryLabel">Categories</h5>
        <button type="button" class="btn btn-secondary" id="categoryBackBtn">Back</button>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <button type="button" class="btn btn-primary" id="allProductsBtn">All Products</button>
        <div id="categoryContainer" class="category-container">
            <!-- Categories will be dynamically injected here -->
        </div>
        <hr />
        <div id="subcategoryContainer" class="subcategory-container">
            <!-- Subcategories will be dynamically injected here -->
        </div>
    </div>
</div>

<!-- Offcanvas Brand Menu -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasBrand" aria-labelledby="offcanvasBrandLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="offcanvasBrandLabel">Brands</h5>
        <button type="button" class="btn btn-secondary" id="brandBackBtn">Back</button>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div id="brandContainer" class="category-container">
            <!-- Brands will be dynamically injected here -->
        </div>
    </div>
</div>

        <div class="bottom-fixed">
            <div class="row align-items-center">
                <!-- Buttons Section -->
                <div class="col-md-7">
                    <div class="d-flex gap-4">
                        <button class="btn btn-primary" id="cart">Cart</button>
                        <button class="btn btn-primary" id="multiple-pay">Multiple Pay</button>
                        <button class="btn btn-warning" id="cash">Cash</button>
                        <button class="btn btn-danger" id="cancel">Cancel</button>
                    </div>
                </div>

                <!-- Total Payable Section -->
                <div class="col-md-5 total-payable-section text-start">
                    <div class="row">
                        <div class="col">
                            <h4>Total Payable:</h4>
                        </div>
                        <div class="col">
                            <h5 id="total" class="text-danger">Rs 0.00</h5>
                        </div>
                    </div>
                </div>
            </div>
        </div>



        {{-- <!-- Product Details Modal -->
<div class="modal fade" id="productDetailsModal" tabindex="-1" aria-labelledby="productDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productDetailsModalLabel">Product Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="modalUnitPrice" class="form-label">Unit Price</label>
                    <input type="number" class="form-control" id="modalUnitPrice" step="0.01">
                </div>
                <div class="mb-3">
                    <label for="discountType" class="form-label">Discount Type</label>
                    <select class="form-select" id="modalDiscountType">
                        <option value="fixed">Fixed Amount</option>
                        <option value="percentage">Percentage</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label for="modalDiscountAmount" class="form-label">Discount Amount</label>
                    <input type="number" class="form-control" id="modalDiscountAmount" step="0.01" value="0">
                </div>
                <div class="mb-3">
                    <label for="modalDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="modalDescription" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" id="saveProductDetails">Save changes</button>
            </div>
        </div>
    </div>
</div> --}}






        @include('sell.pos_ajax')



</body>

</html>
