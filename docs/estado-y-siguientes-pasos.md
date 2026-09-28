# Estado del proyecto y siguientes pasos

> **Para retomar rápido:** lee este archivo completo. Tiene el estado real, lo que falta y los errores ya resueltos (para no repetirlos).

Última actualización: 2026-09-28

---

## 1. Estado actual: desplegado y funcionando

| Componente | Estado |
|---|---|
| App Laravel 13 (monolito Blade + Livewire) | ✅ sirviendo en Railway |
| PostgreSQL 18.6 (Railway) | ✅ las 10 migraciones aplicadas |
| Cloudinary | ✅ probado contra el cloud `dg6iut6sl` |
| Autenticación (login Livewire) + roles | ✅ login, logout, throttle y `role:admin,empleado` |
| Suite de tests | ✅ `43/43` pasando (`php artisan test`) |
| CI GitHub Actions | ✅ `.github/workflows/ci.yml` (PostgreSQL 15 + gate de umbral) |
| Último commit | `feat(auth): login Livewire, logout y control de acceso por roles` (local; pendiente push/deploy) |

**Nada pendiente por commitear ni pushear.** `main` == `origin/main`.

### Infraestructura en Railway

* Proyecto con 2 servicios:
  * **`Postgres`** — base de datos. **NO tocar sus variables** (Railway las genera y las mantiene consistentes).
  * **App** (repo `jabYao/Tag-q-V1`) — las variables se pegan aquí.
* La app obtiene la conexión con las referencias `${{Postgres.PGHOST}}`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`.
* Dominio: ver **Railway → servicio App → `Settings` → `Networking`**.
* `APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}` — **el dominio público debe existir** o el build falla (ver §4).

### Dónde viven las variables (nunca en el repo)

| Entorno | Ubicación |
|---|---|
| Local | `.env` (ignorado por git) |
| Tests | `phpunit.xml` (`tagq_testing` en PostgreSQL) |
| CI | `.github/workflows/ci.yml` |
| Producción | Railway → servicio App → `Variables` |

> Archivos con secretos en `~/Documentos/` (fuera del repo): `railway-vars.txt` (variables de la app).
> No moverlos dentro del repo. `railway-vars-postgres.txt` y `postgres-fix-password.sql` quedaron obsoletos.

---

## 2. Autenticación y roles (implementado 2026-09-28)

**Ya no faltan** el usuario admin ni la pantalla de login: ambos quedaron resueltos.

| Pieza | Ruta |
|---|---|
| Componente Livewire de login | `app/Livewire/Auth/Login.php` + `resources/views/livewire/auth/login.blade.php` |
| Página + layout de invitado | `resources/views/auth/login.blade.php` + `resources/views/layouts/guest.blade.php` |
| Rutas | `GET /login` (`guest`) · `POST /logout` (`auth`) en `routes/web.php` |
| Middleware de rol | `app/Http/Middleware/EnsureUserHasRole.php`, alias `role` en `bootstrap/app.php` |
| Helpers de rol | `User::isAdmin()/isEmpleado()/isCliente()/hasAnyRole()/canAccessAdmin()` |
| Seeder de producción | `database/seeders/ProductionSeeder.php` + `config/tagq.php` |

**Reglas de acceso:**

| Rol | Panel `/admin/*` | Login |
|---|---|---|
| `admin` | ✅ | ✅ redirige al panel |
| `empleado` | ✅ | ✅ redirige al panel |
| `cliente` | ⛔ 403 | ✅ redirige a `/` |
| invitado | ⛔ redirect a `/login` | — |

**Seguridad del login:** validación de email/contraseña, máximo 5 intentos por
`email+IP` (bloqueo 60 s), `session()->regenerate()` al entrar. Mensajes en español.

**Seeder de producción (idempotente):** crea las 3 categorías base y el admin desde
`ADMIN_NAME` / `ADMIN_EMAIL` / `ADMIN_PASSWORD`. Si faltan las credenciales, solo avisa
y no crea ningún admin (nunca deja contraseña por defecto). Corre en cada deploy
(`nixpacks.toml` / `railway.json`): `migrate --force && db:seed --class=ProductionSeeder --force`.

> **Acción pendiente en Railway:** definir `ADMIN_NAME`, `ADMIN_EMAIL` y `ADMIN_PASSWORD`
> en las variables del servicio **App** (nunca en el repo). Al desplegar se crea el admin
> y ya se puede entrar a `/login`.

---

## 3. Lo que falta (elegir por dónde seguir)

| Opción | Alcance | Test |
|---|---|---|
| **B** ⭐ (recomendada) | Catálogo público: listado con Swiper + AOS + filtros Livewire por categoría | Feature |
| **C** | Checkout con reserva de stock sin sobreventa | **Feature primero** (AGENTS.md §3 regla 4) |
| **D** | Gestión de usuarios en el panel (admin crea empleados/clientes) | Feature |

Además: `DatabaseSeeder` (local) sigue siendo el de desarrollo (`admin@tagq.local`,
`test@example.com` y 4 productos placeholder) y duplica las 3 categorías; conviene
que llame a `ProductionSeeder` para no divergir.

---

## 4. Errores ya resueltos — NO repetirlos

Cada uno costó un deploy fallido. El porqué y el fix:

| Síntoma en el deploy | Causa real | Fix aplicado |
|---|---|---|
| `Failed to parse Nixpacks config file` | `[providers]` vacío; Nixpacks espera una **lista** | se eliminó la sección |
| `php version (8.3.35) does not satisfy >=8.4.1` | `nixPkgs = ["php", ...]` forzaba el PHP por defecto (8.3) | dejar que Nixpacks lea `require.php` de `composer.json` → **8.4** |
| `Undefined variable nodejs_24` | `engines.node` abierto (`^20.19 \|\| >=22.12`) → elegía Node 24, inexistente en el archive de nixpkgs que usa el provider PHP | `engines` eliminado + **`.nvmrc` = 22.12.0** (en ese archive `nodejs_22` es exactamente 22.12.0) |
| `node:util.styleText` no exportado | contenedor con Node 18 (el default de Nixpacks) | `.nvmrc` → Node 22 |
| `Invalid URI` en `Request.php:395` (`package:discover`) | `APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}` no resolvía (dominio público sin generar) → `APP_URL=https://` | **generar el dominio primero**: App → `Settings` → `Networking` |
| `password authentication failed for user "postgres"` | el volumen de Postgres ya tenía la base inicializada con otra contraseña; `POSTGRES_PASSWORD` solo se aplica con el disco **vacío** (`Skipping initialization`) | borrar el servicio Postgres **y su volumen**, recrearlo y **no tocar sus variables** |
| (potencial) arranque roto | `artisan serve --port=$PORT` con `PORT` vacío | `${PORT:-8080}` en `nixpacks.toml` y `railway.json` |

**Reglas aprendidas:**

* El servicio **Postgres nunca lleva variables nuestras** (ni Cloudinary, ni APP_KEY). Solo las que genera Railway.
* `package.json` con `engines` abierto rompe el deploy: la versión de Node se fija con `.nvmrc`.
* Todo secreto va en Railway (App) o en `.env` local; **nunca** en archivos versionados.

---

## 5. Arquitectura y convenciones (resumen de AGENTS.md)

* **Monolito SSR**: Laravel 11+ con Blade + Livewire. Sin API separada, sin React, sin MySQL.
* **PostgreSQL** obligatorio (los tests corren contra `tagq_testing`, nunca SQLite: `enum`, `json` y FK no se comportan igual).
* **Todo feature se entrega con su test**: Unit si es pequeño, Feature con `RefreshDatabase` si toca BD/rutas/Livewire/dinero.
* **En dinero y stock el test va primero** y queda como regresión permanente.
* `php artisan test` debe pasar completo antes de cada commit.
* Monto en **COP sin decimales** (`integer`); stock **por unidad, sin sobreventa**.

---

## 6. Cómo retomar mañana

```bash
cd "/home/airj/Documentos/practica/Tag q renovado"

php artisan test              # debe dar 43/43
git status -sb                # ver qué quedó por commitear (auth+roles)

# ver la app en producción
# Railway → servicio App → Settings → Networking → abrir el dominio
```

Antes del próximo commit: definir `ADMIN_EMAIL`/`ADMIN_PASSWORD` en Railway, y luego
elegir la opción B, C o D de §3.

### Mapa del código relevante

| Ruta | Qué es |
|---|---|
| `app/Livewire/Auth/Login.php` | login Livewire: validación, throttle 5 intentos, redirect por rol |
| `app/Http/Middleware/EnsureUserHasRole.php` | middleware `role:admin,empleado` (403 si el rol no alcanza) |
| `app/Models/User.php` | helpers `isAdmin/isEmpleado/isCliente/hasAnyRole/canAccessAdmin` |
| `database/seeders/ProductionSeeder.php` | 3 categorías + admin desde env (idempotente) |
| `config/tagq.php` | credenciales de admin y categorías base |
| `routes/web.php` | `/login`, `/logout` y grupos `/admin` protegidos |
| `app/Services/CartService.php` | carrito con tope por stock (sin sobreventa) |
| `app/Services/CloudinaryService.php` | wrapper del SDK (upload/destroy/ping) |
| `app/Services/ProductImageService.php` | subida múltiple → `products.imagenes`; borra por `public_id` |
| `app/Http/Controllers/Admin/ProductController.php` | CRUD admin de productos |
| `app/Http/Requests/{Store,Update}ProductRequest.php` | validación + autorización `admin`/`empleado` |
| `resources/views/admin/products/*` | vistas Blade del admin |
| `tests/Feature/Auth/LoginTest.php` | login, errores, throttle, logout, inicio |
| `tests/Feature/Auth/RoleAccessTest.php` | matriz de roles sobre `/admin` |
| `tests/Feature/ProductionSeederTest.php` | seeder idempotente y admin desde config |
| `tests/Unit/UserRoleTest.php` | helpers de rol |
| `tests/Feature/CartServiceTest.php` | tests de stock (`#[Group('critical')]` → 0 fallos permitidos en CI) |
| `tests/Feature/AdminProductTest.php` | CRUD + Cloudinary mockeado |
| `config/cloudinary.php` | mapea `CLOUDINARY_*` y la carpeta `tag-q/producto` |
| `nixpacks.toml` / `railway.json` | build (Vite) y start (`migrate` + `ProductionSeeder` + `serve`) |
