<script>
    $(document).ready(function() {
        function filterPermissionGroups() {
            var term = $('#permissionsAssignSearch').val().trim().toLowerCase();
            var visibleGroups = 0;

            if (!term) {
                $('.permission-group-row').show();
                $('.permission-item').show();
                $('#permissionsAssignNoResults').hide();
                return;
            }

            $('.permission-group-row').each(function() {
                var $row = $(this);
                var groupName = String($row.data('group-name') || '').toLowerCase();
                var groupMatch = groupName.indexOf(term) !== -1;
                var $items = $row.find('.permission-item');
                var anyVisible = false;

                if (groupMatch) {
                    $items.show();
                    anyVisible = true;
                } else {
                    $items.each(function() {
                        var permName = String($(this).data('permission-name') || '').toLowerCase();
                        var match = permName.indexOf(term) !== -1;
                        $(this).toggle(match);
                        if (match) {
                            anyVisible = true;
                        }
                    });
                }

                $row.toggle(anyVisible);
                if (anyVisible) {
                    visibleGroups++;
                }
            });

            $('#permissionsAssignNoResults').toggle(visibleGroups === 0);
        }

        $('#permissionsAssignSearch').on('input', filterPermissionGroups);
    });
</script>
