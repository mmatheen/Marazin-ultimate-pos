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
            <div class="card shadow-sm rounded-4 border-0">
                <div class="card-body p-4 bg-white">
                    {{-- Card 1: General — only this form is saved on "Update Settings" --}}
                    <form id="settingsForm" enctype="multipart/form-data">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-12">
                                <label for="app_name" class="form-label fw-bold">App Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-lg rounded-3 shadow-sm"
                                    name="app_name" id="app_name"
                                    value="{{ old('app_name', $setting->app_name) }}" required>
                            </div>
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
                        </div>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4">Update Settings</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Card 2: Price Validation — saves to DB on toggle change --}}
            <div class="card shadow-sm rounded-4 border-0 mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-dark">POS Price & Discount Controls</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
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
                                data-save-url="{{ route('settings.update-price-validation') }}"
                                {{ old('enable_price_validation', $setting->enable_price_validation ?? 1) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>
                            When <strong>enabled</strong>, go to <a href="{{ route('group-role-and-permission-view') }}" class="alert-link">Role & Permissions</a>
                            to assign "edit unit price in pos" and "edit discount in pos" permissions.
                        </small>
                    </div>
                </div>
            </div>

            {{-- Card 3: Free Quantity — saves to DB on toggle change (Master Super Admin only) --}}
            @if(auth()->user()->hasRole('Master Super Admin'))
            <div class="card shadow-sm rounded-4 border-0 mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-dark">Free Quantity Feature</h5>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
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
                                data-save-url="{{ route('settings.update-free-qty') }}"
                                {{ old('enable_free_qty', $setting->enable_free_qty ?? 1) ? 'checked' : '' }}>
                        </div>
                    </div>
                    <div class="alert alert-info mt-3 mb-0 small">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>
                            When <strong>enabled</strong>, go to <a href="{{ route('group-role-and-permission-view') }}" class="alert-link">Role & Permissions</a>
                            to assign <strong>"use free quantity"</strong> to roles that should allow bonus item entry.
                            Free Qty is also tracked in Purchase, Purchase Return, and Sale Return.
                        </small>
                    </div>
                </div>
            </div>
            @endif

            @can('edit sms-settings')
            <div class="card shadow-sm rounded-4 border-0 mt-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark">SMS Gateway Settings</h5>
                    <small class="text-muted">SMSLenz Sri Lanka</small>
                </div>
                <div class="card-body">
                    <form id="smsSettingsForm">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">SMS User ID</label>
                                <input type="text" class="form-control rounded-3" name="sms_user_id" value="{{ old('sms_user_id', $setting->sms_user_id) }}">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">SMS API Key</label>
                                <input type="password" class="form-control rounded-3" name="sms_api_key" placeholder="Leave blank to keep existing key">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Sender ID</label>
                                <input type="text" class="form-control rounded-3" name="sms_sender_id" value="{{ old('sms_sender_id', $setting->sms_sender_id) }}">
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-primary px-4">Update SMS Settings</button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

            @can('sms.send')
            <div class="card shadow-sm rounded-4 border-0 mt-4">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-dark">Manual SMS</h5>
                    <small class="text-muted">Single or bulk</small>
                </div>
                <div class="card-body">
                    <form id="smsSendForm">
                        @csrf
                        <div class="row g-4">
                            <div class="col-md-4">
                                <label class="form-label fw-bold">Phone Number(s)</label>
                                <textarea class="form-control rounded-3" name="phones" rows="6" placeholder="07XXXXXXXX or +947XXXXXXXX\nOne number per line or comma separated"></textarea>
                            </div>
                            <div class="col-md-8">
                                <label class="form-label fw-bold">Message</label>
                                <textarea class="form-control rounded-3" name="message" rows="6" placeholder="Type your SMS message here"></textarea>
                                <small class="text-muted d-block mt-2">Sri Lanka numbers will be normalized to +947 format automatically.</small>
                            </div>
                        </div>

                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-dark px-4">Send SMS</button>
                        </div>
                    </form>
                </div>
            </div>
            @endcan

            {{-- Card 4: Database Backup — separate button --}}
            <div class="card shadow-sm rounded-4 border-0 mt-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0 text-dark">Database Backup</h5>
                </div>
                <div class="card-body d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                    <div class="text-muted small">
                        <p class="mb-1">Create an on-demand backup of the current database.</p>
                        <p class="mb-0">The backup will be password protected and downloaded as a zip file.</p>
                    </div>
                    <button type="button" id="backupNowButton" class="btn btn-outline-secondary">
                        Backup Now (Database)
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<form id="backupForm" action="{{ route('settings.backup-now') }}" method="POST" class="d-none">
    @csrf
</form>

{{-- Image Preview Script + Settings / Backup handlers --}}
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

// Manual DB backup button submits separate form (no AJAX)
document.getElementById('backupNowButton').addEventListener('click', function () {
    document.getElementById('backupForm').submit();
});

// Card 2: save price validation on toggle change
$('#enable_price_validation').on('change', function () {
    var url = $(this).data('save-url');
    var value = $(this).prop('checked') ? 1 : 0;
    $.ajax({
        url: url,
        type: 'POST',
        data: { enable_price_validation: value, _token: $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
            if (res.status) toastr.success(res.message);
            else toastr.error(res.message || 'Failed.');
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Please fix the errors.');
        }
    });
});

// Card 3: save free qty on toggle change
$('#enable_free_qty').on('change', function () {
    var url = $(this).data('save-url');
    var value = $(this).prop('checked') ? 1 : 0;
    $.ajax({
        url: url,
        type: 'POST',
        data: { enable_free_qty: value, _token: $('meta[name="csrf-token"]').attr('content') },
        success: function (res) {
            if (res.status) toastr.success(res.message);
            else toastr.error(res.message || 'Failed.');
        },
        error: function (xhr) {
            toastr.error(xhr.responseJSON?.message || 'Please fix the errors.');
        }
    });
});

$('#smsSettingsForm').on('submit', function (e) {
    e.preventDefault();

    $.ajax({
        url: '{{ route('settings.update-sms-settings') }}',
        type: 'POST',
        data: new FormData(this),
        contentType: false,
        processData: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.status) {
                toastr.success(response.message);
            } else {
                toastr.error(response.message || 'Failed.');
            }
        },
        error: function (xhr) {
            let message = 'Please fix the errors.';
            if (xhr.responseJSON?.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            toastr.error(message);
        }
    });
});

$('#smsSendForm').on('submit', function (e) {
    e.preventDefault();

    $.ajax({
        url: '{{ route('settings.send-sms') }}',
        type: 'POST',
        data: new FormData(this),
        contentType: false,
        processData: false,
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        success: function (response) {
            if (response.status) {
                toastr.success(response.message);
                $('#smsSendForm')[0].reset();
            } else {
                toastr.error(response.message || 'Failed.');
            }
        },
        error: function (xhr) {
            let message = 'Please fix the errors.';
            if (xhr.responseJSON?.errors) {
                message = Object.values(xhr.responseJSON.errors).flat().join('\n');
            }
            toastr.error(message);
        }
    });
});
</script>
@endsection
