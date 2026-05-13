<style>
    .reporteria-shell .reporteria-filter-card,
    .reporteria-shell .reporteria-panel-card,
    .reporteria-shell .reporteria-kpi-card,
    .reporteria-shell .reporteria-table-card {
        border: 0;
        border-radius: 1rem;
        box-shadow: 0 0.5rem 1.25rem rgba(15, 23, 42, 0.08);
    }

    .reporteria-shell .reporteria-filter-card .card-body,
    .reporteria-shell .reporteria-panel-card .card-body,
    .reporteria-shell .reporteria-table-card .card-body {
        padding: 1.5rem;
    }

    .reporteria-shell .reporteria-toolbar {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 1rem;
    }

    .reporteria-shell .reporteria-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: center;
    }

    .reporteria-shell .reporteria-actions .btn {
        min-height: 40px;
    }

    .reporteria-shell .reporteria-kpi-card .card-body {
        padding: 1.15rem 1.25rem;
    }

    .reporteria-shell .reporteria-kpi-label {
        display: block;
        margin-bottom: 0.55rem;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        line-height: 1.25;
        text-transform: uppercase;
        color: #6c757d;
    }

    .reporteria-shell .reporteria-kpi-value {
        margin: 0;
        font-size: clamp(1.7rem, 2.2vw, 2.25rem);
        line-height: 1.08;
        word-break: break-word;
        font-weight: 700;
    }

    .reporteria-shell .reporteria-table-card .card-header {
        background: #fff;
        border-bottom: 0;
        padding: 1.25rem 1.5rem 0;
    }

    .reporteria-shell .reporteria-table-card .card-title,
    .reporteria-shell .reporteria-panel-card .card-title {
        margin: 0;
        font-size: 1rem;
        font-weight: 700;
    }

    .reporteria-shell .reporteria-empty-state {
        margin-top: 1rem;
        padding: 1rem;
        border: 1px solid rgba(13, 110, 253, 0.15);
        border-radius: 1rem;
        background: #f8fafc;
    }

    .reporteria-shell .reporteria-empty-state-title {
        margin-bottom: 0.25rem;
        font-weight: 700;
        color: #0d6efd;
    }

    .reporteria-shell .reporteria-filter-summary {
        margin-top: 1rem;
        padding: 1rem;
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #f8fafc;
    }

    .reporteria-shell .reporteria-filter-summary-badges {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        align-items: center;
    }

    .reporteria-shell .reporteria-table-responsive {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        overflow: hidden;
        background: #fff;
    }

    .reporteria-shell .reporteria-table-responsive table {
        margin-bottom: 0;
    }

    @media (max-width: 767.98px) {
        .reporteria-shell .reporteria-filter-card .card-body,
        .reporteria-shell .reporteria-panel-card .card-body,
        .reporteria-shell .reporteria-table-card .card-body {
            padding: 1rem;
        }

        .reporteria-shell .reporteria-table-card .card-header {
            padding: 1rem 1rem 0;
        }

        .reporteria-shell .reporteria-actions,
        .reporteria-shell .reporteria-toolbar {
            width: 100%;
        }

        .reporteria-shell .reporteria-actions .btn {
            flex: 1 1 100%;
        }
    }
</style>