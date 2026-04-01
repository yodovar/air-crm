(function() {
    'use strict';

    var index = 1;

    function createVariationRow() {
        var row = document.createElement('div');
        row.className = 'variation-row';
        row.dataset.index = index++;
        row.innerHTML =
            '<div class="field">' +
                '<label>Цвет</label>' +
                '<input type="text" name="color[]" placeholder="Черный">' +
            '</div>' +
            '<div class="field">' +
                '<label>Размер</label>' +
                '<input type="text" name="size[]" placeholder="M">' +
            '</div>' +
            '<div class="field">' +
                '<button type="button" class="btn-remove btn-remove-row">×</button>' +
            '</div>' +
            '<div class="field" style="grid-column: 1 / -1;">' +
                '<label>Количество</label>' +
                '<input type="number" name="quantity[]" min="0" value="0" required>' +
            '</div>' +
            '<div class="field" style="grid-column: 1 / -1;">' +
                '<label>Цена</label>' +
                '<input type="text" name="price[]" placeholder="599.00" required>' +
            '</div>';
        return row;
    }

    function updateRemoveButtons() {
        var rows = document.querySelectorAll('.variation-row');
        rows.forEach(function(row, i) {
            var btn = row.querySelector('.btn-remove-row');
            btn.disabled = rows.length <= 1;
        });
    }

    document.getElementById('addVariation').addEventListener('click', function() {
        var container = document.getElementById('variations');
        container.appendChild(createVariationRow());
        updateRemoveButtons();
    });

    document.getElementById('variations').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-row')) {
            var row = e.target.closest('.variation-row');
            if (row && document.querySelectorAll('.variation-row').length > 1) {
                row.remove();
                updateRemoveButtons();
            }
        }
    });

    updateRemoveButtons();
})();
