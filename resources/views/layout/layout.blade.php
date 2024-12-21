<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Preskool - Students</title>
    <link rel="shortcut icon" href='assets/img/favicon.png'>
    <meta name="csrf-token" content="{{ csrf_token() }}">
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



        /* this style for POST page  Start*/

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

        /* .category-grid {
            display: grid !important;
            grid-template-columns: repeat(auto-fit, minmax(150px, 2fr)) !important;
            gap: 8px !important;
            padding: 8px !important;
        }

        .category-card {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            padding: 14px;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            cursor: pointer;
            width: 150px !important;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .category-card h3 {
            font-size: 14px;
            margin: 0;
            color: #333;
        } */

        /* Grid container for products */
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 16px;
            padding: 16px;
        }

        /* Individual product card */
        .product-card {
            background-color: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .product-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 10px rgba(0, 0, 0, 0.15);
        }

        /* Product image */
        .product-card img {
            width: 100%;
            height: 50px;
            object-fit: cover;

        }

        /* Card content */
        .product-card .card-body {
            padding: 10px;
            text-align: center;
        }

        .product-card .card-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 6px;
            color: #333;
        }

        .product-card .card-text {
            font-size: 12px;
            color: #555;
        }

        /* List group item for stock */
        .product-card .list-group-item {
            font-size: 14px;
            background-color: #f9f9f9;
            border-top: 1px solid #ddd;
            text-align: center;
            padding: 10px;
        }

        #categoryContainer {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            justify-content: center;
        }

        .category-card {
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            width: 150px;
            text-align: center;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .category-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .category-card h3 {
            margin-bottom: 15px;
            font-size: 1.2rem;
            color: #333;
        }

        .button-container {
            display: flex;
            justify-content: space-around;
            margin-top: 10px;
        }

        /* .btn {
            padding: 6px 15px;
            font-size: 0.9rem;
            border: 2px solid transparent;
            border-radius: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        } */

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
    </style>

</head>

<body>

    {{-- For jQuery --}}
    <script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>

    {{-- For sound --}}
    <audio class="successSound" src="{{ asset('assets/sounds/success.mp3') }}"></audio>
    <audio class="errorSound" src="{{ asset('assets/sounds/error.mp3') }}"></audio>
    <audio class="warningSound" src="{{ asset('assets/sounds/warning.mp3') }}"></audio>

    <div class="main-wrapper">
        <div class="page-wrapper">
            @yield('content')
            @include('includes.header.header')
            @include('includes.sidebar.sidebar')
            @include('includes.footer.footer')
        </div>
    </div>

    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/datatables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/toastr/toastr.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

    <!-- jQuery Validation Plugin -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.5/jquery.validate.min.js"></script>

    <script>
        $(function() {
            $('.datetime').datetimepicker({
                format: 'hh:mm:ss a'
            });
        });
    </script>

    <script>
        // In your Javascript (external .js resource or <script> tag)
        $(document).ready(function() {
            $('.select2Box').select2();
        });
    </script>

    {{-- Toaster Notifications --}}
    <script>
        $(document).ready(function() {
            var successSound = document.querySelector('.successSound');
            var errorSound = document.querySelector('.errorSound');

            @if (Session::has('toastr-success'))
                toastr.success("{{ Session::get('toastr-success') }}");
                successSound.play();
            @endif

            @if (Session::has('toastr-error'))
                toastr.error("{{ Session::get('toastr-error') }}");
                errorSound.play();
            @endif

            @if (Session::has('toastr-warning'))
                toastr.warning("{{ Session::get('toastr-warning') }}");
            @endif

            @if (Session::has('toastr-info'))
                toastr.info("{{ Session::get('toastr-info') }}");
            @endif
        });
    </script>

    {{-- Prevent Inspect Element --}}
    <script>
        document.addEventListener('keydown', function(event) {
            // Prevent F12 (which opens the developer tools)
            if (event.keyCode === 123) {
                event.preventDefault();
            }

            // Prevent Ctrl+Shift+I (or Cmd+Shift+I on macOS)
            if (event.ctrlKey && event.shiftKey && event.keyCode === 73) {
                event.preventDefault();
            }

            // Prevent Ctrl+Shift+J (or Cmd+Shift+J on macOS)
            if (event.ctrlKey && event.shiftKey && event.keyCode === 74) {
                event.preventDefault();
            }

            // Prevent Ctrl+U (or Cmd+U on macOS) which opens the source code viewer
            if (event.ctrlKey && event.keyCode === 85) {
                event.preventDefault();
            }

            // Prevent Ctrl+Shift+C (or Cmd+Shift+C on macOS) which opens the inspect element tool
            if (event.ctrlKey && event.shiftKey && event.keyCode === 67) {
                event.preventDefault();
            }
        });
    </script>

</body>

</html>
