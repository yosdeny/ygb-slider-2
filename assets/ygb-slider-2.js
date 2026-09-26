/**
 * YGB Slider 2 - Frontend JavaScript
 * Version: 4.2.0
 *
 * Motor unificado que soporta:
 * - Modo clásico: deslizar / fundido / zoom / cubo / random
 * - Modo aleatorio: elige entre fundido/zoom/cubo en cada transición
 * - Modo mixto: cada slide con su propio efecto
 *
 * Sin parallax ni fade del texto desde v4.2.0.
 * Auto-inicializa todas las instancias .ygb-slider de la página.
 */
(function() {
    'use strict';

    var DRAG_CLICK_THRESHOLD = 5;
    var EDGE_RESISTANCE      = 0.35;
    var SNAP_THRESHOLD_RATIO = 0.15;
    var SNAP_THRESHOLD_MIN   = 50;

    var FAMILIA_A = ['deslizar'];
    var FAMILIA_B = ['fundido', 'zoom', 'cubo', 'random'];
    var RANDOM_POOL = ['fundido', 'zoom', 'cubo'];

    var TRANSITION_CLEANUP_MS = 750;

    function prefiereMovimientoReducido() {
        try {
            return !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);
        } catch (e) {
            return false;
        }
    }

    function detectarTransicion(raiz) {
        var trans = raiz.dataset ? raiz.dataset.trans : '';
        if (trans && (FAMILIA_A.indexOf(trans) !== -1 || FAMILIA_B.indexOf(trans) !== -1)) {
            return trans;
        }
        var clases = raiz.className ? raiz.className.split(/\s+/) : [];
        for (var i = 0; i < clases.length; i++) {
            if (clases[i].indexOf('ygb-trans-') === 0) {
                var valor = clases[i].replace('ygb-trans-', '');
                if (FAMILIA_A.indexOf(valor) !== -1 || FAMILIA_B.indexOf(valor) !== -1) {
                    return valor;
                }
            }
        }
        return 'deslizar';
    }

    function esModoMixto(raiz) {
        if (!raiz) return false;
        if (raiz.dataset && raiz.dataset.modo === 'mixto') return true;
        if (raiz.classList && raiz.classList.contains('ygb-modo-mixto')) return true;
        return false;
    }

    function addClass(el, cls) {
        if (el && el.classList && !el.classList.contains(cls)) el.classList.add(cls);
    }

    function removeClass(el, cls) {
        if (el && el.classList && el.classList.contains(cls)) el.classList.remove(cls);
    }

    function forceStyle(el, prop, val) {
        if (!el || !el.style || !el.style.setProperty) return;
        el.style.setProperty(prop, val, 'important');
    }

    function clearStyle(el, prop) {
        if (!el || !el.style) return;
        el.style.removeProperty(prop);
    }

    function randomPick(arr) {
        return arr[Math.floor(Math.random() * arr.length)];
    }

    function inicializarSlider(raiz) {
        if (!raiz || raiz.dataset.ygbInit === '1') return;
        raiz.dataset.ygbInit = '1';

        var instanceId  = raiz.dataset.instance || '0';
        var velocidad   = parseInt(raiz.dataset.vel, 10) || 4000;
        var autoplay    = raiz.dataset.auto === '1';
        var color       = raiz.dataset.color || '#ff6b6b';
        var modoMixto   = esModoMixto(raiz);
        var transicion  = modoMixto ? 'mixto' : detectarTransicion(raiz);

        var esFamiliaA = !modoMixto && (transicion === 'deslizar');
        var esRandom   = !modoMixto && (transicion === 'random');
        var esFamiliaB = !modoMixto && !esFamiliaA;

        if (prefiereMovimientoReducido() && !modoMixto) {
            transicion = 'fundido';
            raiz.setAttribute('data-trans', 'fundido');
            raiz.className = raiz.className.replace(/ygb-trans-\S+/g, '').trim();
            raiz.classList.add('ygb-trans-fundido');
            esFamiliaA = false;
            esRandom = false;
            esFamiliaB = true;
        }

        var contenedor = raiz.querySelector('.ygb-contenedor');
        var pista      = raiz.querySelector('.ygb-pista');
        var dotsDiv    = raiz.querySelector('.ygb-dots');

        if (!contenedor || !pista) return;

        var items = pista.querySelectorAll('.ygb-item');
        var total = items.length;
        if (total === 0) return;

        var actual = 0;
        var animacion = null;
        var tiempoInicio = 0;
        var pausado = false;

        var dragging        = false;
        var startX          = 0;
        var deltaX          = 0;
        var activePointerId = null;
        var wasDragged      = false;

        try {
            var guardado = localStorage.getItem('ygb_slider2_velocidad_' + instanceId);
            if (guardado) {
                var nv = parseInt(guardado, 10);
                if (!isNaN(nv) && nv >= 1000 && nv <= 10000) velocidad = nv;
            }
        } catch (e) {}

        // ============================================================
        // HELPERS
        // ============================================================

        function efectoDe(idx) {
            if (modoMixto) {
                var e = items[idx].dataset.efecto || 'fundido';
                if (FAMILIA_B.indexOf(e) === -1) return 'fundido';
                return e;
            }
            if (esFamiliaA) return 'deslizar';
            return transicion;
        }

        function velocidadDe(idx) {
            var vItem = parseInt(items[idx].dataset.itemVel, 10);
            if (!isNaN(vItem) && vItem >= 1000 && vItem <= 10000) return vItem;
            return velocidad;
        }

        // ============================================================
        // FORZADO DE LAYOUT
        // ============================================================

        function forzarLayoutFamiliaA() {
            forceStyle(pista, 'display', 'flex');
            forceStyle(pista, 'flex-wrap', 'nowrap');
            forceStyle(pista, 'flex-direction', 'row');
            forceStyle(pista, 'position', 'relative');
            forceStyle(pista, 'width', '100%');
            forceStyle(pista, 'transition', 'transform 0.5s cubic-bezier(0.25, 1, 0.5, 1)');

            for (var k = 0; k < total; k++) {
                forceStyle(items[k], 'position', 'relative');
                forceStyle(items[k], 'flex', '0 0 100%');
                forceStyle(items[k], 'width', '100%');
                forceStyle(items[k], 'min-width', '100%');
                forceStyle(items[k], 'opacity', '1');
                forceStyle(items[k], 'z-index', '1');
                forceStyle(items[k], 'pointer-events', 'auto');
                forceStyle(items[k], 'transition', 'none');
                clearStyle(items[k], 'top');
                clearStyle(items[k], 'left');
                clearStyle(items[k], 'height');
            }
            clearStyle(pista, 'height');
            clearStyle(pista, 'min-height');
            clearStyle(contenedor, 'height');
            clearStyle(contenedor, 'min-height');
        }

        function forzarLayoutApilado() {
            forceStyle(pista, 'display', 'block');
            forceStyle(pista, 'position', 'relative');
            forceStyle(pista, 'width', '100%');
            forceStyle(pista, 'margin', '0');
            forceStyle(pista, 'padding', '0');
            forceStyle(pista, 'transform', 'none');
            forceStyle(pista, 'transition', 'none');

            if (modoMixto || esRandom) {
                forceStyle(pista, 'transform-style', 'preserve-3d');
                forceStyle(contenedor, 'perspective', '1200px');
            }

            for (var k = 0; k < total; k++) {
                forceStyle(items[k], 'position', 'absolute');
                forceStyle(items[k], 'top', '0');
                forceStyle(items[k], 'left', '0');
                forceStyle(items[k], 'right', 'auto');
                forceStyle(items[k], 'bottom', 'auto');
                forceStyle(items[k], 'width', '100%');
                forceStyle(items[k], 'max-width', '100%');
                forceStyle(items[k], 'min-width', '0');
                forceStyle(items[k], 'height', 'auto');
                forceStyle(items[k], 'margin', '0');
                forceStyle(items[k], 'padding', '0');
                forceStyle(items[k], 'flex', 'none');
                forceStyle(items[k], 'backface-visibility', 'hidden');
                forceStyle(items[k], 'transform-style', 'preserve-3d');

                if (k === actual) {
                    forceStyle(items[k], 'opacity', '1');
                    forceStyle(items[k], 'z-index', '2');
                    forceStyle(items[k], 'pointer-events', 'auto');
                } else {
                    forceStyle(items[k], 'opacity', '0');
                    forceStyle(items[k], 'z-index', '1');
                    forceStyle(items[k], 'pointer-events', 'none');
                }
            }
        }

        function ajustarAlturaApilado() {
            var maxAlto = 0;
            for (var k = 0; k < total; k++) {
                var h = items[k].offsetHeight || 0;
                if (h > maxAlto) maxAlto = h;
            }
            if (maxAlto > 0) forceStyle(pista, 'height', maxAlto + 'px');
        }

        if (esFamiliaA) {
            forzarLayoutFamiliaA();
        } else {
            forzarLayoutApilado();
        }

        // ============================================================
        // RENDERIZADO
        // ============================================================

        function detenerAnim() {
            if (animacion) {
                cancelAnimationFrame(animacion);
                animacion = null;
            }
        }

        function limpiarTransformsDrag() {
            for (var k = 0; k < total; k++) {
                items[k].style.removeProperty('transform');
                items[k].style.removeProperty('transform-origin');
            }
        }

        function aplicarEstadoActivo() {
            for (var k = 0; k < total; k++) {
                if (k === actual) {
                    addClass(items[k], 'ygb-actual');
                    forceStyle(items[k], 'opacity', '1');
                    forceStyle(items[k], 'z-index', '2');
                    forceStyle(items[k], 'pointer-events', 'auto');
                } else {
                    removeClass(items[k], 'ygb-actual');
                    forceStyle(items[k], 'opacity', '0');
                    forceStyle(items[k], 'z-index', '1');
                    forceStyle(items[k], 'pointer-events', 'none');
                }
            }
        }

        function aplicarTransicionRandom(oldIdx, newIdx, efecto) {
            var cItem = items[newIdx];
            var itemViejo = items[oldIdx];

            for (var k = 0; k < total; k++) {
                if (k === oldIdx || k === newIdx) continue;
                items[k].style.removeProperty('transform');
                items[k].style.removeProperty('transform-origin');
            }

            cItem.style.setProperty('transition', 'none', 'important');
            cItem.style.setProperty('opacity', '0', 'important');

            if (efecto === 'zoom') {
                cItem.style.setProperty('transform', 'scale(1.15)', 'important');
                cItem.style.setProperty('transform-origin', '50% 50%', 'important');
            } else if (efecto === 'cubo') {
                cItem.style.setProperty('transform', 'rotateY(-100deg)', 'important');
                cItem.style.setProperty('transform-origin', '100% 50%', 'important');
            } else {
                cItem.style.setProperty('transform', 'none', 'important');
            }

            void cItem.offsetWidth;

            cItem.style.removeProperty('transition');
            cItem.style.removeProperty('opacity');

            if (efecto === 'zoom') {
                cItem.style.setProperty('transform', 'scale(1)', 'important');
            } else if (efecto === 'cubo') {
                cItem.style.setProperty('transform', 'rotateY(0deg)', 'important');
            } else {
                cItem.style.setProperty('transform', 'none', 'important');
            }

            removeClass(itemViejo, 'ygb-actual');
            forceStyle(itemViejo, 'opacity', '0');
            forceStyle(itemViejo, 'z-index', '1');
            forceStyle(itemViejo, 'pointer-events', 'none');

            addClass(cItem, 'ygb-actual');
            forceStyle(cItem, 'opacity', '1');
            forceStyle(cItem, 'z-index', '2');
            forceStyle(cItem, 'pointer-events', 'auto');

            setTimeout(function() {
                if (items[newIdx] === cItem) {
                    cItem.style.removeProperty('transform');
                    cItem.style.removeProperty('transform-origin');
                }
            }, TRANSITION_CLEANUP_MS);
        }

        function render() {
            if (esFamiliaA) {
                pista.style.transform = 'translate3d(-' + (actual * 100) + '%, 0, 0)';
            } else if (esFamiliaB && !esRandom) {
                limpiarTransformsDrag();
                aplicarEstadoActivo();
                ajustarAlturaApilado();
            }
            actualizarDots();
        }

        function irA(index) {
            if (index < 0) index = total - 1;
            if (index >= total) index = 0;
            if (actual === index) return;

            var oldIdx = actual;
            actual = index;

            if (esRandom) {
                var efecto = randomPick(RANDOM_POOL);
                aplicarTransicionRandom(oldIdx, actual, efecto);
            } else {
                render();
            }

            if (autoplay && !pausado) {
                detenerAnim();
                iniciarTimer();
            }
        }

        function siguiente() {
            irA(actual + 1);
        }

        // ============================================================
        // DOTS
        // ============================================================

        function crearDots() {
            if (!dotsDiv) return;
            dotsDiv.innerHTML = '';
            for (var i = 0; i < total; i++) {
                var dot = document.createElement('div');
                dot.className = 'ygb-dot';
                dot.setAttribute('data-idx', i);
                dot.setAttribute('role', 'button');
                dot.setAttribute('tabindex', '0');
                dot.setAttribute('aria-label', 'Slide ' + (i + 1));

                var bar = document.createElement('div');
                bar.className = 'ygb-dot-bar';

                var fill = document.createElement('div');
                fill.className = 'ygb-dot-fill';
                fill.style.background = color;

                bar.appendChild(fill);
                dot.appendChild(bar);

                (function(idx) {
                    dot.addEventListener('click', function(e) {
                        e.preventDefault();
                        irA(idx);
                    });
                })(i);

                dotsDiv.appendChild(dot);
            }
            actualizarDots();
        }

        function actualizarDots() {
            if (!dotsDiv) return;
            var dots = dotsDiv.querySelectorAll('.ygb-dot');
            for (var i = 0; i < dots.length; i++) {
                dots[i].style.opacity = (i === actual) ? '1' : '0.5';
            }
        }

        // ============================================================
        // AUTOPLAY
        // ============================================================

        function iniciarTimer() {
            detenerAnim();

            var dotActivo = dotsDiv ? dotsDiv.querySelectorAll('.ygb-dot')[actual] : null;
            if (dotActivo) {
                var fill = dotActivo.querySelector('.ygb-dot-fill');
                if (fill) fill.style.width = '0%';
            }

            var velActual = velocidadDe(actual);
            tiempoInicio = (window.performance && performance.now) ? performance.now() : Date.now();

            function animar(ahora) {
                if (pausado) {
                    animacion = requestAnimationFrame(animar);
                    return;
                }

                var transcurrido = ahora - tiempoInicio;
                var progreso = Math.min(1, transcurrido / velActual);

                var dotA = dotsDiv ? dotsDiv.querySelectorAll('.ygb-dot')[actual] : null;
                if (dotA) {
                    var f = dotA.querySelector('.ygb-dot-fill');
                    if (f) f.style.width = (progreso * 100) + '%';
                }

                if (progreso >= 1) {
                    siguiente();
                } else {
                    animacion = requestAnimationFrame(animar);
                }
            }

            animacion = requestAnimationFrame(animar);
        }

        // ============================================================
        // PANEL DEBUG
        // ============================================================

        function cambiarVelocidad(nueva) {
            if (nueva < 1000) nueva = 1000;
            if (nueva > 10000) nueva = 10000;
            velocidad = nueva;

            try {
                localStorage.setItem('ygb_slider2_velocidad_' + instanceId, nueva);
            } catch (e) {}

            var wrapper = raiz.parentNode;
            if (wrapper && wrapper.classList && wrapper.classList.contains('ygb-slider-wrapper')) {
                var rango = wrapper.querySelector('.ygb-rango[data-slider-instance="' + instanceId + '"]');
                var valor = wrapper.querySelector('.ygb-valor[data-slider-instance="' + instanceId + '"]');
                if (rango) rango.value = nueva;
                if (valor) valor.textContent = (nueva / 1000).toFixed(1) + 's';
            }

            if (autoplay && !pausado) {
                detenerAnim();
                iniciarTimer();
            }
        }

        function conectarControlesAdmin() {
            var wrapper = raiz.parentNode;
            if (!wrapper || !wrapper.querySelector) return;

            var rango = wrapper.querySelector('.ygb-rango[data-slider-instance="' + instanceId + '"]');
            if (rango) {
                rango.value = velocidad;
                rango.addEventListener('input', function(e) {
                    cambiarVelocidad(parseInt(e.target.value, 10));
                });
            }

            var valor = wrapper.querySelector('.ygb-valor[data-slider-instance="' + instanceId + '"]');
            if (valor) valor.textContent = (velocidad / 1000).toFixed(1) + 's';

            var botones = wrapper.querySelectorAll('.ygb-pre[data-slider-instance="' + instanceId + '"]');
            for (var i = 0; i < botones.length; i++) {
                botones[i].addEventListener('click', function() {
                    cambiarVelocidad(parseInt(this.dataset.vel, 10));
                });
            }
        }

        // ============================================================
        // AJUSTE DE ALTURA
        // ============================================================

        function reajustarAltura() {
            if (esFamiliaB || modoMixto) ajustarAlturaApilado();
        }

        // ============================================================
        // PREVIEW DE DRAG
        // ============================================================

        function aplicarPreview(candidato, progreso, efecto) {
            if (candidato === null || candidato < 0) return;

            var cItem = items[candidato];
            forceStyle(cItem, 'opacity', String(progreso));
            forceStyle(cItem, 'z-index', '3');

            if (efecto === 'zoom') {
                var escala = 1.15 - 0.15 * progreso;
                cItem.style.setProperty('transform', 'scale(' + escala + ')', 'important');
                cItem.style.setProperty('transform-origin', '50% 50%', 'important');
            } else if (efecto === 'cubo') {
                cItem.style.setProperty('transform-origin', '100% 50%', 'important');
                cItem.style.setProperty('transform', 'rotateY(' + (-100 + 100 * progreso) + 'deg)', 'important');
                items[actual].style.setProperty('transform', 'rotateY(' + (-100 * progreso) + 'deg)', 'important');
                items[actual].style.setProperty('transform-origin', '100% 50%', 'important');
            } else {
                cItem.style.setProperty('transform', 'none', 'important');
            }
        }

        function limpiarPreview() {
            for (var k = 0; k < total; k++) {
                items[k].style.removeProperty('transform');
                items[k].style.removeProperty('transform-origin');

                if (k === actual) {
                    forceStyle(items[k], 'opacity', '1');
                    forceStyle(items[k], 'z-index', '2');
                } else {
                    forceStyle(items[k], 'opacity', '0');
                    forceStyle(items[k], 'z-index', '1');
                }
            }
        }

        // ============================================================
        // DRAG / SWIPE
        // ============================================================

        function onPointerDown(e) {
            if (e.pointerType === 'mouse' && e.button !== 0) return;
            if (activePointerId !== null) return;

            activePointerId = e.pointerId;
            dragging = true;
            startX = e.clientX;
            deltaX = 0;
            wasDragged = false;

            detenerAnim();
            addClass(contenedor, 'ygb-dragging');

            if (contenedor.setPointerCapture) {
                try { contenedor.setPointerCapture(e.pointerId); } catch (err) {}
            }
        }

        function onPointerMove(e) {
            if (!dragging || e.pointerId !== activePointerId) return;

            if (wasDragged && e.cancelable) e.preventDefault();

            deltaX = e.clientX - startX;

            if (!wasDragged && Math.abs(deltaX) > DRAG_CLICK_THRESHOLD) {
                wasDragged = true;
            }

            var ancho = contenedor.clientWidth || 1;

            if (esFamiliaA) {
                var base = -actual * ancho;
                var pos = base + deltaX;

                var enPrimero = (actual === 0 && deltaX > 0);
                var enUltimo  = (actual === total - 1 && deltaX < 0);
                if (enPrimero || enUltimo) {
                    pos = base + deltaX * EDGE_RESISTANCE;
                }

                pista.style.transform = 'translate3d(' + pos + 'px, 0, 0)';
                return;
            }

            var candidato = null;
            if (deltaX < 0 && actual < total - 1) {
                candidato = actual + 1;
            } else if (deltaX > 0 && actual > 0) {
                candidato = actual - 1;
            }

            for (var k = 0; k < total; k++) {
                if (k === actual) continue;
                items[k].style.removeProperty('transform');
                items[k].style.removeProperty('transform-origin');
                forceStyle(items[k], 'opacity', '0');
                forceStyle(items[k], 'z-index', '1');
            }

            var progreso = Math.min(1, Math.abs(deltaX) / ancho);

            if (candidato !== null) {
                var efecto;
                if (esRandom) {
                    efecto = 'fundido';
                } else {
                    efecto = efectoDe(candidato);
                }
                aplicarPreview(candidato, progreso, efecto);
            }
        }

        function onPointerUp(e) {
            if (!dragging || e.pointerId !== activePointerId) return;

            dragging = false;
            activePointerId = null;
            removeClass(contenedor, 'ygb-dragging');

            if (contenedor.releasePointerCapture) {
                try { contenedor.releasePointerCapture(e.pointerId); } catch (err) {}
            }

            var ancho = contenedor.clientWidth || 1;
            var umbral = Math.max(SNAP_THRESHOLD_MIN, ancho * SNAP_THRESHOLD_RATIO);

            var destino = actual;
            if (deltaX < -umbral && actual < total - 1) {
                destino = actual + 1;
            } else if (deltaX > umbral && actual > 0) {
                destino = actual - 1;
            }

            var snapBack = (destino === actual);
            deltaX = 0;

            if (!snapBack) {
                actual = destino;
            }

            limpiarPreview();

            if (esFamiliaA) {
                pista.style.transform = 'translate3d(-' + (actual * 100) + '%, 0, 0)';
            } else {
                aplicarEstadoActivo();
                ajustarAlturaApilado();
            }

            actualizarDots();

            if (autoplay && !pausado) {
                iniciarTimer();
            }
        }

        function onPointerCancel(e) {
            if (!dragging || e.pointerId !== activePointerId) return;
            dragging = false;
            activePointerId = null;
            removeClass(contenedor, 'ygb-dragging');
            deltaX = 0;

            limpiarPreview();
            render();

            if (autoplay && !pausado) iniciarTimer();
        }

        function onClick(e) {
            if (wasDragged) {
                e.preventDefault();
                e.stopPropagation();
                wasDragged = false;
            }
        }

        function onDragStart(e) {
            if (e.cancelable) e.preventDefault();
            return false;
        }

        // ============================================================
        // PAUSA HOVER / VISIBILIDAD
        // ============================================================

        function onMouseEnter() { pausado = true; }
        function onMouseLeave() {
            pausado = false;
            if (autoplay) iniciarTimer();
        }

        function conectarIntersectionObserver() {
            if (!('IntersectionObserver' in window)) return;

            var observer = new IntersectionObserver(function(entries) {
                entries.forEach(function(entry) {
                    if (!entry.isIntersecting) {
                        pausado = true;
                    } else if (!contenedor.matches(':hover')) {
                        pausado = false;
                        if (autoplay) iniciarTimer();
                    }
                });
            }, { threshold: 0.5 });

            observer.observe(contenedor);
        }

        // ============================================================
        // INICIALIZACIÓN
        // ============================================================

        crearDots();
        conectarControlesAdmin();
        render();

        if (esFamiliaB || modoMixto) {
            reajustarAltura();
            setTimeout(reajustarAltura, 50);
            setTimeout(reajustarAltura, 300);
            window.addEventListener('resize', reajustarAltura);
            window.addEventListener('load', function() { setTimeout(reajustarAltura, 100); });

            var imgsCarga = pista.querySelectorAll('img');
            for (var q = 0; q < imgsCarga.length; q++) {
                if (imgsCarga[q].complete) continue;
                imgsCarga[q].addEventListener('load', reajustarAltura);
                imgsCarga[q].addEventListener('error', reajustarAltura);
            }
        }

        if (autoplay) iniciarTimer();

        if (total > 1 && window.PointerEvent) {
            contenedor.addEventListener('pointerdown', onPointerDown);
            contenedor.addEventListener('pointermove', onPointerMove);
            contenedor.addEventListener('pointerup', onPointerUp);
            contenedor.addEventListener('pointercancel', onPointerCancel);
            contenedor.addEventListener('pointerleave', onPointerUp);
            contenedor.addEventListener('click', onClick, true);
        }

        contenedor.addEventListener('dragstart', onDragStart);
        var hijosDrag = contenedor.querySelectorAll('img, a');
        for (var j = 0; j < hijosDrag.length; j++) {
            hijosDrag[j].addEventListener('dragstart', onDragStart);
        }

        contenedor.addEventListener('mouseenter', onMouseEnter);
        contenedor.addEventListener('mouseleave', onMouseLeave);

        conectarIntersectionObserver();

        raiz.ygb = {
            irA: irA,
            siguiente: siguiente,
            cambiarVelocidad: cambiarVelocidad,
            actual: function() { return actual; },
            reajustarAltura: reajustarAltura,
            modo: modoMixto ? 'mixto' : (esRandom ? 'random' : (esFamiliaA ? 'deslizar' : 'apilado')),
            efectoDe: efectoDe
        };
    }

    function inicializarTodos() {
        var sliders = document.querySelectorAll('.ygb-slider');
        for (var i = 0; i < sliders.length; i++) {
            inicializarSlider(sliders[i]);
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', inicializarTodos);
    } else {
        inicializarTodos();
    }

    window.addEventListener('load', function() {
        setTimeout(inicializarTodos, 100);
    });

})();