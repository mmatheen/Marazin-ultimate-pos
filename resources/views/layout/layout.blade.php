<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>ARB - Distribution </title>
    <link rel="shortcut icon" href={{ asset('assets/img/favicon.png') }}>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="autocomplete" content="off">
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
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.14.1/themes/base/jquery-ui.css">

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





        /* Sticky Search and Pagination */
        .dataTables_wrapper {
            position: relative;
        }

        .dataTables_filter {
            position: absolute;
            top: 0;
            right: 0;
            background: white;
            padding: 10px;
            z-index: 10;
        }

        .dataTables_paginate {
            position: absolute;
            bottom: 0;
            right: 0;
            background: white;
            padding: 10px;
            z-index: 10;
        }

        /* Make table wrapper scrollable */
        .table-responsive {
            overflow-x: auto;
            position: relative;
            max-width: 100%;
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
    <script src="https://code.jquery.com/ui/1.14.1/jquery-ui.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/inputmask/5.0.7/inputmask.min.js"></script>

    <script src="{{ asset('assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
    <script src="{{ asset('assets/js/feather.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/summernote/summernote-bs4.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/select2/js/select2.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/moment/moment.min.js') }}"></script>
    <script src="{{ asset('assets/js/bootstrap-datetimepicker.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/apexchart/apexcharts.min.js') }}"></script>
    <script src="{{ asset('assets/plugins/apexchart/chart-data.js') }}"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.6.0/chart.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/date-fns/2.21.3/date-fns.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@1.0.0/dist/chartjs-adapter-date-fns.bundle.min.js"></script>
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

    <script>
            document.addEventListener("DOMContentLoaded", function () {
                document.querySelectorAll("input").forEach(function (input) {
                    input.setAttribute("autocomplete", "off");
                });
            });
        </script>
{{-- <script>
    // Global utility function to format currency
    function formatCurrency(amount) {
        return 'Rs. ' + parseFloat(amount).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    // Function to apply currency formatting to input fields
    function applyCurrencyFormatting() {
        $('input[data-currency]').on('input', function() {
            let value = this.value.replace(/,/g, '');
            if (!isNaN(value) && value !== '') {
                this.value = parseFloat(value).toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2});
            }
        });
    } --}}

{{--     

    // Apply the currency formatting when the document is ready
    $(document).ready(function() {
        applyCurrencyFormatting();
    });
</script> --}}
    {{-- Toaster Notifications --}}

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Inputmask({
                alias: "numeric",
                groupSeparator: ",",
                radixPoint: ".",
                autoGroup: true,
                digits: 2,
                rightAlign: false
            }).mask(".amount");
        });
        </script>
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
