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
