(function () {
    'use strict';

    var params = new URLSearchParams(window.location.search);
    var bookId = parseInt(params.get('id') || '0', 10);
    var errorBox = document.getElementById('book-detail-error');
    var fallbackImage = 'images/img-pro-01.jpg';

    var setText = function (id, value) {
        var el = document.getElementById(id);
        if (el) {
            el.textContent = value;
        }
    };

    var showError = function (message) {
        if (errorBox) {
            errorBox.style.display = 'block';
            if (message) {
                errorBox.innerHTML = message;
            }
        }
    };

    if (!bookId) {
        showError('No se identificó el libro solicitado. Vuelve a la <a href="shop.html">tienda</a> e inténtalo nuevamente.');
        return;
    }

    var coverImg = document.getElementById('book-cover');
    var stockEl = document.getElementById('book-stock');
    var descriptionEl = document.getElementById('book-description');
    var extraInfoBox = document.getElementById('book-extra-info');

    fetch('php/books.php?id=' + encodeURIComponent(bookId), { cache: 'no-store' })
        .then(function (response) {
            if (!response.ok) {
                throw new Error('Respuesta no válida');
            }
            return response.json();
        })
        .then(function (data) {
            var book = data.book || (Array.isArray(data.books) ? data.books[0] : null);
            if (!book) {
                throw new Error('Libro no encontrado');
            }

            document.title = book.titulo + ' - Freshshop';
            setText('book-title', book.titulo || 'Título no disponible');

            var price = typeof book.precio === 'number'
                ? book.precio
                : parseFloat(book.precio || '0') || 0;
            setText('book-price', new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(price));

            if (stockEl) {
                if (book.stock > 0) {
                    stockEl.innerHTML = '<span>Disponible: ' + book.stock + ' en inventario</span>';
                } else {
                    stockEl.innerHTML = '<span>Actualmente agotado</span>';
                }
            }

            setText('book-authors', book.autores || 'Autor no disponible');
            setText('book-categories', book.categorias || 'Sin categoría');
            setText('book-isbn', book.isbn || 'No registrado');

            if (book.fecha_publicacion) {
                var date = new Date(book.fecha_publicacion);
                if (!isNaN(date.getTime())) {
                    setText('book-date', date.toLocaleDateString('es-MX', { year: 'numeric', month: 'long', day: 'numeric' }));
                } else {
                    setText('book-date', book.fecha_publicacion);
                }
            } else {
                setText('book-date', 'Sin fecha de publicación');
            }

            if (descriptionEl) {
                descriptionEl.textContent = book.descripcion || 'No contamos con una descripción para este título.';
            }

            if (coverImg) {
                var coverSource = book.portada_url || book.portada || fallbackImage;
                coverImg.src = coverSource;
                coverImg.alt = 'Portada de ' + (book.titulo || 'libro');
                coverImg.onerror = function () {
                    this.onerror = null;
                    this.src = fallbackImage;
                };
            }

            // Propagar id del libro al botón de carrito
            var addBtn = document.getElementById('add-to-cart-btn');
            if (addBtn && book.id) {
                addBtn.setAttribute('data-book-id', book.id);
                addBtn.setAttribute('data-libros-id', book.id);
            }

            if (extraInfoBox) {
                var snippets = [];
                if (book.autores) {
                    snippets.push('<strong>Autor(es):</strong> ' + book.autores);
                }
                if (book.categorias) {
                    snippets.push('<strong>Categorías:</strong> ' + book.categorias);
                }
                if (typeof book.stock === 'number') {
                    snippets.push('<strong>Existencias:</strong> ' + (book.stock > 0 ? book.stock + ' disponibles' : 'Agotado'));
                }
                if (book.fecha_publicacion) {
                    var publishedNode = document.getElementById('book-date');
                    var publishedText = publishedNode && publishedNode.textContent ? publishedNode.textContent : book.fecha_publicacion;
                    snippets.push('<strong>Publicado:</strong> ' + publishedText);
                }
                snippets.push('Explora más títulos similares en la <a href="shop.html">tienda</a> o revisa tu <a href="my-account.html">historial</a>.');
                extraInfoBox.innerHTML = snippets.join('<br>');
            }

            if (errorBox) {
                errorBox.style.display = 'none';
            }
        })
        .catch(function (error) {
            console.error('Error al cargar el libro:', error);
            showError('No pudimos cargar la información del libro. Vuelve a la <a href="shop.html">tienda</a> e intenta nuevamente.');
        });
})();

