# AGENTS.md — tienda Tag q

**Proyecto:** tienda Tag q — **e-commerce de relojes de pulso**.

## Datos confirmados del negocio

| | |
|---|---|
| **Qué vende** | Relojes de pulso |
| **Mercado** | Colombia |
| **Moneda** | COP |
| **Alcance** | Sitio público + panel de administración |
| **Roles** | Admin · Empleado · Cliente · Invitado (checkout sin cuenta) |
| **Inventario** | Por unidad, sin sobreventa |
| **Métodos de pago** | Tarjeta · PSE · Nequi · Contraentrega · (Wompi) |
| **Estados de pedido** | Pendiente de pago → Pagado → En preparación → Enviado → Entregado · (Cancelado · Reembolsado) |

Instrucciones para agentes de IA que trabajen en este repositorio.
Léelo completo antes de escribir código. Si algo no está aquí, **pregunta**, no asumas.

---


## 1. Arquitectura confirmada

**Patrón arquitectónico general:**

- **Monolito** con **renderizado del lado del servidor (SSR)**.
- **MVC** (Modelo–Vista–Controlador).
- **Un solo proceso Laravel.** No hay separación frontend/backend por API.
- Todo el render ocurre en el servidor con **Blade**; la interactividad se resuelve con **Livewire** (sin endpoints JSON propios).

**Regla dura:** ninguna decisión puede romper el monolito. Si una tarea requiere un frontend separado o una API externa propia, hay que preguntar antes.


---

## 2. Stack confirmado

### Backend
| Tecnología | Uso |
|---|---|
| Laravel 11 | Framework principal. Monolito Blade + Livewire. **Único** framework de backend. |
| Blade | Motor de plantillas / render en servidor. |
| Livewire | Interactividad (carrito, filtros, checkout, formularios) sin API separada. |
| PHPUnit | Testing. |

### Frontend (dentro del mismo proyecto Laravel)
| Tecnología | Uso |
|---|---|
| Alpine.js | JS ligero: menús, modales, tabs, dropdowns. |
| Tailwind CSS | Estilos. |
| Vite (`laravel-vite-plugin`) | Compilador/empaquetador de assets CSS/JS. **NO** es un frontend independiente. |

### Animaciones
| Librería | Uso |
|---|---|
| AOS (Animate On Scroll) | Animaciones de entrada en todo el sitio (`fade-up`, `zoom-out`). |
| GSAP | Animaciones avanzadas/complejas que AOS no cubre. |

### Componentes visuales
| Librería | Uso |
|---|---|
| Swiper | Carruseles y galerías de producto. |

### Datos y servicios
| Servicio | Uso |
|---|---|
| PostgreSQL | Base de datos. |
| Wompi | Pasarela de pagos (tarjeta, PSE, Nequi, Daviplata, contraentrega). |
| Cloudinary | Almacenamiento y optimización de imágenes de producto. |
| Resend | Emails transaccionales. |

### Hosting / CI
| Servicio | Uso |
|---|---|
| Railway | Hosting (backend + frontend juntos, al ser monolito). |
| GitHub Actions | CI. **Estado: pendiente** (ver §7). |

### Descartado explícitamente (no proponer)
- React, arquitectura headless con API separada.
- Vite como **bundler de una SPA** / proyecto frontend independiente.
- MySQL.
- Vercel.
- Node/NestJS como backend.
- Cualquier CQRS/event-sourcing/microservicios para v1.

---

## 3. Regla de testing

**Toda funcionalidad nueva se entrega con su test. Una tarea no está terminada hasta que el test existe y pasa.**

El tamaño define el test:

| Tamaño de lo que se construye | Test obligatorio | Dónde |
|---|---|---|
| Lógica pequeña y aislada (una regla, un cálculo, un método, sin BD ni HTTP) | **Unit** | `tests/Unit` |
| Flujo grande o que toca BD, rutas, servicios, Livewire o dinero/stock | **Feature** (`RefreshDatabase`) | `tests/Feature` |
| Flujo crítico de punta a punta en navegador (compra real, Wompi) | **E2E** (Dusk) | `tests/Browser` |

Reglas:

1. **Nada entra sin test.** Si no se puede testear, se explica por qué antes de cerrar la tarea — no se omite en silencio.
2. **Unit si es pequeño, Feature si es grande, E2E si es el flujo crítico de dinero.** En duda entre Unit y Feature: si necesita BD o la app levantada → Feature.
3. **Los tests corren contra PostgreSQL** (`tagq_testing`), no SQLite: `enum`, `json` y las FK no se comportan igual.
4. **En dinero y stock, el test va primero:** se escribe el test que falla, luego el arreglo, y queda como regresión permanente.
5. **`php artisan test` debe pasar completo antes de cada commit.** La suite es la que impide que una rotura se cuele.

---


## 4. Flujo de Git (regla del dueño)

- **Commit** solo después de correr **toda** la suite (`php artisan test`) y verla **completa en verde**. Si algún test falla, no se commitea.
- **Push** solo con **autorización explícita** del dueño. El agente **nunca** ejecuta `git push` por su cuenta, aunque el commit ya exista y esté listo.
- El commit es **local** por defecto: commitear **no** implica publicar ni desplegar (Railway despliega con el push).
- No saltarse hooks ni tests (`--no-verify`, tests omitidos) para "avanzar".

