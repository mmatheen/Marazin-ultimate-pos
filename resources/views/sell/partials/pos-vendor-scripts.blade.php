{{-- ============================================================
     POS vendor scripts — uses shared partials with layout (single source).
     Order: jQuery → shared stack (+ moment) → validate/sweetalert → Cleave/Inputmask CDN
     ============================================================ --}}
<script src="{{ asset('assets/js/jquery-3.6.0.min.js') }}"></script>
@include('layout.partials.vendor-scripts-shared-stack', ['loadMoment' => true])
@include('layout.partials.vendor-scripts-admin-suffix')
@include('layout.partials.vendor-scripts-pos-extras')
