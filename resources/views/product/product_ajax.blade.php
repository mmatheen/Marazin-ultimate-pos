<div class="modal fade" id="viewProductModal" tabindex="-1" aria-labelledby="viewProductModalLabel" aria-hidden="true">
    <div class="modal-dialog lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="viewProductModalLabel">Product Details</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="productDetails">
          <!-- Modal content will be dynamically inserted here -->
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
        </div>
      </div>
    </div>
  </div>
  

<script>
  let categoryMap = {};
let brandMap = {};
let locationMap = {};

// Fetch categories, brands, and locations on page load
function fetchCategoriesAndBrands() {
    $.ajax({
        url: '/main-category-get-all',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            response.message.forEach(function(category) {
                categoryMap[category.id] = category.mainCategoryName;
            });
        },
        error: function() {
            console.error('Error loading categories');
        }
    });

    $.ajax({
        url: '/brand-get-all',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            response.message.forEach(function(brand) {
                brandMap[brand.id] = brand.name;
            });
        },
        error: function() {
            console.error('Error loading brands');
        }
    });

    // Fetch location data
    $.ajax({
        url: '/location-get-all',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 200) {
                response.message.forEach(function(location) {
                    locationMap[location.id] = location.name; // Store location name with ID as key
                });
            } else {
                console.error('Failed to load location data. Status: ' + response.status);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching location data:', error);
        }
    });
}

// Fetch and display product data
function showFetchData() {
    $.ajax({
        url: '/product-get-all',
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 200) {
                let table = $('#productTable').DataTable();
                table.clear().draw();

                response.message.forEach(function(item) {
                    let row = $('<tr>');
                    row.append('<td><input type="checkbox" class="checked" /></td>');
                    row.append('<td><img src="/assets/images/' + item.product_image + '" alt="' + item.product_name + '" width="50" height="70" /></td>');
                    row.append(`
                        <td>
                            <div class="dropdown dropdown-action">
                                <a href="#" class="action-icon dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                                    <button type="button" class="btn btn-outline-info">Actions &nbsp;<i class="fas fa-sort-down"></i></button>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end">
                                    <a class="dropdown-item" href="#"><i class="fas fa-barcode"></i>&nbsp;Labels</a>
                                    <a class="dropdown-item" href="#" data-bs-toggle="modal" data-bs-target="#viewProductModal" data-id="${item.id}"><i class="fas fa-eye"></i>&nbsp;&nbsp;View</a>
                                    <a class="dropdown-item" href="#"><i class="far fa-edit me-2"></i>&nbsp;&nbsp;Edit</a>
                                    <a class="dropdown-item" href="#"><i class="fas fa-trash"></i>&nbsp;&nbsp;Delete</a>
                                    <a class="dropdown-item" href="#"><i class="fas fa-database"></i>&nbsp;&nbsp;Add or edit opening stock</a>
                                    <a class="dropdown-item" href="#"><i class="fas fa-history"></i>&nbsp;&nbsp;Product stock history</a>
                                    <a class="dropdown-item" href="#"><i class="far fa-copy"></i>&nbsp;&nbsp;Duplicate Product</a>
                                </div>
                            </div>
                        </td>`);
                    row.append('<td>' + item.product_name + '</td>');

                    // Handle multiple locations for each product
                    let locations = item.locations.map(function(location) {
                        return locationMap[location.id] || 'N/A'; // Use locationMap to get the name
                    }).join(', '); // Join multiple locations by comma

                    row.append('<td>' + locations + '</td>');
                    row.append('<td>$' + item.retail_price + '</td>');
                    row.append('<td>' + item.alert_quantity + '</td>');
                    row.append('<td>' + item.product_type + '</td>');
                    row.append('<td>' + (categoryMap[item.main_category_id] || 'N/A') + '</td>');
                    row.append('<td>' + (brandMap[item.brand_id] || 'N/A') + '</td>');
                    row.append('<td>5%</td>'); //just input
                    row.append('<td>' + item.sku + '</td>');
                    table.row.add(row).draw(false);
                });
            } else {
                console.error('Failed to load product data. Status: ' + response.status);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching product data:', error);
        }
    });
}


// Fetch and show product details in the modal
$('#viewProductModal').on('show.bs.modal', function (event) {
    var button = $(event.relatedTarget); // Button that triggered the modal
    var productId = button.data('id'); // Extract product ID from data-id attribute
    
    // Fetch product details by ID
    $.ajax({
        url: '/product-get-details/' + productId,
        type: 'GET',
        dataType: 'json',
        success: function(response) {
            if (response.status === 200) {
                var product = response.message;
                var details = `
                    <div class="table-responsive">
    <table class="table table-bordered table-striped">
        <tbody>
            <tr>
                <td rowspan="8" class="align-middle text-center">
                    <img src='/assets/images/${product.product_image}' width='150' height='200' class="img-fluid rounded" />
                </td>
                <th scope="row">Product Name</th>
                <td>${product.product_name}</td>
            </tr>
            <tr>
                <th scope="row">SKU</th>
                <td>${product.sku}</td>
            </tr>
            <tr>
                <th scope="row">Category</th>
                <td>${categoryMap[product.main_category_id] || 'N/A'}</td>
            </tr>
            <tr>
                <th scope="row">Brand</th>
                <td>${brandMap[product.brand_id] || 'N/A'}</td>
            </tr>
            <tr>
                <th scope="row">Locations</th>
                <td>${product.locations.map(loc => locationMap[loc.id] || 'N/A').join(', ')}</td>
            </tr>
            <tr>
                <th scope="row">Price</th>
                <td>$${product.retail_price}</td>
            </tr>
            <tr>
                <th scope="row">Alert Quantity</th>
                <td>${product.alert_quantity}</td>
            </tr>
            <tr>
                <th scope="row">Product Type</th>
                <td>${product.product_type}</td>
            </tr>
        </tbody>
    </table>
</div>

                `;
                $('#productDetails').html(details);
            } else {
                console.error('Failed to load product details. Status: ' + response.status);
            }
        },
        error: function(xhr, status, error) {
            console.error('Error fetching product details:', error);
        }
    });
});

// Load data when the page is ready
$(document).ready(function() {
    fetchCategoriesAndBrands();
    showFetchData();
});

</script>
