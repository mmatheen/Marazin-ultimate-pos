{{--
    Shared styles: admin shell + POS (no DataTables / app-ui-density unless $withAdminAssets).
    Pass $withAdminAssets = true from layout; false from POS.
--}}
@php $withAdminAssets = $withAdminAssets ?? true; @endphp
<link rel="stylesheet" href="{{ asset('assets/plugins/bootstrap/css/bootstrap.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/feather/feather.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/icons/flags/flags.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/bootstrap-datetimepicker.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/fontawesome.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/fontawesome/css/all.min.css') }}">
<link href="{{ asset('vendor/select2/dist/css/select2.min.css') }}" rel="stylesheet" />
<link rel="stylesheet" href="{{ asset('assets/plugins/datatables/datatables.min.css') }}">
<link rel="stylesheet" href="{{ asset('vendor/datatables-buttons/css/buttons.dataTables.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/toastr/toatr.css') }}">
<link rel="stylesheet" href="{{ asset('assets/plugins/summernote/summernote-bs4.min.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/style.css') }}">
@if($withAdminAssets)
    <link rel="stylesheet" href="{{ asset('assets/css/app-ui-density.css') }}">
    <link rel="stylesheet" href="{{ asset('vendor/jquery-ui/themes/base/jquery-ui.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('vendor/daterangepicker/daterangepicker.css') }}" />
    <link href="{{ asset('vendor/tom-select/dist/css/tom-select.css') }}" rel="stylesheet">
@endif
<link rel="stylesheet" href="{{ asset('vendor/sweetalert/css/sweetalert.min.css') }}">
