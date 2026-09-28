# Estado del proyecto y siguientes pasos

> **Para retomar rápido:** lee este archivo completo. Tiene el estado real, lo que falta y los errores ya resueltos (para no repetirlos).

Última actualización: 2026-09-27

---

## 1. Estado actual: desplegado y funcionando

| Componente | Estado |
|---|---|
| App Laravel 13 (monolito Blade + Livewire) | ✅ sirviendo en Railway |
| PostgreSQL 18.6 (Railway) | ✅ las 10 migraciones aplicadas |
| Cloudinary | ✅ probado contra el cloud `dg6iut6sl` |
| Suite de tests | ✅ `18/18` pasando (`php artisan test`) |
| CI GitHub Actions | ✅ `.github/workflows/ci.yml` (PostgreSQL 15 + gate de umbral) |
| Último commit desplegado | `55614df` |

**Nada pendiente por commitear ni pushear.** `main` == `origin/main`.

### Infraestructura en Railway

* Proyecto con 2 servicios:
  * **`Postgres`** — base de datos. **NO tocar sus variables** (Railway las genera y las mantiene consistentes).
  * **App** (repo `jabYao/Tag-q-V1`) — las variables se pegan aquí.
* La app obtiene la conexión con las referencias `${{Postgres.PGHOST}}`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`.
* Dominio: ver **Railway → servicio App → `Settings` → `Networking`**.
* `APP_URL=https://${{RAILWAY_PUBLIC_DOMAIN}}` — **el dominio público debe existir** o el build falla (ver §3).

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

## 2. Lo que falta (elegir por dónde seguir)

**Huecos importantes hoy:**

1. **No hay usuario admin** → no se puede iniciar sesión (la base solo tiene tablas).
2. **No existe pantalla de login** → `routes/web.php` tiene un stub que devuelve 401:
   ```php
   Route::get('/login', fn () => response('Login requerido', 401))->name('login');
   ```
   Por eso `/admin/products` es inalcanzable, aunque el CRUD ya está listo.
3. `DatabaseSeeder` **no sirve para producción**: crea `test@example.com` con contraseña `password`
   y 4 productos con imágenes de `picsum.photos` (placeholders).

**Opciones acordadas:**

| Opción | Alcance | Test |
|---|---|---|
| **A** ⭐ (recomendada) | Login (Livewire) + `ProductionSeeder` con 3 categorías y admin con `ADMIN_EMAIL`/`ADMIN_PASSWORD` desde variables de entorno | Feature |
| **B** | Catálogo público: listado con Swiper + AOS + filtros Livewire por categoría | Feature |
| **C** | Checkout con reserva de stock sin sobreventa | **Feature primero** (AGENTS.md §3 regla 4) |

---

## 3. Errores ya resueltos — NO repetirlos

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

## 4. Arquitectura y convenciones (resumen de AGENTS.md)

* **Monolito SSR**: Laravel 11+ con Blade + Livewire. Sin API separada, sin React, sin MySQL.
* **PostgreSQL** obligatorio (los tests corren contra `tagq_testing`, nunca SQLite: `enum`, `json` y FK no se comportan igual).
* **Todo feature se entrega con su test**: Unit si es pequeño, Feature con `RefreshDatabase` si toca BD/rutas/Livewire/dinero.
* **En dinero y stock el test va primero** y queda como regresión permanente.
* `php artisan test` debe pasar completo antes de cada commit.
* Monto en **COP sin decimales** (`integer`); stock **por unidad, sin sobreventa**.

---

## 5. Cómo retomar mañana

```bash
cd "/home/airj/Documentos/practica/Tag q renovado"

php artisan test              # debe dar 18/18
git status -sb                # debe decir: ## main...origin/main (limpio)

# ver la app en producción
# Railway → servicio App → Settings → Networking → abrir el dominio
```

Luego elegir la opción A, B o C de §2.

### Mapa del código relevante

| Ruta | Qué es |
|---|---|
| `app/Services/CartService.php` | carrito con tope por stock (sin sobreventa) |
| `app/Services/CloudinaryService.php` | wrapper del SDK (upload/destroy/ping) |
| `app/Services/ProductImageService.php` | subida múltiple → `products.imagenes`; borra por `public_id` |
| `app/Http/Controllers/Admin/ProductController.php` | CRUD admin de productos |
| `app/Http/Requests/{Store,Update}ProductRequest.php` | validación + autorización `admin`/`empleado` |
| `resources/views/admin/products/*` | vistas Blade del admin |
| `tests/Feature/CartServiceTest.php` | tests de stock (`#[Group('critical')]` → 0 fallos permitidos en CI) |
| `tests/Feature/AdminProductTest.php` | CRUD + Cloudinary mockeado |
| `config/cloudinary.php` | mapea `CLOUDINARY_*` y la carpeta `tag-q/producto` |
| `nixpacks.toml` / `railway.json` | build (Vite) y start (`migrate --force` + `serve`) |
