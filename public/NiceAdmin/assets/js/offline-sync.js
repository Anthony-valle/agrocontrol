(function () {
  if (window.__agroOfflineSyncInstalled) {
    return;
  }

  if (!window.indexedDB || !window.fetch) {
    return;
  }

  window.__agroOfflineSyncInstalled = true;

  var DB_NAME = 'agrocontrol_offline';
  var DB_VERSION = 1;
  var STORE_NAME = 'requests';
  var SYNC_INTERVAL_MS = 20000;
  var syncInProgress = false;
  var statusEl = null;
  var trayPanelEl = null;
  var trayListEl = null;
  var trayInfoEl = null;
  var originalFetch = window.fetch.bind(window);

  function openDb() {
    return new Promise(function (resolve, reject) {
      var request = indexedDB.open(DB_NAME, DB_VERSION);

      request.onupgradeneeded = function (event) {
        var db = event.target.result;
        if (!db.objectStoreNames.contains(STORE_NAME)) {
          db.createObjectStore(STORE_NAME, { keyPath: 'id', autoIncrement: true });
        }
      };

      request.onsuccess = function () {
        resolve(request.result);
      };

      request.onerror = function () {
        reject(request.error);
      };
    });
  }

  function runStore(mode, callback) {
    return openDb().then(function (db) {
      return new Promise(function (resolve, reject) {
        var tx = db.transaction(STORE_NAME, mode);
        var store = tx.objectStore(STORE_NAME);
        var resolved = false;

        callback(
          store,
          function (value) {
            resolved = true;
            resolve(value);
          },
          function (error) {
            resolved = true;
            reject(error);
          }
        );

        tx.oncomplete = function () {
          db.close();
          if (!resolved) {
            resolve();
          }
        };

        tx.onerror = function () {
          db.close();
          reject(tx.error);
        };
      });
    });
  }

  function addRequest(record) {
    return runStore('readwrite', function (store, resolve, reject) {
      var req = store.add(record);
      req.onsuccess = function () {
        resolve(req.result);
      };
      req.onerror = function () {
        reject(req.error);
      };
    });
  }

  function getAllRequests() {
    return runStore('readonly', function (store, resolve, reject) {
      var req = store.getAll();
      req.onsuccess = function () {
        resolve(req.result || []);
      };
      req.onerror = function () {
        reject(req.error);
      };
    });
  }

  function deleteRequest(id) {
    return runStore('readwrite', function (store, resolve, reject) {
      var req = store.delete(id);
      req.onsuccess = function () {
        resolve();
      };
      req.onerror = function () {
        reject(req.error);
      };
    });
  }

  function updateRequest(record) {
    return runStore('readwrite', function (store, resolve, reject) {
      var req = store.put(record);
      req.onsuccess = function () {
        resolve(req.result);
      };
      req.onerror = function () {
        reject(req.error);
      };
    });
  }

  function clearRequests() {
    return runStore('readwrite', function (store, resolve, reject) {
      var req = store.clear();
      req.onsuccess = function () {
        resolve();
      };
      req.onerror = function () {
        reject(req.error);
      };
    });
  }

  function countRequests() {
    return runStore('readonly', function (store, resolve, reject) {
      var req = store.count();
      req.onsuccess = function () {
        resolve(req.result || 0);
      };
      req.onerror = function () {
        reject(req.error);
      };
    });
  }

  function objectFromHeaders(headers) {
    var out = {};
    if (!headers) {
      return out;
    }

    if (headers instanceof Headers) {
      headers.forEach(function (value, key) {
        out[String(key).toLowerCase()] = String(value);
      });
      return out;
    }

    if (Array.isArray(headers)) {
      headers.forEach(function (entry) {
        if (Array.isArray(entry) && entry.length === 2) {
          out[String(entry[0]).toLowerCase()] = String(entry[1]);
        }
      });
      return out;
    }

    Object.keys(headers).forEach(function (key) {
      out[String(key).toLowerCase()] = String(headers[key]);
    });
    return out;
  }

  function hasBinaryInFormData(formData) {
    var hasBinary = false;
    formData.forEach(function (value) {
      if (value instanceof Blob || value instanceof File) {
        hasBinary = true;
      }
    });
    return hasBinary;
  }

  function serializeBody(body) {
    if (!body) {
      return { bodyType: 'none', bodyValue: null };
    }

    if (body instanceof FormData) {
      if (hasBinaryInFormData(body)) {
        throw new Error('No se puede guardar offline formularios con archivos adjuntos.');
      }

      var entries = [];
      body.forEach(function (value, key) {
        entries.push([key, String(value)]);
      });
      return { bodyType: 'formData', bodyValue: entries };
    }

    if (body instanceof URLSearchParams) {
      return { bodyType: 'urlEncoded', bodyValue: body.toString() };
    }

    if (typeof body === 'string') {
      return { bodyType: 'text', bodyValue: body };
    }

    throw new Error('Tipo de body no soportado para modo offline.');
  }

  function deserializeBody(record) {
    if (!record || record.bodyType === 'none') {
      return undefined;
    }

    if (record.bodyType === 'formData') {
      var formData = new FormData();
      (record.bodyValue || []).forEach(function (entry) {
        formData.append(entry[0], entry[1]);
      });
      return formData;
    }

    if (record.bodyType === 'urlEncoded') {
      return new URLSearchParams(record.bodyValue || '');
    }

    return record.bodyValue;
  }

  function sameOrigin(url) {
    try {
      return new URL(url, window.location.origin).origin === window.location.origin;
    } catch (error) {
      return false;
    }
  }

  function isMutatingMethod(method) {
    return ['POST', 'PUT', 'PATCH', 'DELETE'].indexOf(String(method || '').toUpperCase()) !== -1;
  }

  function getCsrfToken() {
    var tokenMeta = document.querySelector('meta[name="csrf-token"]');
    return tokenMeta ? (tokenMeta.getAttribute('content') || '') : '';
  }

  function extractRequestDetails(input, init) {
    var url = typeof input === 'string' ? input : input.url;
    var method = ((init && init.method) || (typeof input !== 'string' && input.method) || 'GET').toUpperCase();
    var headers = objectFromHeaders((init && init.headers) || (typeof input !== 'string' && input.headers));
    var bodyRaw = init && typeof init.body !== 'undefined' ? init.body : undefined;
    var bodySerialized = serializeBody(bodyRaw);
    var absoluteUrl = new URL(url, window.location.origin).toString();

    return {
      url: absoluteUrl,
      method: method,
      headers: headers,
      bodyType: bodySerialized.bodyType,
      bodyValue: bodySerialized.bodyValue,
      createdAt: new Date().toISOString(),
      retries: 0
    };
  }

  function buildFormRequestDetails(form, submitter) {
    var action = form.getAttribute('action') || window.location.href;
    var method = String(form.getAttribute('method') || 'POST').toUpperCase();
    var formData = new FormData(form);

    if (submitter && submitter.name) {
      formData.append(submitter.name, submitter.value || '');
    }

    return extractRequestDetails(action, {
      method: method,
      body: formData,
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json'
      }
    });
  }

  function createQueuedResponse() {
    return new Response(JSON.stringify({
      queued: true,
      success: 'Sin conexion: datos guardados localmente y pendientes de sincronizacion.'
    }), {
      status: 202,
      headers: {
        'Content-Type': 'application/json',
        'X-Offline-Queued': '1'
      }
    });
  }

  function ensureStatusElement() {
    if (statusEl) {
      return statusEl;
    }

    statusEl = document.createElement('div');
    statusEl.id = 'offline-sync-status';
    statusEl.style.position = 'fixed';
    statusEl.style.right = '16px';
    statusEl.style.bottom = '16px';
    statusEl.style.zIndex = '2000';
    statusEl.style.padding = '8px 12px';
    statusEl.style.borderRadius = '8px';
    statusEl.style.fontSize = '12px';
    statusEl.style.fontWeight = '600';
    statusEl.style.boxShadow = '0 4px 14px rgba(0,0,0,0.15)';
    statusEl.style.background = '#f8f9fa';
    statusEl.style.color = '#212529';
    statusEl.textContent = 'Sincronizacion offline inicializando...';
    document.body.appendChild(statusEl);
    return statusEl;
  }

  function ensureTrayElements() {
    if (trayPanelEl && trayListEl && trayInfoEl) {
      return;
    }

    trayPanelEl = document.createElement('div');
    trayPanelEl.id = 'offline-sync-tray';
    trayPanelEl.style.position = 'fixed';
    trayPanelEl.style.right = '16px';
    trayPanelEl.style.bottom = '108px';
    trayPanelEl.style.zIndex = '2002';
    trayPanelEl.style.width = '360px';
    trayPanelEl.style.maxWidth = 'calc(100vw - 32px)';
    trayPanelEl.style.maxHeight = '55vh';
    trayPanelEl.style.display = 'none';
    trayPanelEl.style.background = '#ffffff';
    trayPanelEl.style.border = '1px solid #dee2e6';
    trayPanelEl.style.borderRadius = '12px';
    trayPanelEl.style.boxShadow = '0 16px 40px rgba(0,0,0,0.2)';
    trayPanelEl.style.overflow = 'hidden';

    var trayHeader = document.createElement('div');
    trayHeader.style.display = 'flex';
    trayHeader.style.alignItems = 'center';
    trayHeader.style.justifyContent = 'space-between';
    trayHeader.style.padding = '10px 12px';
    trayHeader.style.borderBottom = '1px solid #e9ecef';
    trayHeader.style.background = '#f8f9fa';

    var title = document.createElement('strong');
    title.textContent = 'Bandeja de sincronizacion';
    title.style.fontSize = '13px';

    var closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.textContent = 'Cerrar';
    closeBtn.style.border = '1px solid #ced4da';
    closeBtn.style.background = '#ffffff';
    closeBtn.style.padding = '2px 8px';
    closeBtn.style.fontSize = '12px';
    closeBtn.style.borderRadius = '6px';
    closeBtn.style.cursor = 'pointer';
    closeBtn.addEventListener('click', function () {
      trayPanelEl.style.display = 'none';
    });

    trayHeader.appendChild(title);
    trayHeader.appendChild(closeBtn);

    var controls = document.createElement('div');
    controls.style.display = 'flex';
    controls.style.gap = '8px';
    controls.style.padding = '10px 12px';
    controls.style.borderBottom = '1px solid #e9ecef';

    var syncNowBtn = document.createElement('button');
    syncNowBtn.type = 'button';
    syncNowBtn.textContent = 'Sincronizar ahora';
    syncNowBtn.style.flex = '1';
    syncNowBtn.style.border = 'none';
    syncNowBtn.style.borderRadius = '8px';
    syncNowBtn.style.background = '#198754';
    syncNowBtn.style.color = '#ffffff';
    syncNowBtn.style.fontSize = '12px';
    syncNowBtn.style.fontWeight = '600';
    syncNowBtn.style.padding = '7px 8px';
    syncNowBtn.style.cursor = 'pointer';
    syncNowBtn.onclick = function () {
      syncNowBtn.disabled = true;
      setTrayInfo('Sincronizando pendientes...', '#0d6efd');
      flushQueue({ force: true, manual: true })
        .then(function (summary) {
          if (!summary || summary.skippedOffline) {
            setTrayInfo('No se pudo sincronizar: sin conexion.', '#dc3545');
            return;
          }

          if (summary.total === 0) {
            setTrayInfo('No hay pendientes para sincronizar.', '#6c757d');
            return;
          }

          if (summary.failed > 0) {
            setTrayInfo('Sincronizados: ' + summary.success + ' | Fallidos: ' + summary.failed, '#fd7e14');
            return;
          }

          setTrayInfo('Sincronizacion completada: ' + summary.success + ' procesados.', '#198754');
        })
        .catch(function () {
          setTrayInfo('Ocurrio un error al sincronizar.', '#dc3545');
        })
        .finally(function () {
          syncNowBtn.disabled = false;
        });
    };

    var clearAllBtn = document.createElement('button');
    clearAllBtn.type = 'button';
    clearAllBtn.textContent = 'Vaciar';
    clearAllBtn.style.border = '1px solid #dc3545';
    clearAllBtn.style.borderRadius = '8px';
    clearAllBtn.style.background = '#ffffff';
    clearAllBtn.style.color = '#dc3545';
    clearAllBtn.style.fontSize = '12px';
    clearAllBtn.style.fontWeight = '600';
    clearAllBtn.style.padding = '7px 10px';
    clearAllBtn.style.cursor = 'pointer';
    clearAllBtn.onclick = function () {
      clearRequests().then(function () {
        updateStatusLabel();
        renderPendingList();
        setTrayInfo('Cola de sincronizacion vaciada.', '#6c757d');
      });
    };

    controls.appendChild(syncNowBtn);
    controls.appendChild(clearAllBtn);

    trayListEl = document.createElement('div');
    trayListEl.id = 'offline-sync-tray-list';
    trayListEl.style.maxHeight = 'calc(55vh - 94px)';
    trayListEl.style.overflowY = 'auto';
    trayListEl.style.padding = '8px';

    trayInfoEl = document.createElement('div');
    trayInfoEl.id = 'offline-sync-tray-info';
    trayInfoEl.style.padding = '8px 12px';
    trayInfoEl.style.fontSize = '12px';
    trayInfoEl.style.borderTop = '1px solid #e9ecef';
    trayInfoEl.style.background = '#f8f9fa';
    trayInfoEl.style.color = '#6c757d';
    trayInfoEl.textContent = 'Listo para sincronizar.';

    trayPanelEl.appendChild(trayHeader);
    trayPanelEl.appendChild(controls);
    trayPanelEl.appendChild(trayListEl);
    trayPanelEl.appendChild(trayInfoEl);

    trayListEl.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-offline-delete-id]');
      if (!btn) {
        return;
      }

      var id = Number(btn.getAttribute('data-offline-delete-id'));
      if (!id) {
        return;
      }

      deleteRequest(id).then(function () {
        updateStatusLabel();
        renderPendingList();
      });
    });

    document.body.appendChild(trayPanelEl);
  }

  function setTrayInfo(message, color) {
    if (trayInfoEl) {
      trayInfoEl.textContent = message;
      trayInfoEl.style.color = color || '#6c757d';
    }

    var status = ensureStatusElement();
    status.textContent = message;
    status.style.color = color || '#212529';
  }

  function escapeHtml(value) {
    return String(value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function formatDateLabel(value) {
    try {
      return new Date(value).toLocaleString();
    } catch (error) {
      return value || '';
    }
  }

  function compactUrl(value) {
    var text = String(value || '');
    return text.length <= 56 ? text : text.slice(0, 53) + '...';
  }

  function renderPendingList() {
    if (!trayListEl) {
      return;
    }

    getAllRequests()
      .then(function (requests) {
        requests.sort(function (a, b) {
          return (a.id || 0) - (b.id || 0);
        });

        if (!requests.length) {
          trayListEl.innerHTML = '<div style="padding:10px 8px; color:#6c757d; font-size:12px;">Sin pendientes de sincronizacion.</div>';
          return;
        }

        trayListEl.innerHTML = requests.map(function (record) {
          var methodColor =
            record.method === 'DELETE' ? '#dc3545' :
            record.method === 'POST' ? '#0d6efd' :
            record.method === 'PATCH' ? '#fd7e14' :
            '#6f42c1';

          return (
            '<div style="border:1px solid #e9ecef; border-radius:10px; padding:8px; margin-bottom:8px;">' +
              '<div style="display:flex; justify-content:space-between; align-items:center; gap:8px;">' +
                '<span style="font-size:11px; font-weight:700; color:' + methodColor + ';">' + escapeHtml(record.method || 'POST') + '</span>' +
                '<button type="button" data-offline-delete-id="' + Number(record.id || 0) + '" style="border:1px solid #ced4da; background:#fff; border-radius:6px; padding:2px 7px; font-size:11px; cursor:pointer;">Quitar</button>' +
              '</div>' +
              '<div style="font-size:12px; color:#212529; margin-top:4px; word-break:break-all;">' + escapeHtml(compactUrl(record.url || '')) + '</div>' +
              '<div style="font-size:11px; color:#6c757d; margin-top:4px;">' + escapeHtml(formatDateLabel(record.createdAt)) + '</div>' +
              '<div style="font-size:11px; color:#6c757d; margin-top:2px;">Reintentos: ' + escapeHtml(String(record.retries || 0)) + '</div>' +
              (record.lastError
                ? '<div style="font-size:11px; color:#dc3545; margin-top:4px; white-space:normal;">' + escapeHtml(record.lastError) + '</div>'
                : '') +
            '</div>'
          );
        }).join('');
      })
      .catch(function () {
        trayListEl.innerHTML = '<div style="padding:10px 8px; color:#dc3545; font-size:12px;">No se pudo leer la cola offline.</div>';
      });
  }

  function openTray() {
    ensureTrayElements();
    trayPanelEl.style.display = 'block';
    renderPendingList();
  }

  function closeTray() {
    if (trayPanelEl) {
      trayPanelEl.style.display = 'none';
    }
  }

  function updateStatusLabel() {
    countRequests().then(function (pending) {
      var el = ensureStatusElement();

      if (navigator.onLine) {
        if (pending > 0) {
          el.textContent = 'En linea | Pendientes: ' + pending;
          el.style.background = '#fff3cd';
          el.style.color = '#664d03';
        } else {
          el.textContent = 'En linea | Sin pendientes';
          el.style.background = '#d1e7dd';
          el.style.color = '#0f5132';
        }
      } else {
        el.textContent = 'Sin conexion | Pendientes: ' + pending;
        el.style.background = '#f8d7da';
        el.style.color = '#842029';
      }

      if (trayPanelEl && trayPanelEl.style.display !== 'none') {
        renderPendingList();
      }
    }).catch(function () {
      return;
    });
  }

  function enqueueRequest(record) {
    return addRequest(record).then(function () {
      updateStatusLabel();
      renderPendingList();
    });
  }

  function shouldIntercept(details) {
    return isMutatingMethod(details.method) && sameOrigin(details.url);
  }

  function notifyQueueError(error) {
    console.warn('No fue posible guardar en cola offline:', error);
  }

  function ensureCsrfHeader(headers) {
    var token = getCsrfToken();
    if (!token) {
      return;
    }

    if (!headers['x-csrf-token']) {
      headers['x-csrf-token'] = token;
    }
    if (!headers.accept) {
      headers.accept = 'application/json';
    }
    if (!headers['x-requested-with']) {
      headers['x-requested-with'] = 'XMLHttpRequest';
    }
  }

  function refreshFormDataCsrf(body) {
    if (!(body instanceof FormData)) {
      return body;
    }

    var token = getCsrfToken();
    if (token) {
      body.delete('_token');
      body.append('_token', token);
    }

    return body;
  }

  function replayRequest(record) {
    var headers = Object.assign({}, record.headers || {});
    ensureCsrfHeader(headers);
    var body = refreshFormDataCsrf(deserializeBody(record));

    return originalFetch(record.url, {
      method: record.method,
      headers: headers,
      body: body,
      credentials: 'same-origin'
    });
  }

  function normalizeErrorMessage(message) {
    if (!message) {
      return 'Error desconocido';
    }

    var text = String(message).replace(/\s+/g, ' ').trim();
    return text.length > 220 ? text.slice(0, 217) + '...' : text;
  }

  function buildHttpError(response, bodyText) {
    var base = 'HTTP ' + response.status + ' ' + (response.statusText || 'Error');
    var cleaned = normalizeErrorMessage(bodyText || '');
    return cleaned ? (base + ' | ' + cleaned) : base;
  }

  function flushQueue(options) {
    var force = options && options.force;

    if (syncInProgress) {
      return Promise.resolve({ total: 0, success: 0, failed: 0, skippedInProgress: true, skippedOffline: false });
    }

    if (!force && !navigator.onLine) {
      return Promise.resolve({ total: 0, success: 0, failed: 0, skippedInProgress: false, skippedOffline: true });
    }

    syncInProgress = true;

    return getAllRequests()
      .then(function (requests) {
        var summary = { total: requests.length, success: 0, failed: 0, skippedInProgress: false, skippedOffline: false };

        if (!requests.length) {
          return summary;
        }

        requests.sort(function (a, b) {
          return (a.id || 0) - (b.id || 0);
        });

        var chain = Promise.resolve();
        requests.forEach(function (record) {
          chain = chain.then(function () {
            var attemptTime = new Date().toISOString();

            return replayRequest(record)
              .then(function (response) {
                if (response.ok) {
                  summary.success += 1;
                  return deleteRequest(record.id);
                }

                summary.failed += 1;
                return response.clone().text().catch(function () {
                  return '';
                }).then(function (text) {
                  record.retries = (record.retries || 0) + 1;
                  record.lastTriedAt = attemptTime;
                  record.lastError = buildHttpError(response, text);
                  return updateRequest(record);
                });
              })
              .catch(function (error) {
                summary.failed += 1;
                record.retries = (record.retries || 0) + 1;
                record.lastTriedAt = attemptTime;
                record.lastError = normalizeErrorMessage(error && error.message ? error.message : 'Error de red');
                return updateRequest(record).catch(function () {
                  return Promise.resolve();
                });
              });
          });
        });

        return chain.then(function () {
          return summary;
        });
      })
      .finally(function () {
        syncInProgress = false;
        updateStatusLabel();
        renderPendingList();
      });
  }

  function showQueuedMessage() {
    var message = 'Sin conexion: la solicitud se guardo localmente y se sincronizara cuando vuelva el internet.';
    if (window.Swal && typeof window.Swal.fire === 'function') {
      window.Swal.fire({
        icon: 'info',
        title: 'Guardado offline',
        text: message,
        confirmButtonText: 'Aceptar'
      });
      return;
    }

    window.alert(message);
  }

  function shouldQueueForm(form) {
    if (!(form instanceof HTMLFormElement)) {
      return false;
    }

    if (String(form.getAttribute('method') || 'GET').toUpperCase() === 'GET') {
      return false;
    }

    if (!sameOrigin(form.getAttribute('action') || window.location.href)) {
      return false;
    }

    var enctype = String(form.enctype || '').toLowerCase();
    if (enctype.indexOf('multipart/form-data') !== -1) {
      return false;
    }

    return true;
  }

  window.fetch = function (input, init) {
    var requestUrl = typeof input === 'string' ? input : (input && input.url ? input.url : '');
    var requestMethod = String(((init && init.method) || (typeof input !== 'string' && input && input.method) || 'GET')).toUpperCase();
    var requestDetails = {
      url: new URL(requestUrl, window.location.origin).toString(),
      method: requestMethod
    };

    if (!shouldIntercept(requestDetails)) {
      return originalFetch(input, init);
    }

    if (!navigator.onLine) {
      var offlineDetails;
      try {
        offlineDetails = extractRequestDetails(input, init || {});
      } catch (serializationError) {
        return Promise.reject(serializationError);
      }

      return enqueueRequest(offlineDetails)
        .then(function () {
          return createQueuedResponse();
        })
        .catch(function (error) {
          notifyQueueError(error);
          throw error;
        });
    }

    return originalFetch(input, init).catch(function (error) {
      if (navigator.onLine) {
        throw error;
      }

      var fallbackDetails;
      try {
        fallbackDetails = extractRequestDetails(input, init || {});
      } catch (serializationError) {
        return Promise.reject(serializationError);
      }

      return enqueueRequest(fallbackDetails)
        .then(function () {
          return createQueuedResponse();
        })
        .catch(function (queueError) {
          notifyQueueError(queueError);
          throw error;
        });
    });
  };

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!shouldQueueForm(form) || navigator.onLine) {
      return;
    }

    try {
      var submitter = event.submitter || null;
      var record = buildFormRequestDetails(form, submitter);
      event.preventDefault();
      enqueueRequest(record)
        .then(function () {
          showQueuedMessage();
        })
        .catch(function (error) {
          notifyQueueError(error);
          if (window.Swal && typeof window.Swal.fire === 'function') {
            window.Swal.fire('Error', error.message || 'No se pudo guardar offline.', 'error');
          }
        });
    } catch (error) {
      notifyQueueError(error);
    }
  }, true);

  window.addEventListener('online', function () {
    flushQueue({ force: false, manual: false });
  });

  window.addEventListener('offline', function () {
    updateStatusLabel();
  });

  window.AgroOfflineSync = {
    syncNow: function () {
      return flushQueue({ force: true, manual: true });
    },
    openTray: openTray,
    closeTray: closeTray,
    getPendingRequests: getAllRequests,
    clearPendingRequests: function () {
      return clearRequests().then(function () {
        updateStatusLabel();
        renderPendingList();
      });
    }
  };

  window.addEventListener('DOMContentLoaded', function () {
    ensureStatusElement();
    ensureTrayElements();
    updateStatusLabel();
    renderPendingList();
    flushQueue({ force: false, manual: false });
    window.setInterval(function () {
      flushQueue({ force: false, manual: false });
    }, SYNC_INTERVAL_MS);
  });
})();