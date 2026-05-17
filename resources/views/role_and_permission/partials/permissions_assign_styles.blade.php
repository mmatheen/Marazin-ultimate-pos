<style>
    .permission-items-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        column-gap: 16px;
    }

    .permission-items-grid .permission-item {
        width: 100%;
        max-width: 100%;
        padding-left: 0;
        padding-right: 0;
    }

    #permissionsAssignSearch {
        max-width: 480px;
    }

    @media (max-width: 768px) {
        .permission-items-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
