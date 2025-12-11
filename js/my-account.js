(function () {
    'use strict';

    var profileForm, deleteBtn, saveBtn, ordersContainer, ordersEmpty;
    var fallbackImage = 'images/img-pro-01.jpg';

    function qs(id) {
        return document.getElementById(id);
    }

    function loadProfile() {
        return fetch('php/profile.php?action=get', { credentials: 'include' })
            .then(function (res) {
                if (res.status === 401) {
                    window.location.href = 'login.html';
                    return Promise.reject(new Error('No autenticado'));
                }
                if (!res.ok) throw new Error('No se pudo cargar el perfil');
                return res.json();
            })
            .then(function (data) {
                var p = data.profile || {};
                qs('profile-nombre').value = p.nombre || '';
                qs('profile-apellido').value = p.apellido || '';
                qs('profile-email').value = p.email || '';
                qs('profile-telefono').value = p.telefono || '';
                var pts = document.querySelector('[data-user-points]');
                if (pts && typeof p.puntos !== 'undefined') pts.textContent = p.puntos + ' pts';
            });
    }

    function saveProfile(e) {
        e.preventDefault();
        var body = new URLSearchParams();
        body.append('action', 'update');
        body.append('nombre', qs('profile-nombre').value.trim());
        body.append('apellido', qs('profile-apellido').value.trim());
        body.append('email', qs('profile-email').value.trim());
        body.append('telefono', qs('profile-telefono').value.trim());
        body.append('password', qs('profile-password').value);

        saveBtn.disabled = true;
        saveBtn.textContent = 'Guardando...';

        fetch('php/profile.php', {
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
                return res.json().catch(function () { return {}; }).then(function (d) {
                    throw new Error(d.error || 'No se pudo guardar');
                });
            }
            return res.json();
        }).then(function () {
            alert('Perfil actualizado');
            qs('profile-password').value = '';
        }).catch(function (err) {
            console.error(err);
            alert(err.message || 'Error al guardar');
        }).finally(function () {
            saveBtn.disabled = false;
            saveBtn.textContent = 'Guardar cambios';
        });
    }

    function deleteAccount() {
        if (!confirm('¿Seguro que deseas eliminar tu cuenta? Esta acción es irreversible.')) return;
        deleteBtn.disabled = true;
        fetch('php/profile.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=delete'
        }).then(function (res) {
            if (res.status === 401) {
                window.location.href = 'login.html';
                return Promise.reject(new Error('No autenticado'));
            }
            if (!res.ok) {
                return res.json().catch(function () { return {}; }).then(function (d) {
                    throw new Error(d.error || 'No se pudo eliminar la cuenta');
                });
            }
            return res.json();
        }).then(function () {
            alert('Cuenta eliminada. Te redirigiremos al inicio.');
            window.location.href = 'index.html';
        }).catch(function (err) {
            console.error(err);
            alert(err.message || 'Error al eliminar la cuenta');
        }).finally(function () {
            deleteBtn.disabled = false;
        });
    }

    function renderOrders(orders) {
        if (!ordersContainer) return;
        ordersContainer.innerHTML = '';
        if (!orders || !orders.length) {
            if (ordersEmpty) ordersEmpty.style.display = 'block';
            return;
        }
        if (ordersEmpty) ordersEmpty.style.display = 'none';
        orders.forEach(function (order) {
            var card = document.createElement('div');
            card.className = 'card mb-3';
            var itemsHtml = (order.items || []).map(function (it) {
                return '<li class="list-group-item d-flex align-items-center">' +
                    '<img src="' + (it.portada_url || fallbackImage) + '" alt="" style="width:50px;height:50px;object-fit:cover;margin-right:10px;">' +
                    '<div class="flex-grow-1">' +
                    '<div><strong>' + (it.titulo || ('Libro #' + it.libro_id)) + '</strong></div>' +
                    '<div class="text-muted">Cant: ' + it.cantidad + ' | $' + it.precio_unitario.toFixed(2) + '</div>' +
                    '</div>' +
                    '<div>$' + (it.cantidad * it.precio_unitario).toFixed(2) + '</div>' +
                    '</li>';
            }).join('');
            card.innerHTML =
                '<div class="card-header d-flex justify-content-between align-items-center">' +
                '<div><strong>Pedido #' + order.id + '</strong> • ' + (order.estado || '') + '</div>' +
                '<div class="text-muted small">' + (order.fecha_pedido || '') + '</div>' +
                '</div>' +
                '<div class="card-body">' +
                '<p class="mb-1">Total: $' + (order.total || 0).toFixed(2) + '</p>' +
                '<p class="mb-1">Puntos obtenidos: ' + (order.puntos_obtenidos || 0) + '</p>' +
                '<p class="mb-1">Método de pago: ' + (order.metodo_pago || '') + '</p>' +
                '<p class="mb-3">Dirección: ' + (order.direccion_envio || '') + '</p>' +
                '<ul class="list-group list-group-flush">' + itemsHtml + '</ul>' +
                '</div>';
            ordersContainer.appendChild(card);
        });
    }

    function loadOrders() {
        return fetch('php/orders.php', { credentials: 'include' })
            .then(function (res) {
                if (res.status === 401) {
                    window.location.href = 'login.html';
                    return Promise.reject(new Error('No autenticado'));
                }
                if (!res.ok) throw new Error('No se pudieron cargar los pedidos');
                return res.json();
            })
            .then(function (data) {
                renderOrders(data.orders || []);
            }).catch(function (err) {
                console.error(err);
                alert(err.message || 'Error al cargar pedidos');
            });
    }

    function init() {
        profileForm = qs('profile-form');
        deleteBtn = qs('delete-account-btn');
        saveBtn = qs('save-profile-btn');
        ordersContainer = qs('orders-container');
        ordersEmpty = qs('orders-empty');

        if (profileForm) {
            profileForm.addEventListener('submit', saveProfile);
        }
        if (deleteBtn) {
            deleteBtn.addEventListener('click', function (e) {
                e.preventDefault();
                deleteAccount();
            });
        }

        loadProfile();
        loadOrders();
    }

    document.addEventListener('DOMContentLoaded', init);
})();

