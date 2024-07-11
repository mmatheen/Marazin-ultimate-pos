
<!-- this below script will come at the last or after script link -->
<script type="text/javascript">

    $(document).ready(function () {

        showFetchData();

        //show data code start
        function showFetchData() {
        $.ajax({
            url: '/warranty-get-all',
            type: 'GET',
            dataType: 'json',
            success: function(response) {
            var tbody = $('#warranty tbody'); // Get reference to the table body
            var table = $('#warranty').DataTable(); // Get reference to the DataTable
            table.clear().draw(); // Clear existing rows

            response.message.forEach(function(item) {
            let exampleRow = $('<tr>');
            exampleRow.append('<td>' + item.id + '</td>');
            exampleRow.append('<td>' + item.name + '</td>');
            exampleRow.append('<td>' + item.duration + '</td>');
            exampleRow.append('<td>' + item.duration_type + '</td>');
            exampleRow.append('<td>' + item.description + '</td>');
            exampleRow.append('<td><button type="button" value="' + item.id + '" class="edit_btn btn btn-outline-info btn-rounded"><i class="feather-edit"></i></button></td>');
            exampleRow.append('<td><button type="button" value="' + item.id + '" class="delete_btn btn btn-outline-danger btn-rounded"> <i class="feather-trash-2 me-1"></i></button></td>');
            table.row.add(exampleRow).draw(false);
        });
     },

        });
     }


     //insert code start

    $(document).on('submit', '#addAndEditForm', function(e) {
        e.preventDefault();

        let formData = new FormData($('#addAndEditForm')[0]);
        $.ajax({
            url: 'warranty-store', // Ensure this line generates the correct URL
            type: 'post',
            data: formData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        // Update the error message directly under each input field
                        $('#' + key + '_error').html(err_value);
                    });
                } else if (response.status == 200) {
                    $("#addAndEditForm")[0].reset(); // Reset the form
                    $('#addAndEditModal').modal('hide');
                    showFetchData();   // Call showFetchData() after successful insertion
                    //alert(response.message);
                    alertify.set('notifier','position', 'top-right');
                    alertify.success(response.message);
                }
            }
        });
    });

     //insert code end

     //edit code start
     $(document).on('click', '.edit_btn', function(e) {
        e.preventDefault();
        var id=$(this).val();
         $('#editModal').modal('show');
         $.ajax({
            url: 'warranty-edit/'+id,
            type: 'get',
            success: function(response) {
                if (response.status == 404) {
                    $('#editModal').modal('hide');
                    alertify.set('notifier','position', 'top-right');
                    alertify.success(response.message);

                } else if (response.status == 200) {
                  $('#edit_id').val(id);
                  $('#edit_courseName').val(response.message.courseName);
                  $('#edit_shortName').val(response.message.shortName);
                  $('#edit_batch').val(response.message.batch);
                  $('#edit_duration').val(response.message.duration);
                  $('#edit_amount').val(response.message.amount);

                }
            }
        });
     });
      //edit code end


    //update code start
     $(document).on('submit', '#updateForm', function(e) {
        e.preventDefault();
        var id=$('#edit_id').val();
        let editFormData = new FormData($('#updateForm')[0]);
        $.ajax({
            url: 'warranty-update/'+id,
            type: 'post',
            data: editFormData,
            contentType: false,
            processData: false,
            dataType: 'json',
            success: function(response) {
                if (response.status == 400) {
                    $.each(response.errors, function(key, err_value) {
                        // Update the error message directly under each input field
                        $('#' + key + '_updateError').html(err_value);
                    });
                } else if (response.status == 200) {
                    $("#updateForm")[0].reset(); // Reset the form
                    $('#editModal').modal('hide');
                    showFetchData();   // Call showFetchData() after successful insertion
                    alertify.set('notifier','position', 'top-right');
                    alertify.success(response.message);
                }
            }
        });
    });
     // update code end


     // delete start
     $(document).on('click', '.delete_btn', function(e) {
        e.preventDefault();
        var id=$(this).val();
        $('#deleteModal').modal('show');
        $('#deleting_id').val(id);
     });

     $(document).on('click', '.confirm_delete_btn', function(e) {
        e.preventDefault();
        var id=$('#deleting_id').val();
        // Fetch CSRF token from meta tag
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        $.ajax({
            url: 'warranty-delete/'+id,
            type: 'delete',
            dataType: 'json',
            headers: {'X-CSRF-TOKEN': csrfToken},
            success: function(response) {
                if (response.status == 404) {

                    alert(response.message);
                    $('#deleteModal').modal('hide');
                }

                else if (response.status == 200) {
                    $('#deleteModal').modal('hide');
                    showFetchData();
                    //alert(response.message);
                    alertify.set('notifier','position', 'top-right');
                    alertify.success(response.message);
                }
            }
        });

     });

     //delete code end

    });
</script>
