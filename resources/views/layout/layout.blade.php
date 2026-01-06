<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    {{-- <title>ARB - Distribution </title> --}}
    {{-- <title>ARB - Fashion @yield('title') </title>
    <link rel="shortcut icon" href="{{ URL::to('assets/img/ARB Logo.png') }}"> --}}
    <!-- Dynamic App Name in Title -->
    <meta charset="UTF-8">
<title>{{ $activeSetting?->app_name ?? 'My App' }} - @yield('title')</title>

    <!-- Dynamic Favicon -->
    <link rel="icon" href="{{ $activeSetting?->favicon_url }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ $activeSetting?->favicon_url }}" type="image/x-icon">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="autocomplete" content="off">
    {{-- Google Fonts - Keep CDN or download font files separately --}}
    <link
        href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/feather/feather.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/icons/flags/flags.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
    {{-- <link rel="stylesheet" href="{{ asset('assets/plugins/select2/css/select2.min.css') }}"> --}}
    <link href="{{ asset('vendor/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('assets/plugins/datatables/datatables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/datatables-buttons/css/buttons.dataTables.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toatr.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-bs4.min.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <link href="{{ asset('vendor/tom-select/dist/css/tom-select.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('vendor/sweetalert/css/sweetalert.min.css') }}">




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
    </style>


    {{-- datatable custom style color code start  --}}

    <style>
        /* PDF button style */
        .dt-button.buttons-pdf.buttons-html5 {
            background-color: #1372ee !important;
            border-color: #1372ee !important;
            color: #fff !important;
            margin: 20px 10px;
        }

        /* Excel button style */
        .dt-button.buttons-excel.buttons-html5 {
            background-color: #26C76F !important;
            border-color: #26C76F !important;
            color: #fff !important;
            margin: 20px 10px;
        }

        /* Print button style */
        .dt-button.buttons-print {
            background-color: #131111 !important;
            border-color: #000 !important;
            color: #fff !important;
            margin: 20px 10px;
        }

        /* Flex row for search filter and dt-controls */
        .dataTables_wrapper .dt-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            /* optional: wrap on small screens */
        }

        /* Optional: spacing adjustments */
        .dataTables_wrapper .dt-buttons {
            margin-bottom: 10px;
        }

        .dataTables_wrapper .dt-controls {
            margin-left: auto;
            /* or use padding/margin if needed for alignment */
        }
    </style>

    {{-- data table customize style end --}}


    {{-- visibility column style end --}}
    <style>
        .selected-column {
            background-color: rgb(205, 222, 231) !important;
            font-weight: 600;
        }
    </style>

    @stack('styles')
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
    <script src="{{ asset('vendor/jquery-ui/js/jquery-ui.js') }}"></script>
    <script src="{{ asset('vendor/tom-select/dist/js/tom-select.complete.min.js') }}"></script>
    <script src="{{ asset('vendor/inputmask/inputmask.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('vendor/moment/moment.min.js') }}"></script>
    <script type="text/javascript" src="{{ asset('vendor/daterangepicker/daterangepicker.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
    {{-- <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script> --}}
    <script src="{{ asset('vendor/select2/dist/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
    <script src="{{ asset('vendor/chartjs/chart.min.js') }}"></script>
    <script src="{{ asset('vendor/date-fns/date-fns.min.js') }}"></script>
    <script src="{{ asset('vendor/chartjs-adapter-date-fns/chartjs-adapter-date-fns.bundle.min.js') }}">
    </script>


    <!-- DataTables -->
    <script src="{{ asset('assets/plugins/datatables/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/datatables/datatables.min.js') }}"></script>

    <!-- Buttons extension for DataTables -->
    <script src="{{ asset('vendor/datatables-buttons/js/dataTables.buttons.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.bootstrap5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.html5.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.print.min.js') }}"></script>
    <script src="{{ asset('vendor/datatables-buttons/js/buttons.colVis.min.js') }}"></script>

    <!-- JSZip and PDFMake for Excel/PDF export -->
    <script src="{{ asset('vendor/jszip/jszip.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/pdfmake.min.js') }}"></script>
    <script src="{{ asset('vendor/pdfmake/vfs_fonts.js') }}"></script>


    <script src="{{ asset('assets/plugins/toastr/toastr.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/toastr/toastr.js') }}"></script>
    <script src="{{ asset('assets/js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.js') }}"></script>
    <script src="{{ asset('assets/js/script.js') }}"></script>

    <!-- jQuery Validation Plugin -->
    <script src="{{ asset('vendor/jquery-validate/jquery.validate.min.js') }}"></script>
    <script src="{{ asset('vendor/sweetalert/js/sweetalert.min.js') }}"></script>

    <script>
        // Load CSRF cookie once
        document.addEventListener('DOMContentLoaded', async () => {
            await fetch('/sanctum/csrf-cookie', {
                method: 'GET',
                credentials: 'include'
            });
        });
    </script>
    <script>
        $(function() {
            $('.datetime').datetimepicker({
                format: 'hh:mm:ss a'
            });
        });
    </script>

    <script>
        $(document).ready(function() {
            $('.selectBox').select2();

            $('.selectBox').on('select2:open', function() {
                // Use setTimeout to wait for DOM update
                setTimeout(() => {
                    // Get all open Select2 dropdowns
                    const allDropdowns = document.querySelectorAll('.select2-container--open');

                    // Get the most recently opened dropdown (last one)
                    const lastOpenedDropdown = allDropdowns[allDropdowns.length - 1];

                    if (lastOpenedDropdown) {
                        // Find the search input inside this dropdown
                        const searchInput = lastOpenedDropdown.querySelector(
                            '.select2-search__field');

                        if (searchInput) {
                            searchInput.focus(); // Focus the search input
                            searchInput.select(); // Optional: select any existing text
                        }
                    }
                }, 10); // Very short delay to allow DOM render
            });
        });
    </script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            document.querySelectorAll("input").forEach(function(input) {
                input.setAttribute("autocomplete", "off");
            });
        });

        // Disable all console.log in production
        if (typeof window !== 'undefined' && window.location.hostname !== 'localhost') {
            console.log = function() {};
        }

        // Sales Rep Assignment Status Management
        document.addEventListener("DOMContentLoaded", function() {
            // Check if refreshSalesRepAssignments function exists (from sales-rep-modal component)
            if (typeof window.refreshSalesRepAssignments === 'function') {

                // Refresh assignments on page load if it's a new day
                const lastRefreshDate = localStorage.getItem('salesRepLastRefreshDate');
                const today = new Date().toDateString();

                if (lastRefreshDate !== today) {
                    // It's a new day, refresh assignments to update statuses
                    window.refreshSalesRepAssignments();
                    localStorage.setItem('salesRepLastRefreshDate', today);
                }

                // Set up periodic refresh every 30 minutes to catch any status changes
                setInterval(function() {
                    if (typeof window.refreshSalesRepAssignments === 'function') {
                        window.refreshSalesRepAssignments();
                    }
                }, 30 * 60 * 1000); // 30 minutes
            }
        });
    </script>
    <script>
        // Global utility function to format currency
        function formatCurrency(amount) {
            return 'Rs. ' + parseFloat(amount).toLocaleString('en-IN', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
        }

        // Function to apply currency formatting to input fields
        function applyCurrencyFormatting() {
            $('input[data-currency]').on('input', function() {
                let value = this.value.replace(/,/g, '');
                if (!isNaN(value) && value !== '') {
                    this.value = parseFloat(value).toLocaleString('en-IN', {
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                }
            });
        }

        // Apply the currency formatting when the document is ready
        $(document).ready(function() {
            applyCurrencyFormatting();
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

    <script>
        $(document).on('submit', 'form', function(e) {
            // Skip global handler for specific forms that have their own custom handlers
            if ($(this).hasClass('skip-global-handler') || $(this).attr('id') === 'importProductForm' || $(this).attr('data-skip-global') === 'true') {
                return; // Let the form handle its own submission
            }

            e.preventDefault(); // prevent default submit

            let $form = $(this);
            let $btn = $form.find('button[type="submit"]');

            let originalText = $btn.text();
            $btn.data('original-text', originalText);


            $btn.prop('disabled', true).text('Please wait...');

            $.ajax({
                type: $form.attr('method'),
                url: $form.attr('action'),
                data: $form.serialize(),
                success: function(response) {
                    $btn.prop('disabled', false).text($btn.data('original-text'));

                },
                error: function(xhr) {

                    $btn.prop('disabled', false).text($btn.data('original-text'));

                    if (xhr.status === 422) {
                        let errors = xhr.responseJSON.errors;
                        $.each(errors, function(key, value) {
                            let input = $form.find('[name="' + key + '"]');
                            input.after('<small class="text-danger">' + value[0] + '</small>');
                        });
                    } else {
                        console.log('Something went wrong');
                    }
                }
            });
        });
    </script>

    @stack('scripts')
</body>

</html>
