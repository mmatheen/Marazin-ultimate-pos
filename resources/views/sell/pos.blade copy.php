<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link rel="shortcut icon" href={{asset('assets/img/favicon.png')}}>
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>POS</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
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
        /* General Styles */
.is-invalidRed { border-color: #e61414 !important; }
.is-validGreen { border-color: rgb(3, 105, 54) !important; }

/* Responsive Margin Top */
@media (max-width: 575.98px), (min-width: 576px) {
    .mt-xs-2px { margin-top: 14px !important; }
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
    font-size: px;
    font-weight: 800;
    color: #d9534f;
    margin: 0;
}
.total-payable-section button { margin-top: 20px; }

/* Quantity Container */
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
.quantity-container button:hover { background-color: #0056b3 !important; }
.quantity-minus { background-color: red !important; }
.quantity-plus { background-color: green !important; }

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
.row .col-auto { margin-right: 20px !important; }

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
.product-card:hover { transform: translateY(-10px); }
.product-card img {
    width: 50%;
    height: 80px;
    object-fit: fill;
}
.product-card-body {
    padding: 8px;
    font-family: Arial, sans-serif;
}
.product-card-body h6 {
    font-size: 12px;
    font-weight: bold;
    margin-bottom: 5px;
    color: #333;
}
.product-card-body p {
    font-size: 10px;
    margin-bottom: 5px;
    color: #777;
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
/* .offcanvas-header {
    background-color: #007bff;
    color: #fff;
} */
.offcanvas-body {
    padding: 15px;
}
.offcanvas-body button {
    background-color: transparent; /* Remove the background color */
    color: #007bff; /* Set the text color to blue */
    border: 2px solid #007bff; /* Add a blue outline border */
    padding: 8px 16px;
    margin-bottom: 10px;
    width: 100%;
    text-align: left;
    border-radius: 4px;
    transition: background-color 0.3s ease, color 0.3s ease;
}
.offcanvas-body button:hover {
    background-color: #007bff; /* Add blue background on hover */
    color: #fff; /* Change text color to white on hover */
}

/* Category Styles */
.category-container {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    justify-content: center;
}
.category-card, .brand-card {
    width: calc(33.33% - 10px);
    border: 1px solid #ddd;
    border-radius: 8px;
    text-align: center;
    padding: 10px;
    background-color: #fff;
    box-shadow: 0px 4px 6px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease-in-out;
}
.category-card:hover, .brand-card:hover {
    transform: translateY(-10px);
}
.category-card h6, .brand-card h6 {
    font-size: 14px;
    margin: 10px 0;
    color: #333;
}
.category-footer, .brand-footer {
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

/* Brand Button Styles */
/* .brand-footer button {
    background-color: transparent;
    color: #007bff;
    border: 2px solid #007bff;
    padding: 5px 10px;
    border-radius: 5px;
    font-size: 12px;
    cursor: pointer;
    transition: background-color 0.3s ease, color 0.3s ease;
} */
/* .brand-footer button:hover {
    background-color: #007bff;
    color: #fff;
} */

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
@media (max-width: 768px) {
    #category-sidebar {
        width: 200px;
    }
    .category-card, .product-card {
        margin-bottom: 15px;
    }
    .category-footer .btn {
        padding: 3px 8px;
    }
}

/* Updated Loader Styles */
.loader-container {
  display: flex;
  justify-content: center;
  align-items: center;
  height: 100vh; /* Use viewport height to ensure full coverage */
  width: 100vw; /* Use viewport width to ensure full coverage */
  position: fixed; /* Ensure loader is on top of everything */
  top: 0;
  left: 0;
  z-index: 1;
  background: rgba(255, 255, 255, 0.8); /* Optional background overlay */
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

  20%, 25% {
    transform: scale(1.3) rotate(90deg);
  }

  45%, 50% {
    transform: scale(1) rotate(180deg);
  }

  70%, 75% {
    transform: scale(1.3) rotate(270deg);
  }

  95%, 100% {
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
    border: 1px solid rgba(0,0,0,.15);
    border-radius: 4px;
    box-shadow: 0 6px 12px rgba(0,0,0,.175);
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
                                            <div class="d-flex justify-content-between align-items-center mb-3">
                                                <h5 class="me-2">Location: <strong>@if ($location) {{ $location->name }} @endif</strong></h5>
                                                <div>
                                                    <input type="datetime-local" class="form-control d-inline-block w-auto" />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Buttons Section -->
                            <div class="col-md-7">
                                <div class="row d-flex justify-content-end align-items-center">
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm" onclick="window.history.back();"><i class="fas fa-backward"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i class="fas fa-window-close"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm"><i class="fas fa-business-time"></i></button>
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
                                        <button class="btn btn-light btn-sm"><i class="fas fa-pause-circle"></i></button>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-primary btn-sm d-flex align-items-center" style="width: 140px; padding: 5px 10px;">
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
                                        <input type="text" id="discount" class="form-control" placeholder="0.00">
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
                        <div class="row mb-3">
                            <div class="d-flex justify-content-between w-100 mb-2">
                                <button type="button" class="btn btn-primary text-center w-50 me-3" id="allProductsBtn">All Products</button>
                                <button type="button" class="btn btn-primary text-center w-100 me-3" id="category-btn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasCategory" aria-controls="offcanvasCategory">Category</button>
                                <button type="button" class="btn btn-primary text-center w-100" id="brand-btn" data-bs-toggle="offcanvas" data-bs-target="#offcanvasBrand" aria-controls="offcanvasBrand">Brand</button>
                            </div>
                        </div>

                        <div class="row mt-2 scrollable-content" id="product-container">

                            <div id="productContainer" class="row g-3">
                                <!-- Products will be dynamically injected here -->
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
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div id="categoryContainer" class="category-container">
            <!-- Categories will be dynamically injected here -->
        </div>
    </div>
</div>

<!-- Offcanvas Subcategory Menu -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasSubcategory" aria-labelledby="offcanvasSubcategoryLabel">
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
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasBrand" aria-labelledby="offcanvasBrandLabel">
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


        <div class="bottom-fixed">
            <div class="row align-items-center">
                <!-- Buttons Section -->
                <div class="col-md-7">
                    <div class="d-flex gap-4">
                        <button class="btn btn-primary" id="cart">Cart</button>
                        <button class="btn btn-primary" id="multiple-pay">Multiple Pay</button>
                        <button class="btn btn-warning" id="cashButton">Cash</button>
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






        @include('sell.pos_ajax')



</body>

</html>
