(function() {
    'use strict';

    var orderItems = [];

    function addToOrder() {
        var select = document.getElementById('variation_id');
        var qtyInput = document.getElementById('item_quantity');
        var option = select.options[select.selectedIndex];
        if (!option || !option.value) {
            alert('Выберите товар');
            return;
        }
        var variationId = parseInt(option.value, 10);
        var quantity = parseInt(qtyInput.value, 10) || 0;
        var stock = parseInt(option.dataset.quantity || 0, 10);
        var price = parseFloat(option.dataset.price || 0);
        var label = option.dataset.label || option.textContent;

        if (quantity < 1) {
            alert('Введите количество');
            return;
        }
        if (quantity > stock) {
            alert('Недостаточно на складе. Остаток: ' + stock);
            return;
        }

        orderItems.push({ variation_id: variationId, quantity: quantity, price: price, label: label });
        renderOrderItems();
        updateTotal();
        document.getElementById('orderItemsCard').style.display = 'block';
        document.getElementById('submitBtn').disabled = false;
    }

    function escapeHtml(s) {
        var div = document.createElement('div');
        div.textContent = s;
        return div.innerHTML;
    }

    function renderOrderItems() {
        var container = document.getElementById('orderItems');
        container.innerHTML = '';
        orderItems.forEach(function(item, i) {
            var div = document.createElement('div');
            div.className = 'variation-item';
            div.innerHTML =
                '<div class="field">' +
                    '<input type="hidden" name="variation_id[]" value="' + item.variation_id + '">' +
                    '<input type="hidden" name="quantity[]" value="' + item.quantity + '">' +
                    '<input type="hidden" name="price[]" value="' + item.price + '">' +
                    '<span>' + escapeHtml(item.label) + ' × ' + item.quantity + ' = ' + (item.quantity * item.price).toFixed(2) + ' ₽</span>' +
                '</div>' +
                '<button type="button" class="btn-remove btn-remove-item" data-index="' + i + '">×</button>';
            container.appendChild(div);
        });
    }

    function updateTotal() {
        var total = orderItems.reduce(function(sum, item) {
            return sum + item.quantity * item.price;
        }, 0);
        document.getElementById('totalPrice').textContent = 'Итого: ' + total.toFixed(2) + ' ₽';
    }

    function removeOrderItem(index) {
        orderItems.splice(index, 1);
        renderOrderItems();
        updateTotal();
        if (orderItems.length === 0) {
            document.getElementById('orderItemsCard').style.display = 'none';
            document.getElementById('submitBtn').disabled = true;
        }
    }

    document.getElementById('addToOrder').addEventListener('click', addToOrder);

    document.getElementById('orderItems').addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-remove-item')) {
            removeOrderItem(parseInt(e.target.dataset.index, 10));
        }
    });

    document.getElementById('orderForm').addEventListener('submit', function(e) {
        if (orderItems.length === 0) {
            e.preventDefault();
            alert('Добавьте товары в заказ');
            return false;
        }
    });
})();
