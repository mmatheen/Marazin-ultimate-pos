@extends('layout.layout')

@section('content')

<style>
    .image-preview {
        min-height: 150px;
        display: flex;
        align-items: center;
        justify-content: center;
        border: 2px dashed #ddd;
        border-radius: 8px;
        background: #f9f9f9;
        overflow: hidden;
    }

    .image-preview img {
        width: 120px;
        height: 120px;
        object-fit: contain; /* keeps aspect ratio */
        border-radius: 6px;
    }
</style>

<div class="content container-fluid">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col-sm-12">
                <div class="page-sub-header">
                    <h3 class="page-title">Business Settings</h3>
                    <ul class="breadcrumb">
                        <li class="breadcrumb-item">Admin</li>
                        <li class="breadcrumb-item active">Settings</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-lg rounded-4 border-0">
                <div class="card-body p-4">
                    <form id="settingsForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">

                            <!-- App Name -->
                            <div class="col-md-12">
                                <label for="app_name" class="form-label fw-bold">App Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg rounded-3 shadow-sm"
                                    name="app_name" id="app_name"
                                    value="{{ old('app_name', $setting->app_name) }}" required>
                            </div>

                            <!-- Logo Upload -->
                            <div class="col-md-6">
                                <label for="logo" class="form-label fw-bold">Logo</label>
                                <div class="upload-box border rounded-3 p-3 text-center shadow-sm">
                                    <input type="file" class="form-control d-none" name="logo" id="logo" accept="image/*">
                                    <div id="logoPreview" class="image-preview">
                                        @if ($setting->logo)
                                          <img src="{{ Storage::url('settings/' . $setting->logo) }}" alt="Logo">

                                        @else
                                            <p class="text-muted">No logo uploaded</p>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('logo').click();">Select Logo</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm d-none" id="removeLogo">Remove</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Favicon Upload -->
                            <div class="col-md-6">
                                <label for="favicon" class="form-label fw-bold">Favicon</label>
                                <div class="upload-box border rounded-3 p-3 text-center shadow-sm">
                                    <input type="file" class="form-control d-none" name="favicon" id="favicon" accept="image/*">
                                    <div id="faviconPreview" class="image-preview">
                                        @if ($setting->favicon)
                                            <img src="{{ Storage::url('settings/' . $setting->favicon) }}" class="img-fluid rounded mb-2" width="40">
                                        @else
                                            <p class="text-muted">No favicon uploaded</p>
                                        @endif
                                    </div>
                                    <div class="mt-2">
                                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="document.getElementById('favicon').click();">Select Favicon</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm d-none" id="removeFavicon">Remove</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Price Validation Toggle -->
                            <div class="col-md-12">
                                <div class="card border-primary shadow-sm">
                                    <div class="card-header bg-primary text-white">
                                        <h5 class="mb-0"><i class="fas fa-shield-alt me-2"></i>POS Price & Discount Controls</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Enable Price Validation</h6>
                                                <p class="text-muted small mb-0">
                                                    <strong>ON (Strict):</strong> Only users with permissions can edit prices/discounts in POS<br>
                                                    <strong>OFF (Flexible):</strong> All users can freely edit prices/discounts in POS
                                                </p>
                                            </div>
                                            <div class="form-check form-switch" style="transform: scale(1.5);">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    name="enable_price_validation" id="enable_price_validation"
                                                    {{ old('enable_price_validation', $setting->enable_price_validation ?? 1) ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mt-3 mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <small>
                                                When <strong>enabled</strong>, go to <a href="{{ route('group-role-and-permission-view') }}" class="alert-link">Role & Permissions</a>
                                                to assign "edit unit price in pos" and "edit discount in pos" permissions.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Free Quantity Feature Toggle (Master Super Admin Only) --}}
                            @if(auth()->user()->hasRole('Master Super Admin'))
                            <div class="col-md-12">
                                <div class="card border-success shadow-sm">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Free Quantity Feature</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h6 class="mb-1">Enable Free Quantity (Bonus Items)</h6>
                                                <p class="text-muted small mb-0">
                                                    <strong>ON:</strong> Free Qty column visible in POS for users with "use free quantity" permission<br>
                                                    <strong>OFF:</strong> Free Qty column completely hidden for all users
                                                </p>
                                            </div>
                                            <div class="form-check form-switch" style="transform: scale(1.5);">
                                                <input class="form-check-input" type="checkbox" role="switch"
                                                    name="enable_free_qty" id="enable_free_qty"
                                                    {{ old('enable_free_qty', $setting->enable_free_qty ?? 1) ? 'checked' : '' }}>
                                            </div>
                                        </div>
                                        <div class="alert alert-info mt-3 mb-0">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <small>
                                                When <strong>enabled</strong>, go to <a href="{{ route('group-role-and-permission-view') }}" class="alert-link">Role & Permissions</a>
                                                to assign <strong>"use free quantity"</strong> to roles that should allow bonus item entry.
                                                Free Qty is also tracked in Purchase, Purchase Return, and Sale Return.
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif

                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-lg btn-primary shadow-sm px-4">💾 Update Settings</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Image Preview Script --}}
<script>
function previewImage(input, previewId, removeBtnId) {
    const file = input.files[0];
    const preview = document.getElementById(previewId);
    const removeBtn = document.getElementById(removeBtnId);

    if (file) {
        const reader = new FileReader();
        reader.onload = function (e) {
            preview.innerHTML = `<img src="${e.target.result}" alt="Preview">`;

            removeBtn.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    }
}

document.getElementById('logo').addEventListener('change', function() {
    previewImage(this, 'logoPreview', 'removeLogo');
});
document.getElementById('favicon').addEventListener('change', function() {
    previewImage(this, 'faviconPreview', 'removeFavicon');
});

document.getElementById('removeLogo').addEventListener('click', function() {
    document.getElementById('logo').value = '';
    document.getElementById('logoPreview').innerHTML = '<p class="text-muted">No logo uploaded</p>';
    this.classList.add('d-none');
});

document.getElementById('removeFavicon').addEventListener('click', function() {
    document.getElementById('favicon').value = '';
    document.getElementById('faviconPreview').innerHTML = '<p class="text-muted">No favicon uploaded</p>';
    this.classList.add('d-none');
});


// Ajax form submission
$(document).ready(function () {
    $('#settingsForm').on('submit', function(e) {
        e.preventDefault();

        $.ajax({
            url: '/site-settings/update',
            type: 'POST',
            data: new FormData(this),
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.status) {
                    toastr.success(response.message);
                    setTimeout(() => window.location.reload(), 1200);
                } else {
                    toastr.error(response.message || 'Failed.');
                }
            },
            error: function(xhr) {
                let message = 'Please fix the errors.';
                if (xhr.responseJSON?.errors) {
                    message = Object.values(xhr.responseJSON.errors).flat().join('\n');
                }
                toastr.error(message);
            }
        });
    });
});
</script>
@endsection
