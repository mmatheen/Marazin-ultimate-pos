@extends('layout.layout')

@section('content')

<style>
    .settings-card-header {
        background: #fafbfc !important;
        border-bottom: 1px solid #eef0f3 !important;
    }

    .settings-card-header h5 {
        font-weight: 600;
        letter-spacing: -0.01em;
        color: #1e293b !important;
    }

    /* Features panel — single clean stack */
    .settings-features-shell {
        border: 1px solid #e8ecf1;
        border-radius: 1rem;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 24px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }

    .settings-features-head {
        padding: 1.35rem 1.5rem 1.15rem;
        background: linear-gradient(180deg, #fafbfd 0%, #fff 100%);
        border-bottom: 1px solid #eef1f5;
    }

    .settings-features-head h2 {
        font-size: 1.125rem;
        font-weight: 600;
        color: #0f172a;
        letter-spacing: -0.02em;
    }

    .feature-row {
        padding: 1.35rem 1.5rem;
        transition: background 0.15s ease;
    }

    .feature-row:not(:last-child) {
        border-bottom: 1px solid #f1f5f9;
    }

    .feature-row:hover {
        background: #fbfcfe;
    }

    .feature-row__icon {
        width: 2.5rem;
        height: 2.5rem;
        border-radius: 0.65rem;
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
        font-size: 1rem;
        color: #475569;
        background: #f1f5f9;
    }

    .feature-row__title {
        font-size: 0.95rem;
        font-weight: 600;
        color: #0f172a;
        margin-bottom: 0.2rem;
        letter-spacing: -0.01em;
    }

    .feature-row__meta {
        font-size: 0.8rem;
        color: #64748b;
        line-height: 1.45;
        max-width: 42rem;
    }

    .feature-row__meta strong {
        color: #475569;
        font-weight: 600;
    }

    .feature-switch .form-check-input {
        width: 2.75rem;
        height: 1.35rem;
        margin-top: 0;
        cursor: pointer;
    }

    .feature-hint {
        margin-top: 0.85rem;
        padding: 0.65rem 0.85rem;
        border-radius: 0.5rem;
        background: #f8fafc;
        border: 1px solid #eef2f7;
        font-size: 0.8125rem;
        color: #64748b;
        line-height: 1.5;
    }

    .feature-hint a {
        font-weight: 500;
    }

    .feature-hint .hint-ico {
        color: #94a3b8;
        margin-right: 0.35rem;
    }

    .settings-surface-card {
        border: 1px solid #e8ecf1 !important;
        box-shadow: 0 1px 2px rgba(15, 23, 42, 0.04), 0 4px 24px rgba(15, 23, 42, 0.06);
    }

    .settings-nav-shell {
        background: #fff;
        border: 1px solid #e8e8ef;
        border-radius: 1rem;
        box-shadow: 0 1px 3px rgba(15, 23, 42, 0.06);
    }

    @media (min-width: 992px) {
        .settings-nav-shell {
            position: sticky;
            top: 1rem;
        }
    }

    .settings-page-nav {
        gap: 0.35rem;
        z-index: 2;
    }

    .settings-nav-btn {
        display: block;
        width: 100%;
        margin: 0;
        padding: 0.65rem 0.9rem;
        border: none;
        border-radius: 0.65rem;
        background: transparent;
        color: #4b5563;
        font-weight: 600;
        font-size: 0.9rem;
        text-align: left;
        line-height: 1.3;
        transition: background 0.15s ease, color 0.15s ease;
    }

    .settings-nav-btn:hover {
        background: #f4f4f8;
        color: #1f2937;
    }

    .settings-nav-btn:focus-visible {
        outline: none;
        box-shadow: 0 0 0 2px #fff, 0 0 0 4px #818cf8;
    }

    .settings-nav-btn.active {
        background: linear-gradient(135deg, #eef2ff 0%, #e0e7ff 100%);
        color: #312e81;
        box-shadow: inset 0 0 0 1px #c7d2fe;
    }

    @media (max-width: 991.98px) {
        .settings-page-nav {
            flex-wrap: nowrap;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 0.15rem;
            gap: 0.5rem;
        }

        .settings-nav-btn {
            flex: 0 0 auto;
            width: auto;
            white-space: nowrap;
        }
    }

    .settings-panels-host {
        min-height: 12rem;
    }

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

    <div class="row g-4 align-items-start">
        <aside class="col-lg-3 col-xl-2">
            <div class="settings-nav-shell p-2 p-lg-3">
                <p class="small text-muted text-uppercase fw-bold letter-spacing mb-2 mb-lg-3 px-1 d-none d-lg-block" style="font-size: 0.7rem; letter-spacing: 0.06em;">Sections</p>
                <nav class="settings-page-nav nav flex-lg-column flex-row flex-nowrap"
                    role="tablist"
                    aria-label="Settings sections">
                    <button type="button" class="settings-nav-btn active" role="tab" aria-selected="true"
                        id="tab-general" aria-controls="settings-panel-general"
                        data-settings-target="general">General</button>
                    <button type="button" class="settings-nav-btn" role="tab" aria-selected="false"
                        id="tab-features" aria-controls="settings-panel-features"
                        data-settings-target="features">Features</button>
                    @if(auth()->user()->can('edit sms-settings') || auth()->user()->can('sms.send'))
                    <button type="button" class="settings-nav-btn" role="tab" aria-selected="false"
                        id="tab-sms" aria-controls="settings-panel-sms"
                        data-settings-target="sms">SMS</button>
                    @endif
                    @can('backup database')
                    <button type="button" class="settings-nav-btn" role="tab" aria-selected="false"
                        id="tab-backup" aria-controls="settings-panel-backup"
                        data-settings-target="backup">Backup</button>
                    @endcan
                </nav>
            </div>
        </aside>
        <div class="col-lg-9 col-xl-10 settings-panels-host">
            <section id="settings-panel-general" class="settings-panel" role="tabpanel" aria-labelledby="tab-general" aria-hidden="false" data-settings-panel="general">
            <div class="card settings-surface-card rounded-4 border-0 overflow-hidden">
                <div class="card-header settings-card-header border-0 py-3 rounded-0">
                    <h5 class="mb-0 text-dark">General</h5>
                </div>
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
            </section>

            <section id="settings-panel-features" class="settings-panel d-none" role="tabpanel" aria-labelledby="tab-features" aria-hidden="true" data-settings-panel="features">
            <div class="settings-features-shell">
                <header class="settings-features-head">
                    <h2 class="mb-1">POS features</h2>
                    <p class="text-muted small mb-0">Control how the register behaves. Toggles save automatically.</p>
                </header>

                {{-- Price validation — saves to DB on toggle change --}}
                <article class="feature-row">
                    <div class="d-flex gap-3 gap-md-4 align-items-start">
                        <div class="feature-row__icon d-none d-sm-flex" aria-hidden="true">
                            <i class="fas fa-tags"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-column flex-sm-row align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="feature-row__title">Price &amp; discount validation</div>
                                    <p class="feature-row__meta mb-0">
                                        <strong>On:</strong> only permitted users can change unit price or discounts in POS.
                                        <strong class="d-inline-block ms-1">Off:</strong> everyone can edit freely.
                                    </p>
                                </div>
                                <div class="form-check form-switch feature-switch flex-shrink-0 align-self-sm-center ms-sm-2">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        name="enable_price_validation" id="enable_price_validation"
                                        data-save-url="{{ route('settings.update-price-validation') }}"
                                        {{ old('enable_price_validation', $setting->enable_price_validation ?? 1) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="feature-hint mb-0">
                                <i class="fas fa-info-circle hint-ico"></i>
                                When enabled, assign permissions under
                                <a href="{{ route('group-role-and-permission-view') }}">Roles &amp; permissions</a>
                                (“edit unit price in pos”, “edit discount in pos”).
                            </div>
                        </div>
                    </div>
                </article>

                @if(auth()->user()->hasRole('Master Super Admin'))
                {{-- Free quantity — saves to DB on toggle change --}}
                <article class="feature-row">
                    <div class="d-flex gap-3 gap-md-4 align-items-start">
                        <div class="feature-row__icon d-none d-sm-flex" aria-hidden="true">
                            <i class="fas fa-gift"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-column flex-sm-row align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="feature-row__title">Free quantity (bonus lines)</div>
                                    <p class="feature-row__meta mb-0">
                                        <strong>On:</strong> Free Qty appears in POS for users with the right permission.
                                        <strong class="d-inline-block ms-1">Off:</strong> column hidden for everyone.
                                    </p>
                                </div>
                                <div class="form-check form-switch feature-switch flex-shrink-0 align-self-sm-center ms-sm-2">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        name="enable_free_qty" id="enable_free_qty"
                                        data-save-url="{{ route('settings.update-free-qty') }}"
                                        {{ old('enable_free_qty', $setting->enable_free_qty ?? 1) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="feature-hint mb-0">
                                <i class="fas fa-info-circle hint-ico"></i>
                                Grant <strong>use free quantity</strong> in
                                <a href="{{ route('group-role-and-permission-view') }}">Roles &amp; permissions</a>.
                                Tracked in purchase, returns, and POS as applicable.
                            </div>
                        </div>
                    </div>
                </article>
                @endif

                @can('edit backorder-settings')
                {{-- Backorders — saves to DB on toggle change --}}
                <article class="feature-row">
                    <div class="d-flex gap-3 gap-md-4 align-items-start">
                        <div class="feature-row__icon d-none d-sm-flex" aria-hidden="true">
                            <i class="fas fa-dolly"></i>
                        </div>
                        <div class="flex-grow-1 min-w-0">
                            <div class="d-flex flex-column flex-sm-row align-items-start justify-content-between gap-3">
                                <div>
                                    <div class="feature-row__title">Sale-order backorders</div>
                                    <p class="feature-row__meta mb-0">
                                        <strong>On:</strong> shortages on sale orders can be recorded as pending backorder.
                                        <strong class="d-inline-block ms-1">Off:</strong> stock is enforced; no shortage backorder line.
                                    </p>
                                </div>
                                <div class="form-check form-switch feature-switch flex-shrink-0 align-self-sm-center ms-sm-2">
                                    <input class="form-check-input" type="checkbox" role="switch"
                                        name="enable_backorders" id="enable_backorders"
                                        data-save-url="{{ route('settings.update-backorders') }}"
                                        {{ old('enable_backorders', $setting->enable_backorders ?? 0) ? 'checked' : '' }}>
                                </div>
                            </div>
                            <div class="feature-hint mb-0">
                                <i class="fas fa-info-circle hint-ico"></i>
                                Applies to <strong>sale order</strong> workflow. Normal POS checkout rules stay strict.
                            </div>
                        </div>
                    </div>
                </article>
                @endcan
            </div>
            </section>

            @if(auth()->user()->can('edit sms-settings') || auth()->user()->can('sms.send'))
            <section id="settings-panel-sms" class="settings-panel d-none" role="tabpanel" aria-labelledby="tab-sms" aria-hidden="true" data-settings-panel="sms">
            @can('edit sms-settings')
            <div class="card settings-surface-card rounded-4 border-0 overflow-hidden mb-0">
                <div class="card-header settings-card-header border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
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
            <div class="card settings-surface-card rounded-4 border-0 mt-4 overflow-hidden mb-0">
                <div class="card-header settings-card-header border-0 py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
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
            </section>
            @endif

            @can('backup database')
            {{-- Database Backup — matches SettingController backupNow middleware --}}
            <section id="settings-panel-backup" class="settings-panel d-none" role="tabpanel" aria-labelledby="tab-backup" aria-hidden="true" data-settings-panel="backup">
            <div class="card settings-surface-card rounded-4 border-0 overflow-hidden">
                <div class="card-header settings-card-header border-0 py-3">
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

            <form id="backupForm" action="{{ route('settings.backup-now') }}" method="POST" class="d-none">
    @csrf
            </form>
            </section>
            @endcan
        </div>
    </div>
</div>

{{-- Image Preview Script + Settings / Backup handlers --}}
<script>
(function () {
    function initSettingsPanels() {
        var nav = document.querySelector('.settings-page-nav');
        if (!nav) return;
        var buttons = nav.querySelectorAll('[data-settings-target]');
        var panels = document.querySelectorAll('[data-settings-panel]');
        if (!buttons.length || !panels.length) return;

        var ids = Array.prototype.map.call(buttons, function (b) {
            return b.getAttribute('data-settings-target');
        });

        function applyPanel(id, updateHash) {
            if (ids.indexOf(id) === -1) return;
            buttons.forEach(function (btn) {
                var on = btn.getAttribute('data-settings-target') === id;
                btn.classList.toggle('active', on);
                btn.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            panels.forEach(function (panel) {
                var on = panel.getAttribute('data-settings-panel') === id;
                panel.classList.toggle('d-none', !on);
                panel.setAttribute('aria-hidden', on ? 'false' : 'true');
            });
            if (updateHash && window.history && window.history.replaceState) {
                try {
                    var base = window.location.pathname + window.location.search;
                    window.history.replaceState(null, '', base + '#' + id);
                } catch (e) { /* ignore */ }
            }
        }

        buttons.forEach(function (btn) {
            btn.addEventListener('click', function () {
                applyPanel(btn.getAttribute('data-settings-target'), true);
            });
        });

        var hash = (window.location.hash || '').replace(/^#/, '').trim();
        if (hash && ids.indexOf(hash) !== -1) {
            applyPanel(hash, false);
        } else {
            applyPanel(ids[0], false);
        }

        window.addEventListener('hashchange', function () {
            var h = (window.location.hash || '').replace(/^#/, '').trim();
            if (h && ids.indexOf(h) !== -1) {
                applyPanel(h, false);
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSettingsPanels);
    } else {
        initSettingsPanels();
    }
})();

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

// Manual DB backup (only when backup card is visible — user has permission)
(function () {
    var backupBtn = document.getElementById('backupNowButton');
    var backupForm = document.getElementById('backupForm');
    if (backupBtn && backupForm) {
        backupBtn.addEventListener('click', function () {
            backupForm.submit();
        });
    }
})();

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

// Card 4: save backorders on toggle change
$('#enable_backorders').on('change', function () {
    var url = $(this).data('save-url');
    var value = $(this).prop('checked') ? 1 : 0;
    $.ajax({
        url: url,
        type: 'POST',
        data: { enable_backorders: value, _token: $('meta[name="csrf-token"]').attr('content') },
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
