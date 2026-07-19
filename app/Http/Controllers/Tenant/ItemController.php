<?php
namespace App\Http\Controllers\Tenant;

use Illuminate\Support\Facades\DB;
use App\Exports\DigemidItemExport;
use App\Helpers\BarcodeHelper;
use App\Exports\ItemExport;
use App\Exports\ItemExportWp;
use App\Exports\ItemExtraDataExport;
use App\Http\Controllers\Controller;
use App\Http\Controllers\PdfUnionController;
use App\Http\Controllers\SearchItemController;
use App\Http\Requests\Tenant\ItemRequest;
use App\Http\Resources\Tenant\ItemCollection;
use App\Http\Resources\Tenant\ItemResource;
use App\Imports\CatalogImport;
use App\Imports\ItemsImport;
use App\Imports\ItemsImportRestaurant;
use App\Imports\ItemPresentationsImport;
use App\Exports\ItemPresentationsExport;
use App\Models\Tenant\Catalogs\AffectationIgvType;
use App\Models\Tenant\Catalogs\AttributeType;
use App\Models\Tenant\Catalogs\CatColorsItem;
use App\Models\Tenant\Catalogs\CatItemMoldCavity;
use App\Models\Tenant\Catalogs\CatItemMoldProperty;
use App\Models\Tenant\Catalogs\CatItemPackageMeasurement;
use App\Models\Tenant\Catalogs\CatItemProductFamily;
use App\Models\Tenant\Catalogs\CatItemStatus;
use App\Models\Tenant\Catalogs\CatItemUnitBusiness;
use App\Models\Tenant\Catalogs\CatItemUnitsPerPackage;
use App\Models\Tenant\Catalogs\ChargeDiscountType;
use App\Models\Tenant\Catalogs\CurrencyType;
use App\Models\Tenant\Catalogs\OperationType;
use App\Models\Tenant\Catalogs\PriceType;
use App\Models\Tenant\Catalogs\SystemIscType;
use App\Models\Tenant\Catalogs\Tag;
use App\Models\Tenant\Catalogs\UnitType;
use App\Models\Tenant\CatItemSize;
use App\Models\Tenant\Company;
use App\Models\Tenant\Configuration;
use App\Models\Tenant\Establishment;
use App\Models\Tenant\Item;
use App\Models\Tenant\ItemImage;
use App\Models\Tenant\ItemMovement;
use App\Models\Tenant\ItemSupply;
use App\Models\Tenant\ItemTag;
use App\Models\Tenant\ItemUnitType;
use App\Models\Tenant\ItemWarehousePrice;
use App\Models\Tenant\Warehouse;
use App\Traits\OfflineTrait;
use Barryvdh\DomPDF\Facade\Pdf as PDF;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Excel;
use Modules\Account\Models\Account;
use Modules\Digemid\Models\CatDigemid;
use Modules\Finance\Helpers\UploadFileHelper;
use Modules\Inventory\Models\ItemWarehouse;
use Modules\Item\Models\Brand;
use Modules\Item\Models\Category;
use Modules\Item\Models\ItemLot;
use Modules\Item\Models\ItemLotsGroup;
use Mpdf\HTMLParserMode;
use Mpdf\Mpdf;
use setasign\Fpdi\Fpdi;
use Modules\Inventory\Models\InventoryConfiguration;
use Modules\Inventory\Models\Inventory;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Helpers\CacheHelper;
use Modules\Item\Http\Controllers\EditorTagController;
use Modules\Item\Models\TagTemplate;
use App\Models\Tenant\ItemUnitTypePrice;
use App\Models\Tenant\PriceLabel;

class ItemController extends Controller
{
    use OfflineTrait;

    /**
     * Sincroniza los precios dinámicos de un ItemUnitType
     *
     * @param int $itemUnitTypeId
     * @param array $prices
     * @return void
     */
    protected function syncItemUnitTypePrices($itemUnitTypeId, array $prices)
    {
        // Obtener IDs de precios existentes del request
        $priceIds = collect($prices)
            ->filter(function($price) {
                return isset($price['id']) && $price['id'] > 0;
            })
            ->pluck('id')
            ->toArray();

        // Eliminar precios que ya no están en el request
        ItemUnitTypePrice::where('item_unit_type_id', $itemUnitTypeId)
            ->when(count($priceIds) > 0, function($query) use ($priceIds) {
                $query->whereNotIn('id', $priceIds);
            })
            ->delete();

        // Crear o actualizar precios
        foreach ($prices as $priceData) {
            $priceId = $priceData['id'] ?? null;

            $price = ItemUnitTypePrice::firstOrNew(
                ['id' => $priceId],
                ['item_unit_type_id' => $itemUnitTypeId]
            );

            $price->item_unit_type_id = $itemUnitTypeId;
            $price->price_label_id = $priceData['price_label_id'];
            $price->price = $priceData['price'];
            $price->is_active = $priceData['is_active'] ?? true;
            $price->save();
        }
    }

    public function index(\Illuminate\Http\Request $request)
    {
        if ($request->has('iniciar_operacion_webp')) {
            // Traemos TODOS los productos sin usar filtros de base de datos
            $all_items = \App\Models\Tenant\Item::all();

            $total_bd = $all_items->count();
            $convertidos = 0;
            $ya_eran_webp = 0;
            $archivos_no_encontrados = 0;
            $errores = 0;
            $sin_foto = 0;

            foreach($all_items as $item) {
                // Filtro manual en PHP (Limpiamos espacios vacíos por si acaso)
                $imagen_limpia = trim($item->image);

                if (empty($imagen_limpia) || $imagen_limpia === 'imagen-no-disponible.jpg' || $imagen_limpia === 'imagen-no-disponible.webp') {
                    $sin_foto++;
                    continue;
                }

                // Si la miniatura ya dice ".webp", no perdemos tiempo
                if ($item->image_small && strpos($item->image_small, '.webp') !== false) {
                    $ya_eran_webp++;
                    continue;
                }

                $directory = 'public/uploads/items/';
                $image_path = $directory . $imagen_limpia;

                if (\Illuminate\Support\Facades\Storage::exists($image_path)) {
                    try {
                        $file_content = \Illuminate\Support\Facades\Storage::get($image_path);

                        $name_parts = explode('.', $imagen_limpia);
                        $prefix = str_replace('.' . end($name_parts), '', $imagen_limpia);

                        // Generar Medium WebP
                        $image_medium = \Image::make($file_content)->resize(512, null, function ($c) { $c->aspectRatio(); $c->upsize(); });
                        \Illuminate\Support\Facades\Storage::put($directory . $prefix . '_medium.webp', (string) $image_medium->encode('webp', 80));

                        // Generar Small WebP
                        $image_small = \Image::make($file_content)->resize(256, null, function ($c) { $c->aspectRatio(); $c->upsize(); });
                        \Illuminate\Support\Facades\Storage::put($directory . $prefix . '_small.webp', (string) $image_small->encode('webp', 70));

                        // Actualizar BD
                        $item->image_medium = $prefix . '_medium.webp';
                        $item->image_small = $prefix . '_small.webp';
                        $item->save();

                        $convertidos++;
                    } catch (\Exception $e) {
                        $errores++;
                    }
                } else {
                    $archivos_no_encontrados++;
                }
            }

            return "<div style='font-family: sans-serif; padding: 40px;'>
                        <h2>¡Reporte de Operación (V3 Fuerza Bruta) 🦅!</h2>
                        <p>Total de productos leídos en la Base de Datos: <b>{$total_bd}</b></p>
                        <hr>
                        <ul style='font-size: 18px; line-height: 1.8;'>
                            <li style='color: green;'>Convertidos a WebP exitosamente: <b>{$convertidos}</b></li>
                            <li style='color: blue;'>Ya estaban listos (WebP): <b>{$ya_eran_webp}</b></li>
                            <li style='color: gray;'>No tenían foto propia (usan default): <b>{$sin_foto}</b></li>
                            <li style='color: orange;'>Fotos huérfanas (En BD pero no en disco): <b>{$archivos_no_encontrados}</b></li>
                            <li style='color: red;'>Errores de lectura de imagen: <b>{$errores}</b></li>
                        </ul>
                    </div>";
        }

        $type = 'PRODUCTS';
        return view('tenant.items.index', compact('type'));
    }

    public function indexServices()
    {
        $type = 'ZZ';
        return view('tenant.items.index', compact('type'));
    }

    public function index_ecommerce()
    {
        return view('tenant.items_ecommerce.index');
    }

    public function columns()
    {
        return [
            'description' => 'Nombre',
            'internal_id' => 'Código interno',
            'barcode' => 'Código de barras',
            'model' => 'Modelo',
            'brand' => 'Marca',
            'date_of_due' => 'Fecha vencimiento',
            'lot_code' => 'Código lote',
            'active' => 'Habilitados',
            'inactive' => 'Inhabilitados',
            'category' => 'Categoria'
        ];
    }

    public function records(Request $request)
    {
        // Generar clave de caché basada en todos los filtros
        $cacheParams = [
            'column' => $request->column,
            'value' => $request->value,
            'type' => $request->type,
            'isEcommerce' => $request->query('isEcommerce'),
            'isRestaurant' => $request->isRestaurant,
            'isPharmacy' => $request->isPharmacy,
            'list_value' => $request->list_value,
            'show_disabled' => $request->show_disabled,
            'sort_field' => $request->get('sort_field', 'id'),
            'sort_direction' => $request->get('sort_direction', 'desc'),
            'page' => $request->get('page', 1),
        ];

        $cacheKey = 'items_list_' . md5(json_encode($cacheParams));

        if ($this->pingCache()) {
            return $this->cacheWithTagKey(
                $cacheKey,
                ['items_list'],
                600, // 10 minutos
                fn() => new ItemCollection($this->getRecords($request)->paginate(config('tenant.items_per_page'))),
                [
                    'section' => 'Items List',
                    'filters' => $cacheParams,
                ]
            );
        } else {
            return new ItemCollection($this->getRecords($request)->paginate(config('tenant.items_per_page')));
        }
        // Usar método centralizado de caché
    }


    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getRecords(Request $request)
    {

        $isEcommerce = filter_var($request->query('isEcommerce'), FILTER_VALIDATE_BOOLEAN);
        // $records = Item::whereTypeUser()->whereNotIsSet();
        $records = $this->getInitialQueryRecords($isEcommerce, $request->isRestaurant ?? false);

        $sortField = $request->get('sort_field', 'id');
        $sortDirection = $request->get('sort_direction', 'desc');

        switch ($request->column)
        {

            case 'brand':
                $records->whereHas('brand',function($q) use($request){
                                    $q->where('name', 'like', "%{$request->value}%");
                                });
                break;
            case 'category':
                $records->whereHas('category',function($q) use($request){
                                    $q->where('name', 'like', "%{$request->value}%");
                                });
                break;

            case 'active':
                $records->whereIsActive();
                break;

            case 'inactive':
                $records->whereIsNotActive();
                break;

            default:
                if($request->has('column'))
                {
                    if($this->applyAdvancedRecordsSearch() && $request->column === 'description')
                    {
                        if($request->value) $records->whereAdvancedRecordsSearch($request->column, $request->value);
                    }
                    else
                    {
                        $records->where($request->column, 'like', "%{$request->value}%");
                    }
                }
                break;
        }

        if ($request->has('show_disabled')) {
            switch ($request->show_disabled) {
                case 'enabled':
                    $records->where('active', 1);
                    break;
                case 'disabled':
                    $records->where('active', 0);
                    break;
                // no hacer nada si es 'all'
            }
        }
        if ($request->type) {
            if($request->type ==='PRODUCTS') {
                // listar solo productos en la lista de productos
                $records->whereNotService();
            }else{
                $records->whereService();
            }
        }
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $records->Pharmacy()
                ->with(['cat_digemid']);
        }

        $isRestaurant = $request->has('isRestaurant') && $request->isRestaurant === 'true';
        $isEcommerce = $request->has('isEcommerce') && $request->isEcommerce === 'true';

        if ($request->has('list_value')) {
            switch ($request->list_value) {
                case 'visible':
                    if ($isRestaurant) {
                        $records->where('apply_restaurant', 1);
                    }
                    if ($isEcommerce) {
                        $records->where('apply_store', 1);
                    }
                    break;

                case 'hidden':
                    if ($isRestaurant) {
                        $records->where('apply_restaurant', 0);
                    }
                    if ($isEcommerce) {
                        $records->where('apply_store', 0);
                    }
                    break;

                case 'with_supplies':
                    if ($isRestaurant) {
                        $records->whereHas('restaurantItemSupplies');
                    }
                    break;
            }
        }


        return $records->orderBy($sortField, $sortDirection);

    }


    /**
     *
     * Aplicar filtros iniciales a la consulta
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function getInitialQueryRecords($isEcommerce, $isRestaurant)
    {

        if(Configuration::getRecordIndividualColumn('list_items_by_warehouse') && !$isEcommerce)
        {
            $records = Item::whereWarehouse()->whereNotIsSet();
        }
        else
        {
            if($isRestaurant === "true")
            {
                $records = Item::whereTypeUser();
            } else {
                $records = Item::whereTypeUser()->whereNotIsSet();
            }
        }

        return $records;
    }


    public function create()
    {
        return view('tenant.items.form');
    }

    public function tables()
    {
        $unit_types = UnitType::whereActive()->orderByDescription()->get();
        $currency_types = CurrencyType::whereActive()->orderByDescription()->get();
        $attribute_types = AttributeType::whereActive()->orderByDescription()->get();
        $system_isc_types = SystemIscType::whereActive()->orderByDescription()->get();
        $affectation_igv_types = AffectationIgvType::whereActive()->get();
        $warehouses = Warehouse::all();
        $accounts = Account::all();
        $tags = Tag::all();
        $categories = Category::all();
        $brands = Brand::all();
        $configuration= Configuration::first();
        /** Informacion adicional */
        $colors = collect([]);
        $CatItemStatus=$colors;
        $CatItemUnitBusiness = $colors;
        $CatItemMoldCavity = $colors;
        $CatItemPackageMeasurement =$colors;
        $CatItemUnitsPerPackage = $colors;
        $CatItemMoldProperty = $colors;
        $CatItemProductFamily= $colors;
        $CatItemSize= $colors;
        if($configuration->isShowExtraInfoToItem()){
            $colors = CatColorsItem::all();
            $CatItemStatus= CatItemStatus::all();
            $CatItemSize= CatItemSize::all();
            $CatItemUnitBusiness = CatItemUnitBusiness::all();
            $CatItemMoldCavity = CatItemMoldCavity::all();
            $CatItemPackageMeasurement = CatItemPackageMeasurement::all();
            $CatItemUnitsPerPackage = CatItemUnitsPerPackage::all();
            $CatItemMoldProperty = CatItemMoldProperty::all();
            $CatItemProductFamily= CatItemProductFamily::all();
        }
        /** Informacion adicional */
        $configuration = $configuration->getCollectionData();
        $inventory_configuration = InventoryConfiguration::firstOrFail();
        $next_internal_id = str_pad((Item::max('id') ?? 0) + 1, 5, '0', STR_PAD_LEFT);
        /*
        $configuration = Configuration::select(
            'affectation_igv_type_id',
            'is_pharmacy',
            'show_extra_info_to_item'
        )->firstOrFail();
        */
        return compact(
            'unit_types',
            'currency_types',
            'attribute_types',
            'system_isc_types',
            'affectation_igv_types',
            'warehouses',
            'accounts',
            'tags',
            'categories',
            'brands',
            'configuration',
            'colors',
            'CatItemSize',
            'CatItemMoldCavity',
            'CatItemMoldProperty',
            'CatItemUnitBusiness',
            'CatItemStatus',
            'CatItemPackageMeasurement',
            'CatItemProductFamily',
            'CatItemUnitsPerPackage',
            'inventory_configuration',
            'next_internal_id'
        );
    }

    public function record($id)
    {

        // $record = new ItemResource(Item::findOrFail($id));
        // return $record;
        if ($this->pingCache()) {
            return $this->cacheWithTagKey(
                "item_detail_{$id}", // Clave de caché específica para el detalle del item
                ['item_detail'], // Etiqueta para el detalle del item
                3600, // 1 hora (el detalle cambia menos frecuentemente que las listas)
                fn() => new ItemResource(Item::findOrFail($id)),
                [ 'section' => 'Item Detail', 'item_id' => $id ] // Contexto adicional para logging
            );
        } else {
            return new ItemResource(Item::findOrFail($id));
        }

    }

    public function store(ItemRequest $request) {


        $id = $request->input('id');
        if (!$request->barcode) {
            if ($request->internal_id) {
                $request->merge(['barcode' => $request->internal_id]);
            }
        }
        $item = Item::firstOrNew(['id' => $id]);
        $item->item_type_id = '01';
        $item->amount_plastic_bag_taxes = Configuration::firstOrFail()->amount_plastic_bag_taxes;

        // Blindaje de precios: un vendedor sin permiso per-usuario no puede alterar precios al editar.
        // Se revierten los valores a los originales antes del fill (RECONCILE de seller_can_edit_product a
        // users.permission_edit_item_prices — ver Planes/hechos/2026-07-14-marzo-01-blindaje-precios).
        $auth_user = auth()->user();
        $block_prices = ($id && $auth_user && $auth_user->type === 'seller' && !$auth_user->permission_edit_item_prices);

        if ($block_prices) {
            $original_item = Item::find($id);
            if ($original_item) {
                $request->merge([
                    'sale_unit_price'                  => $original_item->sale_unit_price,
                    'purchase_unit_price'              => $original_item->purchase_unit_price,
                    'percentage_of_profit'             => $original_item->percentage_of_profit,
                    'sale_affectation_igv_type_id'     => $original_item->sale_affectation_igv_type_id,
                    'purchase_affectation_igv_type_id' => $original_item->purchase_affectation_igv_type_id,
                ]);
            }
        }

        if ($request->has('date_of_due')) {
            $time = $request->date_of_due;
            $date = null;
            if (isset($time['date'])) {
                $date = $time['date'];
                if (!empty($date)) {
                    $request->merge(['date_of_due' => Carbon::createFromFormat('Y-m-d H:i:s.u', $date)]);
                }
            }
        }
        $current_lot = null;
        if(!empty($item->id)){
            $current_lot = ItemLotsGroup::where([
                'code' => $item->lot_code,
                'item_id'=>$item->id
            ])->first();
        }

        $item->fill($request->all());

        $temp_path = $request->input('temp_path');
        if($temp_path) {

            $directory = 'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR;

            $slug_name = Str::slug($item->description);
            if($item->internal_id){
                $slug_name = Str::slug($item->internal_id);
            }
            $prefix_name = Str::limit($slug_name, 20, '');

            $file_name_old = $request->input('image');
            $file_name_old_array = explode('.', $file_name_old);
            $file_content = file_get_contents($temp_path);
            $datenow = date('YmdHis');
            $file_name = $prefix_name.'-'.$datenow.'.'. end($file_name_old_array);

            UploadFileHelper::checkIfValidFile($file_name, $temp_path, true);

            Storage::put($directory.$file_name, $file_content);
            $item->image = $file_name;

            // ⚡ 2. CLON TÁCTICO WEBP (MEDIUM - Para catálogos grandes)
            $image_medium = \Image::make($temp_path);
            $file_name_medium = $prefix_name.'-'.$datenow.'_medium.webp';
            $image_medium->resize(512, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            // Convertimos a WebP manteniendo una gran calidad (80)
            Storage::put($directory.$file_name_medium, (string) $image_medium->encode('webp', 80));
            $item->image_medium = $file_name_medium;

            // ⚡ 3. CLON TÁCTICO WEBP (SMALL - Exclusivo para el POS)
            $image_small = \Image::make($temp_path);
            $file_name_small = $prefix_name.'-'.$datenow.'_small.webp';
            $image_small->resize(256, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            });
            // Convertimos a WebP con calidad optimizada (70)
            Storage::put($directory.$file_name_small, (string) $image_small->encode('webp', 70));
            $item->image_small = $file_name_small;

        } else if(!$request->input('image') && !$request->input('temp_path') && !$request->input('image_url')){
            // 🛡️ REGLA PARA IMAGEN POR DEFECTO
            $item->image = 'imagen-no-disponible.webp';
            $item->image_medium = 'imagen-no-disponible.webp';
            $item->image_small = 'imagen-no-disponible.webp';
        }

        $item->save();

        foreach ($request->item_unit_types as $value) {

            $item_unit_type = ItemUnitType::firstOrNew(['id' => $value['id']]);
            $item_unit_type->item_id = $item->id;
            $item_unit_type->description = $value['description'];
            $item_unit_type->unit_type_id = $value['unit_type_id'];
            $item_unit_type->quantity_unit = $value['quantity_unit'];

            // Si el vendedor está bloqueado, solo puede fijar precio en presentaciones NUEVAS, no editar existentes.
            if (!$block_prices || !$item_unit_type->exists) {
                $item_unit_type->price_default = $value['price_default'];

                // Mantener compatibilidad con campos legacy (deprecados)
                if (isset($value['price1'])) {
                    $item_unit_type->price1 = $value['price1'];
                    $item_unit_type->price2 = $value['price2'];
                    $item_unit_type->price3 = $value['price3'];
                }
            }

            $item_unit_type->save();

            // Sincronizar precios dinámicos (bloqueado para vendedor sin permiso)
            if (!$block_prices && isset($value['prices']) && is_array($value['prices'])) {
                $this->syncItemUnitTypePrices($item_unit_type->id, $value['prices']);
            }

            // migracion desarrollo sin terminar #1401
            $barcodeProvided = array_key_exists('barcode', $value) && $value['barcode'] !== null && trim((string)$value['barcode']) !== '';

            if (!$barcodeProvided) {
                $item_unit_type->barcode = $item_unit_type->id . $item_unit_type->unit_type_id . $item_unit_type->quantity_unit;
            } else {
                $item_unit_type->barcode = $value['barcode'];
            }
            $item_unit_type->save();
        }
        if (isset($request->supplies)) {
            foreach($request->supplies as $value){

                if(!isset($value['item_id'])) $value['item_id'] = $item->id;
                $itemSupply = ItemSupply::firstOrCreate(['id' => $value['id']],$value);
                $itemSupply->fill($value);
                $itemSupply->save();
            }
        }

        $configuration = Configuration::first();
        if($configuration->isShowExtraInfoToItem()){
            // Extra data
            if($request->has('colors')){
                $item->setItemColor($request->colors);
            }
            if($request->has('CatItemUnitsPerPackage')){
                $item->setItemUnitsPerPackage($request->CatItemUnitsPerPackage);
            }
            if($request->has('CatItemMoldCavity')){
                $item->setItemMoldCavity($request->CatItemMoldCavity);
            }
            if($request->has('CatItemMoldProperty')){
                $item->setItemMoldProperty($request->CatItemMoldProperty);
            }
            if($request->has('CatItemUnitBusiness')){
                $item->setItemUnitBusiness($request->CatItemUnitBusiness);
            }
            if($request->has('CatItemStatus')){
                $item->setItemStatus($request->CatItemStatus);
            }
            if($request->has('CatItemPackageMeasurement')){
                $item->setItemPackageMeasurement($request->CatItemPackageMeasurement);
            }
            if($request->has('CatItemProductFamily')){
                $item->setItemProductFamily($request->CatItemProductFamily);
            }
            if($request->has('CatItemSize')){
                $item->setItemSize($request->CatItemSize);
            }
            // Extra data
        }



        if ($request->tags_id) {
            ItemTag::destroy(   ItemTag::where('item_id', $item->id)->pluck('id'));
            foreach ($request->tags_id as $value) {
                ItemTag::create(['item_id' => $item->id,  'tag_id' => $value]);
                //$tag = ItemTag::where('item_id', $item->id)->where('tag_id', $value)->first();
            }
        }

        if (!$id) {

            // $item->lots()->delete();
            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();

            //$warehouse = WarehouseModule::find(auth()->user()->establishment_id);
            if($warehouse && !isset($request->warehouse_id)){
                $item->warehouse_id = $warehouse->id;
                $item->save();
            }

            $v_lots = isset($request->lots) ? $request->lots:[];

            foreach ($v_lots as $lot) {
                $item->lots()->create([
                    'date' => $lot['date'],
                    'series' => $lot['series'],
                    'item_id' => $item->id,
                    'warehouse_id' => $warehouse ? $warehouse->id:null,
                    'has_sale' => false,
                    'state' => $lot['state'],
                ]);
            }

            // ADR-0015 (rev.): switch GLOBAL "los lotes mandan sobre el stock"
            // (configurations.lots_govern_stock). En OFF (default) el stock manda y se valida
            // Σlotes ≤ stock. En ON el stock seguirá a los lotes (ver applyLotsGovernStock),
            // por lo que ese clamp no aplica.
            $lots_govern_stock = (bool) optional(Configuration::first())->lots_govern_stock;
            $warehouse_id_resolved = $warehouse ? $warehouse->id : null;

            // Guard de sobre-asignación: la suma de cantidades de lotes reales de cada
            // almacén no puede exceder su stock real.
            if (isset($request->lots_enabled) && $request->lots_enabled) {
                foreach ($request->input('lots_tab', []) as $lot_data) {
                    if ((float)($lot_data['quantity'] ?? 0) < 0) {
                        return response()->json([
                            'success' => false,
                            'message' => 'La cantidad de un lote no puede ser negativa.',
                        ], 422);
                    }
                }

                $sum_by_warehouse = [];
                foreach ($request->input('lots_tab', []) as $lot_data) {
                    if (($lot_data['code'] ?? null) === ItemLotsGroup::LIBRE_CODE) continue;
                    $wh = !empty($lot_data['warehouse_id']) ? $lot_data['warehouse_id'] : $warehouse_id_resolved;
                    $sum_by_warehouse[$wh] = ($sum_by_warehouse[$wh] ?? 0) + (float)($lot_data['quantity'] ?? 0);
                }

                if (!$lots_govern_stock && !empty($sum_by_warehouse)) {
                    $stock_by_warehouse = [$warehouse_id_resolved => (float) $request->input('stock', 0)];
                    $over = ItemLotsGroup::findStockOverAllocations($sum_by_warehouse, $stock_by_warehouse);
                    if (!empty($over)) {
                        $v    = $over[0];
                        $diff = (float) $v['sum'] - (float) $v['stock'];
                        return [
                            'success' => false,
                            'message' => "No se puede guardar. En el almacén [{$v['description']}] los lotes suman {$v['sum']} unidad(es) pero el stock real es {$v['stock']}. Reduce {$diff} unidad(es) antes de guardar.",
                        ];
                    }
                }
            }

            $lots_enabled = isset($request->lots_enabled) ? $request->lots_enabled : false;
            $lots_tab     = $request->input('lots_tab', []);

            if ($lots_enabled && !empty($lots_tab)) {
                foreach ($lots_tab as $lot_data) {
                    $lot_warehouse = !empty($lot_data['warehouse_id']) ? $lot_data['warehouse_id'] : $warehouse_id_resolved;
                    ItemLotsGroup::create([
                        'code'         => $lot_data['code'],
                        'item_id'      => $item->id,
                        'quantity'     => (float)($lot_data['quantity'] ?? 0),
                        'date_of_due'  => $lot_data['date_of_due'] ?? null,
                        'warehouse_id' => $lot_warehouse,
                    ]);
                }
            }

            // En OFF el LIBRE absorbe el stock sin lotes de cada almacén.
            // En ON el stock sigue a los lotes (ADR-0015).
            if ($lots_enabled) {
                if ($lots_govern_stock) {
                    $this->applyLotsGovernStock($item);
                } else {
                    ItemLotsGroup::syncLibreForOrphanStock($item->id);
                }
            }
        } else {
            /*
            $item->lots()->delete();
            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();
            $v_lots = isset($request->lots) ? $request->lots:[];
            foreach ($v_lots as $lot) {
                if ($lot['deleted'] == true) {
                    ItemLot::find($lot['id'])->delete();
                } else {
                    if ( isset( $lot['id'] )) {
                        ItemLot::find($lot['id'])->update([
                            'date' => $lot['date'],
                            'series' => $lot['series'],
                            'state' => $lot['state'],
                        ]);
                    } else {
                        $item->lots()->create([
                            'date' => $lot['date'],
                            'series' => $lot['series'],
                            'item_id' => $item->id,
                            'warehouse_id' => $warehouse ? $warehouse->id:null,
                            'has_sale' => false,
                            'state' => $lot['state'],
                        ]);
                    }
                }
            }
            */
            /****************************** SECCION PARA SEIRES EN ITEMLOT **********************************************/
            $establishment = Establishment::where('id', auth()->user()->establishment_id)->first();
            $warehouse = Warehouse::where('establishment_id',$establishment->id)->first();
            $v_lots = isset($request->lots) ? $request->lots:[];
            foreach ($v_lots as $lot) {
                /**
                 * @var  ItemLot $temp_serie
                 * @var Int $lot_id
                 * @var Bool $delete
                 */
                $lot_id = isset($lot['id'])? (int) $lot['id']:0;
                $delete = isset($lot['deleted'])?(boolean)$lot['deleted']:false;
                if($lot_id != 0){
                    $temp_serie = ItemLot::find($lot_id);
                    if(!empty($temp_serie)){
                        if($delete == true){
                            $temp_serie->delete();
                        }else{
                            $temp_serie
                                ->setDate($lot['date'])
                                ->setSeries($lot['series'])
                                ->setState($lot['state'])
                                ->push();
                        }
                    }
                }else{
                    $temp_serie = new ItemLot([
                        'date' => $lot['date'],
                        'series' => $lot['series'],
                        'item_id' => $item->id,
                        'warehouse_id' => $warehouse ? $warehouse->id:null,
                        'has_sale' => false,
                        'state' => $lot['state'],
                    ]);
                    $temp_serie->push();
                }
            }

            $lots_enabled = isset($request->lots_enabled) ? $request->lots_enabled:false;
            $lots_tab     = $request->input('lots_tab', []);
            $warehouse_id_resolved = $warehouse ? $warehouse->id : null;

            if ($lots_enabled) {
                // IDs que el frontend envía (lotes existentes que deben conservarse).
                $incoming_ids = collect($lots_tab)
                    ->pluck('id')
                    ->filter()
                    ->values()
                    ->toArray();

                // Eliminar lotes que el usuario quitó de la tabla — solo si no tienen movimientos.
                // El LIBRE se excluye: lo gestiona el sync de stock huérfano, no la tabla de lotes.
                $lots_to_delete = ItemLotsGroup::where('item_id', $item->id)
                    ->where('code', '!=', ItemLotsGroup::LIBRE_CODE)
                    ->whereNotIn('id', $incoming_ids)
                    ->get();

                $protected = $lots_to_delete->filter(fn($l) => $l->has_movements);
                if ($protected->isNotEmpty()) {
                    $codes = $protected->pluck('code')->join(', ');
                    return response()->json([
                        'success' => false,
                        'message' => "No se pueden eliminar los lotes [{$codes}] porque tienen movimientos registrados.",
                    ], 422);
                }

                $lots_to_delete->each->delete();

                foreach ($lots_tab as $lot_data) {
                    $lot_warehouse = !empty($lot_data['warehouse_id']) ? $lot_data['warehouse_id'] : $warehouse_id_resolved;

                    if (!empty($lot_data['id'])) {
                        // Lote existente → actualizar código, cantidad y fecha.
                        $lot = ItemLotsGroup::find((int)$lot_data['id']);
                        if ($lot) {
                            $update_data = [
                                'quantity'     => (float)($lot_data['quantity'] ?? 0),
                                'warehouse_id' => $lot_warehouse,
                            ];
                            // código y fecha son inmutables si el lote tiene movimientos.
                            if (!$lot->has_movements) {
                                $update_data['code']        = $lot_data['code'];
                                $update_data['date_of_due'] = $lot_data['date_of_due'] ?? null;
                            }
                            $lot->update($update_data);
                        }
                    } else {
                        // Lote nuevo (sin id) → crear.
                        ItemLotsGroup::create([
                            'code'         => $lot_data['code'],
                            'item_id'      => $item->id,
                            'quantity'     => (float)($lot_data['quantity'] ?? 0),
                            'date_of_due'  => $lot_data['date_of_due'] ?? null,
                            'warehouse_id' => $lot_warehouse,
                        ]);
                    }
                }

                // OFF: recalcular el LIBRE de cada almacén (el sobrante sin clasificar queda
                // representado). ON: el stock pasa a ser Σlotes + ajuste en kardex.
                $lots_govern_stock = (bool) optional(Configuration::first())->lots_govern_stock;
                if ($lots_govern_stock) {
                    $this->applyLotsGovernStock($item);
                } else {
                    ItemLotsGroup::syncLibreForOrphanStock($item->id);
                }
            } else {
                // lots_enabled desactivado → eliminar todos los lotes (solo si ninguno tiene
                // movimientos). El LIBRE (gestionado por el sistema) no cuenta para el bloqueo,
                // pero se borra igual.
                $existing_lots = ItemLotsGroup::where('item_id', $item->id)->get();
                $protected     = $existing_lots->filter(fn($l) => $l->has_movements && $l->code !== ItemLotsGroup::LIBRE_CODE);

                if ($protected->isNotEmpty()) {
                    $codes = $protected->pluck('code')->join(', ');
                    return response()->json([
                        'success' => false,
                        'message' => "No se puede desactivar lotes porque los lotes [{$codes}] tienen movimientos registrados. Contacte a soporte si necesita hacerlo.",
                    ], 422);
                }

                ItemLotsGroup::where('item_id', $item->id)->delete();
            }
        }

        $directory = 'public'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR;

        $multi_images = isset($request->multi_images) ? $request->multi_images:[];

        foreach ($multi_images as $im) {

            $file_name = $im['filename'];
            UploadFileHelper::checkIfValidFile($file_name, $im['temp_path'], true);

            $file_content = file_get_contents($im['temp_path']);
            Storage::put($directory.$file_name, $file_content);

            ItemImage::create(['item_id'=> $item->id, 'image' => $file_name]);
        }

        if (!$item->barcode) {
            $item->barcode = str_pad($item->id, 12, '0', STR_PAD_LEFT);
        }

        $item->update();

        // migracion desarrollo sin terminar #1401
        // $inventory_configuration = InventoryConfiguration::firstOrFail();

        // if($inventory_configuration->generate_internal_id == 1) {
        //     if(!$item->internal_id) {
        //         $items = Item::count();
        //         $item->internal_id = (string)($items + 1);
        //         $item->save();
        //     }
        // }

        $this->generateInternalId($item);

        /********************************* SECCION PARA PRECIO POR ALMACENES ******************************************/

        // Precios por almacenes
        // $warehouses = $request->warehouses;

        if (!$block_prices) {
            $this->createItemWarehousePrices($request, $item);
        }

        // if ($warehouses) {
            // /** @var ItemWarehousePrice $price */

            // foreach ($warehouses as $warehouse) {
            //     $price = ItemWarehousePrice::where([
            //         'item_id' => $item->id,
            //         'warehouse_id' => $warehouse['id'],
            //     ])->first();
            //     if(empty($price)){
            //         $price = new ItemWarehousePrice([
            //             'item_id' => $item->id,
            //             'warehouse_id' => $warehouse['id'],
            //         ]) ;
            //     }
            //     $price
            //         ->setPrice($warehouse['price'])
            //         ->push();
            // }

            /*
            ItemWarehousePrice::where('item_id', $item->id)
                ->delete();

            foreach ($warehouses as $warehousePrice) {
                try {
                    $price = $warehousePrice['price'];
					if (is_numeric($warehousePrice['price'])) {
						ItemWarehousePrice::query()->insert([
							'item_id'      => $item->id,
							'warehouse_id' => $warehousePrice['id'],
							'price'        => $price,
						]);
					}
                } catch (\Throwable $th) {
                    \Log::error('No se pudo agregar el precio del producto al almacén ' . $warehousePrice['id']);
                }
            }
            */
        // }

        // Invalidar caché del item individual cuando se edita
        if (isset($id) && $id) {
            CacheHelper::forget(['item_detail'], "item_detail_{$id}");
        }
        // Invalidar caché de listas cuando se crea/edita un item
        CacheHelper::flush(['items_list']);

        return [
            'success' => true,
            'message' => ($id)?'Producto editado con éxito':'Producto registrado con éxito',
            'id' => $item->id
        ];
    }

    /**
     * Modo ON de ADR-0015 (los lotes mandan sobre el stock):
     *   1. Vacía el LIBRE para que no contamine futuras sumas.
     *   2. Calcula el delta = Σlotes reales − stock actual del almacén.
     *   3. Crea un Inventory(type=1, quantity=delta). El listener Inventory::created
     *      (InventoryChangeServiceProvider) aplica updateStock(+delta) y escribe el
     *      kardex — NO duplicar esas operaciones aquí.
     *
     * La sobreventa la sigue gobernando stock_control (no este switch): el déficit vive
     * en item_warehouse.stock negativo, fuera de lotes (ADR-0012).
     */
    private function applyLotsGovernStock(Item $item): void
    {
        $warehouse_ids = ItemLotsGroup::where('item_id', $item->id)
            ->whereNotNull('warehouse_id')
            ->distinct()
            ->pluck('warehouse_id');

        foreach ($warehouse_ids as $warehouse_id) {
            if (empty($warehouse_id)) continue;

            $sum_real = (float) ItemLotsGroup::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse_id)
                ->where('code', '!=', ItemLotsGroup::LIBRE_CODE)
                ->sum('quantity');

            // El LIBRE deja de contar: lo vaciamos para que no contamine futuras sumas.
            ItemLotsGroup::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse_id)
                ->where('code', ItemLotsGroup::LIBRE_CODE)
                ->update(['quantity' => 0]);

            $current = (float) (ItemWarehouse::where('item_id', $item->id)
                ->where('warehouse_id', $warehouse_id)
                ->value('stock') ?? 0);
            $delta = $sum_real - $current;

            // Sin cambio real → no movemos stock ni ensuciamos el kardex.
            if (abs($delta) < 0.0001) continue;

            // El listener de Inventory::created (type 1) aplica updateStock(+delta) y crea
            // el kardex. NO duplicar esas operaciones aquí.
            Inventory::create([
                'type'         => 1,
                'description'  => 'Ajuste por lotes',
                'item_id'      => $item->id,
                'warehouse_id' => $warehouse_id,
                'quantity'     => $delta,
            ]);
        }
    }

    /**
     * Devuelve el siguiente internal_id sugerido (considera productos normales y compuestos)
     * sin paginación, evitando la desincronización entre secciones.
     *
     * GET /items/next-internal-id
     */
    public function nextInternalId(): array
    {
        return ['next_internal_id' => Item::getNextInternalId()];
    }

    /**
     * Reasigna internal_id a ítems cuyo código es un barcode colocado por error.
     *
     * Un barcode tiene 8–14 dígitos numéricos (EAN-8, EAN-13, UPC-A, etc.).
     * Un internal_id legítimo tiene como máximo 5 dígitos (00001…99999).
     * Los internal_ids alfanuméricos (LIBRE-SYS, DELIVERY-ECOM, etc.) NUNCA se tocan.
     *
     * Criterio de contaminación: numérico Y longitud >= 8.
     *
     * POST /items/repair-internal-ids
     */
    public function repairInternalIds(): array
    {
        $contaminated = Item::whereNotNull('internal_id')
            ->whereRaw("internal_id REGEXP '^[0-9]+$'")
            ->whereRaw("LENGTH(internal_id) >= 8")
            ->orderBy('id')
            ->get(['id', 'internal_id', 'description']);

        if ($contaminated->isEmpty()) {
            return [
                'success' => true,
                'fixed'   => 0,
                'message' => 'No hay internal_ids contaminados con barcodes. Todo está limpio.',
            ];
        }

        $changes = [];

        foreach ($contaminated as $item) {
            $newId = Item::getNextInternalId();

            $changes[] = [
                'id'       => $item->id,
                'nombre'   => $item->description,
                'anterior' => $item->internal_id,
                'nuevo'    => $newId,
            ];

            $item->internal_id = $newId;
            $item->save();
        }

        return [
            'success' => true,
            'fixed'   => count($changes),
            'cambios' => $changes,
        ];
    }

    /**
     * Asigna un código interno numérico secuencial a los productos cuyo
     * internal_id esté vacío o sea NULL.
     *
     * Por defecto es PREVIEW (solo reporta). Ejecuta los cambios con ?apply=1.
     *
     * POST /items/assign-missing-internal-ids
     */
    public function assignMissingInternalIds(Request $request): array
    {
        $apply = $request->boolean('apply');

        $items = Item::where(function ($query) {
                $query->whereNull('internal_id')
                      ->orWhere('internal_id', '');
            })
            ->orderBy('id')
            ->get(['id', 'description', 'internal_id']);

        if ($items->isEmpty()) {
            return [
                'success' => true,
                'fixed'   => 0,
                'message' => 'No hay productos sin código interno.',
            ];
        }

        $nextInternalId = (int) Item::getNextInternalId();
        $changes = [];

        foreach ($items as $item) {
            $newId = $this->getNextAvailableInternalIdForAssignment($nextInternalId);

            $changes[] = [
                'id'       => $item->id,
                'nombre'   => $item->description,
                'anterior' => $item->internal_id,
                'nuevo'    => $newId,
            ];

            if ($apply) {
                $item->internal_id = $newId;
                $item->save();
            }
        }

        return [
            'success' => true,
            'apply'   => $apply,
            'fixed'   => count($changes),
            'cambios' => $changes,
        ];
    }

    /**
     * Devuelve el siguiente código interno numérico disponible para asignación
     * masiva, saltando colisiones. Actualiza la referencia del contador.
     */
    private function getNextAvailableInternalIdForAssignment(int &$nextInternalId): string
    {
        do {
            $candidate = str_pad((string) $nextInternalId, 5, '0', STR_PAD_LEFT);
            $exists = Item::where('internal_id', $candidate)->exists();
            if ($exists) {
                $nextInternalId++;
            }
        } while ($exists);

        $nextInternalId++;

        return $candidate;
    }

    /**
     * Limpia los códigos de barras existentes: elimina espacios, caracteres de
     * control y normaliza separadores a comas para soportar múltiples barcodes
     * por producto.
     *
     * Por defecto es PREVIEW (solo reporta). Ejecuta los cambios con ?apply=1.
     *
     * POST /items/clean-barcodes
     */
    public function cleanBarcodes(Request $request): array
    {
        $apply = $request->boolean('apply');

        $items = Item::whereNotNull('barcode')
            ->orderBy('id')
            ->get(['id', 'internal_id', 'description', 'barcode']);

        $changes = [];

        foreach ($items as $item) {
            $normalized = BarcodeHelper::normalize($item->barcode);

            if ($normalized === $item->barcode) {
                continue;
            }

            $changes[] = [
                'id'          => $item->id,
                'internal_id' => $item->internal_id,
                'description' => $item->description,
                'anterior'    => $item->barcode,
                'nuevo'       => $normalized,
            ];

            if ($apply) {
                $item->barcode = $normalized;
                $item->save();
            }
        }

        return [
            'success' => true,
            'apply'   => $apply,
            'fixed'   => count($changes),
            'cambios' => $changes,
        ];
    }

    /**
     * Reconcilia la sobre-asignación de lotes heredada del bug ADR-0014 (la Nota de
     * Venta descontaba stock pero no el lote, dejando Σ(lotes reales) > stock).
     *
     * Para cada (item, almacén) con Σ(lotes reales) > max(stock,0), reduce los lotes
     * en orden FEFO (vence primero → se consume primero, ADR-0003) hasta igualar el
     * stock real (fuente autoritativa). Luego re-sincroniza el lote LIBRE.
     *
     * Por defecto es PREVIEW (solo reporta). Ejecuta los cambios con ?apply=1.
     *
     * GET /items/fix-lots
     */
    public function fixLots(Request $request): array
    {
        $apply = $request->boolean('apply');

        $groups = ItemLotsGroup::where('code', '<>', ItemLotsGroup::LIBRE_CODE)
            ->select('item_id', 'warehouse_id')
            ->groupBy('item_id', 'warehouse_id')
            ->get();

        $changes = [];

        foreach ($groups as $g) {
            $stock = (float) (ItemWarehouse::where('item_id', $g->item_id)
                ->where('warehouse_id', $g->warehouse_id)
                ->value('stock') ?? 0);

            $target = max($stock, 0);

            $lots = ItemLotsGroup::where('item_id', $g->item_id)
                ->where('warehouse_id', $g->warehouse_id)
                ->where('code', '<>', ItemLotsGroup::LIBRE_CODE)
                ->orderBy('date_of_due')
                ->orderBy('id')
                ->get();

            $sum_real = (float) $lots->sum('quantity');

            if ($sum_real <= $target) {
                continue; // sin sobre-asignación: no se toca
            }

            $excess = $sum_real - $target;
            $lot_changes = [];

            foreach ($lots as $lot) {
                if ($excess <= 0) {
                    break;
                }
                $q = (float) $lot->quantity;
                if ($q <= 0) {
                    continue;
                }
                $reduce = min($q, $excess);

                $lot_changes[] = [
                    'lote'    => $lot->code,
                    'vence'   => $lot->date_of_due,
                    'antes'   => $q,
                    'despues' => $q - $reduce,
                ];

                if ($apply) {
                    $lot->quantity = $q - $reduce;
                    $lot->save();
                }

                $excess -= $reduce;
            }

            if ($apply) {
                // Mantiene la invariante LIBRE = max(0, stock − Σreales);
                // tras la reducción Σreales == max(stock,0) → LIBRE queda en 0.
                ItemLotsGroup::syncLibreForOrphanStock($g->item_id, $g->warehouse_id);
            }

            $item = Item::find($g->item_id);

            $changes[] = [
                'item_id'          => $g->item_id,
                'producto'         => $item ? $item->description : null,
                'almacen_id'       => $g->warehouse_id,
                'suma_lotes_antes' => $sum_real,
                'stock_real'       => $stock,
                'reducido'         => round($sum_real - $target, 4),
                'lotes'            => $lot_changes,
            ];
        }

        return [
            'success'          => true,
            'modo'             => $apply ? 'APLICADO' : 'PREVIEW (agrega ?apply=1 para ejecutar)',
            'grupos_afectados' => count($changes),
            'detalle'          => $changes,
        ];
    }

    /**
     * Renumera TODOS los internal_ids numéricos de forma secuencial (00001, 00002, …)
     * preservando el orden relativo actual. Los internal_ids no numéricos
     * (ej: "LIBRE-SYS", "ABC-TOCAR") se omiten completamente.
     *
     * Útil para limpiar duplicados y huecos en la numeración tras una importación
     * masiva incorrecta. Seguro de ejecutar múltiples veces (idempotente en orden).
     *
     * POST /items/reorder-internal-ids
     */
    public function reorderInternalIds(): array
    {
        // Captura el orden actual antes de tocar nada.
        $items = Item::whereNotNull('internal_id')
            ->whereRaw("internal_id REGEXP '^[0-9]+$'")
            ->orderByRaw("CAST(internal_id AS UNSIGNED) ASC, id ASC")
            ->get(['id', 'internal_id', 'description']);

        if ($items->isEmpty()) {
            return [
                'success'   => true,
                'reordered' => 0,
                'message'   => 'No hay internal_ids numéricos para reordenar.',
            ];
        }

        $changes = [];

        DB::transaction(function () use ($items, &$changes) {
            // Libera todos los numéricos a NULL primero para evitar colisiones
            // lógicas durante la reasignación secuencial.
            Item::whereNotNull('internal_id')
                ->whereRaw("internal_id REGEXP '^[0-9]+$'")
                ->update(['internal_id' => null]);

            $counter = 1;

            foreach ($items as $item) {
                $newId = str_pad($counter, 5, '0', STR_PAD_LEFT);

                $changes[] = [
                    'id'       => $item->id,
                    'nombre'   => $item->description,
                    'anterior' => $item->internal_id,
                    'nuevo'    => $newId,
                ];

                Item::where('id', $item->id)->update(['internal_id' => $newId]);
                $counter++;
            }
        });

        return [
            'success'   => true,
            'reordered' => count($changes),
            'cambios'   => $changes,
        ];
    }

    public function visibleMassive(Request $request)
    {
        $type_product = $request->input('resource');
        $column = $type_product === 'restaurant' ? 'apply_restaurant' : 'apply_store';

        try {
            $items = Item::whereNotNull('internal_id')
                ->where($column, 0);

            if ($type_product === 'restaurant') {
                $items->where(function ($q) {
                    $q->where('unit_type_id', '!=', 'ZZ')
                    ->orWhereExists(function ($sub) {
                        $sub->select(DB::raw(1))
                            ->from('restaurant_item_supplies')
                            ->whereColumn(
                                'restaurant_item_supplies.item_id',
                                'items.id'
                            );
                    });
                });
            }

            $items->update([
                $column => true
            ]);

            return [
                'success' => true,
                'message' => 'Todo los productos son visible en el restaurante'
            ];
        } catch (\Throwable $th) {
            return [
                'success' => false,
                'message' => $th->getMessage()
            ];
        }

    }
    /**
     *
     * Generar codigo interno de forma automatica
     *
     * @param  Item $item
     * @return void
     */
    public function generateInternalId(Item &$item)
    {
        $inventory_configuration = InventoryConfiguration::select('generate_internal_id')->firstOrFail();

        if($inventory_configuration->generate_internal_id && !$item->internal_id)
        {
            $item->internal_id = str_pad($item->id, 5, '0', STR_PAD_LEFT);
            $item->save();
        }
    }



    /**
     * @param ItemRequest|null $request
     * @param null $item
     * @throws Exception
     */
    private function createItemWarehousePrices(ItemRequest $request = null, Item $item = null)
    {
        if ($request !== null && $request->has('item_warehouse_prices') && $item !== null) {
            foreach ($request->item_warehouse_prices as $item_warehouse_price) {
                if ($item_warehouse_price['price'] && $item_warehouse_price['price'] != '') {
                    ItemWarehousePrice::updateOrCreate([
                        'item_id' => $item->id,
                        'warehouse_id' => $item_warehouse_price['warehouse_id'],
                    ], [
                        'price' => $item_warehouse_price['price'],
                    ]);
                } else {
                    if ($item_warehouse_price['id']) {
                        ItemWarehousePrice::findOrFail($item_warehouse_price['id'])->delete();
                    }
                }
            }
        }
    }


    /**
     * Eliminar item
     *
     * Usado en:
     * Modules\MobileApp\Http\Controllers\Api\ItemController
     *
     * @param  int $id
     * @return array
     *
     */
    public function destroy($id)
    {
        try {

            $item = Item::findOrFail($id);
            // Evita violaciones de FK en items cuando quedan lotes de cabecera huérfanos.
            ItemLotsGroup::where('item_id', $item->id)->delete();
            $this->deleteRecordInitialKardex($item);
            $this->deleteRecordInitialWeightedCosts($item);
            $item->delete();

            // Invalidar caché del item individual cuando se elimina
            CacheHelper::forget(['item_detail'], "item_detail_{$id}");
            // Invalidar caché de listas cuando se elimina un item
            CacheHelper::flush(['items_list']);

            return [
                'success' => true,
                'message' => 'Producto eliminado con éxito'
            ];

        } catch (Exception $e) {

            return ($e->getCode() == '23000') ? ['success' => false,'message' => 'El producto esta siendo usado por otros registros, no puede eliminar'] : ['success' => false,'message' => 'Error inesperado, no se pudo eliminar el producto'];

        }


    }

    public function destroyMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemDeleted = 0;
        $count = $selected->count();


        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemDeleted){
            $response = $this->destroy($id);
            if ($response['success']) $itemDeleted += 1;
        });

        return [
            'success' => true,
            'message' => "Se eliminaron {$itemDeleted} productos de {$count} productos seleccionados"
        ];

    }



    public function destroyItemUnitType($id)
    {
        $item_unit_type = ItemUnitType::findOrFail($id);
        $item_unit_type->delete();

        return [
            'success' => true,
            'message' => 'Registro eliminado con éxito'
        ];
    }


    public function import(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|numeric|min:1'
        ]);
        if ($request->hasFile('file')) {
            try {
                $import = new ItemsImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    /**
     * Importa masivamente presentaciones (item_unit_types) desde Excel.
     * Los precios se persisten en item_unit_type_prices (ver ItemPresentationsImport).
     *
     * @param Request $request
     * @return array
     */
    public function importPresentations(Request $request)
    {
        if ($request->hasFile('file')) {
            try {
                (new ItemPresentationsImport())->import($request->file('file'), null, Excel::XLSX);
                return [
                    'success' => true,
                    'message' => 'Presentaciones importadas correctamente',
                ];
            } catch (Exception $e) {
                Log::error('Error al importar presentaciones: '.$e->getMessage());
                return [
                    'success' => false,
                    'message' => 'Error al importar: '.$e->getMessage(),
                ];
            }
        }
        return [
            'success' => false,
            'message' => 'No se ha subido ningún archivo',
        ];
    }

    /**
     * Exporta las presentaciones (item_unit_types) a Excel para migración masiva.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportPresentations(Request $request)
    {
        return (new ItemPresentationsExport())->download('Migracion_Presentaciones_'.date('YmdHis').'.xlsx');
    }

    public function importRestaurant(Request $request)
    {
        $request->validate([
            'warehouse_id' => 'required|numeric|min:1'
        ]);
        if ($request->hasFile('file')) {
            try {
                $import = new ItemsImportRestaurant();
                $import->import($request->file('file'), null, Excel::XLSX);
                $data = $import->getData();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => $data
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function catalog(Request $request)
    {
        $request->validate([
            'catalog_id' => 'required|numeric|min:1'
        ]);
        if ($request->hasFile('file')) {
            try {
                $old_digemid = CatDigemid::setInactiveMassive();
                $import = new CatalogImport();
                $import->import($request->file('file'), null, Excel::XLSX);
                $updated  = $import->getUpdated();
                return [
                    'success' => true,
                    'message' =>  __('app.actions.upload.success'),
                    'data' => count($updated),
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' =>  $e->getMessage()
                ];
            }
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    public function upload(Request $request)
    {

        $validate_upload = UploadFileHelper::validateUploadFile($request, 'file', 'jpg,jpeg,png,gif,svg,webp');

        if(!$validate_upload['success']){
            return $validate_upload;
        }

        if ($request->hasFile('file')) {
            $new_request = [
                'file' => $request->file('file'),
                'type' => $request->input('type'),
            ];

            return $this->upload_image($new_request);
        }
        return [
            'success' => false,
            'message' =>  __('app.actions.upload.error'),
        ];
    }

    function upload_image($request)
    {
        $file = $request['file'];
        $type = $request['type'];

        $temp = tempnam(sys_get_temp_dir(), $type);
        file_put_contents($temp, file_get_contents($file));

        $mime = mime_content_type($temp);
        $data = file_get_contents($temp);

        return [
            'success' => true,
            'data' => [
                'filename' => $file->getClientOriginalName(),
                'temp_path' => $temp,
                'temp_image' => 'data:' . $mime . ';base64,' . base64_encode($data)
            ]
        ];
    }

    private function deleteRecordInitialKardex($item){

        if($item->kardex->count() == 1){
            ($item->kardex[0]->type == null) ? $item->kardex[0]->delete() : false;
        }

    }


    /**
     *
     * @param  Item $item
     * @return void
     */
    private function deleteRecordInitialWeightedCosts($item)
    {
        if($item->weighted_average_costs()->count() == 1)
        {
            $item->weighted_average_cost()->delete();
        }
    }


    public function visibleStore(Request $request)
    {
        $item = Item::find($request->id);

        if(!$item->internal_id && $request->apply_store){
            return [
                'success' => false,
                'message' =>'Para habilitar la visibilidad, debe asignar un codigo interno al producto',
            ];
        }

        $visible = $request->apply_store == true ? 1 : 0 ;
        $item->apply_store = $visible;
        $item->save();

        return [
            'success' => true,
            'message' => ($visible > 0 )?'El Producto ya es visible en tienda virtual' : 'El Producto ya no es visible en tienda virtual',
            'id' => $request->id
        ];

    }

    public function duplicate(Request $request)
    {
        // return $request->id;
        $obj = Item::find($request->id);

        if($obj->lots_enabled){
            $obj->date_of_due = null;
            $obj->lot_code = null;
            $obj->stock = 0;
        }

        $new = $obj->setDescription($obj->getDescription().' (Duplicado)')->replicate();
        $new->save();

        return [
            'success' => true,
            'data' => [
                'id' => $new->id,
            ],
        ];

    }

    public function disable($id)
    {
        try {

            $item = Item::findOrFail($id);
            $item->active = 0;
            $item->save();

            return [
                'success' => true,
                'message' => 'Producto inhabilitado con éxito'
            ];

        } catch (Exception $e) {

            return  ['success' => false, 'message' => 'Error inesperado, no se pudo inhabilitar el producto'];

        }
    }

    public function disableMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemDisabled = 0;
        $count = $selected->count();


        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemDisabled){
            $response = $this->disable($id);
            if ($response['success']) $itemDisabled += 1;
        });

        return [
            'success' => true,
            'message' => "Se inhabilitaron {$itemDisabled} productos de {$count} productos seleccionados"
        ];

    }

    public function hiddenSearchMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemHidden = 0;
        $count = $selected->count();

        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemHidden){
            $response = $this->hiddenSearch($id);
            if ($response['success']) $itemHidden += 1;
        });

        return [
            'success' => true,
            'message' => "Se ocultaron de las búsquedas {$itemHidden} productos de {$count} productos seleccionados"
        ];

    }

    public function showSearchMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemShown = 0;
        $count = $selected->count();

        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemShown){
            $response = $this->showSearch($id);
            if ($response['success']) $itemShown += 1;
        });

        return [
            'success' => true,
            'message' => "Se mostraron en las búsquedas {$itemShown} productos de {$count} productos seleccionados"
        ];

    }

    public function images($item)
    {
        $records = ItemImage::where('item_id', $item)->get()->transform(function($row){
            return [
                'id' => $row->id,
                'item_id' => $row->item_id,
                'image' => $row->image,
                'name' => $row->image,
                'url'=> asset('storage'.DIRECTORY_SEPARATOR.'uploads'.DIRECTORY_SEPARATOR.'items'.DIRECTORY_SEPARATOR.$row->image)
            ];
        });
        return [
            'success' => true,
            'data' => $records
        ];
    }

    public function delete_images($id)
    {
        $record = ItemImage::findOrFail($id);
        $record->delete();

        return [
            'success' => true,
            'message' => 'Imagen eliminada con éxito'
        ];
    }


    public function enable($id)
    {
        try {

            $item = Item::findOrFail($id);
            $item->active = 1;
            $item->save();

            return [
                'success' => true,
                'message' => 'Producto habilitado con éxito'
            ];

        } catch (Exception $e) {

            return  ['success' => false, 'message' => 'Error inesperado, no se pudo habilitar el producto'];

        }
    }

    public function hiddenSearch($id)
    {
        try {

            $item = Item::findOrFail($id);
            $item->hidden_search = 1;
            $item->save();

            return [
                'success' => true,
                'message' => 'Producto oculto de las búsquedas con éxito'
            ];

        } catch (Exception $e) {

            return  ['success' => false, 'message' => 'Error inesperado, no se pudo ocultar el producto'];

        }
    }

    public function showSearch($id)
    {
        try {

            $item = Item::findOrFail($id);
            $item->hidden_search = 0;
            $item->save();

            return [
                'success' => true,
                'message' => 'Producto visible en las búsquedas con éxito'
            ];

        } catch (Exception $e) {

            return  ['success' => false, 'message' => 'Error inesperado, no se pudo mostrar el producto'];

        }
    }

    public function enableMassive(Request $request)
    {
        $selected = collect($request->selected);
        $itemEnable = 0;
        $count = $selected->count();

        if ($count == 0 ) {
            return [
                'success'  => false,
                'message' => 'Tiene que seleccionar los items'
            ];
        }

        $selected->each(function($id) use (&$itemEnable){
            $response = $this->enable($id);
            if ($response['success']) $itemEnable += 1;
        });

        return [
            'success' => true,
            'message' => "Se habilitaron {$itemEnable} productos de {$count} productos seleccionados"
        ];

    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function export(Request $request)
    {
        $d_start = null;
        $d_end = null;
        $period = $request->period;

        switch ($period) {
            case 'month':
                $d_start = Carbon::parse($request->month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_start.'-01')->endOfMonth()->format('Y-m-d');
                break;
            case 'between_months':
                $d_start = Carbon::parse($request->month_start.'-01')->format('Y-m-d');
                $d_end = Carbon::parse($request->month_end.'-01')->endOfMonth()->format('Y-m-d');
                break;
        }

        // $date = $request->month_start.'-01';
        // $start_date = Carbon::parse($date);
        // $end_date = Carbon::parse($date)->addMonth()->subDay();

        $items = Item::whereTypeUser()->whereNotIsSet();
        $extradata = [];
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $extradata[]='sanitary';
            $extradata[]='cod_digemid';
            $items->Pharmacy();
        }

        if($period !== 'all'){
            $items->whereBetween('items.created_at', [$d_start, $d_end]);
        }

        $records = $items->with('item_unit_types.prices')->get();
        $price_labels = PriceLabel::active()->ordered()->get();
        
        return (new ItemExport())
            ->setExtraData($extradata)
            ->records($records)
            ->priceLabels($price_labels)
            ->download('Reporte_Items_'.Carbon::now().'.xlsx');

    }

    /**
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportWp(Request $request) {
        $records = Item::query();
        $extradata = [];
        $isPharmacy = $request->isPharmacy == 'true' ? true : false;
        if ($request->has('isPharmacy') && $isPharmacy == true) {
            $extradata[] = 'sanitary';
            $extradata[] = 'cod_digemid';
            $records->Pharmacy();
        }
        $records = $records->get();
        return (new ItemExportWp())
            ->setExtraData($extradata)
            ->records($records)
            ->download('Reporte_Items_Woocommerce_'.Carbon::now().'.csv', Excel::CSV);

    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Response
     */
    public function downloadExtraDataPdf(Request $request){
        $field ='';
        $records = $this->exportExtraItem($request,$field);


        $pdf = PDF::loadView('tenant.items.exports.items_extra_data',
            compact("records", "field"))
            ->setPaper('a4', 'landscape');

        $filename = 'Reporte_Items_Extra_Data_'.Carbon::now().'.xlsx';

        return $pdf->download($filename.'.pdf');
    }

    /**
     * @param Request $request
     *
     * @return \Illuminate\Http\Response|mixed|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function downloadExtraDataItemsExcel(Request $request){
        $field ='';
        $items = $this->exportExtraItem($request,$field);
        $excel = new ItemExtraDataExport();
        $excel->setRecords($items)->setField($field);
        $filename = 'Reporte_Items_Extra_Data_'.Carbon::now().'.xlsx';

        return $excel->download($filename);
        return $excel->view();

    }

    /**
     * Obtiene lo smovimientos de inventario para la categoria correspondiente,
     * se implementa en pdf y excel por igual
     *
     * @param Request $request
     * @param         $field
     *
     * @return Item[]|\Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Query\Builder[]|\Illuminate\Support\Collection
     */
    public function exportExtraItem(Request $request, &$field){

        $stockByAttribute = ItemMovement::getQueryToStockWithOutItemId(auth()->user()->establishment_id)->distinct();
        $field = $request->fields ?? '';
        if($field == 'colors'){
            $stockByAttribute->where('item_movement_rel_extra.item_color_id','!=',0);
        }elseif($field == 'CatItemMoldProperty'){
            $stockByAttribute->where('item_movement_rel_extra.item_mold_properties_id','!=',0);
        }elseif($field == 'CatItemUnitBusiness'){
            $stockByAttribute->where('item_movement_rel_extra.item_unit_business_id','!=',0);
        }elseif($field == 'CatItemStatus'){
            $stockByAttribute->where('item_movement_rel_extra.item_status_id','!=',0);
        }
        elseif($field == 'CatItemPackageMeasurement'){
            $stockByAttribute->where('item_movement_rel_extra.item_package_measurements_id','!=',0);
        }
        elseif($field == 'CatItemProductFamily'){
            $stockByAttribute->where('item_movement_rel_extra.item_product_family_id','!=',0);
        }
        elseif($field == 'CatItemSize'){
            $stockByAttribute->where('item_movement_rel_extra.item_size_id','!=',0);
        }
        elseif($field == 'CatItemUnitsPerPackage'){
            $stockByAttribute->where('item_movement_rel_extra.item_units_per_package_id','!=',0);
        }
        elseif($field == 'CatItemMoldCavity'){
            $stockByAttribute->where('item_movement_rel_extra.item_mold_cavities_id','!=',0);
        }
        $itemsIds =$stockByAttribute->get()->pluck('item_id')->unique();
        $items = Item::wherein('id',$itemsIds)->get()->transform(function (Item $row){
           return $row->getCollectionData();
        });
        return $items;

    }
    public function exportBarCode(Request $request){

        ini_set("pcre.backtrack_limit", "50000000");

        $start = $request[0];
        $end = $request[1];

        $records = Item::whereBetween('id', [$start, $end]);
        $extradata = [];
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $extradata[]='sanitary';
            $extradata[]='cod_digemid';
            $records->Pharmacy();
        }
        $extra_data = $extradata;
        $records = $records->get();
        $pdf = new Mpdf([
            'mode' => 'utf-8',
            'format' => [
                104.1,
                101.6
            ],
            'margin_top' => 2,
            'margin_right' => 2,
            'margin_bottom' => 0,
            'margin_left' => 2
        ]);
        $html = view('tenant.items.exports.items-barcode', compact('records','extra_data'))->render();

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $pdf->output('etiquetas_'.now()->format('Y_m_d').'.pdf', 'I');
    }

    /**
     * Genera los codigos de barra por archivo para los items que tengan internal_id o barcode
     * Se prioriza barcode, sino se genera internal_id
     *
     * @param Request $request
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse
     * @throws \Mpdf\MpdfException
     * @throws \Throwable
     */
    public function exportBarCodeFull(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");

        $start = $request[0];
        $end = $request[1];

        $records = Item::whereBetween('id', [$start, $end])
            ->where(function($q){
                $q->orwhere('barcode','!=','');
                $q->orwhere('internal_id','!=','');
            })
            // ->wherenotnull('barcode')
        ;
        $extradata = [];
        $establishment = \Auth::user()->establishment;
        $isPharmacy = false;
        if($request->has('isPharmacy') ){
            $isPharmacy = ($request->isPharmacy==='true')?true:false;
        }
        if($isPharmacy == true){
            $extradata[]='sanitary';
            $extradata[]='cod_digemid';
            $records->Pharmacy();
        }
        $extra_data = $extradata;
        $records = $records->get();
        $height = 30;

        $width = 48;
        $pdfj = new Fpdi();
        /** @var Item $item */
        foreach($records as $item){
            $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    $height
                ],
                'margin_top' => 2,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ]);
            $html = view('tenant.items.exports.items-barcode-full', compact('item','extra_data','establishment'))->render();
            $pdf->AddPage();
            $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);
            PdfUnionController::addFpi($pdfj, $pdf);
        }

        return PdfUnionController::ResponseAsFile($pdfj,'bar_code_full');

    }
    /**
     * Exporta items al formato de DIGEMID
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\Response|\Symfony\Component\HttpFoundation\BinaryFileResponse
     */
    public function exportDigemid(Request $request)
    {
        ini_set('max_execution_time', 0);
        $company = Company::first();
        $company_cod_digemid = $company->cod_digemid;
        $records = CatDigemid::where('active',1);
        $max_prices = $records->max('max_prices');
            $records = $records->get();
        $export = new DigemidItemExport();
        $export->setRecords($records)->setCompanyCodDigemid($company_cod_digemid)->setMaxPrice($max_prices);

        return $export->download('Reporte_Items_Digemid_'.Carbon::now().'.xlsx');
    }

    public function printBarCode(Request $request)
    {
        $controler = app(EditorTagController::class);
        $id = $request->id;


        $template = TagTemplate::with('fields')->where('is_default', true)->first();

        if (!$template) {
            return [
                'success' => false,
                'message' => 'No se ha configurado una plantilla por defecto para imprimir etiquetas. Por favor, configure una plantilla e intente nuevamente.'
            ];
        }

        $request->merge([
            'type' => 'individual',
            'items' => [$id],
            'quantity_per_item' => 1,
            'template_id' => $template->id,
        ]);

        return $controler->export($request);

    }

    public function printBarCodeX(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");
        $id = $request->input('id');
        $format = $request->input('format');

        $record = Item::find($id);
        $item_warehouse = ItemWarehouse::where([['item_id', $id], ['warehouse_id', auth()->user()
            ->establishment->warehouse->id]])->first();

        if(!$item_warehouse){
            return [
                'success' => false,
                'message' => "El producto seleccionado no esta disponible en su almacen!"
            ];
        }

        if($item_warehouse->stock < 1){
            return [
                'success' => false,
                'message' => "El producto seleccionado no tiene stock disponible en su almacen, no puede generar etiquetas!"
            ];
        }

        $stock = $item_warehouse->stock;

        $width = ($format == 1) ? 84 : 104.1;
        $height = ($format == 1) ? 30 : 28;

        $pdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => [
                    $width,
                    $height
                    ],
                'margin_top' => 2,
                'margin_right' => 2,
                'margin_bottom' => 0,
                'margin_left' => 2
            ]);
        $html = view('tenant.items.exports.items-barcode-x', compact('record', 'stock', 'format'))->render();

        // return $html;

        $pdf->WriteHTML($html, HTMLParserMode::HTML_BODY);

        $pdf->output('etiquetas_1x'.$format.'_'.now()->format('Y_m_d').'.pdf', 'I');

    }

    public function itemLast()
    {
        $record = Item::latest()->first();
        // Guard: tenant nuevo sin productos → $record es null; evitamos el 500.
        return json_encode(['data' => $record ? $record->id : null]);
    }

    public function tablesImport()
    {
        $user = auth()->user();
        $warehouses = Warehouse::select('id', 'description');
        if ($user->type !== 'admin') {
            $warehouses = $warehouses->where('id', $user->establishment_id);
        }

        return response()->json([
            'warehouses' => $warehouses->get(),
        ], 200);
    }

    /**
     * Obtiene una lista de items del sistema
     *
     * @param \Illuminate\Http\Request $r
     *
     * @return \App\Http\Resources\Tenant\ItemCollection
     */
    public function getAllItems(Request $r){
        $records = $this->getRecords($r);
        return new ItemCollection($records->paginate(5000));

    }


    public function searchItemById($id)
    {
        // $items = SearchItemController::searchByIdToModal($id);
        $items = SearchItemController::getItemsToSupply(null, $id);
        return compact('items');
    }


    public function searchItems(Request $request)
    {

        $items = SearchItemController::getItemsToSupply($request);

        return compact('items');

    }

    public function item_tables()
    {
        // $items = $this->table('items');
        $items = SearchItemController::getItemsToDocuments();
        $categories = [];
        $affectation_igv_types = AffectationIgvType::whereActive()->get();
        $system_isc_types = SystemIscType::whereActive()->get();
        $price_types = PriceType::whereActive()->get();
        $operation_types = OperationType::whereActive()->get();
        $discount_types = ChargeDiscountType::whereType('discount')->whereLevel('item')->get();
        $charge_types = ChargeDiscountType::whereType('charge')->whereLevel('item')->get();
        $attribute_types = AttributeType::whereActive()->orderByDescription()->get();
        $is_client = $this->getIsClient();

        $configuration= Configuration::first();

        /** Informacion adicional */
        $colors = collect([]);
        $CatItemSize=$colors;
        $CatItemStatus=$colors;
        $CatItemUnitBusiness = $colors;
        $CatItemMoldCavity = $colors;
        $CatItemPackageMeasurement =$colors;
        $CatItemUnitsPerPackage = $colors;
        $CatItemMoldProperty = $colors;
        $CatItemProductFamily= $colors;
        if($configuration->isShowExtraInfoToItem()){

            $colors = CatColorsItem::all();
            $CatItemSize= CatItemSize::all();
            $CatItemStatus= CatItemStatus::all();
            $CatItemUnitBusiness = CatItemUnitBusiness::all();
            $CatItemMoldCavity = CatItemMoldCavity::all();
            $CatItemPackageMeasurement = CatItemPackageMeasurement::all();
            $CatItemUnitsPerPackage = CatItemUnitsPerPackage::all();
            $CatItemMoldProperty = CatItemMoldProperty::all();
            $CatItemProductFamily= CatItemProductFamily::all();
        }
        $price_labels = PriceLabel::select('position','label')->active()->get();


        /** Informacion adicional */

        return compact(
            'items',
            'categories',
            'affectation_igv_types',
            'system_isc_types',
            'price_types',
            'operation_types',
            'discount_types',
            'charge_types',
            'attribute_types',
            'is_client',
            'colors',
            'CatItemSize',
            'CatItemMoldCavity',
            'CatItemMoldProperty',
            'CatItemUnitBusiness',
            'CatItemStatus',
            'CatItemPackageMeasurement',
            'CatItemProductFamily',
            'price_labels',
            'CatItemUnitsPerPackage');
    }

    public function exportTxtBartender(Request $request)
    {
        ini_set("pcre.backtrack_limit", "50000000");

        $items = $request->items;
        $columns = $request->columns;

        $columnSelected = $this->getColumnsToBartender($columns);
        $columnsKey = array_keys($columnSelected);

        $itemCollect = collect($items)->map(function($item){

            if(sizeof($item['size']) > 0){
                $sizes = CatItemSize::whereIn('id',$item['size'])->get();
                $item['size'] = $sizes->pluck('name')->implode('-');
            }else{
                $item['size'] = " ";
            }

            if(sizeof($item['color']) > 0){
                $sizes = CatColorsItem::whereIn('id',$item['color'])->get();
                $item['color'] = $sizes->pluck('name')->implode('-');
            }else{
                $item['color'] = " ";
            }

            if(sizeof($item['status']) > 0){
                $sizes = CatItemStatus::whereIn('id',$item['status'])->get();
                $item['status'] = $sizes->pluck('name')->implode('-');
            }else{
                $item['status'] = " ";
            }

            $price_formated = $item['sale_unit_price'];
            $price_formated = $item['currency_type_symbol'].number_format($item['sale_unit_price'], 2);
            $item['sale_unit_price'] = $price_formated;

            return $item;
        });

        $dataItems = $itemCollect->flatMap(function ($item) use ($columnsKey)  {
            return array_map(function () use ($item,$columnsKey) {
                $item = array_intersect_key($item, array_flip($columnsKey));
                $orderedItem = array_replace(array_flip($columnsKey), $item);
                return $orderedItem ;
            }, range(1, $item['quantity_printer']));
        });

        $nombre_archivo = "TxtBartender".Carbon::now();

        $response = new StreamedResponse(function () use ($dataItems,$columnSelected) {
            $handle = fopen('php://output', 'w');

            $headers = array_values($columnSelected);

            fwrite($handle, implode(',', $headers) . "\n");

            foreach ($dataItems as $item) {
                $data = array_values($item);
                fwrite($handle, implode(',', $data) . "\n");
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/plain');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$nombre_archivo.'"');

        return $response;

    }

    private function getColumnsToBartender($columns){

        $optionalColumns = [
            'internal_id' => 'Código Interno',
            'description' => 'Nombre',
            'barcode' => 'Código de barras',
            'category' => 'Categoría',
            'unit_type_id' => 'Unidad',
            'brand' => 'Marca',
            'sale_unit_price' => 'Precio',
            'size' => 'Talla',
            'color' => 'Colores',
            'status' => 'Status'
        ];

        $selected = array_intersect_key($optionalColumns, array_flip($columns));

        return $selected;
    }



}
