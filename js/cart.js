(function () {
    'use strict';

    var fallbackImage = 'images/img-pro-01.jpg';

    var fetchCart = function () {
        return fetch('php/cart.php?action=list', {
            credentials: 'include'
        }).then(function (res) {
            if (!res.ok) throw new Error('No se pudo obtener el carrito');
            return res.json();
        });
    };

    var renderMiniCart = function (cart) {
        var sideMenu = document.querySelector('.side .cart-list');
        var badge = document.querySelector('.attr-nav .badge');
        if (!sideMenu) return;

        sideMenu.innerHTML = '';
        var items = (cart && cart.items) || [];
        if (!items.length) {
            sideMenu.innerHTML = '<li><p>Tu carrito está vacío.</p></li>';
        } else {
            items.slice(0, 3).forEach(function (item) {
                var li = document.createElement('li');
                li.innerHTML = '<a href="shop-detail.html?id=' + item.libros_id + '" class="photo"><img src="' + (item.portada_url || fallbackImage) + '" class="cart-thumb" alt=""></a>' +
                    '<h6><a href="shop-detail.html?id=' + item.libros_id + '">' + item.titulo + '</a></h6>' +
                    '<p>' + item.cantidad + 'x - <span class="price">$' + item.precio.toFixed(2) + '</span></p>';
                sideMenu.appendChild(li);
            });
            var totalLi = document.createElement('li');
            totalLi.className = 'total';
            totalLi.innerHTML = '<a href="cart.html" class="btn btn-default hvr-hover btn-cart">Ver carrito</a>' +
                '<span class="float-right"><strong>Total</strong>: $' + (cart.totals ? cart.totals.subtotal.toFixed(2) : '0.00') + '</span>';
            sideMenu.appendChild(totalLi);
        }
        if (badge) {
            badge.textContent = cart && cart.totals ? cart.totals.quantity : 0;
        }
    };

    var renderCartPage = function (cart) {
        var tbody = document.getElementById('cart-items-body');
        var totalsBox = document.getElementById('cart-totals');
        var emptyAlert = document.getElementById('cart-empty');
        if (!tbody || !totalsBox) return;

        tbody.innerHTML = '';
        var items = (cart && cart.items) || [];
        if (!items.length) {
            if (emptyAlert) emptyAlert.style.display = 'block';
            totalsBox.innerHTML = '<p class="text-center">No hay productos en tu carrito.</p>';
            return;
        }
        if (emptyAlert) emptyAlert.style.display = 'none';

        items.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="thumbnail-img"><a href="shop-detail.html?id=' + item.libros_id + '"><img class="img-fluid" src="' + (item.portada_url || fallbackImage) + '" alt=""></a></td>' +
                '<td class="name-pr"><a href="shop-detail.html?id=' + item.libros_id + '">' + item.titulo + '</a></td>' +
                '<td class="price-pr"><p>$ ' + item.precio.toFixed(2) + '</p></td>' +
                '<td class="quantity-box"><input data-libros-id="' + item.libros_id + '" class="c-input-text qty text cart-qty" type="number" value="' + item.cantidad + '" min="0" step="1"></td>' +
                '<td class="total-pr"><p>$ ' + item.subtotal.toFixed(2) + '</p></td>' +
                '<td class="remove-pr"><a href="#" class="btn-remove" data-libros-id="' + item.libros_id + '"><i class="fas fa-times"></i></a></td>';
            tbody.appendChild(tr);
        });

        var totals = cart.totals || { subtotal: 0 };
        totalsBox.innerHTML =
            '<div class="order-box">' +
            '<h3>Resumen</h3>' +
            '<div class="d-flex"><h4>Subtotal</h4><div class="ml-auto font-weight-bold"> $ ' + totals.subtotal.toFixed(2) + ' </div></div>' +
            '<div class="d-flex"><h4>Envío</h4><div class="ml-auto font-weight-bold"> $ 0.00 </div></div>' +
            '<hr>' +
            '<div class="d-flex gr-total"><h5>Total</h5><div class="ml-auto h5">$ ' + totals.subtotal.toFixed(2) + '</div></div>' +
            '<hr>' +
            '</div>';
    };

    var addToCart = function (bookId, qty) {
        return fetch('php/cart.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=add&libro_id=' + encodeURIComponent(bookId) + '&cantidad=' + encodeURIComponent(qty || 1)
        }).then(function (res) {
            if (res.status === 401) {
                window.location.href = 'login.html';
                return Promise.reject(new Error('No autenticado'));
            }
            if (!res.ok) {
                return res.json().catch(function(){ return {}; }).then(function(body){
                    var msg = body && body.error ? body.error : 'No se pudo agregar al carrito';
                    throw new Error(msg);
                });
            }
            return res.json();
        });
    };

    var updateQuantity = function (bookId, qty) {
        return fetch('php/cart.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=update&libro_id=' + encodeURIComponent(bookId) + '&cantidad=' + encodeURIComponent(qty)
        }).then(function (res) {
            if (res.status === 401) {
                window.location.href = 'login.html';
                return Promise.reject(new Error('No autenticado'));
            }
            if (!res.ok) throw new Error('No se pudo actualizar el carrito');
            return res.json();
        });
    };

    var removeItem = function (bookId) {
        return fetch('php/cart.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove&libro_id=' + encodeURIComponent(bookId)
        }).then(function (res) {
            if (res.status === 401) {
                window.location.href = 'login.html';
                return Promise.reject(new Error('No autenticado'));
            }
            if (!res.ok) throw new Error('No se pudo eliminar el producto');
            return res.json();
        });
    };

    var initAddToCartButton = function () {
        var btn = document.getElementById('add-to-cart-btn');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var bookId = btn.getAttribute('data-book-id');
            if (!bookId) return;
            addToCart(bookId, 1).then(function (payload) {
                renderMiniCart(payload.cart || payload);
                alert('Producto agregado al carrito');
            }).catch(function (err) {
                console.error(err);
                alert('No se pudo agregar al carrito');
            });
        });
    };

    var initCartPage = function () {
        var tbody = document.getElementById('cart-items-body');
        if (!tbody) return;
        fetchCart().then(function (cart) {
            renderCartPage(cart);
            renderMiniCart(cart);
        }).catch(function (err) {
            console.error(err);
        });

        tbody.addEventListener('change', function (e) {
            var target = e.target;
            if (target && target.classList.contains('cart-qty')) {
                var bookId = target.getAttribute('data-libros-id');
                var qty = parseInt(target.value || '0', 10);
                updateQuantity(bookId, qty).then(function (payload) {
                    renderCartPage(payload.cart || payload);
                    renderMiniCart(payload.cart || payload);
                }).catch(function (err) {
                    console.error(err);
                    alert(err.message || 'No se pudo actualizar la cantidad');
                });
            }
        });

        tbody.addEventListener('click', function (e) {
            var target = e.target.closest('.btn-remove');
            if (target) {
                e.preventDefault();
                var bookId = target.getAttribute('data-libros-id');
                removeItem(bookId).then(function (payload) {
                    renderCartPage(payload.cart || payload);
                    renderMiniCart(payload.cart || payload);
                }).catch(function (err) {
                    console.error(err);
                    alert(err.message || 'No se pudo eliminar el producto');
                });
            }
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        // Actualizar mini carrito en cualquier página
        fetchCart().then(renderMiniCart).catch(function () { /* silencioso */ });

        initAddToCartButton();
        initCartPage();

        // Toggle del side-menu para ver el carrito
        var sidePanel = document.querySelector('.side');
        var closeSide = sidePanel ? sidePanel.querySelector('.close-side') : null;
        var sideLinks = document.querySelectorAll('.side-menu > a, .attr-nav .side-menu > a');

        // Botón de colapso adicional dentro del panel
        if (sidePanel && !sidePanel.querySelector('.side-close-btn')) {
            var extraClose = document.createElement('button');
            extraClose.type = 'button';
            extraClose.className = 'side-close-btn btn btn-secondary btn-sm';
            extraClose.textContent = 'Cerrar';
            extraClose.addEventListener('click', function (e) {
                e.preventDefault();
                sidePanel.classList.remove('open');
            });
            // Insertar al inicio del panel
            sidePanel.insertBefore(extraClose, sidePanel.firstChild);
        }

        if (sidePanel && sideLinks.length) {
            sideLinks.forEach(function (link) {
                link.addEventListener('click', function (e) {
                    e.preventDefault();
                    sidePanel.classList.add('open');
                });
            });
        }
        if (closeSide && sidePanel) {
            closeSide.addEventListener('click', function (e) {
                e.preventDefault();
                sidePanel.classList.remove('open');
            });
        }
    });

})();

