(function() {
    'use strict';

    var grid = document.getElementById('home-books-grid');
    var emptyState = document.getElementById('home-books-empty');

    if (!grid) {
        return;
    }

    var fallbackImage = 'images/img-pro-01.jpg';

    var renderCard = function(book) {
        var col = document.createElement('div');
        col.className = 'col-lg-3 col-md-6 special-grid';

        var card = document.createElement('div');
        card.className = 'products-single fix';

        var box = document.createElement('div');
        box.className = 'box-img-hover';

        var badgeWrapper = document.createElement('div');
        badgeWrapper.className = 'type-lb';
        var badge = document.createElement('p');
        badge.textContent = book.stock > 0 ? 'Disponible' : 'Agotado';
        badge.className = book.stock > 0 ? 'sale' : 'new';
        badgeWrapper.appendChild(badge);

        var img = document.createElement('img');
        img.src = book.portada || fallbackImage;
        img.alt = book.titulo;
        img.className = 'img-fluid';

        var mask = document.createElement('div');
        mask.className = 'mask-icon';
        var meta = document.createElement('ul');
        meta.innerHTML = '<li><span class=\"book-meta\"><i class=\"fas fa-feather\"></i> ' +
            (book.autores || 'Autor no disponible') + '</span></li>' +
            '<li><span class=\"book-meta\"><i class=\"fas fa-tag\"></i> ' +
            (book.categorias || 'Sin categoría') + '</span></li>';

        var btn = document.createElement('a');
        btn.className = 'cart';
        btn.href = 'shop-detail.html?id=' + book.id;
        btn.textContent = 'Ver detalle';

        mask.appendChild(meta);
        mask.appendChild(btn);

        box.appendChild(badgeWrapper);
        box.appendChild(img);
        box.appendChild(mask);

        var info = document.createElement('div');
        info.className = 'why-text';
        var price = parseFloat(book.precio) || 0;
        info.innerHTML = '<h4>' + book.titulo + '</h4>' +
            '<p>' + (book.descripcion ? book.descripcion.substring(0, 80) + '…' : 'Sin descripción disponible') + '</p>' +
            '<h5>$' + price.toFixed(2) + '</h5>';

        card.appendChild(box);
        card.appendChild(info);
        col.appendChild(card);
        return col;
    };

    fetch('php/books.php')
        .then(function(response) {
            if (!response.ok) {
                throw new Error('No se pudieron cargar los libros');
            }
            return response.json();
        })
        .then(function(payload) {
            var books = (payload && payload.books) ? payload.books.slice(0, 4) : [];
            if (!books.length) {
                if (emptyState) {
                    emptyState.style.display = 'block';
                }
                return;
            }
            grid.innerHTML = '';
            books.forEach(function(book) {
                grid.appendChild(renderCard(book));
            });
            if (emptyState) {
                emptyState.style.display = 'none';
            }
        })
        .catch(function() {
            if (emptyState) {
                emptyState.style.display = 'block';
            }
        });
})();

