/**
 * Envío del formulario de cotización flotas → correo (misma URL del sitio + PHP).
 */
(function (global) {
  "use strict";

  function flotaLeadEndpoints() {
    if (global.AD_FLOTA_LEAD && global.AD_FLOTA_LEAD.endpoint) {
      return [global.AD_FLOTA_LEAD.endpoint];
    }

    if (location.protocol === "file:") {
      return [];
    }

    var host = (location.hostname || "").toLowerCase();
    var path = location.pathname || "";
    var urls = [];

    function pushUnique(href) {
      if (href && urls.indexOf(href) === -1) {
        urls.push(href);
      }
    }

    if (host === "www.autodealer.com.co" || host === "autodealer.com.co") {
      /* Producción: sitio en public_html/ (CanalFlotas/, flota-lead.php en raíz) */
      pushUnique(location.origin + "/flota-lead.php");
      pushUnique(location.origin + "/api/flota-lead.php");
      pushUnique(location.origin + "/autodealer-nuevo/flota-lead.php");
      pushUnique(location.origin + "/autodealer-nuevo/api/flota-lead.php");
      return urls;
    }

    if (path.indexOf("/autodealer-nuevo/") !== -1) {
      pushUnique(location.origin + "/autodealer-nuevo/flota-lead.php");
      pushUnique(location.origin + "/autodealer-nuevo/api/flota-lead.php");
      return urls;
    }

    try {
      pushUnique(new URL("../flota-lead.php", location.href).href);
      pushUnique(new URL("../api/flota-lead.php", location.href).href);
      pushUnique(new URL("flota-lead.php", location.href).href);
      pushUnique(new URL("api/flota-lead.php", location.href).href);
    } catch (e) {
      return [];
    }
    return urls;
  }

  function encodeBody(data) {
    var p = new URLSearchParams();
    Object.keys(data).forEach(function (key) {
      p.append(key, data[key]);
    });
    return p.toString();
  }

  function friendlyError(body, status) {
    var msg =
      (body && body.error) ||
      (body && body.message) ||
      (body && body.data && body.data.message) ||
      "";
    if (
      body &&
      (body.code === "rest_no_route" ||
        (typeof msg === "string" && msg.indexOf("ninguna ruta") !== -1))
    ) {
      return (
        "El envío no está activo en el servidor. Sube api/flota-lead.php al hosting " +
        "o escríbenos a servicio@autodealer.com.co."
      );
    }
    if (status === 404) {
      return (
        "No encontramos flota-lead.php en el servidor (404). " +
        "Sube flota-lead.php y flota-lead-config.php a public_html/autodealer-nuevo/ (ver DEPLOY-HOSTINGER.md)."
      );
    }
    return (
      msg ||
      "No se pudo enviar. Intenta de nuevo o escríbenos a servicio@autodealer.com.co."
    );
  }

  function networkErrorMessage() {
    return (
      "No pudimos enviar desde aquí. Entra a la página desde autodealer.com.co " +
      "(no abras el HTML guardado en tu computador). Si ya está en la web, falta subir la carpeta api/ al hosting."
    );
  }

  function payloadFromForm(form) {
    var fd = new FormData(form);
    return {
      nombre: String(fd.get("nombre") || "").trim(),
      email: String(fd.get("email") || "").trim(),
      tel: String(fd.get("tel") || "").trim(),
      size: String(fd.get("size") || "").trim(),
      company: String(fd.get("company") || "").trim(),
    };
  }

  function showToast(msg, isError) {
    var toast = document.getElementById("fpToast");
    var toastMsg = document.getElementById("fpToastMsg");
    var toastIcon = toast && toast.querySelector("i");
    if (!toast || !toastMsg) return;
    toastMsg.textContent = msg;
    if (toastIcon) {
      toastIcon.className = isError
        ? "fa-solid fa-circle-exclamation"
        : "fa-solid fa-circle-check";
      toastIcon.style.color = isError ? "#fca5a5" : "#86efac";
    }
    toast.classList.toggle("fp-toast--err", !!isError);
    toast.classList.add("fp-show");
    setTimeout(function () {
      toast.classList.remove("fp-show");
    }, isError ? 6500 : 4200);
  }

  function bindForm(form, options) {
    if (!form || form.dataset.flotaLeadBound === "1") return;
    form.dataset.flotaLeadBound = "1";
    var onSuccess = options && options.onSuccess;

    form.addEventListener("submit", function (e) {
      e.preventDefault();
      var urls = flotaLeadEndpoints();
      if (!urls.length) {
        showToast(
          "Este formulario solo envía correo cuando la página está en internet (autodealer.com.co), no abriendo el archivo en tu PC.",
          true
        );
        return;
      }

      var btn = form.querySelector('button[type="submit"]');
      var data = payloadFromForm(form);
      if (btn) {
        btn.disabled = true;
        btn.dataset.flotaLeadLabel = btn.dataset.flotaLeadLabel || btn.innerHTML;
        btn.innerHTML = "Enviando…";
      }

      function postAt(index) {
        var url = urls[index];
        return fetch(url, {
          method: "POST",
          mode: "cors",
          headers: {
            "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
          },
          body: encodeBody(data),
        }).then(function (res) {
          return res
            .json()
            .catch(function () {
              return {};
            })
            .then(function (body) {
              return { ok: res.ok, status: res.status, body: body, url: url };
            });
        });
      }

      function tryNext(index) {
        if (index >= urls.length) {
          return Promise.reject(
            new Error(
              "No encontramos flota-lead.php en el servidor (404). Sube flota-lead.php y flota-lead-config.php a public_html/autodealer-nuevo/."
            )
          );
        }
        return postAt(index).then(function (result) {
          if (result.status === 404 && index + 1 < urls.length) {
            return tryNext(index + 1);
          }
          return result;
        });
      }

      tryNext(0)
        .then(function (result) {
          var body = result.body || {};
          if (!result.ok || !body.ok) {
            throw new Error(friendlyError(body, result.status));
          }
          var first = data.nombre.split(" ")[0];
          form.reset();
          if (typeof onSuccess === "function") onSuccess(first);
          showToast(
            "Gracias" +
              (first ? ", " + first : "") +
              " — recibimos tu solicitud. Te contactamos en 24 h hábiles.",
            false
          );
        })
        .catch(function (err) {
          var msg = err && err.message ? err.message : "";
          if (!msg || msg === "Failed to fetch" || err.name === "TypeError") {
            msg = networkErrorMessage();
          }
          showToast(msg, true);
        })
        .finally(function () {
          if (btn) {
            btn.disabled = false;
            btn.innerHTML = btn.dataset.flotaLeadLabel || "Enviar solicitud";
          }
        });
    });
  }

  global.ADFlotaLead = {
    endpoints: flotaLeadEndpoints,
    bindForm: bindForm,
    openModal: function () {
      var modal = document.getElementById("fpModal");
      if (modal) {
        modal.classList.add("fp-open");
        document.body.style.overflow = "hidden";
      }
    },
  };

  function init() {
    document.querySelectorAll("form[data-flota-lead]").forEach(function (form) {
      bindForm(form, {
        onSuccess: function () {
          if (form.id === "fpForm") {
            var modal = document.getElementById("fpModal");
            if (modal) {
              modal.classList.remove("fp-open");
              document.body.style.overflow = "";
            }
          }
        },
      });
    });
  }

  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", init);
  } else {
    init();
  }
})(window);
