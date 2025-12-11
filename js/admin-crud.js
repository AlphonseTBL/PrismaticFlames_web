(function () {
    'use strict';

    var tablesList = document.getElementById('tables-list');
    var dataTable = document.getElementById('data-table');
    var crudForm = document.getElementById('crud-form');
    var crudStatus = document.getElementById('crud-status');
    var btnRefresh = document.getElementById('btn-refresh');
    var btnNew = document.getElementById('btn-new');
    var btnSave = document.getElementById('btn-save');
    var btnDelete = document.getElementById('btn-delete');

    var currentTable = null;
    var currentColumns = [];
    var currentRows = [];
    var selectedId = null;
    var formMode = 'view'; // view | new | edit

    function fetchTables() {
        fetch('php/admin-crud.php?action=tables', { credentials: 'include' })
            .then(handleResponse)
            .then(function (data) {
                renderTables(data.tables || []);
            })
            .catch(showError);
    }

    function handleResponse(res) {
        if (res.status === 401) {
            window.location.href = 'login.html';
            return Promise.reject(new Error('No autenticado'));
        }
        if (res.status === 403) {
            return Promise.reject(new Error('No autorizado'));
        }
        if (!res.ok) {
            return res.json().catch(function(){return {};}).then(function(d){
                throw new Error(d.error || 'Error en la solicitud');
            });
        }
        return res.json();
    }

    function showError(err) {
        console.error(err);
        if (crudStatus) crudStatus.textContent = err.message || 'Error';
    }

    function renderTables(tables) {
        if (!tablesList) return;
        tablesList.innerHTML = '';
        tables.forEach(function (t) {
            var li = document.createElement('li');
            li.className = 'list-group-item list-group-item-action';
            li.textContent = t;
            li.style.cursor = 'pointer';
            li.addEventListener('click', function () {
                loadTable(t);
            });
            tablesList.appendChild(li);
        });
    }

    function loadTable(table) {
        currentTable = table;
        selectedId = null;
        formMode = 'view';
        disableActions();
        if (crudStatus) crudStatus.textContent = 'Cargando ' + table + '...';
        Promise.all([
            fetch('php/admin-crud.php?action=columns&table=' + encodeURIComponent(table), { credentials: 'include' }).then(handleResponse),
            fetch('php/admin-crud.php?action=select&table=' + encodeURIComponent(table) + '&limit=50', { credentials: 'include' }).then(handleResponse)
        ]).then(function (results) {
            currentColumns = (results[0].columns || []).map(function (c) { return c.Field; });
            currentRows = results[1].rows || [];
            renderTable();
            renderForm();
            if (crudStatus) crudStatus.textContent = 'Tabla ' + table + ' cargada';
        }).catch(showError);
    }

    function renderTable() {
        if (!dataTable) return;
        var thead = dataTable.querySelector('thead');
        var tbody = dataTable.querySelector('tbody');
        thead.innerHTML = '';
        tbody.innerHTML = '';
        if (!currentColumns.length) return;

        var trHead = document.createElement('tr');
        trHead.innerHTML = '<th>ID</th>' + currentColumns.map(function (c) {
            if (c === 'id') return '';
            return '<th>' + c + '</th>';
        }).join('');
        thead.appendChild(trHead);

        currentRows.forEach(function (row) {
            var tr = document.createElement('tr');
            tr.style.cursor = 'pointer';
            tr.addEventListener('click', function () {
                selectRow(row.id);
            });
            var cells = '<td>' + (row.id || '') + '</td>';
            currentColumns.forEach(function (c) {
                if (c === 'id') return;
                cells += '<td>' + (row[c] === null || typeof row[c] === 'undefined' ? '' : row[c]) + '</td>';
            });
            tr.innerHTML = cells;
            tbody.appendChild(tr);
        });
    }

    function renderForm(data) {
        if (!crudForm) return;
        crudForm.innerHTML = '';
        currentColumns.forEach(function (c) {
            if (c === 'id') return;
            var col = document.createElement('div');
            col.className = 'form-group col-md-6';
            var label = document.createElement('label');
            label.textContent = c;
            var input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.name = c;
            input.value = data && typeof data[c] !== 'undefined' && data[c] !== null ? data[c] : '';
            col.appendChild(label);
            col.appendChild(input);
            crudForm.appendChild(col);
        });
    }

    function selectRow(id) {
        selectedId = id;
        formMode = 'edit';
        var row = currentRows.find(function (r) { return String(r.id) === String(id); });
        renderForm(row);
        enableActions();
    }

    function disableActions() {
        if (btnSave) btnSave.disabled = true;
        if (btnDelete) btnDelete.disabled = true;
    }

    function enableActions() {
        if (btnSave) btnSave.disabled = false;
        if (btnDelete) btnDelete.disabled = false;
    }

    function collectFormData() {
        var data = {};
        if (!crudForm) return data;
        Array.prototype.forEach.call(crudForm.querySelectorAll('input[name]'), function (input) {
            data[input.name] = input.value;
        });
        return data;
    }

    function createRecord() {
        var data = collectFormData();
        var body = new URLSearchParams();
        body.append('action', 'insert');
        body.append('table', currentTable || '');
        body.append('data', JSON.stringify(data));
        if (crudStatus) crudStatus.textContent = 'Guardando...';
        fetch('php/admin-crud.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(handleResponse).then(function () {
            if (crudStatus) crudStatus.textContent = 'Registro creado';
            loadTable(currentTable);
        }).catch(showError);
    }

    function updateRecord() {
        if (!selectedId) {
            if (crudStatus) crudStatus.textContent = 'Selecciona un registro.';
            return;
        }
        var data = collectFormData();
        var body = new URLSearchParams();
        body.append('action', 'update');
        body.append('table', currentTable || '');
        body.append('id', selectedId);
        body.append('data', JSON.stringify(data));
        if (crudStatus) crudStatus.textContent = 'Actualizando...';
        fetch('php/admin-crud.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(handleResponse).then(function () {
            if (crudStatus) crudStatus.textContent = 'Registro actualizado';
            loadTable(currentTable);
        }).catch(showError);
    }

    function deleteRecord() {
        if (!selectedId) {
            if (crudStatus) crudStatus.textContent = 'Selecciona un registro.';
            return;
        }
        if (!confirm('¿Eliminar el registro #' + selectedId + '?')) return;
        var body = new URLSearchParams();
        body.append('action', 'delete');
        body.append('table', currentTable || '');
        body.append('id', selectedId);
        if (crudStatus) crudStatus.textContent = 'Eliminando...';
        fetch('php/admin-crud.php', {
            method: 'POST',
            credentials: 'include',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: body.toString()
        }).then(handleResponse).then(function () {
            if (crudStatus) crudStatus.textContent = 'Registro eliminado';
            loadTable(currentTable);
        }).catch(showError);
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (btnRefresh) btnRefresh.addEventListener('click', function () {
            if (currentTable) loadTable(currentTable);
        });
        if (btnNew) btnNew.addEventListener('click', function () {
            selectedId = null;
            formMode = 'new';
            renderForm({});
            if (crudStatus) crudStatus.textContent = 'Nuevo registro';
            if (btnSave) btnSave.disabled = false;
            if (btnDelete) btnDelete.disabled = true;
        });
        if (btnSave) btnSave.addEventListener('click', function () {
            if (formMode === 'new') {
                createRecord();
            } else if (formMode === 'edit') {
                updateRecord();
            }
        });
        if (btnDelete) btnDelete.addEventListener('click', deleteRecord);
        fetchTables();
    });
})();

