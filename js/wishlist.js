(function () {
    'use strict';

    var fallbackImage = 'images/img-pro-01.jpg';

    var fetchWishlist = function () {
        return fetch('php/wishlist.php?action=list', { credentials: 'include' })
            .then(function (res) {
                if (res.status === 401) {
                    window.location.href = 'login.html';
                    return Promise.reject(new Error('No autenticado'));
                }
                if (!res.ok) throw new Error('No se pudo cargar la lista de deseos');
                return res.json();
            });
    };

    var addToWishlist = function (bookId) {
        return fetch('php/wishlist.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=add&libro_id=' + encodeURIComponent(bookId)
        }).then(function (res) {
            if (res.status === 401) {
                window.location.href = 'login.html';
                return Promise.reject(new Error('No autenticado'));
            }
            if (!res.ok) {
                return res.json().catch(function(){ return {}; }).then(function(body){
                    throw new Error(body.error || 'No se pudo agregar a favoritos');
                });
            }
            return res.json();
        });
    };

    var removeFromWishlist = function (bookId) {
        return fetch('php/wishlist.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=remove&libro_id=' + encodeURIComponent(bookId)
        }).then(function (res) {
            if (res.status === 401) {
                window.location.href = 'login.html';
                return Promise.reject(new Error('No autenticado'));
            }
            if (!res.ok) throw new Error('No se pudo eliminar de favoritos');
            return res.json();
        });
    };

    var renderWishlistPage = function (items) {
        var tbody = document.getElementById('wishlist-body');
        var emptyAlert = document.getElementById('wishlist-empty');
        if (!tbody) return;

        tbody.innerHTML = '';
        if (!items || !items.length) {
            if (emptyAlert) emptyAlert.style.display = 'block';
            return;
        }
        if (emptyAlert) emptyAlert.style.display = 'none';

        items.forEach(function (item) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td class="thumbnail-img"><a href="shop-detail.html?id=' + item.libros_id + '">' +
                '<img class="img-fluid" src="' + (item.portada_url || fallbackImage) + '" alt=""></a></td>' +
                '<td class="name-pr"><a href="shop-detail.html?id=' + item.libros_id + '">' + item.titulo + '</a></td>' +
                '<td class="price-pr"><p>$ ' + (item.precio || 0).toFixed(2) + '</p></td>' +
                '<td class="quantity-box">Disponible</td>' +
                '<td class="add-pr"><a class="btn hvr-hover btn-add-cart" data-libros-id="' + item.libros_id + '" href="#">Agregar al carrito</a></td>' +
                '<td class="remove-pr"><a href="#" class="btn-remove-wish" data-libros-id="' + item.libros_id + '"><i class="fas fa-times"></i></a></td>';
            tbody.appendChild(tr);
        });
    };

    var bindWishlistPage = function () {
        var tbody = document.getElementById('wishlist-body');
        if (!tbody) return;

        fetchWishlist().then(function (payload) {
            renderWishlistPage(payload.items || []);
        }).catch(function (err) {
            console.error(err);
            alert(err.message || 'No se pudo cargar tu lista de deseos');
        });

        tbody.addEventListener('click', function (e) {
            var addCart = e.target.closest('.btn-add-cart');
            if (addCart) {
                e.preventDefault();
                var bookId = addCart.getAttribute('data-libros-id');
                // reutilizar cart.php
                fetch('php/cart.php', {
                    method: 'POST',
                    credentials: 'include',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=add&libro_id=' + encodeURIComponent(bookId) + '&cantidad=1'
                }).then(function (res) {
                    if (res.status === 401) {
                        window.location.href = 'login.html';
                        return Promise.reject(new Error('No autenticado'));
                    }
                    if (!res.ok) throw new Error('No se pudo agregar al carrito');
                    return res.json();
                }).then(function () {
                    alert('Agregado al carrito');
                }).catch(function (err) {
                    console.error(err);
                    alert(err.message || 'No se pudo agregar al carrito');
                });
                return;
            }

            var removeBtn = e.target.closest('.btn-remove-wish');
            if (removeBtn) {
                e.preventDefault();
                var bookIdR = removeBtn.getAttribute('data-libros-id');
                removeFromWishlist(bookIdR).then(function (payload) {
                    renderWishlistPage(payload.items || []);
                }).catch(function (err) {
                    console.error(err);
                    alert(err.message || 'No se pudo eliminar');
                });
            }
        });
    };

    var bindDetailButton = function () {
        var btn = document.getElementById('add-to-wishlist-btn');
        if (!btn) return;
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            var bookId = btn.getAttribute('data-book-id') || btn.getAttribute('data-libros-id');
            if (!bookId) return;
            addToWishlist(bookId).then(function () {
                alert('Agregado a tu lista de deseos');
            }).catch(function (err) {
                console.error(err);
                alert(err.message || 'No se pudo agregar a favoritos');
            });
        });
    };

    document.addEventListener('DOMContentLoaded', function () {
        bindDetailButton();
        bindWishlistPage();
    });
})();

