<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>Preskool - Students</title>
    <link rel="shortcut icon" href="assets/img/favicon.png">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,400;0,500;0,700;0,900;1,400;1,500;1,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/plugins/feather/feather.css">
    <link rel="stylesheet" href="assets/plugins/icons/flags/flags.css">
    <link rel="stylesheet" href="assets/css/bootstrap-datetimepicker.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/fontawesome.min.css">
    <link rel="stylesheet" href="assets/plugins/fontawesome/css/all.min.css">
    <link rel="stylesheet" href="assets/plugins/select2/css/select2.min.css">
    <link rel="stylesheet" href="assets/plugins/datatables/datatables.min.css">
    <link rel="stylesheet" href="assets/plugins//toastr/toatr.css">
    <link rel="stylesheet" href="assets/plugins/summernote/summernote-bs4.min.css">
    <link rel="stylesheet" href="assets/css/style.css">

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


</head>

<body>

    {{-- for jquery --}}
    <script src="assets/js/jquery-3.6.0.min.js"></script>


    {{-- for sound --}}
    <audio class="successSound" src="assets/sounds/success.mp3"></audio>
    <audio class="errorSound" src="assets/sounds/error.mp3"></audio>
    <audio class="warningSound" src="assets/sounds/warning.mp3"></audio>

    <div class="main-wrapper">

        <div class="page-wrapper">
            @yield('content')
            @include('includes.header.header')
            @include('includes.sidebar.sidebar')
            @include('includes.footer.footer')
        </div>
    </div>

    <script src="assets/plugins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/feather.min.js"></script>
    <script src="assets/plugins/slimscroll/jquery.slimscroll.min.js"></script>
    <script src="assets/plugins/summernote/summernote-bs4.min.js"></script>
    <script src="assets/plugins/select2/js/select2.min.js"></script>
    <script src="assets/plugins/moment/moment.min.js"></script>
    <script src="assets/js/bootstrap-datetimepicker.min.js"></script>
    <script src="assets/plugins/apexchart/apexcharts.min.js"></script>
    <script src="assets/plugins/apexchart/chart-data.js"></script>
    <script src="assets/plugins/datatables/jquery.dataTables.min.js"></script>
    <script src="assets/plugins/datatables/datatables.min.js"></script>
    <script src="assets/plugins/toastr/toastr.min.js"></script>
    <script src="assets/plugins/toastr/toastr.js"></script>
    <script src="assets/js/jquery-ui.min.js"></script>
    <script src="assets/plugins/bootstrap-tagsinput/js/bootstrap-tagsinput.js"></script>
    <script src="assets/js/script.js"></script>

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


    {{-- This is Toaster --}}

    <script>
        $(document).ready(function() {
            var successSound = document.querySelector('.successSound');
            var errorSound = document.querySelector('.errorSound');

            @if(Session::has('toastr-success'))
            toastr.success("{{ Session::get('toastr-success') }}");
            successSound.play();
            @endif

            @if(Session::has('toastr-error'))
            toastr.error("{{ Session::get('toastr-error') }}");
            errorSound.play();
            @endif

            @if(Session::has('toastr-warning'))
            toastr.warning("{{ Session::get('toastr-warning') }}");
            @endif

            @if(Session::has('toastr-info'))
            toastr.info("{{ Session::get('toastr-info') }}");
            @endif
        });

    </script>

</body>
</html>
