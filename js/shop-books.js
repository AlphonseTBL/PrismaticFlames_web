(function() {
    'use strict';

    var gridContainer = document.getElementById('books-grid');
    var listContainer = document.getElementById('books-list');
    var categoriesContainer = document.getElementById('books-categories');
    var gridEmpty = document.getElementById('books-grid-empty');
    var listEmpty = document.getElementById('books-list-empty');

    if (!gridContainer || !listContainer) {
        return;
    }

    var fallbackImage = 'images/img-pro-01.jpg';

    var createBadge = function(label, text) {
        var span = document.createElement('span');
        span.className = 'book-badge';
        span.textContent = label + ': ' + text;
        return span;
    };

    var renderGridItem = function(book) {
        var col = document.createElement('div');
        col.className = 'col-sm-6 col-md-6 col-lg-4 col-xl-4';

        var card = document.createElement('div');
        card.className = 'products-single fix';

        var box = document.createElement('div');
        box.className = 'box-img-hover';

        var flag = document.createElement('div');
        flag.className = 'type-lb';
        var flagText = document.createElement('p');
        flagText.textContent = book.stock > 0 ? 'Disponible' : 'Agotado';
        flagText.className = book.stock > 0 ? 'sale' : 'new';
        flag.appendChild(flagText);

        var img = document.createElement('img');
        img.src = book.portada || fallbackImage;
        img.alt = book.titulo;
        img.className = 'img-fluid';

        var mask = document.createElement('div');
        mask.className = 'mask-icon';

        var ul = document.createElement('ul');
        ul.innerHTML = '<li><span class="book-meta"><i class="fas fa-feather"></i> ' + (book.autores || 'Autor no disponible') + '</span></li>' +
            '<li><span class="book-meta"><i class="fas fa-tag"></i> ' + (book.categorias || 'Sin categoría') + '</span></li>';

        var cartLink = document.createElement('a');
        cartLink.className = 'cart';
        cartLink.href = 'shop-detail.html?id=' + book.id;
        cartLink.textContent = 'Ver detalle';

        mask.appendChild(ul);
        mask.appendChild(cartLink);

        box.appendChild(flag);
        box.appendChild(img);
        box.appendChild(mask);

        var info = document.createElement('div');
        info.className = 'why-text';
        var price = parseFloat(book.precio) || 0;
        info.innerHTML = '<h4>' + book.titulo + '</h4>' +
            '<p>' + (book.descripcion ? book.descripcion.substring(0, 120) + '…' : 'Sin descripción disponible') + '</p>' +
            '<h5>$' + price.toFixed(2) + '</h5>';

        card.appendChild(box);
        card.appendChild(info);
        col.appendChild(card);
        return col;
    };

    var renderListItem = function(book) {
        var box = document.createElement('div');
        box.className = 'list-view-box';

        var row = document.createElement('div');
        row.className = 'row';

        var colImg = document.createElement('div');
        colImg.className = 'col-sm-6 col-md-6 col-lg-4 col-xl-4';

        var card = document.createElement('div');
        card.className = 'products-single fix';

        var boxImg = document.createElement('div');
        boxImg.className = 'box-img-hover';

        var flag = document.createElement('div');
        flag.className = 'type-lb';
        var flagText = document.createElement('p');
        flagText.textContent = book.stock > 0 ? 'Disponible' : 'Agotado';
        flagText.className = book.stock > 0 ? 'sale' : 'new';
        flag.appendChild(flagText);

        var img = document.createElement('img');
        img.src = book.portada || fallbackImage;
        img.alt = book.titulo;
        img.className = 'img-fluid';

        boxImg.appendChild(flag);
        boxImg.appendChild(img);
        card.appendChild(boxImg);
        colImg.appendChild(card);

        var colInfo = document.createElement('div');
        colInfo.className = 'col-sm-6 col-md-6 col-lg-8 col-xl-8';
        var info = document.createElement('div');
        info.className = 'why-text full-width';
        var price = parseFloat(book.precio) || 0;
        info.innerHTML = '<h4>' + book.titulo + '</h4>' +
            '<h5>$' + price.toFixed(2) + '</h5>' +
            '<p>' + (book.descripcion || 'Sin descripción disponible.') + '</p>';

        var metaWrapper = document.createElement('div');
        metaWrapper.className = 'book-meta-list';
        metaWrapper.appendChild(createBadge('Autores', book.autores || 'No especificado'));
        metaWrapper.appendChild(createBadge('Categorías', book.categorias || 'No asignadas'));
        metaWrapper.appendChild(createBadge('Stock', book.stock > 0 ? book.stock + ' disponibles' : 'Agotado'));
        info.appendChild(metaWrapper);

        var button = document.createElement('a');
        button.className = 'btn hvr-hover';
        button.href = 'shop-detail.html?id=' + book.id;
        button.textContent = 'Ver detalle';
        info.appendChild(button);

        colInfo.appendChild(info);

        row.appendChild(colImg);
        row.appendChild(colInfo);
        box.appendChild(row);

        return box;
    };

    var renderCategories = function(books) {
        if (!categoriesContainer) {
            return;
        }
        var map = {};
        books.forEach(function(book) {
            if (!book.categorias) {
                return;
            }
            book.categorias.split(',').map(function(name) {
                return name.trim();
            }).filter(Boolean).forEach(function(name) {
                map[name] = (map[name] || 0) + 1;
            });
        });

        var entries = Object.keys(map).sort().map(function(key) {
            return {
                name: key,
                count: map[key],
            };
        });

        if (!entries.length) {
            categoriesContainer.innerHTML = '<p>No hay categorías disponibles.</p>';
            return;
        }

        var list = document.createElement('div');
        list.className = 'list-group';
        entries.forEach(function(entry, index) {
            var link = document.createElement('a');
            link.href = '#';
            link.className = 'list-group-item list-group-item-action' + (index === 0 ? ' active' : '');
            link.textContent = entry.name + ' ';
            var small = document.createElement('small');
            small.className = 'text-muted';
            small.textContent = '(' + entry.count + ')';
            link.appendChild(small);
            list.appendChild(link);
        });

        categoriesContainer.innerHTML = '';
        categoriesContainer.appendChild(list);
    };

    var showEmptyState = function() {
        gridContainer.innerHTML = '';
        listContainer.innerHTML = '';
        if (gridEmpty) {
            gridEmpty.style.display = 'block';
        }
        if (listEmpty) {
            listEmpty.style.display = 'block';
        }
    };

    fetch('php/books.php')
        .then(function(response) {
            if (!response.ok) {
                throw new Error('No se pudo cargar el catálogo');
            }
            return response.json();
        })
        .then(function(payload) {
            var books = payload && Array.isArray(payload.books) ? payload.books : [];
            if (!books.length) {
                showEmptyState();
                return;
            }

            gridContainer.innerHTML = '';
            listContainer.innerHTML = '';

            books.forEach(function(book) {
                gridContainer.appendChild(renderGridItem(book));
                listContainer.appendChild(renderListItem(book));
            });

            if (gridEmpty) {
                gridEmpty.style.display = 'none';
            }
            if (listEmpty) {
                listEmpty.style.display = 'none';
            }

            renderCategories(books);
        })
        .catch(function(error) {
            console.error(error);
            showEmptyState();
            if (categoriesContainer) {
                categoriesContainer.innerHTML = '<p>No se pudieron cargar las categorías.</p>';
            }
        });
})();

