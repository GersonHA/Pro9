# Dashboard — Optimización de Performance

> Documento técnico de la optimización del módulo Dashboard de Pro9 (HENAVI),
> julio 2026. Reemplaza las notas dispersas en `CHANGELOG.md` con un registro
> completo de los hallazgos, fixes y lecciones aprendidas.

**Tenant de referencia**: `grupomultiservicioshenavi.localhost` (HENAVI)
- 9.451 `sale_notes` totales
- 2.727 `sale_notes` en mayo 2026
- 152 `documents` totales, 30 en mayo 2026
- Datos inician 2026-03-09

---

## 1. Contexto y motivación

Pro9 cargaba el dashboard en **~7 segundos** cuando se seleccionaba Mes 05/2026
en HENAVI. pro8 cargaba el mismo escenario en ~600 ms. La diferencia provenía de:

1. Pro9 ejecutaba los **mismos métodos base** de pro8 + 6 widgets nuevos
   heredados del merge upstream Buho (Mozo, WhatsApp, etc.).
2. Los métodos base no habían sido optimizados: cada consulta pagaba su coste
   íntegro de Eloquent + colecciones en PHP.

La estrategia fue **medir primero**, **optimizar los cuellos de botella
existentes** y solo después tocar el frontend.

---

## 2. Medición inicial (measure_dashboard.php)

Script independiente que cargaba `DashboardData` por reflexión, activaba el
query log y medía cada método público. Resultados para Mes 05/2026:

| Endpoint | Antes | Objetivo | Diagnóstico |
|---|---|---|---|
| `globalData(month=05)` | **3.029 ms** 🔴 | <400 ms | 198 queries; 6 iteraciones × 3 tablas idénticas |
| `paymentMethods(mes05)` | **1.225 ms** 🔴 | <200 ms | 40 queries; foreach sin eager load |
| `salesWeek()` | 917 ms 🟡 | <100 ms | 218 queries; itera 7 días uno por uno |
| `data()` (Mes 05) | 360 ms | <200 ms | `collect()->sum()` en PHP |
| `monthGoal()` | 339 ms | <100 ms | Eager load faltante |
| `cashFlow(months=6)` | 309 ms | <100 ms | 8 iteraciones × 3 tablas idénticas |
| `kpi->data(mes05)` | 30 ms ✅ | — | OK |
| `debtors(limit=4)` | 39 ms ✅ | — | OK |

**Estimación de carga total del dashboard** al abrir con Mes 05/2026:
- `data()` + `globalData()` + 7 widgets de Buho + `kpi()` + … = **~7.400 ms**
  ejecutándose en paralelo.
- Cliente recibe el último ≈ **3.000-4.000 ms**.

### Patrones problemáticos identificados

**A — `globalData()` ejecuta 6 veces las mismas 3 queries base.**
`monthlyKpis()` iteraba mes por mes y dentro de cada mes llamaba
`document_totals_globals()` + `sale_note_totals_global()`. Cada uno hacía `->get()`
de TODA la tabla filtrada. 18 queries `whereBetween('date_of_issue', ...)` cuando
1 query agregada `GROUP BY DATE_FORMAT(...)` bastaba.

**B — `document_totals()` / `sale_note_totals()` descargaban TODOS los registros
y filtraban en PHP.** Además hacían N+1 dentro del foreach para sumar `payments`.

**C — N+1 confirmado** en `paymentMethods`, `salesWeek`, `monthGoal`, `cashFlow`
por lazy loads de relaciones no desactivados (`users`, `establishments`,
`countries`, `departments` recargados en cada iteración).

**D — `salesWeek()` iteraba 7 días × 3 tablas × N estados** = ~218 queries.

**E — `cashFlow()` iteraba 8 meses** con la misma query redundante.

---

## 3. Fixes aplicados

Todos los cambios viven en `modules/Dashboard/Helpers/DashboardData.php` y
`modules/Dashboard/Traits/TotalsTrait.php`. Commit principal:
**`7fdcd9b0` perf(dashboard): SQL aggregations replace Eloquent->get()+collect()->sum() loops**

### Fix #1 — `monthlyKpis()` → GROUP BY agregado
Reemplazado el loop de 6 meses con UNA query `GROUP BY DATE_FORMAT(...)` que
devuelve suma por mes y por moneda. Toca también `kpisForRangesAggregated()`
y `kpisForRangeAggregatedByMonth()`.

```php
// Antes: 6 iteraciones × 3 tablas × get() + collect()->sum()
foreach ($months as $m) {
    $sn = SaleNote::whereBetween('date_of_issue', [$m->start, $m->end])->get();
    $doc = Document::whereBetween('date_of_issue', [$m->start, $m->end])->get();
    // ... collect()->sum() en PHP
}

// Después: 1 query por tabla con GROUP BY
$sale_notes_by_month = DB::connection('tenant')->table('sale_notes')
    ->selectRaw("DATE_FORMAT(date_of_issue, '%Y-%m') as month,
                 currency_type_id,
                 SUM(total) as total,
                 SUM(total * exchange_rate_sale) as total_pen")
    ->whereBetween('date_of_issue', [$start, $end])
    ->groupBy('month', 'currency_type_id')
    ->get()
    ->groupBy('month');
```

### Fix #2 — `document_totals()` / `sale_note_totals()` → SUM en SQL
Reemplazado el `->get()` + `collect()->sum()` + `foreach payments` con queries
agregadas directas que diferencian PEN/USD y NC (notas de crédito) en el mismo
`SELECT` con `CASE WHEN`.

### Fix #3 — `document_totals_globals()` / `sale_note_totals_global()` → SUM en SQL
Idéntico al Fix #2 pero sin filtro de establishment. Impacto grande con rangos
largos porque ya no descarga las 9.451 NV en PHP.

### Fix #4 — `salesWeek()` → GROUP BY por día
1 query agregada por tabla que agrupa por día, en lugar de iterar 7 días con
`getDocumentsByDay()`.

### Fix #5 — `paymentMethods()` → JOINs + GROUP BY
`document_payments` JOIN `documents` + GROUP BY método. Sin N+1.

### Fix #6 — `lowStock()`, `monthGoal()`, `cashFlow()` → eliminar redundancias
- `cashFlow()` ya no llama `kpisForRange()` en bucle (era 1 query por mes).
- `monthGoal()` se beneficia de los eager loads previos.
- `lowStock()` separó la carga eager a un solo batch query.

### Fix #7 — `totals()` → SQL aggregation con DB::table()
`DashboardData::totals()` reemplazó `Eloquent->get()` por `DB::table()->get()` para
evitar lazy loads implícitos. Esto introdujo el bug del Fix #9 abajo.

### Fix #8 — `TotalsTrait` (purchase/expense/sale_note/document totals) → SUM
Archivo: `modules/Dashboard/Traits/TotalsTrait.php` (271 líneas).
Los 4 métodos `get_*_totals()` ahora hacen:
- 1 SUM aggregation por tabla principal
- 1 JOIN aggregation por tabla de pagos

```php
// Patrón aplicado en los 4 métodos:
$agg = (clone $builder)->selectRaw("
    SUM(CASE WHEN currency_type_id = 'PEN' THEN total ELSE 0 END) as pen,
    SUM(CASE WHEN currency_type_id = 'USD' THEN total * exchange_rate_sale ELSE 0 END) as usd_pen
")->first();

$payments = (clone $payment_query)->selectRaw("
    SUM(CASE WHEN sale_notes.currency_type_id = 'PEN' THEN sale_note_payments.payment ELSE 0 END) as pen_pay,
    SUM(CASE WHEN sale_notes.currency_type_id = 'USD' THEN sale_note_payments.payment * sale_notes.exchange_rate_sale ELSE 0 END) as usd_pay
")->first();
```

---

## 4. Bugs encontrados durante la verificación

### Fix #9 — Chart desaparecía al seleccionar mes con datos (commit `63ee5aab`)

**Síntoma**: Seleccionar 06/2026 → "Sin totales para graficar en el periodo."

**Causa raíz**: El cambio de `Eloquent->get()` (que hidrata columnas fecha como
`Carbon`) a `DB::table()->get()` (que devuelve `stdClass` con fechas como string)
rompió dos métodos:

1. `getDocumentsByDays()`: comparaba `where('date_of_issue', $d_start)` donde
   `$d_start` era `Carbon` — comparación siempre falsa → todos los días en 0.
2. `getDocumentsByMonths()`: `$row->date_of_issue->format('m')` —
   `stdClass` no tiene método `->format()` → excepción silenciosa.

**Fix**:
- `getDocumentsByDays()`: agregado `$date_str = $d_start->format('Y-m-d');` al
  inicio del `while` y usado string en `where('date_of_issue', $date_str)`.
- `getDocumentsByMonths()`: reemplazado `$row->date_of_issue->format('m')` por
  `substr($row->date_of_issue, 5, 2)` en todos los callbacks de filtro.

**Verificación**: totales cuadran exactamente con pro8 para 2026-04, 2026-05 y
2026-06. Performance mantenida (`data()` en 62 ms con 26 queries, `totals()` en
15 ms con 4 queries).

### Fix #10 — Datepicker quedaba en mes actual en vez del seleccionado

**Síntoma**: Seleccionar 05/2026 → input muestra "05/2026" pero al abrir el
popup el mes en negrita es "jul" (mes actual del sistema). El usuario pidió
"debería estar en negrita mayo".

**Causa raíz**: Element UI 2.13 usa **`fecha`** (no moment) para parsear el
valor de `value-format`. Con `value-format="yyyy-MM"` y value `"2026-05"`,
`fecha.parse()` devuelve **Invalid Date** porque falta el componente día.
El panel del popup queda con `new Date()` (hoy = julio) como fecha base y
el valor del input se ignora.

**Fix** (commits `7b7b6fca` + `8276b956`):

1. Refactor: `form.month_start`, `form.month_end`, `form.date_start`,
   `form.date_end` ahora son **objetos `Date` nativos** en lugar de strings.
2. Eliminados los `value-format="yyyy-MM"` / `"yyyy-MM-dd"` de los 4
   `el-date-picker` (se mantiene `format="MM/yyyy"` solo para el rendering
   del input).
3. Nuevo método `apiPayload()` que formatea las fechas a string justo antes
   de enviarlas al backend (`YYYY-MM` y `YYYY-MM-DD`, lo que
   `Carbon::parse($x.'-01')` espera).
4. `loadData()`, `loadDataAditional()`, `loadDataUtilities()` y
   `clickDownload()` usan `apiPayload()` en lugar de `this.form` crudo.
5. `initForm()` y `changePeriod()` inicializan con `moment().toDate()`.
6. `pickerOptionsMonths` / `pickerOptionsDates` reescritas para comparar
   Date vs Date con `moment(time).isBefore(form.month_start, 'month')`.
7. `getGeneralChartStartDate()` ya no usa strict parsing
   (`moment(date, "YYYY-MM", true)`).
8. Se eliminó el método auxiliar `syncPickerToValue()` (con el refactor ya
   no hace falta; era un parche para un síntoma, no para la causa).

**Lecciones aprendidas**:
- `fecha` (parser interno de Element UI 2.x) **no maneja formatos sin día**.
- Cualquier workaround vía `panel.date` falla porque el panel se renderiza
  con la fecha inválida.
- Usar Date objects en `v-model` es la única forma robusta de evitar este bug.

---

## 5. Resultados finales

| Endpoint | Antes | Después | Reducción |
|---|---|---|---|
| `globalData(month=05)` | 3.029 ms | ~400 ms | **-87 %** |
| `globalData(last_week)` | 580 ms | ~100 ms | -83 % |
| `salesWeek()` | 917 ms | ~80 ms | -91 % |
| `paymentMethods()` | 1.225 ms | ~200 ms | -84 % |
| `data()` (Mes 05) | 360 ms | ~80 ms | -78 % |
| `cashFlow()` | 309 ms | ~100 ms | -68 % |
| Otros (~6 widgets) | ~600 ms | ~200 ms | -67 % |
| **TOTAL dashboard** | **~7.000 ms** | **~1.200 ms** | **-83 %** |

El dashboard pasa de **~7 segundos a ~1.2 segundos** de carga con Mes 05/2026
en HENAVI. Sigue siendo más lento que pro8 (~600 ms) porque pro9 carga los
mismos widgets básicos + 7 widgets nuevos de Buho, pero la diferencia dejó de
ser bloqueante.

---

## 6. Archivos modificados

```
modules/Dashboard/Helpers/DashboardData.php     (1.866 líneas, principal)
modules/Dashboard/Traits/TotalsTrait.php       (271 líneas, reescrito)
modules/Dashboard/Resources/assets/js/views/index.vue
                                              (2 fixes UX: datepicker)
```

**No se modificó**:
- `DashboardController.php` (mismos endpoints, mismo shape JSON)
- `DashboardKpi.php` (ya estaba a 30 ms, OK)
- Ningún modelo Eloquent
- Ninguna migración
- Ningún componente Vue de gráficos

---

## 7. Lecciones aprendidas

1. **Medir antes de optimizar.** Sin `measure_dashboard.php` habríamos atacado
   al azar. El script reveló que `globalData()` era 8× más lento que el segundo
   peor endpoint.

2. **`Eloquent->get()` no es gratis.** Hidratar modelos paga CPU + memoria y
   abre la puerta a lazy loads. Para totales agregados, `DB::table()->selectRaw(...)`
   es estrictamente mejor — pero hay que recordar que devuelve `stdClass` con
   fechas como **string**, no `Carbon` (Fix #9).

3. **Los N+1 de relaciones se acumulan.** `users`, `establishments`,
   `countries` recargados en cada iteración del foreach suman cientos de
   queries. Eager load explícito o `without()` selectivo.

4. **`fecha` (parser de Element UI) no acepta año-mes sin día.** Cualquier
   `value-format="yyyy-MM"` está roto silenciosamente. Usar Date objects en
   `v-model` y formatear solo al cruzar la frontera con el backend.

5. **El JSON shape no cambia.** Mantener las mismas claves en cada método
   permite meter la optimización sin tocar el frontend ni un solo componente
   Vue de gráficos.

---

## 8. Trabajo futuro (no hecho)

Estos son pendientes identificados durante la optimización pero fuera del scope:

- Cachear `globalData()` con TTL de 5-10 min cuando no hay filtro custom.
- Mover el `SaleNote::with(['customer','items'])` de los 6 widgets de Buho a
  una sola query agregada.
- Reemplazar el array de charts con `@vue/server-rendered` para primer paint
  más rápido.
- ~~Auditar `DashboardSalePurchase` (data_aditional) por el mismo patrón N+1.~~
  **Hecho 2026-07-17** — ver § siguiente.

---

## Fase 2: data_aditional (Productos más vendidos)

**Fecha**: 2026-07-17
**Síntoma reportado**: "En productos más vendidos siempre hay mucho retardo".

### Medición inicial

Tenant: **HENAVI**, Mes 05/2026 (2,727 NV + 30 documentos).
Script: `measure_items_by_sales.php` (wrapper de `items_by_sales()` vía reflection).

| | Antes | Después | Reducción |
|---|---|---|---|
| `items_by_sales(est, mes05)` | 4,271 ms | **5 ms** | **-99.9%** |
| Queries disparadas | 6,464 | 3 | **-99.95%** |
| `data_aditional` completo | 4,383 ms | 124 ms | -97% |

### Causa raíz

`DashboardSalePurchase::items_by_sales()` (113 líneas reescritas) tenía **3 niveles de N+1**:

**N+1 #1 — Lazy load de `items` por cada documento/NV** (líneas 317-327 de la versión previa):
```php
foreach ($documents as $doc) {
    foreach ($doc->items as $item) { $document_items->push($item); }  // 1 query/doc
}
foreach ($sale_notes as $s_notes) {
    foreach ($s_notes->items as $item) { $sale_note_items->push($item); }  // 1 query/NV (¡2,727!)
}
```

**N+1 #2 — `Item::find()` por cada item_id único** (línea 341):
```php
foreach ($group_items as $items) {
    $item = Item::without([...])->where('status', true)->find($items[0]->item_id);  // 1 query/item
```

**N+1 #3 — Acceso a `$it->document` o `$it->sale_note` por cada item** (líneas 350 y 369):
```php
foreach ($items as $it) {
    if ($it->document) {  // 1 query/item
        $totals += $this->calculateTotalCurrency($it->document->currency_type_id, ...);
    } else {
        $totals += $this->calculateTotalCurrency($it->sale_note->currency_type_id, ...);  // 1 query/item
    }
}
```

Queries repetidas confirmadas en el query log:
```
x339  select * from cat_affectation_igv_types where id in (10)
x339  select * from cat_system_isc_types where 0 = 1
x23   select * from document_items where document_id = ?
```

### Fix aplicado

Reemplazar el triple nested loop con **3 queries SQL agregadas**:

1. **`document_items` agregado por `item_id`** con JOIN a `documents` para separar ventas normales de NC en el mismo `SELECT` usando `CASE WHEN`:
   ```php
   $doc_items = DB::connection('tenant')->table('document_items as di')
       ->selectRaw("
           di.item_id,
           SUM(CASE WHEN d.document_type_id IN ('01','03','08') THEN
               CASE WHEN d.currency_type_id = 'PEN' THEN di.total
                    WHEN d.currency_type_id = 'USD' THEN di.total * d.exchange_rate_sale
               END
           ELSE 0 END) as total_sale,
           SUM(CASE WHEN d.document_type_id NOT IN ('01','03','08') THEN
               CASE WHEN d.currency_type_id = 'PEN' THEN di.total
                    WHEN d.currency_type_id = 'USD' THEN di.total * d.exchange_rate_sale
               END
           ELSE 0 END) as total_credit,
           SUM(CASE WHEN d.document_type_id IN ('01','03','08') THEN di.quantity
                    ELSE -di.quantity END) as move_quantity
       ")
       ->join('documents as d', 'd.id', '=', 'di.document_id')
       ->where('d.establishment_id', $establishment_id)
       ->whereIn('d.state_type_id', ['01','03','05','07','13'])
       ->when($d_start && $d_end, fn($q) => $q->whereBetween('d.date_of_issue', [$d_start, $d_end]))
       ->groupBy('di.item_id')->get()->keyBy('item_id');
   ```

2. **`sale_note_items` agregado por `item_id`** (estructura idéntica pero sin NC):
   ```php
   $sn_items = DB::connection('tenant')->table('sale_note_items as sni')
       ->selectRaw("
           sni.item_id,
           SUM(CASE WHEN sn.currency_type_id = 'PEN' THEN sni.total
                    WHEN sn.currency_type_id = 'USD' THEN sni.total * sn.exchange_rate_sale
               END) as total_sale,
           0 as total_credit,
           SUM(sni.quantity) as move_quantity
       ")
       ->join('sale_notes as sn', 'sn.id', '=', 'sni.sale_note_id')
       ->where('sn.establishment_id', $establishment_id)
       ->where('sn.changed', false)
       ->whereIn('sn.state_type_id', ['01','03','05','07','13'])
       ->when($d_start && $d_end, fn($q) => $q->whereBetween('sn.date_of_issue', [$d_start, $d_end]))
       ->groupBy('sni.item_id')->get()->keyBy('item_id');
   ```

3. **Merge en PHP** (item_id como clave) + **batch lookup** de items activos para descripción:
   ```php
   foreach ($doc_items as $item_id => $row) {
       $merged[(int) $item_id] = ['total_sale' => ..., 'total_credit' => ..., 'move_quantity' => ...];
   }
   foreach ($sn_items as $item_id => $row) {
       // suma al existente o crea
   }

   $item_records = DB::connection('tenant')->table('items')
       ->select('id', 'description', 'internal_id')
       ->whereIn('id', $merged->keys())->where('status', true)
       ->get()->keyBy('id');
   ```

### Verificación

Comparación byte-por-byte del output antes/después (HENAVI Mayo 2026, top 10 productos por ingreso):

```diff
- {"ms": 5256, "queries": 6464}
+ {"ms": 5, "queries": 3}
```

El array `items_by_sales` es **idéntico** en ambos casos (mismos 10 productos, mismos totales,
mismos `move_quantity`). No se cambió la forma del JSON, los endpoints, ni el frontend.

### Lo que NO se modificó

- `top_customers()` y `purchase_totals()` — están OK (119 ms / 1 ms).
- Frontend (`TopProducts.vue`, `index.vue`).
- Endpoints del `DashboardController`.
- Modelos, migraciones.

### Archivos modificados

```
modules/Dashboard/Helpers/DashboardSalePurchase.php  (113 líneas reescritas en items_by_sales)
measure_items_by_sales.php                          (script de validación)
PERFORMANCE.md                                      (esta sección)
```

### Lecciones aprendidas (Fase 2)

1. **Las cargas perezosas son invisibles hasta que se explotan.** `Document::without(['items', ...])` PARECE desactivar la relación pero NO lo hace — `without()` solo previene eager loading, no previene lazy load. El único `without()` efectivo aquí habría sido eliminar la relación del modelo, pero eso rompe otras partes. La solución correcta es `JOIN ... GROUP BY` y nunca tocar `->items` en PHP.

2. **`->find($id)` en un loop es siempre N+1.** Aunque `find()` toma una clave primaria (rápida), 339 lookups × ~3 ms = 1+ segundo solo en overhead de query parsing y conexión.

3. **`data_aditional` ahora se completa en 124 ms** con `top_customers` (118 ms) dominando. Ese método todavía tiene 22 queries duplicadas de `users`, `establishments`, `countries` que serían el siguiente objetivo (revisitar si el usuario reporta lentitud en top_customers).

---

## Fase 3: `/utilities` 500 por OOM (2026-07-17 11:10)

### Trigger

El usuario revisó F12 → network y reportó que el endpoint `/utilities` salía en
**rojo (500)** al seleccionar **Mes 05/2026** en HENAVI. Confirmado en
`storage/logs/laravel-2026-07-17.log`:

```
[2026-07-17 10:07:52] local.ERROR: Allowed memory size of 134217728 bytes exhausted
[2026-07-17 10:14:26] local.ERROR: Allowed memory size of 134217728 bytes exhausted
... (15+ entradas idénticas hasta 10:57)
```

PHP-FPM prod corre con `memory_limit=128M`. La causa raíz: `DashboardUtility::data()`
materializaba **15k+ modelos Eloquent** en RAM y disparaba N+1 masivo.

### Medición del problema

`measure_utilities.php` (script CLI con `memory_limit=128M` para igualar prod):

| Métrica | Valor |
|---|---|
| `SaleNoteItem` cargados | **8,198** items |
| `SaleNote` recargados en `getTotalSaleNotesByItems` | **2,716** modelos |
| `DocumentItem` cargados | ~miles |
| Memoria peak (CLI) | 94.5 MB / 128 MB (al límite) |
| Tiempo total | 4,350 ms |
| HTTP-FPM peak estimado | ~125 MB (sobre 128 MB → OOM) |

### Causas específicas encontradas

1. **`getTotalSaleNotesByItems()` (L229 original)** — `SaleNote::whereRecordsByItems($ids)->get()->sum(fn)` materializaba 2,716 modelos Eloquent solo para sumar 3 columnas.

2. **`foreach ($sale_note_items as $sln)` (L255 original)** — dentro hacía `$sln->sale_note->currency_type_id` → **1 lazy load por item = ~8,198 queries**.

3. **`foreach ($document_items as $doc_it)` (L328 original)** — `$doc_it->document->currency_type_id` y `$doc_it->document->document_type_id` → **2 lazy loads por item**.

4. **Bug colateral** (L309 original) — `Document::select('id','total','document_type_id','currency_type_id')` pero líneas 321/323 usaban `$doc->exchange_rate_sale` que NO estaba en el select → **1 lazy load por cada documento USD**. HENAVI no tiene docs USD en mayo-26 (0 casos) pero en otros tenants esto causaría OOM.

### Fix aplicado

**3 cambios quirúrgicos** en `modules/Dashboard/Helpers/DashboardUtility.php`:

**Cambio 1** — `getTotalSaleNotesByItems()` ahora es 1 query SQL agregada:
```php
return (float) DB::connection('tenant')->table('sale_notes')
    ->whereIn('id', $sale_note_ids)
    ->selectRaw("SUM(CASE WHEN currency_type_id = 'PEN' THEN total
                          WHEN currency_type_id = 'USD' THEN total * exchange_rate_sale
                     END) as total_transformed")
    ->value('total_transformed') ?? 0;
```

Reproduce matemáticamente `getTransformTotal()`: PEN→total, USD→total×exchange_rate_sale.

**Cambio 2** — `SaleNoteItem::whereHas('sale_note',...)` ahora precarga `sale_note` con `with(['sale_note' => fn($q) => $q->without([...])->select('id','currency_type_id','exchange_rate_sale')])`. Convierte 8,198 lazy loads en 1 query batch.

**Cambio 3** — Análogo para `DocumentItem::whereHas('document',...)` + agregar `exchange_rate_sale` al `select` de `Document::whereIn` (línea L309) para eliminar el bug de lazy load por USD.

### Resultado (HENAVI Mes 05/2026)

| Métrica | Antes | Después | Cambio |
|---|---|---|---|
| Tiempo total | 4,350 ms | **31 ms** | **-99.3%** |
| Memoria peak (CLI) | 94.5 MB | **34 MB** | **-64%** |
| Queries totales | ~30k (N+1) | **9** (1 duplicada) | **-99.97%** |
| HTTP-FPM | 🔴 500 OOM | 🟢 <500ms | **fix** |

Output idéntico byte por byte: `{"total_income":"22567.60","total_egress":"5061.82","utility":"17505.78"}`.

### Verificación

```bash
docker exec pro9_app php -l modules/Dashboard/Helpers/DashboardUtility.php
docker exec pro9_app php -d memory_limit=128M measure_utilities.php
docker exec pro9_app php measure_dashboard.php | grep "utilities\("
```

### Lo que NO se modificó

- Frontend (Vue) — JSON shape idéntico.
- `DashboardController::utilities()` — endpoint intacto.
- `getTotalExpenses()`, `getPurchaseUnitPrice()`, `getQuantityUnitPresentation()` — no son hot path.

### Lecciones aprendidas (Fase 3)

1. **`->get()->sum(fn)` es siempre sospechoso cuando N crece.** Traer N modelos solo para sumar 3 columnas que ya están en el SELECT es desperdicio de RAM y CPU. La regla es: si necesitas solo sumas, `SUM(...)` en SQL; si necesitas lógica compleja por fila, `->pluck('col')->pipe('SUM')` o `->reduce()` sin materializar el modelo.

2. **`with()` no es opcional cuando el foreach accede a relaciones.** Si el foreach hace `$item->relation->campo`, sin `with()` obtienes 1 query por item. La única manera fiable de detectar estos bugs es contar queries con `DB::enableQueryLog()` y revisar el patrón repetido.

3. **`select(...)` debe incluir TODO lo que el foreach lee después.** Un campo faltante en el select NO causa error — causa un lazy load silencioso por cada fila. La revisión manual línea por línea es la única defensa.

4. **128 MB es el nuevo límite crítico.** Con 15k+ modelos Eloquent en RAM, cualquier endpoint que materialice colecciones masivas puede cruzar el límite en HTTP-FPM aunque el CLI aguante. La regla práctica: peak CLI × 1.3 = peak HTTP-FPM estimado. Si > 100 MB, refactorizar a SQL aggregation.