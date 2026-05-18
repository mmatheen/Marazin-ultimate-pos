    /** Slug for view-contact URLs (ASCII); server redirects to canonical Laravel Str::slug. */
    function slugifyContactNameForUrl(row) {
        var raw = (row.full_name || '').trim();
        if (!raw) {
            var parts = [];
            if (row.first_name) {
                parts.push(String(row.first_name).trim());
            }
            if (row.last_name) {
                parts.push(String(row.last_name).trim());
            }
            raw = parts.join(' ').trim();
        }
        if (!raw) {
            return 'customer';
        }
        var slug = raw.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '');
        return slug || 'customer';
    }

    $(document).ready(function() {
        var csrfToken = $('meta[name="csrf-token"]').attr('content'); //for crf token

        // Check if current user is a sales rep (check early) — set from Blade via window.CustomerAjaxBootstrap
        var bootstrap = window.CustomerAjaxBootstrap || {};
        var isSalesRep = !!bootstrap.isSalesRep;
        var canViewCity = !!bootstrap.canViewCity;
        var canManageCustomerSmsOptIn = !!bootstrap.canManageCustomerSmsOptIn;

        // Initialize DataTable
        try {
            initializeCustomerTable();
        } catch (error) {
            console.error('Error initializing customer table:', error);
        }

        // For sales reps in POS, skip initial customer load - route filtering will handle it
        // Only fetch all customers for non-sales reps or when not on POS page
        const isPOSPage = window.location.pathname.includes('/pos-create') || window.location.pathname.includes('/pos');
        if (!isSalesRep || !isPOSPage) {
            fetchCustomerData();
        } else {
            console.log('📌 Sales rep on POS: Skipping initial customer load, waiting for route-based filtering');
            // fetchCustomerData() never runs for reps, so set flag here — otherwise "My invoices due" never shows
            window.posShowRepInvoiceDue = true;
            $('#customer-id').html('<option value="">Please Select</option>');
        }

        if (canViewCity && $('#city_search_input').length) {
            fetchCities();
        }

        // Build validation rules conditionally
        var validationRules = {
            first_name: {
                required: true,
            },
            mobile_no: {
                required: true,
            },
            credit_limit: {
                required: true,
                number: true,
            },
            customer_type: {
                required: true,
            },
        };

        var validationMessages = {
            first_name: {
                required: "First Name is required",
            },
            mobile_no: {
                required: "Mobile No is required",
            },
            credit_limit: {
                required: "Credit Limit is required",
                number: "Credit Limit must be a number",
            },
            customer_type: {
                required: "Customer Type is required",
            },
        };

        // Add city validation only for sales reps with city view permission
        if (isSalesRep && canViewCity) {
            validationRules.city_id = {
                required: true,
            };
            validationMessages.city_id = {
                required: "City is required for sales representatives",
            };
        }

        // add form and update validation rules code start
        var addAndUpdateValidationOptions = {
            rules: validationRules,
            messages: validationMessages,
            errorElement: 'span',
            errorPlacement: function(error, element) {
                error.addClass('text-danger');
                error.insertAfter(element);
            },
            highlight: function(element, errorClass, validClass) {
                $(element).addClass('is-invalidRed').removeClass('is-validGreen');
            },
            unhighlight: function(element, errorClass, validClass) {
                $(element).removeClass('is-invalidRed').addClass('is-validGreen');
            }

        };

        // Apply validation to both forms
        $('#addAndUpdateForm').validate(addAndUpdateValidationOptions);

        // add form and update validation rules code end

        // Function to reset form and validation errors
        function resetFormAndValidation() {
            // Reset the form fields
            $('#addAndUpdateForm')[0].reset();
            // Reset the validation messages and states
            $('#addAndUpdateForm').validate().resetForm();
            $('#addAndUpdateForm').find('.is-invalidRed').removeClass('is-invalidRed');
            $('#addAndUpdateForm').find('.is-validGreen').removeClass('is-validGreen');
            // Remove info banner
            $('.city-info-banner').remove();
            // Clear custom city search
            if (window.setCityValue) {
                window.setCityValue('', '');
            }
            $('#city_dropdown').hide();
        }

        // Clear form and validation errors when the modal is hidden
        $('#addAndEditCustomerModal').on('hidden.bs.modal', function() {
            resetFormAndValidation();
        });

        // Custom city search is now handled by initializeCitySearch() function

        // Initialize Select2 for other dropdowns when modal opens (exclude city search)
        $('#addAndEditCustomerModal').on('shown.bs.modal', function() {
            $('#addAndEditCustomerModal .selectBox:not(.city-search-input)').select2({
                dropdownParent: $('#addAndEditCustomerModal'),
                minimumResultsForSearch: -1,
                width: "100%"
            });
        });

        // Show Add Customer Modal
        $('#addCustomerButton').click(function() {
            $('#modalTitle').text('New Customer');
            $('#modalButton').text('Save');
            $('#addAndUpdateForm')[0].reset();
            // Only clear validation/errors inside the customer modal — never global $('.text-danger'),
            // which would wipe POS credit strip (#total-due-amount, available credit HTML, etc.)
            $('#addAndEditCustomerModal').find('.text-danger').text('');
            $('#edit_id').val(''); // Clear the edit_id to ensure it's not considered an update

            // Set default customer type to retailer
            $('#edit_customer_type').val('retailer').trigger('change');

            $('#edit_credit_limit').val('0');

            // Show helpful message for non-sales rep users with city view permission
            if (!isSalesRep && canViewCity) {
                // Add a subtle info banner at the top of the modal
                if ($('.city-info-banner').length === 0) {
                    const infoBanner = `
                        <div class="alert alert-info alert-dismissible fade show city-info-banner" role="alert">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>City Selection:</strong> Adding a city is optional but helps sales representatives filter customers by location.
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    `;
                    $('.modal-body .text-center').after(infoBanner);
                }
            }

            // Clear city search input for new customer
            if (window.setCityValue) {
                window.setCityValue('', '');
            }

            $('#addAndEditCustomerModal').modal('show');
        });

        function initializeCustomerTable() {
            if ($.fn.DataTable.isDataTable('#customer')) {
                $('#customer').DataTable().destroy();
            }
            $('#customer tbody').empty();

            $('#customer').DataTable({
                processing: true,
                ajax: {
                    url: '/customer-get-all',
                    type: 'GET',
                    dataType: 'json',
                    data: function(d) {
                        d.city_id = $('#cityFilter').val();
                    },
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    dataSrc: function(response) {
                        if (response && response.status === 200 && Array.isArray(response.message)) {
                            return response.message;
                        }
                        console.error('Invalid response format:', response);
                        return [];
                    },
                    error: function(xhr, status, error) {
                        console.error('Customer DataTable AJAX error:', error);
                        if (typeof toastr !== 'undefined') {
                            toastr.error('Failed to load customer data.', 'Error');
                        }
                    }
                },
                columns: [
                    {
                        data: null,
                        title: 'Action',
                        render: function(data, type, row) {
                            const contactSlug = slugifyContactNameForUrl(row);
                            const b = window.CustomerAjaxBootstrap || {};
                            const r = b.routes || {};
                            const listSale = r.listSale || '';
                            const dueReport = r.dueReport || '';
                            const viewBase = r.viewContactBase || '';
                            let actions = '<div class="btn-group" role="group">';

                            const salesReportItems = [];
                            if (b.canViewAllSales) {
                                salesReportItems.push(`
                                        <li><a class="dropdown-item" href="${listSale}?customer_id=${row.id}" target="_blank">
                                            <i class="feather-list text-success"></i> All Sales
                                        </a></li>`);
                            }
                            if (b.canViewDueReport) {
                                salesReportItems.push(`
                                        <li><a class="dropdown-item" href="${dueReport}?report_type=customer&customer_id=${row.id}" target="_blank">
                                            <i class="feather-alert-circle text-danger"></i> Due Sales
                                        </a></li>`);
                            }

                            const customerActionItems = [];
                            if (b.canViewContactDetail) {
                                customerActionItems.push(`
                                            <li><a class="dropdown-item" href="${viewBase}/${row.id}/${contactSlug}" target="_blank" data-customer-view-contact-link="1">
                                                <i class="feather-user text-success"></i> View Contact
                                            </a></li>`);
                            }
                            if (b.canViewLedger) {
                                customerActionItems.push(`
                                            <li><a class="dropdown-item ledger_btn" href="#" data-id="${row.id}">
                                                <i class="feather-book-open text-primary"></i> Ledger
                                            </a></li>`);
                            }
                            if (b.canEditCustomer) {
                                customerActionItems.push(`
                                            <li><a class="dropdown-item edit_btn" href="#" data-id="${row.id}">
                                                <i class="feather-edit text-info"></i> Edit
                                            </a></li>`);
                            }
                            if (b.canDeleteCustomer) {
                                customerActionItems.push(`
                                            <li><a class="dropdown-item delete_btn" href="#" data-id="${row.id}">
                                                <i class="feather-trash-2 text-danger"></i> Delete
                                            </a></li>`);
                            }

                            if (!salesReportItems.length && !customerActionItems.length) {
                                return '';
                            }

                            actions += `
                                <div class="dropdown">
                                    <button type="button" class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="feather-settings"></i> Actions
                                    </button>
                                    <ul class="dropdown-menu">`;
                            if (salesReportItems.length) {
                                actions += `
                                        <li><h6 class="dropdown-header">Sales Reports</h6></li>
                                        ${salesReportItems.join('')}`;
                            }
                            if (customerActionItems.length) {
                                if (salesReportItems.length) {
                                    actions += `<li><hr class="dropdown-divider"></li>`;
                                }
                                actions += `
                                        <li><h6 class="dropdown-header">Customer Actions</h6></li>
                                        ${customerActionItems.join('')}`;
                            }
                            actions += `
                                    </ul>
                                </div>
                            `;

                            actions += '</div>';
                            return actions;
                        },
                        orderable: false
                    },
                    {
                        data: null,
                        render: function(data, type, row, meta) {
                            return meta.row + meta.settings._iDisplayStart + 1;
                        },
                        title: 'ID',
                        orderable: false
                    },
                    {
                        data: 'first_name',
                        title: 'First Name',
                        defaultContent: ''
                    },
                    {
                        data: 'last_name',
                        title: 'Last Name',
                        defaultContent: '',
                        render: function(data, type, row) {
                            return data || '';
                        }
                    },
                    {
                        data: 'mobile_no',
                        title: 'Mobile No',
                        defaultContent: ''
                    },
                    {
                        data: 'email',
                        title: 'Email',
                        defaultContent: '',
                        render: function(data, type, row) {
                            return data || '';
                        }
                    },
                    {
                        data: 'city_name',
                        title: 'City',
                        defaultContent: '',
                        render: function(data, type, row) {
                            return data || '';
                        }
                    },
                    {
                        data: 'customer_type',
                        title: 'Customer Type',
                        defaultContent: 'Not Set',
                        render: function(data, type, row) {
                            return data ? data.charAt(0).toUpperCase() + data.slice(1) : 'Not Set';
                        }
                    },
                    {
                        data: 'address',
                        title: 'Address',
                        defaultContent: '',
                        render: function(data, type, row) {
                            return data || '';
                        }
                    },
                    {
                        data: 'opening_balance',
                        title: 'Opening Balance',
                        defaultContent: '0',
                        render: function(data, type, row) {
                            return data || '0';
                        }
                    },
                    {
                        data: 'credit_limit',
                        title: 'Credit Limit',
                        defaultContent: '0',
                        render: function(data, type, row) {
                            return data || '0';
                        }
                    },
                    {
                        data: 'total_sale_due',
                        title: 'Total Sale Due',
                        defaultContent: '0',
                        render: function(data, type, row) {
                            return data || '0';
                        }
                    },
                    {
                        data: 'total_return_due',
                        title: 'Total Return Due',
                        defaultContent: '0',
                        render: function(data, type, row) {
                            return data || '0';
                        }
                    },
                    {
                        data: 'total_advance_credit',
                        title: 'Advance Credit',
                        defaultContent: '0',
                        render: function(data, type, row) {
                            const advance = parseFloat(data) || 0;
                            const formatted = advance.toFixed(2);
                            if (advance > 0) {
                                return `<span class="text-success fw-bold">${formatted}</span>`;
                            }
                            return formatted;
                        }
                    },
                    {
                        data: 'current_balance',
                        title: 'Current Balance',
                        defaultContent: '0',
                        render: function(data, type, row) {
                            // Format the balance with proper styling
                            const balance = parseFloat(data) || 0;
                            const formattedBalance = balance.toFixed(2);

                            // Color code: green for credit (negative), red for debit (positive)
                            if (balance > 0) {
                                return `<span class="text-danger fw-bold">${formattedBalance}</span>`;
                            } else if (balance < 0) {
                                return `<span class="text-success fw-bold">${formattedBalance}</span>`;
                            }
                            return formattedBalance;
                        }
                    }
                ],
                pageLength: 10,
                lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
                responsive: true,
                order: [[3, 'asc']], // Order by first_name (now at position 3)
                buttons: [
                    {
                        extend: 'pdfHtml5',
                        text: '<i class="fas fa-file-pdf"></i> PDF',
                        className: 'btn btn-danger btn-sm',
                        title: 'Customer List - ' + ($('#cityFilter option:selected').text() || 'All Cities'),
                        orientation: 'landscape',
                        pageSize: 'A4',
                        exportOptions: {
                            columns: [1, 3, 4, 10, 12, 13, 14, 15] // ID, First Name, Last Name, Opening Balance, Total Sale Due, Total Return Due, Advance Credit, Current Balance
                        },
                        customize: function(doc) {
                            // Add two empty columns to the PDF table
                            var tableBody = doc.content[doc.content.length - 1].table.body;

                            // Add headers for Cheque Amount and Cash Amount
                            tableBody[0].push({ text: 'Cheque Amount', style: 'tableHeader' });
                            tableBody[0].push({ text: 'Cash Amount', style: 'tableHeader' });

                            // Add empty cells for data rows
                            for (var i = 1; i < tableBody.length; i++) {
                                tableBody[i].push({ text: '' });
                                tableBody[i].push({ text: '' });
                            }

                            // Add header
                            doc.content[0].text = 'Customer List Report';
                            doc.content[0].style = 'header';
                            doc.content[0].alignment = 'center';
                            doc.content[0].fontSize = 16;
                            doc.content[0].bold = true;
                            doc.content[0].margin = [0, 0, 0, 10];

                            // Add filter info
                            var cityFilter = $('#cityFilter option:selected').text();
                            if (cityFilter && cityFilter !== 'All Cities') {
                                doc.content.splice(1, 0, {
                                    text: 'Filtered by City: ' + cityFilter,
                                    alignment: 'center',
                                    fontSize: 12,
                                    margin: [0, 0, 0, 10]
                                });
                            }

                            // Add date
                            doc.content.splice(cityFilter && cityFilter !== 'All Cities' ? 2 : 1, 0, {
                                text: 'Generated on: ' + new Date().toLocaleString(),
                                alignment: 'center',
                                fontSize: 10,
                                margin: [0, 0, 0, 15]
                            });

                            // Style the table
                            doc.styles.tableHeader = {
                                bold: true,
                                fontSize: 10,
                                color: 'white',
                                fillColor: '#2d3748',
                                alignment: 'center'
                            };

                            // Make table more compact
                            doc.content[doc.content.length - 1].table.widths = Array(doc.content[doc.content.length - 1].table.body[0].length).fill('auto');

                            // Style data rows
                            var objLayout = {};
                            objLayout['hLineWidth'] = function(i) { return 0.5; };
                            objLayout['vLineWidth'] = function(i) { return 0.5; };
                            objLayout['hLineColor'] = function(i) { return '#aaa'; };
                            objLayout['vLineColor'] = function(i) { return '#aaa'; };
                            objLayout['paddingLeft'] = function(i) { return 4; };
                            objLayout['paddingRight'] = function(i) { return 4; };
                            doc.content[doc.content.length - 1].layout = objLayout;

                            // Highlight current balance (credit balance) in red
                            var tableBody = doc.content[doc.content.length - 1].table.body;
                            for (var i = 1; i < tableBody.length; i++) {
                                if (!tableBody[i]) continue; // Skip if row is undefined

                                // Total Sale Due column (position 4 in exported PDF)
                                if (tableBody[i][4] !== undefined) {
                                    var saleDueValue = tableBody[i][4].text || tableBody[i][4];
                                    if (parseFloat(saleDueValue) > 0) {
                                        if (typeof tableBody[i][4] === 'object') {
                                            tableBody[i][4].color = 'red';
                                            tableBody[i][4].bold = true;
                                        } else {
                                            tableBody[i][4] = { text: tableBody[i][4], color: 'red', bold: true };
                                        }
                                    }
                                }

                                // Total Return Due column (position 5 in exported PDF)
                                if (tableBody[i][5] !== undefined) {
                                    var returnDueValue = tableBody[i][5].text || tableBody[i][5];
                                    if (parseFloat(returnDueValue) > 0) {
                                        if (typeof tableBody[i][5] === 'object') {
                                            tableBody[i][5].color = 'red';
                                            tableBody[i][5].bold = true;
                                        } else {
                                            tableBody[i][5] = { text: tableBody[i][5], color: 'red', bold: true };
                                        }
                                    }
                                }

                                // Current Balance column (position 6 in exported PDF) - customer credit balance
                                if (tableBody[i][6] !== undefined) {
                                    var currentBalanceValue = tableBody[i][6].text || tableBody[i][6];
                                    if (parseFloat(currentBalanceValue) > 0) {
                                        if (typeof tableBody[i][6] === 'object') {
                                            tableBody[i][6].color = 'red';
                                            tableBody[i][6].bold = true;
                                        } else {
                                            tableBody[i][6] = { text: tableBody[i][6], color: 'red', bold: true };
                                        }
                                    }
                                }
                            }

                            // Add footer with summary
                            var totalCustomers = tableBody.length - 1;
                            doc.content.push({
                                text: 'Total Customers: ' + totalCustomers,
                                alignment: 'right',
                                fontSize: 10,
                                bold: true,
                                margin: [0, 10, 0, 0]
                            });
                        }
                    },
                    {
                        extend: 'excelHtml5',
                        text: '<i class="fas fa-file-excel"></i> Excel',
                        className: 'btn btn-success btn-sm',
                        title: 'Customer List',
                        exportOptions: {
                            columns: [1, 3, 4, 10, 12, 13, 14, 15] // ID, First Name, Last Name, Opening Balance, Total Sale Due, Total Return Due, Advance Credit, Current Balance
                        },
                        customize: function(xlsx) {
                            var sheet = xlsx.xl.worksheets['sheet1.xml'];
                            var $sheet = $(sheet);
                            var $rows = $sheet.find('row');

                            // Add two empty columns (Cheque Amount and Cash Amount) to each row
                            $rows.each(function(index) {
                                var $row = $(this);
                                var cellCount = $row.find('c').length;

                                // Add header or empty cells
                                if (index === 0) {
                                    // Add header cells
                                    $row.append('<c t="inlineStr" r="' + String.fromCharCode(65 + cellCount) + '1"><is><t>Cheque Amount</t></is></c>');
                                    $row.append('<c t="inlineStr" r="' + String.fromCharCode(65 + cellCount + 1) + '1"><is><t>Cash Amount</t></is></c>');
                                } else {
                                    // Add empty data cells
                                    $row.append('<c t="inlineStr" r="' + String.fromCharCode(65 + cellCount) + (index + 1) + '"><is><t></t></is></c>');
                                    $row.append('<c t="inlineStr" r="' + String.fromCharCode(65 + cellCount + 1) + (index + 1) + '"><is><t></t></is></c>');
                                }
                            });
                        }
                    },
                    {
                        extend: 'print',
                        text: '<i class="fas fa-print"></i> Print',
                        className: 'btn btn-info btn-sm',
                        title: 'Customer List - ' + ($('#cityFilter option:selected').text() || 'All Cities'),
                        exportOptions: {
                            columns: [1, 3, 4, 10, 12, 13, 14, 15] // ID, First Name, Last Name, Opening Balance, Total Sale Due, Total Return Due, Advance Credit, Current Balance
                        },
                        customize: function(win) {
                            // Add two empty columns to the printed table
                            $(win.document.body).find('table thead tr').append('<th>Cheque Amount</th><th>Cash Amount</th>');
                            $(win.document.body).find('table tbody tr').each(function() {
                                $(this).append('<td></td><td></td>');
                            });

                            $(win.document.body).css('font-size', '10pt');
                            $(win.document.body).find('table').addClass('compact').css('font-size', 'inherit');

                            // Add custom header
                            $(win.document.body).prepend(
                                '<h2 style="text-align:center;">Customer List Report</h2>' +
                                '<p style="text-align:center;">Generated on: ' + new Date().toLocaleString() + '</p>'
                            );

                            var cityFilter = $('#cityFilter option:selected').text();
                            if (cityFilter && cityFilter !== 'All Cities') {
                                $(win.document.body).find('h2').after(
                                    '<p style="text-align:center;"><strong>Filtered by City: ' + cityFilter + '</strong></p>'
                                );
                            }
                        }
                    }
                ],
                language: {
                    processing: "Loading customer data...",
                    emptyTable: "No customers found",
                    zeroRecords: "No matching customers found",
                    info: "Showing _START_ to _END_ of _TOTAL_ customers",
                    infoEmpty: "Showing 0 to 0 of 0 customers",
                    infoFiltered: "(filtered from _MAX_ total customers)",
                    search: "Search customers:",
                    paginate: {
                        first: "First",
                        last: "Last",
                        next: "Next",
                        previous: "Previous"
                    }
                },
                dom: '<"dt-top"B><"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6"f>>' +
                     '<"row"<"col-sm-12"tr>>' +
                     '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>'
            });
        }

        //Fetch cities
        // Simple city search implementation
        var allCities = [];
        var selectedCityId = '';
        var citySearchInitialized = false;

        // Make allCities globally accessible
        window.allCities = allCities;

        function fetchCities() {
            $.ajax({
                url: '/api/cities',
                type: 'GET',
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    console.log('Cities API Response:', response); // Debug log
                    if (response.status && response.data) {
                        // Clear and repopulate the array to maintain reference
                        allCities.length = 0;
                        Array.prototype.push.apply(allCities, response.data);
                        window.allCities = allCities; // Keep global reference updated
                        console.log('Cities loaded:', allCities.length, allCities); // Debug log
                        if (!citySearchInitialized) {
                            setupCitySearch();
                            citySearchInitialized = true;
                        }
                    } else {
                        console.error('Invalid cities response:', response);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching cities:', error);
                }
            });
        }

        function setupCitySearch() {
            const input = $('#city_search_input');
            const dropdown = $('#city_dropdown');
            const hiddenInput = $('#edit_city_id');
            let currentIndex = -1;

            // Search as user types
            input.on('input', function() {
                const query = $(this).val().trim();
                selectedCityId = '';
                hiddenInput.val('');
                currentIndex = -1;

                console.log('Search query:', query, 'Available cities:', allCities.length); // Debug log

                if (query === '') {
                    dropdown.hide();
                    return;
                }

                const matches = allCities.filter(city =>
                    city.name.toLowerCase().includes(query.toLowerCase())
                );

                console.log('Matches found:', matches.length, matches); // Debug log

                if (matches.length > 0) {
                    showResults(matches);
                    currentIndex = 0; // Select first result by default
                    highlightOption();
                } else {
                    showNoResults();
                }
            });

            // Keyboard navigation
            input.on('keydown', function(e) {
                const options = $('.city-option');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    if (currentIndex < options.length - 1) {
                        currentIndex++;
                        highlightOption();
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    if (currentIndex > 0) {
                        currentIndex--;
                        highlightOption();
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (currentIndex >= 0 && options.eq(currentIndex).length) {
                        selectCity(options.eq(currentIndex));
                    }
                } else if (e.key === 'Escape') {
                    dropdown.hide();
                    currentIndex = -1;
                }
            });

            // Clear text when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('.city-search-container').length) {
                    dropdown.hide();
                    if (!selectedCityId && input.val().trim() !== '') {
                        input.val('');
                        hiddenInput.val('');
                    }
                }
            });

            function showResults(cities) {
                const content = cities.map(city =>
                    `<div class="city-option" data-id="${city.id}" data-name="${city.name}">${city.name}</div>`
                ).join('');

                $('.city-dropdown-content').html(content);
                dropdown.show();

                $('.city-option').on('click', function() {
                    selectCity($(this));
                });
            }

            function selectCity(option) {
                const cityId = option.data('id');
                const cityName = option.data('name');

                selectedCityId = cityId;
                input.val(cityName);
                hiddenInput.val(cityId);
                dropdown.hide();
                currentIndex = -1;
            }

            function highlightOption() {
                $('.city-option').removeClass('highlighted');
                if (currentIndex >= 0) {
                    $('.city-option').eq(currentIndex).addClass('highlighted');
                }
            }

            function showNoResults() {
                $('.city-dropdown-content').html('<div class="city-no-results">No cities found</div>');
                dropdown.show();
            }

            window.setCityValue = function(cityId, cityName) {
                selectedCityId = cityId || '';
                input.val(cityName || '');
                hiddenInput.val(cityId || '');
            };
        }


        // Show Edit Modal
        $(document).on('click', '.edit_btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id') || $(this).val();
            $('#modalTitle').text('Edit Customer');
            $('#modalButton').text('Update');
            $('#addAndUpdateForm')[0].reset();
            $('#addAndEditCustomerModal').find('.text-danger').text('');
            $('#edit_id').val(id);

            $.ajax({
                url: 'customer-edit/' + id,
                type: 'get',
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else if (response.status == 200 && response.customer) {
                        $('#edit_first_name').val(response.customer.first_name || '');
                        $('#edit_last_name').val(response.customer.last_name || '');
                        $('#edit_mobile_no').val(response.customer.mobile_no || '');
                        $('#edit_email').val(response.customer.email || '');
                        $('#edit_address').val(response.customer.address || '');
                        $('#edit_opening_balance').val(response.customer.opening_balance ||
                            '');
                        var cl = response.customer.credit_limit;
                        $('#edit_credit_limit').val(
                            cl === null || cl === undefined || cl === '' ? '0' : cl
                        );
                        if (canViewCity) {
                            const cityId = response.customer.city_id || '';
                            const cityName = response.customer.city_name || '';
                            if (window.setCityValue) {
                                window.setCityValue(cityId, cityName);
                            }
                        }
                        if (canManageCustomerSmsOptIn) {
                            $('#edit_allow_sms').prop('checked', !!response.customer.allow_sms);
                        }

                        $('#edit_customer_type').val(response.customer.customer_type || '')
                            .trigger('change');

                        // Show modal
                        $('#addAndEditCustomerModal').modal('show');
                    } else {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Failed to load customer data', 'Error');
                    }
                }
            });
        });


        // Submit Add/Update Form
        $('#addAndUpdateForm').submit(function(e) {
            e.preventDefault();

            // Validate the form before submitting
            if (!$('#addAndUpdateForm').valid()) {
                document.getElementsByClassName('warningSound')[0].play(); //for sound
                toastr.options = {
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                };
                toastr.error('Invalid inputs, Check & try again!!', 'Warning');
                return; // Return if form is not valid
            }

            let formData = new FormData(this);
            if (!canViewCity) {
                formData.delete('city_id');
            }
            if (!canManageCustomerSmsOptIn) {
                formData.delete('allow_sms');
            }
            let id = $('#edit_id').val(); // for edit
            let url = id ? 'customer-update/' + id : 'customer-store';
            let type = id ? 'post' : 'post';

            $.ajax({
                url: url,
                type: type,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                data: formData,
                contentType: false,
                processData: false,
                dataType: 'json',
                success: function(response) {
                    if (response.status == 200) {
                        $('#addAndEditCustomerModal').modal('hide');
                        // Customer list table exists only on the contacts page — POS has no #customer DataTable
                        if ($.fn.DataTable && $.fn.DataTable.isDataTable('#customer')) {
                            $('#customer').DataTable().ajax.reload();
                        }
                        fetchCustomerData();
                        document.getElementsByClassName('successSound')[0]
                            .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, id ? 'Updated' : 'Added');
                        resetFormAndValidation();
                    } else if (response.status == 400) {
                        // Handle validation errors that come through success callback
                        if (response.errors) {
                            // Display field-specific validation errors
                            $.each(response.errors, function(key, err_value) {
                                $('#' + key + '_error').html(Array.isArray(
                                    err_value) ? err_value[0] : err_value);
                            });

                            // Show simple error toastr for validation errors
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };

                            // Show specific error message for mobile number duplicates
                            if (response.errors.mobile_no) {
                                toastr.error('Mobile number already exists!', 'Error');
                            } else if (response.errors.email) {
                                toastr.error('Email already exists!', 'Error');
                            } else {
                                toastr.error('Please fix the errors and try again.',
                                    'Error');
                            }
                        } else if (response.message) {
                            // Show generic error message
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };
                            toastr.error('Customer already exists!', 'Error');
                        }
                    } else {
                        // Handle any other status codes with clean message
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Unable to create customer. Please try again.',
                            'Error');
                    }
                },
                error: function(xhr, status, error) {
                    console.log('Error response:', xhr.status, xhr.responseJSON);
                    if (xhr.status === 400) {
                        // Handle validation errors
                        var response = xhr.responseJSON;
                        if (response && response.errors) {
                            // Display field-specific validation errors
                            $.each(response.errors, function(key, err_value) {
                                $('#' + key + '_error').html(Array.isArray(
                                    err_value) ? err_value[0] : err_value);
                            });

                            // Show clean error toastr for validation errors
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };

                            // Show specific error message for different validation errors
                            if (response.errors.mobile_no) {
                                toastr.error('Mobile number already exists!', 'Error');
                            } else if (response.errors.email) {
                                toastr.error('Email already exists!', 'Error');
                            } else {
                                toastr.error('Please fix the errors and try again.',
                                    'Error');
                            }
                        } else if (response && response.message) {
                            // Show simple generic error message
                            toastr.options = {
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            };
                            toastr.error('Customer already exists!', 'Error');
                        }
                    } else if (xhr.status === 500) {
                        // Handle server errors with clean message
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(
                            'Unable to create customer due to a server error. Please try again later.',
                            'Server Error');
                    } else {
                        // Handle other errors with simple message
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error('Unable to create customer. Please try again.',
                            'Error');
                    }
                }
            });
        });


        // Delete Customer
        $(document).on('click', '.delete_btn', function(e) {
            e.preventDefault();
            var id = $(this).data('id') || $(this).val();
            $('#deleteModal').modal('show');
            $('#deleting_id').val(id);
            $('#deleteName').text('Delete customer');
        });

        $(document).on('click', '.confirm_delete_btn', function() {
            var id = $('#deleting_id').val();
            $.ajax({
                url: 'customer-delete/' + id,
                type: 'delete',
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                },
                success: function(response) {
                    if (response.status == 404) {
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.error(response.message, 'Error');
                    } else {
                        $('#deleteModal').modal('hide');
                        $('#customer').DataTable().ajax.reload();
                        document.getElementsByClassName('successSound')[0]
                            .play(); //for sound
                        toastr.options = {
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        };
                        toastr.success(response.message, 'Deleted');
                    }
                }
            });
        });

        // Navigate to Customer Ledger
        $(document).on('click', '.ledger_btn', function(e) {
            e.preventDefault();
            var customerId = $(this).data('id') || $(this).val();
            // Navigate to customer ledger page with customer ID as parameter
            window.location.href = '/account-ledger?customer_id=' + customerId;
        });


        /**
         * POS customer dropdown: advance pool for applying to current bill.
         * Prefer total_advance_credit from API; if absent/zero, use negative current_balance
         * (debit − credit < 0 ⇔ customer prepaid — same rule as account ledger advance).
         */
        function posLedgerAdvanceFromCustomerPayload(customer) {
            if (!customer || customer.id == 1) {
                return 0;
            }
            const rawAdv = parseFloat(customer.total_advance_credit);
            const adv = Number.isFinite(rawAdv) ? rawAdv : 0;
            if (adv > 0.005) {
                return adv;
            }
            const rawBal = parseFloat(customer.current_balance);
            if (Number.isFinite(rawBal) && rawBal < -0.005) {
                return Math.abs(rawBal);
            }
            return 0;
        }

        function posApplyLedgerFieldsToOption(option, customer) {
            if (!option || !customer) {
                return;
            }
            const lb = parseFloat(customer.current_balance);
            const ledgerBalance = Number.isFinite(lb) ? lb : 0;
            const adv = posLedgerAdvanceFromCustomerPayload(customer);
            option.attr('data-ledger-balance', ledgerBalance);
            option.attr('data-ledger-advance', adv);
            option.data('ledger_balance', ledgerBalance);
            option.data('ledger_advance', adv);
        }

        window.posLedgerAdvanceFromCustomerPayload = posLedgerAdvanceFromCustomerPayload;

        function fetchCustomerData() {
            // Prevent multiple simultaneous loads
            if (window.customerDataLoading) {
                console.log('⏸️ Customer data already loading, skipping duplicate request');
                return $.Deferred().reject('Already loading').promise();
            }

            window.customerDataLoading = true;
            console.log('📥 Fetching customer data from /customer-get-all');

            return $.ajax({
                url: '/customer-get-all',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const customerSelect = $('#customer-id');

                    // Don't clear dropdown if it's already been populated by route filtering
                    if (window.salesRepCustomersLoaded) {
                        console.log('⏭️ Customers already loaded by route filtering, skipping');
                        return;
                    }

                    customerSelect.empty();

                    if (data && data.status === 200 && Array.isArray(data.message)) {
                        window.posShowRepInvoiceDue = !!data.show_rep_invoice_due;
                        window.posCustomerLedgerMap = {};
                        const sortedCustomers = data.message.sort((a, b) => {
                            if (a.first_name === 'Walk-in') return -1;
                            if (b.first_name === 'Walk-in') return 1;
                            return 0;
                        });

                        const optionNodes = [];
                        sortedCustomers.forEach(customer => {
                            const optionNode = document.createElement('option');
                            optionNode.value = customer.id;
                            const option = $(optionNode);

                            if (customer.first_name === 'Walk-in') {
                                optionNode.textContent = `${customer.first_name || ''} ${customer.last_name || ''}`;
                                optionNode.setAttribute('data-customer-type', 'retailer'); // Walk-in customer is always retailer
                            } else {
                                const customerType = customer.customer_type
                                    ? ` - ${customer.customer_type.charAt(0).toUpperCase() + customer.customer_type.slice(1)}`
                                    : '';
                                optionNode.textContent = `${customer.first_name || ''} ${customer.last_name || ''}${customerType} (${customer.mobile_no || ''})`;
                                optionNode.setAttribute('data-customer-type', customer.customer_type || 'retailer');
                            }

                            option.data('due', customer.current_due || 0); // Default due to 0
                            option.data('credit_limit', customer.credit_limit || 0); // Add credit limit data
                            option.data('myInvoiceDue', parseFloat(customer.my_invoice_due) || 0);
                            posApplyLedgerFieldsToOption(option, customer);
                            const cid = String(customer.id || '');
                            if (cid) {
                                const lb = parseFloat(customer.current_balance);
                                const advEff = posLedgerAdvanceFromCustomerPayload(customer);
                                window.posCustomerLedgerMap[cid] = {
                                    lb: Number.isFinite(lb) ? lb : 0,
                                    adv: Number.isFinite(advEff) ? advEff : 0
                                };
                            }
                            optionNodes.push(optionNode);
                        });
                        customerSelect.append(optionNodes);

                        // Always select Walking Customer by default
                        const walkingCustomer = sortedCustomers.find(customer => customer
                            .first_name === 'Walk-in');
                        if (walkingCustomer) {
                            customerSelect.val(walkingCustomer.id);
                            updateDueAmount(walkingCustomer.current_due || 0);
                            updateCreditLimit(walkingCustomer.credit_limit || 0, walkingCustomer
                                .current_due || 0, true); // true for isWalkIn
                            customerSelect.trigger('change');
                        }
                    } else {
                        console.error('Failed to fetch customer data:', data ? data.message :
                            'No data received');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching customer data:', error);
                },
                complete: function() {
                    window.customerDataLoading = false;
                    console.log('✅ Customer data loading completed');
                }
            });
        }


        function updateDueAmount(dueAmount) {
            // Ensure dueAmount is a valid number before calling toFixed
            dueAmount = isNaN(dueAmount) ? 0 : dueAmount;
            $('#total-due-amount').text(`Rs. ${dueAmount.toFixed(2)}`);
        }

        function updateRepInvoiceDueRow(myInvoiceDue, visible) {
            const $row = $('#rep-my-invoices-due-row');
            if (!$row.length) {
                return;
            }
            myInvoiceDue = isNaN(myInvoiceDue) ? 0 : parseFloat(myInvoiceDue);
            const repDueEnabled = !!window.posShowRepInvoiceDue || !!isSalesRep || !!window.isSalesRep;
            if (visible && repDueEnabled) {
                $('#rep-my-invoices-due-amount').text(`Rs. ${myInvoiceDue.toFixed(2)}`);
                $row.show();
            } else {
                $row.hide();
            }
        }

        function updateCreditLimit(creditLimit, dueAmount = 0, isWalkIn = false) {
            const creditInfoContainer = $('.customer-credit-info');
            const creditLimitElement = $('#credit-limit-amount');
            const availableCreditElement = $('#available-credit-amount');

            if (isWalkIn) {
                // Hide entire credit info section for walk-in customers
                creditInfoContainer.hide();
                updateRepInvoiceDueRow(0, false);
            } else {
                // Show credit info section for other customers
                creditLimit = isNaN(creditLimit) ? 0 : parseFloat(creditLimit);
                dueAmount = isNaN(dueAmount) ? 0 : parseFloat(dueAmount);

                const remainingCredit = Math.max(creditLimit - dueAmount, 0);
                const isOverLimit = dueAmount > creditLimit;

                // Update credit limit display
                creditLimitElement.text(`Rs. ${creditLimit.toFixed(2)}`);

                // Update available credit display with appropriate styling
                if (isOverLimit) {
                    const overAmount = dueAmount - creditLimit;
                    availableCreditElement.html(
                        `<span class="text-danger">⚠️ Over by Rs. ${overAmount.toFixed(2)}</span>`);
                } else {
                    availableCreditElement.html(
                        `<span class="text-success">✓ Rs. ${remainingCredit.toFixed(2)}</span>`);
                }

                creditInfoContainer.show();
            }
        }

        $('#customer-id').off('change.posAdvanceUi').on('change.posAdvanceUi', function() {
            const selectedOption = $(this).find('option:selected');
            const customerId = selectedOption.val();
            const customerText = selectedOption.text().toLowerCase();

            // No customer chosen — hide due/credit strip (avoids empty Rs 0.00 block + saves space)
            if (customerId === '' || customerId === null || customerId === undefined) {
                $('.customer-credit-info').hide();
                updateRepInvoiceDueRow(0, false);
                return;
            }

            const dueAmount = selectedOption.data('due') || 0;
            const creditLimit = selectedOption.data('credit_limit') || 0;
            const myInvoiceDue = selectedOption.data('myInvoiceDue') || 0;

            // Check if it's walk-in customer (ID = 1 or text contains 'walk-in')
            const isWalkIn = customerId === '1' || customerText.includes('walk-in');

            updateDueAmount(dueAmount);
            updateCreditLimit(creditLimit, dueAmount, isWalkIn);
            updateRepInvoiceDueRow(myInvoiceDue, !isWalkIn);
            if (typeof window.posRefreshAdvanceApplyUi === 'function') {
                window.posRefreshAdvanceApplyUi();
                setTimeout(function() {
                    if (typeof window.posRefreshAdvanceApplyUi === 'function') {
                        window.posRefreshAdvanceApplyUi();
                    }
                }, 60);
            }
        });

        // City filter change event
        $('#cityFilter').on('change', function() {
            $('#customer').DataTable().ajax.reload();
        });

        // Apply filter button
        $('#applyFilterButton').on('click', function() {
            $('#customer').DataTable().ajax.reload();
            toastr.success('Filter applied successfully', 'Success');
        });

        // Clear filter button
        $('#clearFilterButton').on('click', function() {
            $('#cityFilter').val('').trigger('change');
            $('#customer').DataTable().ajax.reload();
            toastr.info('Filter cleared', 'Info');
        });

        // Export PDF button
        $('#exportPdfButton').on('click', function() {
            var table = $('#customer').DataTable();
            table.button('.buttons-pdf').trigger();
        });


        window.customerFunctions = {
            fetchCustomerData: fetchCustomerData,
            // other functions NOT exposed unless added here
        };

        function getPosBillFinalTotal() {
            if (typeof window.getPosLiveFinalTotalFromCart === 'function') {
                const live = window.getPosLiveFinalTotalFromCart();
                if (typeof live === 'number' && !Number.isNaN(live)) {
                    return Math.max(0, live);
                }
            }

            const parse = window.Pos && window.Pos.Utils && typeof window.Pos.Utils.parseFormattedAmount === 'function'
                ? window.Pos.Utils.parseFormattedAmount
                : function (s) {
                    return parseFloat(String(s || '').replace(/[\s\u00a0\u202f]/g, '').replace(/,/g, '').replace(/[^0-9.-]/g, '')) || 0;
                };

            const tryEl = (el) => {
                if (!el) return 0;
                const raw = (el.textContent || el.innerText || '').trim();
                return parse(raw) || 0;
            };

            let v = tryEl(document.getElementById('final-total-amount'));
            if (v > 0.005) return v;

            const footerAmt = document.querySelector('#total .pos-footer-amount-value');
            v = tryEl(footerAmt);
            if (v > 0.005) return v;

            v = tryEl(document.getElementById('modal-total-payable'));
            if (v > 0.005) return v;

            v = tryEl(document.getElementById('mobile-final-total-inline'));
            if (v > 0.005) return v;

            v = tryEl(document.getElementById('mobile-final-total'));
            if (v > 0.005) return v;

            v = tryEl(document.getElementById('modal-final-total'));
            if (v > 0.005) return v;

            // Last-resort fallback: derive directly from billing rows (subtotal or qty*price).
            const billingBody = document.getElementById('billing-body');
            if (billingBody) {
                let sum = 0;
                billingBody.querySelectorAll('tr').forEach(function (row) {
                    const subtotalEl = row.querySelector('.subtotal');
                    let line = parse(
                        subtotalEl && subtotalEl.getAttribute('data-total')
                            ? subtotalEl.getAttribute('data-total')
                            : (subtotalEl ? subtotalEl.textContent : '')
                    ) || 0;
                    if (line <= 0.005) {
                        const qtyEl = row.querySelector('.quantity-input');
                        const priceEl = row.querySelector('.price-input');
                        const qty = parseFloat(qtyEl && qtyEl.value ? qtyEl.value : 0) || 0;
                        const price = parseFloat(priceEl && priceEl.value ? priceEl.value : 0) || 0;
                        line = qty * price;
                    }
                    if (line > 0) sum += line;
                });
                if (sum > 0.005) return sum;
            }

            return 0;
        }

        function posReadSelectedCustomerLedger(opt, customerId) {
            let lb = parseFloat(opt.data('ledger_balance'));
            if (!Number.isFinite(lb)) {
                lb = parseFloat(opt.attr('data-ledger-balance'));
            }
            if (!Number.isFinite(lb)) {
                lb = 0;
            }
            let adv = parseFloat(opt.data('ledger_advance'));
            if (!Number.isFinite(adv)) {
                adv = parseFloat(opt.attr('data-ledger-advance'));
            }
            if (!Number.isFinite(adv)) {
                adv = 0;
            }
            const cid = String(customerId || '');
            const map = window.posCustomerLedgerMap || {};
            if (cid && map[cid]) {
                if ((Math.abs(lb) <= 0.005) && Number.isFinite(map[cid].lb)) {
                    lb = map[cid].lb;
                }
                if ((adv <= 0.005) && Number.isFinite(map[cid].adv)) {
                    adv = map[cid].adv;
                }
            }
            if (adv <= 0.005 && lb < -0.005) {
                adv = Math.abs(lb);
            }
            return { lb: lb, adv: adv };
        }

        function posRefreshAdvanceApplyUi(baseTotalOverride) {
            const row = $('#pos-advance-apply-row');
            if (!row.length) return;
            const $chk = $('#pos-use-advance-checkbox');
            const $amt = $('#pos-apply-advance-amount');

            function posUpdatePayablePreview(baseTotal) {
                let base = Number.isFinite(baseTotal) ? baseTotal : getPosBillFinalTotal();
                if (!Number.isFinite(base) || base < 0) base = 0;
                const use = $chk.is(':checked') && !$chk.prop('disabled');
                const maxA = parseFloat($amt.attr('data-max-advance') || $amt.attr('max') || '0') || 0;
                let adv = use ? (parseFloat($amt.val()) || 0) : 0;
                adv = Math.max(0, Math.min(adv, maxA, base));
                const net = Math.max(0, Math.round((base - adv) * 100) / 100);

                window.posAdvancePayablePreview = {
                    base_total: Math.round(base * 100) / 100,
                    advance_amount: Math.round(adv * 100) / 100,
                    net_payable: net
                };

                const fmtAmt = (typeof window.formatAmountWithSeparators === 'function')
                    ? window.formatAmountWithSeparators
                    : (v) => Number(v).toFixed(2);
                const footerAmt = document.querySelector('#total .pos-footer-amount-value');
                if (footerAmt) footerAmt.textContent = fmtAmt(net.toFixed(2));
                const payAmt = document.getElementById('payment-amount');
                if (payAmt) payAmt.textContent = 'Rs ' + fmtAmt(net.toFixed(2));
                const mobileInline = document.getElementById('mobile-final-total-inline');
                if (mobileInline) mobileInline.textContent = 'Rs. ' + fmtAmt(net.toFixed(2));
                const mobileFooter = document.getElementById('mobile-final-total');
                if (mobileFooter) mobileFooter.textContent = 'Rs. ' + fmtAmt(net.toFixed(2));
            }

            const customerId = $('#customer-id').val();
            const opt = $('#customer-id option:selected');
            const text = (opt.text() || '').toLowerCase();
            const isWalkIn = customerId === '1' || customerId === 1 || text.includes('walk-in');

            function posAdvanceClearHints() {
                $('#pos-advance-combined-hint').text('');
                $('#pos-advance-hint-cell').attr('title', '');
            }

            function posAdvanceSetHint(visibleLine, titleExtra) {
                $('#pos-advance-combined-hint').text(visibleLine || '');
                const full = [visibleLine, titleExtra].filter(function (x) { return x && String(x).trim(); }).join(' — ');
                $('#pos-advance-hint-cell').attr('title', full);
            }

            if (!customerId || isWalkIn) {
                row.addClass('d-none').removeClass('d-flex');
                $chk.prop('checked', false).prop('disabled', false);
                $amt.val('0').prop('disabled', true).attr('max', '0').attr('data-max-advance', '0');
                posAdvanceClearHints();
                // Walk-in / no customer: no advance UI — footer must show full bill total, not 0.
                const billTotal = Number.isFinite(baseTotalOverride) ? baseTotalOverride : getPosBillFinalTotal();
                posUpdatePayablePreview(billTotal);
                return;
            }

            const finalTotal = Number.isFinite(baseTotalOverride) ? baseTotalOverride : getPosBillFinalTotal();

            const { lb, adv } = posReadSelectedCustomerLedger(opt, customerId);
            const hasAdvanceSignal = (!Number.isNaN(adv) && adv > 0.005) || (!Number.isNaN(lb) && lb < -0.005);

            if (!hasAdvanceSignal) {
                row.addClass('d-none').removeClass('d-flex');
                $chk.prop('checked', false).prop('disabled', false);
                $amt.val('0').prop('disabled', true).attr('max', '0').attr('data-max-advance', '0');
                posAdvanceClearHints();
                posUpdatePayablePreview(finalTotal);
                return;
            }

            let maxA = 0;
            if (finalTotal > 0.005) {
                // Manual-advance policy:
                // max applicable is limited by explicit advance pool only.
                // Customer due/ledger debit must not auto-block using advance.
                if (!Number.isNaN(adv) && adv > 0.005) {
                    maxA = Math.min(finalTotal, adv);
                } else if (!Number.isNaN(lb) && lb < -0.005) {
                    // Legacy fallback for old payloads without explicit advance field.
                    maxA = Math.min(finalTotal, -lb);
                } else {
                    maxA = 0;
                }
            }

            let balHint = '';
            if (!Number.isNaN(adv) && adv > 0.005) {
                balHint = 'Pool Rs. ' + adv.toFixed(2);
            }
            if (!Number.isNaN(lb) && lb < -0.005) {
                const creditAmt = -lb;
                balHint = balHint
                    ? (balHint + ' · Credit Rs. ' + creditAmt.toFixed(2))
                    : ('Credit Rs. ' + creditAmt.toFixed(2));
            }

            row.removeClass('d-none').addClass('d-flex');

            if (finalTotal <= 0.005) {
                $chk.prop('checked', false).prop('disabled', true);
                $amt.val('0').prop('disabled', true).attr('max', '0').attr('data-max-advance', '0');
                posAdvanceSetHint('Add items first.', balHint);
                posUpdatePayablePreview(finalTotal);
                return;
            }

            if (maxA <= 0.005) {
                $chk.prop('checked', false).prop('disabled', true);
                $amt.val('0').prop('disabled', true).attr('max', '0').attr('data-max-advance', '0');
                posAdvanceSetHint('No advance for this bill.', balHint);
                posUpdatePayablePreview(finalTotal);
                return;
            }

            $chk.prop('disabled', false);
            posAdvanceSetHint('Bill Rs. ' + finalTotal.toFixed(2) + ' · max Rs. ' + maxA.toFixed(2), balHint);
            const maxStr = maxA.toFixed(2);
            const chk = $chk.is(':checked');
            $amt.attr('max', maxStr).attr('data-max-advance', maxStr).prop('disabled', !chk);

            // UX: if user enabled checkbox, auto-fill best amount (max applicable for this bill).
            if (chk) {
                $amt.val(maxStr);
            } else {
                $amt.val('0');
            }
            posUpdatePayablePreview(finalTotal);
        }
        window.posRefreshAdvanceApplyUi = posRefreshAdvanceApplyUi;

        $(document).off('change.posAdvanceCheckbox', '#pos-use-advance-checkbox')
            .on('change.posAdvanceCheckbox', '#pos-use-advance-checkbox', function() {
            const on = $(this).is(':checked');
            const $amt = $('#pos-apply-advance-amount');
            const maxA = parseFloat($amt.attr('data-max-advance') || $amt.attr('max') || '0') || 0;
            $amt.prop('disabled', !on);
            if (!on) {
                $amt.val('0');
            } else {
                // Auto-fetch to input: min(bill total, available advance)
                $amt.val(maxA.toFixed(2));
                if (typeof window.posRefreshAdvanceApplyUi === 'function') window.posRefreshAdvanceApplyUi();
            }
            if (typeof window.posRefreshAdvanceApplyUi === 'function') window.posRefreshAdvanceApplyUi();
        });

        // Prevent entering more than max advance (or negative values).
        $(document).off('input.posAdvanceAmount', '#pos-apply-advance-amount')
            .on('input.posAdvanceAmount', '#pos-apply-advance-amount', function() {
            const $el = $(this);
            const maxA = parseFloat($el.attr('data-max-advance') || $el.attr('max') || '0') || 0;
            let v = parseFloat($el.val());
            if (!Number.isFinite(v)) return;
            if (v < 0) v = 0;
            if (v > maxA) v = maxA;
            $el.val(v);
            if (typeof window.posRefreshAdvanceApplyUi === 'function') window.posRefreshAdvanceApplyUi();
        });

    });
