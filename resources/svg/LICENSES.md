# Licencias de los iconos

Los SVG de `resources/svg/` vienen de tres fuentes distintas. Cada carpeta corresponde a una familia y a una licencia.

| Carpeta | Familia | Origen | Licencia | Archivos |
|---|---|---|---|---|
| `feather/` | Feather Icons | https://github.com/feathericons/feather | MIT | 59 |
| `mdi/` | Material Design Icons | https://github.com/Templarian/MaterialDesign | Apache 2.0 | 10 |
| `brand/` | Simple Icons | https://github.com/simple-icons/simple-icons | CC0 1.0 | 8 |
| `brand/` | Nequi (oficial) | https://www.nequi.com.co — CDN `cdn.prod.website-files.com/.../logo-nequi.svg` | Marca registrada (uso permitido para indicar medio de pago) | 1 |
| `brand/` | Wompi (oficial) | https://public-assets.wompi.com/brand_wompi/logos/logo-primary.svg | Marca registrada Bancolombia (uso permitido para indicar medio de pago) | 1 (+ isotipo) |
| `brand/` | PSE (oficial) | https://www.pse.com.co — `logo-PSE_.jpg` 240×240 (Liferay) | Marca registrada ACH Colombia | 1 (JPG, raster) |
| `brand/` | Daviplata (provisional) | Vector simplificado `120×40` con `#E30613` — no es el oficial de Davivienda | Provisional, reemplazar por vector oficial | 1 |

## Notas

- Las licencias MIT, Apache 2.0 y CC0 permiten uso comercial y no exigen atribución visible en la interfaz. Se conserva este archivo como registro.
- **Los logos de marca siguen siendo marcas registradas.** La licencia CC0 de Simple Icons cubre el archivo SVG, no el derecho sobre la marca. Los logos de pago deben usarse solo para indicar métodos aceptados, sin deformarlos ni recolorearlos, siguiendo las guías de cada marca.
- **Convención de renderizado:** `feather/` es de trazo (`stroke`), `mdi/` y `brand/` son de relleno (`fill`). El componente `C-01` aplica la convención según la carpeta.
- Los iconos se recortaron de los repos completos: solo se copiaron los 77 que usa el proyecto. No se instaló ninguna dependencia.

## Pendientes — resueltos

- PSE y Daviplata agregados el 2026-09-25. Queda pendiente reemplazar Daviplata provisional por el vector oficial de Davivienda cuando se consiga.

## Agregados 2026-09-25

- `brand/nequi.svg` (95×30, `#CA0080`/`#200020`) — oficial Nequi, 2.6KB
- `brand/wompi.svg` (316×74, `#2C2A29`) — oficial Wompi Bancolombia, 3.5KB (+ `wompi-isotipo.svg` 121×121)
- `brand/pse.jpg` (240×240, 19KB) — oficial PSE/ACH Colombia, JPG con fondo blanco (único asset público)
- `brand/daviplata.svg` (120×40, `#E30613`) — provisional Daviplata (rect rojo + texto blanco), reemplazar por oficial
- Total del proyecto ahora **81 archivos** (58 Feather + 10 MDI + 13 Brand: 11 SVG + 1 JPG + 1 isotipo) — en preview 80 (isotipo no listado)
