(function () {
    'use strict';

    var fallbackImage = 'images/img-pro-01.jpg';
    var cartData = null;

    function fetchCart() {
        return fetch('php/cart.php?action=list', { credentials: 'include' })
            .then(function (res) {
                if (res.status === 401) {
                    window.location.href = 'login.html';
                    return Promise.reject(new Error('No autenticado'));
                }
                if (!res.ok) throw new Error('No se pudo obtener el carrito');
                return res.json();
            });
    }

    function renderCart(cart) {
        var tbody = document.getElementById('checkout-items-body');
        var empty = document.getElementById('checkout-empty');
        var totalBox = document.getElementById('checkout-total');
        if (!tbody || !totalBox) return;

        tbody.innerHTML = '';
        var items = (cart && cart.items) || [];
        if (!items.length) {
            if (empty) empty.style.display = 'block';
            totalBox.textContent = '$ 0.00';
            return;
        }
        if (empty) empty.style.display = 'none';

        items.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="thumbnail-img"><a href="shop-detail.html?id=' + item.libros_id + '"><img class="img-fluid" src="' + (item.portada_url || fallbackImage) + '" alt=""></a></td>' +
                '<td class="name-pr"><a href="shop-detail.html?id=' + item.libros_id + '">' + item.titulo + '</a></td>' +
                '<td class="price-pr"><p>$ ' + item.precio.toFixed(2) + '</p></td>' +
                '<td class="quantity-box text-center">' + item.cantidad + '</td>' +
                '<td class="total-pr"><p>$ ' + item.subtotal.toFixed(2) + '</p></td>';
            tbody.appendChild(tr);
        });

        var total = cart.totals ? cart.totals.subtotal : 0;
        totalBox.textContent = '$ ' + (total || 0).toFixed(2);
    }

    function getPaymentMethod() {
        var checked = document.querySelector('input[name="paymentMethod"]:checked');
        return checked ? checked.id : '';
    }

    function placeOrder() {
        var btn = document.getElementById('place-order-btn');
        if (!btn) return;

        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var metodo = getPaymentMethod();
            var direccion = (document.getElementById('address-full') || {}).value || '';

            if (!cartData || !cartData.items || !cartData.items.length) {
                alert('Tu carrito está vacío.');
                return;
            }
            if (!direccion.trim()) {
                alert('Ingresa una dirección válida antes de pagar.');
                return;
            }

            btn.disabled = true;
            btn.textContent = 'Procesando...';

            var body = new URLSearchParams();
            body.append('metodo_pago', metodo);
            body.append('direccion_envio', direccion.trim());

            fetch('php/checkout.php', {
                method: 'POST',
                credentials: 'include',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: body.toString()
            }).then(function (res) {
                if (res.status === 401) {
                    window.location.href = 'login.html';
                    return Promise.reject(new Error('No autenticado'));
                }
                if (!res.ok) {
                    return res.json().catch(function () { return {}; }).then(function (data) {
                        throw new Error(data.error || 'No se pudo procesar el pago');
                    });
                }
                return res.json();
            }).then(function (data) {
                if (!data || !data.success) {
                    throw new Error(data && data.error ? data.error : 'No se pudo completar el pedido');
                }
                alert('Pedido confirmado. ID #' + data.pedido_id + '. Puntos ganados: ' + (data.puntos || 0));
                window.location.href = 'my-account.html';
            }).catch(function (err) {
                console.error(err);
                alert(err.message || 'Ocurrió un error al pagar');
            }).finally(function () {
                btn.disabled = false;
                btn.textContent = 'Realizar pedido';
            });
        });
    }

    function init() {
        fetchCart().then(function (cart) {
            cartData = cart;
            renderCart(cart);
        }).catch(function (err) {
            console.error(err);
            alert(err.message || 'No se pudo cargar el carrito');
        });
        placeOrder();
    }

    document.addEventListener('DOMContentLoaded', init);
})();

