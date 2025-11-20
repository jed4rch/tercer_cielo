<?php
require_once '../includes/init.php';
require_once '../includes/func_carrito.php';

$carrito = get_carrito();
$total_items = contar_items_carrito();
$total_precio = get_total_carrito();
?>

<!-- BOTÓN FLOTANTE -->
<button class="btn-carrito-flotante" onclick="toggleCarrito()">
    <i class="bi bi-cart3"></i>
    <span class="badge bg-danger" id="btn-contador"><?= $total_items ?></span>
</button>

<!-- CARRITO LATERAL -->
<div class="carrito-flotante" id="carritoFlotante">
    <div class="carrito-header">
        <h5><i class="bi bi-cart3"></i> Carrito <span class="badge bg-success" id="carrito-contador"><?= $total_items ?></span></h5>
        <button class="btn-close btn-close-white" onclick="cerrarCarrito()"></button>
    </div>
    <div id="carrito-body" class="p-3">
        <?php
        $carrito = get_carrito();
        $total_precio = get_total_carrito();
        $tiene_productos = !empty($carrito) && is_array($carrito);
        ?>
        <?php if (empty($carrito)): ?>
            <p class="text-center text-muted">Tu carrito está vacío</p>
        <?php else: ?>
            <?php foreach ($carrito as $id => $item): ?>
                <div class="carrito-item d-flex align-items-center mb-3" data-id="<?= $id ?>">
                    <?php
                    $img_src = !empty($item['imagen'])
                        ? $item['imagen']
                        : '../assets/img/default-product.jpg';
                    ?>
                    <a href="producto.php?id=<?= $id ?>" class="me-3">
                        <img src="<?= htmlspecialchars($img_src) ?>" 
                             alt="<?= htmlspecialchars($item['nombre']) ?>"
                             class="rounded"
                             style="width:50px;height:50px;object-fit:contain;background:#f8f9fa;"
                             onerror="this.src='../assets/img/default-product.jpg';">
                    </a>
                    <div class="flex-grow-1">
                        <h6 class="mb-1">
                            <a href="producto.php?id=<?= $id ?>" class="text-decoration-none text-dark">
                                <?= htmlspecialchars($item['nombre']) ?>
                            </a>
                        </h6>
                        <small style="color: #007bff; font-weight: 600;">S/ <?= number_format($item['precio'], 2) ?></small>
                    </div>
                    <div class="d-flex align-items-center">
                        <button class="btn btn-sm" onclick="cambiarCant(<?= $id ?>, -1)" style="border: 2px solid #007bff; color: #007bff; background: white; transition: all 0.2s ease;" onmouseover="this.style.background='#007bff'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#007bff';">-</button>
                        <input type="text"
                            class="form-control form-control-sm mx-1 text-center cantidad-input"
                            value="<?= $item['cantidad'] ?>"
                            readonly
                            style="width:50px; border: 2px solid #e9ecef;">
                        <button class="btn btn-sm" onclick="cambiarCant(<?= $id ?>, 1)" style="border: 2px solid #007bff; color: #007bff; background: white; transition: all 0.2s ease;" onmouseover="this.style.background='#007bff'; this.style.color='white';" onmouseout="this.style.background='white'; this.style.color='#007bff';">+</button>
                        <button class="btn btn-sm ms-2" onclick="eliminar(<?= $id ?>)" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); color: white; border: none; transition: all 0.2s ease;" onmouseover="this.style.transform='scale(1.05)'; this.style.boxShadow='0 2px 8px rgba(220, 53, 69, 0.3)';" onmouseout="this.style.transform='scale(1)'; this.style.boxShadow='none';">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </div>
                <hr class="my-2">
            <?php endforeach; ?>

            <!-- === BOTÓN "IR A PAGAR" AHORA SE RECARGA === -->
            <div class="carrito-footer mt-3">
                <div class="d-flex justify-content-between mb-3" style="padding: 1rem; background: linear-gradient(135deg, rgba(0, 123, 255, 0.05) 0%, rgba(0, 86, 179, 0.05) 100%); border-radius: 8px;">
                    <strong style="color: #495057;">Total:</strong>
                    <strong style="color: #007bff; font-size: 1.25rem;">S/ <?= number_format($total_precio, 2) ?></strong>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($tiene_productos): ?>
                        <a href="checkout.php" class="btn w-100" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white; font-weight: 600; border: none; padding: 0.75rem; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 123, 255, 0.3); transition: all 0.3s ease; text-decoration: none; display: inline-block;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 15px rgba(0, 123, 255, 0.4)';" onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 10px rgba(0, 123, 255, 0.3)';">
                            <i class="bi bi-lock"></i> Ir a Pagar
                        </a>
                    <?php else: ?>
                        <button class="btn w-100" disabled style="background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%); color: white; font-weight: 600; border: none; padding: 0.75rem; border-radius: 8px; opacity: 0.65;">
                            <i class="bi bi-lock"></i> Ir a Pagar
                        </button>
                    <?php endif; ?>
                <?php else: ?>
                    <a href="login.php" class="btn w-100" style="color: #007bff; border: 2px solid #007bff; font-weight: 600; padding: 0.75rem; border-radius: 8px; transition: all 0.3s ease; text-decoration: none; display: inline-block; background: white;" onmouseover="this.style.background='linear-gradient(135deg, #007bff 0%, #0056b3 100%)'; this.style.color='white'; this.style.borderColor='transparent'; this.style.transform='translateY(-2px)';" onmouseout="this.style.background='white'; this.style.color='#007bff'; this.style.borderColor='#007bff'; this.style.transform='translateY(0)';">
                        Inicia sesión para pagar
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .btn-carrito-flotante {
        position: fixed;
        bottom: 20px;
        right: 20px;
        width: 60px;
        height: 60px;
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        border: none;
        border-radius: 50%;
        font-size: 1.5rem;
        box-shadow: 0 6px 20px rgba(0, 123, 255, 0.4);
        z-index: 1050;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .btn-carrito-flotante:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(0, 123, 255, 0.5);
        background: linear-gradient(135deg, #0056b3 0%, #004494 100%);
    }

    .btn-carrito-flotante .badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        border: 2px solid white;
        font-size: 0.7rem;
        min-width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        box-shadow: 0 2px 8px rgba(220, 53, 69, 0.4);
    }

    .carrito-flotante {
        position: fixed;
        top: 0;
        right: -400px;
        width: 380px;
        height: 100vh;
        background: white;
        box-shadow: -5px 0 25px rgba(0, 123, 255, 0.15);
        z-index: 1051;
        transition: right 0.4s ease;
        display: flex;
        flex-direction: column;
    }

    .carrito-flotante.abierto {
        right: 0;
    }

    .carrito-header {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: white;
        padding: 1.25rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 10px rgba(0, 123, 255, 0.2);
    }

    .carrito-header h5 {
        margin: 0;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 0.5rem;
    }

    .carrito-header .badge {
        background: rgba(255, 255, 255, 0.25);
        color: white;
        font-weight: 600;
        padding: 0.35rem 0.65rem;
        border-radius: 8px;
    }

    .carrito-body {
        flex: 1;
        overflow-y: auto;
        padding: 1rem;
    }

    .carrito-footer {
        border-top: 1px solid #eee;
        background: #f8f9fa;
    }

    /* Ocultar flechas del input de cantidad */
    .cantidad-input {
        appearance: textfield;
        -moz-appearance: textfield;
        cursor: default;
        background-color: #f8f9fa;
    }
    
    .cantidad-input::-webkit-outer-spin-button,
    .cantidad-input::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    @media (max-width: 576px) {
        .carrito-flotante {
            width: 100%;
            right: -100%;
        }
    }
</style>

<script>
window.addEventListener('load', function() {
    let carritoAbierto = false;

    function toggleCarrito() {
        const el = document.getElementById('carritoFlotante');
        el.classList.toggle('abierto');
        carritoAbierto = el.classList.contains('abierto');
        if (carritoAbierto) recargarCarrito();
    }

    function cerrarCarrito() {
        document.getElementById('carritoFlotante').classList.remove('abierto');
        carritoAbierto = false;
    }

    // === RECARGAR + DISPARAR EVENTO SIEMPRE ===
    function recargarCarrito() {
        return fetch('get_carrito_lateral.php')
            .then(r => r.json())
            .then(data => {
                // Actualizar HTML
                document.getElementById('carrito-body').innerHTML = data.html || '<p class="text-center text-muted p-3">Tu carrito está vacío</p>';
                actualizarContadores(data.items_count || 0, data.total || '0.00');

                // === CONSTRUIR items SIEMPRE ===
                const items = {};
                // Si hay items, llenar
                if (data.items && Object.keys(data.items).length > 0) {
                    Object.keys(data.items).forEach(id => {
                        items[id] = {
                            cantidad: data.items[id].cantidad,
                            stock_total: data.items[id].stock_total
                        };
                    });
                }
                // Si NO hay items, items = {} (vacío)

                // === DISPARAR EVENTO SIEMPRE ===
                window.dispatchEvent(new CustomEvent('carritoActualizado', {
                    detail: {
                        items,
                        items_count: data.items_count || 0,
                        total: data.total || '0.00'
                    }
                }));
                
                return data;
            })
            .catch(() => {
                document.getElementById('carrito-body').innerHTML = '<p class="text-danger text-center">Error al cargar</p>';
                return { items: {}, items_count: 0, total: '0.00' };
            });
    }

    // Hacer las funciones disponibles globalmente
    window.toggleCarrito = toggleCarrito;
    window.cerrarCarrito = cerrarCarrito;
    window.recargarCarrito = recargarCarrito;
});

    // === ACTUALIZAR CATÁLOGO Y PÁGINA DE PRODUCTO ===
    window.addEventListener('carritoActualizado', e => {
        const items = e.detail.items || {};

        // Actualizar productos en catálogo
        document.querySelectorAll('.producto-card').forEach(card => {
            const input = card.querySelector('input[name="producto_id"]');
            if (!input) return;
            const id = input.value;

            // Obtener stock total del atributo data-stock-total o del DOM
            let stockTotal = parseInt(card.getAttribute('data-stock-total'));
            
            // Si no existe el atributo, intentar extraerlo del texto "Disponible: X / Y"
            if (!stockTotal || isNaN(stockTotal)) {
                const dispText = card.querySelector('.disponible-count')?.closest('small')?.textContent;
                if (dispText) {
                    const match = dispText.match(/\/\s*(\d+)/);
                    stockTotal = match ? parseInt(match[1]) : 0;
                } else {
                    stockTotal = 0;
                }
            }

            // Cantidad en carrito (0 si no está)
            const enCarrito = items[id]?.cantidad || 0;
            const disponible = stockTotal - enCarrito;

            const dispEl = card.querySelector('.disponible-count');
            if (dispEl) dispEl.textContent = disponible;

            const cantInput = card.querySelector('.cantidad-input');
            if (cantInput) {
                cantInput.max = disponible > 0 ? disponible : 1;
                if (parseInt(cantInput.value) > disponible && disponible > 0) {
                    cantInput.value = disponible;
                }
            }

            const btn = card.querySelector('.btn-agregar');
            if (btn) {
                const text = btn.querySelector('.btn-text');
                if (disponible <= 0) {
                    btn.disabled = true;
                    if (text) text.textContent = 'Sin stock';
                } else {
                    btn.disabled = false;
                    if (text) text.textContent = 'Agregar';
                }
            }
        });

    });

    function actualizarContadores(items, total) {
        // Actualizar todos los contadores del carrito en la página
        const contadores = document.querySelectorAll('#carrito-contador, #btn-contador, .carrito-contador');
        contadores.forEach(contador => {
            contador.textContent = items;
        });
    }

    // === AGREGAR ===
    let submitting = false;
    
    document.addEventListener('submit', function(e) {
        if (e.target.classList.contains('add-form')) {
            e.preventDefault();
            
            // Prevenir múltiples envíos simultáneos
            if (submitting) return;
            submitting = true;
            
            const form = e.target;
            const btn = form.querySelector('.btn-agregar');
            const spinner = btn.querySelector('.spinner-border');
            const text = btn.querySelector('.btn-text');
            const cantidadInput = form.querySelector('.cantidad-input');
            const disponibleEl = form.closest('.producto-card')?.querySelector('.disponible-count') ||
                                form.parentElement.querySelector('.disponible-count');

            btn.disabled = true;
            text.classList.add('d-none');
            spinner.classList.remove('d-none');

            fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        mostrarToast('¡Agregado!', 'success');
                        actualizarContadores(data.items, data.total);
                        
                        // Actualizar stock disponible con datos del servidor
                        if (data.nuevoDisponible !== undefined) {
                            disponibleEl.textContent = data.nuevoDisponible;
                            cantidadInput.max = Math.max(1, data.nuevoDisponible);
                            if (parseInt(cantidadInput.value) > data.nuevoDisponible) {
                                cantidadInput.value = data.nuevoDisponible > 0 ? data.nuevoDisponible : 1;
                            }
                        }
                        
                        cantidadInput.value = 1;
                        
                        // Disparar evento de actualización para sincronizar toda la página
                        window.dispatchEvent(new CustomEvent('carritoActualizado', {
                            detail: {
                                items: data.items || {},
                                items_count: data.items_count || data.items,
                                total: data.total || '0.00'
                            }
                        }));
                        
                        // Siempre recargar el carrito para forzar la actualización
                        recargarCarrito();
                    } else {
                        mostrarToast(data.mensaje || 'Error', 'danger');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    mostrarToast('Error al procesar la solicitud', 'danger');
                })
                .finally(() => {
                    btn.disabled = false;
                    text.classList.remove('d-none');
                    spinner.classList.add('d-none');
                    submitting = false;
                });
        }
    });

    // === CAMBIAR CANTIDAD ===
    function cambiarCant(id, cambio) {
        const item = document.querySelector(`.carrito-item[data-id="${id}"]`);
        if (!item) return;
        const input = item.querySelector('input.cantidad-input');
        let nueva = parseInt(input.value) + cambio;
        if (nueva < 0) nueva = 0;
        input.value = nueva;
        fetch('actualizar_carrito.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `producto_id=${id}&cantidad=${nueva}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    recargarCarrito();
                } else {
                    input.value = parseInt(input.value) - cambio;
                }
            })
            .catch(() => recargarCarrito());
    }

    // === ELIMINAR ===
    function eliminar(id) {
        mostrarConfirmacion(
            '¿Eliminar producto?',
            '¿Estás seguro de eliminar este producto del carrito?',
            () => {
                fetch('eliminar_carrito.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded'
                        },
                        body: `producto_id=${id}`
                    })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            mostrarToast('Producto eliminado del carrito', 'success');
                            recargarCarrito(); // RECARGA + DISPARA EVENTO
                        } else {
                            mostrarToast('Error al eliminar el producto', 'danger');
                        }
                    })
                    .catch(() => {
                        mostrarToast('Error de conexión', 'danger');
                        recargarCarrito();
                    });
            }
        );
    }

    // === MODAL DE CONFIRMACIÓN ===
    function mostrarConfirmacion(titulo, mensaje, onConfirm) {
        // Crear overlay
        const overlay = document.createElement('div');
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            animation: fadeIn 0.2s;
        `;
        
        // Crear modal
        const modal = document.createElement('div');
        modal.style.cssText = `
            background: white;
            border-radius: 12px;
            padding: 30px;
            max-width: 400px;
            width: 90%;
            box-shadow: 0 10px 40px rgba(0,0,0,0.3);
            animation: slideIn 0.3s;
        `;
        
        modal.innerHTML = `
            <style>
                @keyframes fadeIn {
                    from { opacity: 0; }
                    to { opacity: 1; }
                }
                @keyframes slideIn {
                    from { transform: translateY(-20px); opacity: 0; }
                    to { transform: translateY(0); opacity: 1; }
                }
            </style>
            <div style="text-align: center; margin-bottom: 20px;">
                <div style="width: 60px; height: 60px; background: #fff3cd; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 15px;">
                    <i class="bi bi-exclamation-triangle" style="font-size: 30px; color: #856404;"></i>
                </div>
                <h5 style="margin: 0 0 10px 0; color: #333; font-weight: 600;">${titulo}</h5>
                <p style="margin: 0; color: #666; font-size: 15px;">${mensaje}</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <button id="btnCancelar" style="flex: 1; padding: 12px; border: 2px solid #dee2e6; background: white; color: #6c757d; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    Cancelar
                </button>
                <button id="btnConfirmar" style="flex: 1; padding: 12px; border: none; background: #dc3545; color: white; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.2s;">
                    Eliminar
                </button>
            </div>
        `;
        
        overlay.appendChild(modal);
        document.body.appendChild(overlay);
        
        // Efectos hover
        const btnCancelar = modal.querySelector('#btnCancelar');
        const btnConfirmar = modal.querySelector('#btnConfirmar');
        
        btnCancelar.onmouseover = () => btnCancelar.style.background = '#f8f9fa';
        btnCancelar.onmouseout = () => btnCancelar.style.background = 'white';
        btnConfirmar.onmouseover = () => btnConfirmar.style.background = '#bb2d3b';
        btnConfirmar.onmouseout = () => btnConfirmar.style.background = '#dc3545';
        
        // Eventos
        btnCancelar.onclick = () => overlay.remove();
        btnConfirmar.onclick = () => {
            overlay.remove();
            onConfirm();
        };
        overlay.onclick = (e) => {
            if (e.target === overlay) overlay.remove();
        };
    }

    // === TOAST ===
    function mostrarToast(msg, tipo = 'success') {
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${tipo} border-0 position-fixed`;
        toast.style.top = '20px';
        toast.style.right = '20px';
        toast.style.zIndex = '1070';
        toast.innerHTML = `<div class="d-flex"><div class="toast-body">${msg}</div><button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button></div>`;
        document.body.appendChild(toast);
        new bootstrap.Toast(toast).show();
        setTimeout(() => toast.remove(), 3000);
    }
</script>