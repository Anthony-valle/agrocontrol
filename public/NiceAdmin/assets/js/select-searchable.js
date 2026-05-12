(function () {
    "use strict";

    function shouldSkipSelect(select) {
        if (!select || select.dataset.noSearch === "true" || select.classList.contains("no-search")) {
            return true;
        }

        // Keep dependent selects in native mode unless explicitly forced.
        if (select.hasAttribute("onchange") && !select.classList.contains("searchable-select") && !select.classList.contains("select2")) {
            return true;
        }

        const id = (select.id || "").toLowerCase();
        const name = (select.getAttribute("name") || "").toLowerCase();

        // Keep pagination and tiny utility selectors as native selects.
        if (["customperpage", "selectperpage", "perpage"].includes(id)) {
            return true;
        }

        if (["perpage", "customperpage", "per_page"].includes(name)) {
            return true;
        }

        if (select.closest(".datatable-pagination") || select.closest(".table-pagination-meta")) {
            return true;
        }

        return false;
    }

    function initSearchableSelects(root) {
        if (!window.jQuery || !jQuery.fn || !jQuery.fn.select2) {
            return;
        }

        const scope = root || document;
        // Safe mode: only initialize selects explicitly marked for search.
        const selectList = scope.querySelectorAll("select.searchable-select, select.select2, select[data-search='true']");

        selectList.forEach(function (select) {
            if (shouldSkipSelect(select)) {
                return;
            }

            if (select.classList.contains("select2-hidden-accessible")) {
                return;
            }

            const placeholder = select.getAttribute("data-placeholder") || "Escribe para buscar...";
            const inModal = select.closest(".modal");

            jQuery(select).select2({
                theme: "bootstrap-5",
                width: "100%",
                placeholder: placeholder,
                allowClear: !select.required,
                minimumResultsForSearch: 0,
                dropdownParent: inModal ? jQuery(inModal) : jQuery(document.body)
            });
        });
    }

    document.addEventListener("DOMContentLoaded", function () {
        initSearchableSelects(document);

        document.addEventListener("shown.bs.modal", function (event) {
            initSearchableSelects(event.target || document);
        });

        const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType !== 1) {
                        return;
                    }

                    if (node.matches && node.matches("select")) {
                        initSearchableSelects(node.parentElement || document);
                        return;
                    }

                    if (node.querySelector && node.querySelector("select")) {
                        initSearchableSelects(node);
                    }
                });
            });
        });

        observer.observe(document.body, {
            childList: true,
            subtree: true
        });
    });
})();
