<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Session;
use Image;
use Route;
use Auth;
use Config;
use Storage;
use URL;

use App\ProductImageType;
use App\ProductImage;

use App\Repositories\ProductRepository;
use App\Repositories\MenusRepository;
use App\Repositories\OrdersRepository;
use App\Repositories\VendorsRepository;
use App\Repositories\UserParameterListRepository;
use App\Repositories\UserParametersRepository;
use App\Repositories\ProductImagesRepository;
use App\Repositories\CategoriesRepository;
use App\Repositories\SettingsRepository;
use App\Repositories\ArticlesRepository;


class SiteController extends Controller
{
    //Repositories
    public $p_rep;//ProductRepository
    public $m_rep;//MenusRepository
    public $o_rep;//OrdersRepository
    public $v_rep;//VendorsRepository
    public $up_rep;//UserParametersRepository
    public $upl_rep;//UserParameterListRepository
    public $img_rep;//ProductImagesRepository
    public $cat_rep;//CategoriesRepository
    public $set_rep;//SettingsRepository
    public $a_rep;//ArticlesRepository

    //End Repositories

    protected $left_bar;//
    protected $user;//User
    protected $used_denied_id;
    protected $default_upl;//UserPropertiesListDefault
    protected $title;//Html Title
    protected $template;//Active template
    protected $vars = array();
    protected $menus;//All menus denied for user as Menu model
    protected $content_header;
    protected $content;
    protected $cart;//Shopping Cart
    protected $current_vendor;//
    protected $img_types;//Array product_image_types table
    protected $settings;//All settings from DB
    protected $category_list;//Коллекция корневых категорий сайта сгруппирована по родителю

    public $vendor = 2;
    public $top_cart = TRUE;//Флаг показа верхней корзины
    public $order = FALSE;//Текущий заказ

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
                        ProductRepository $products, 
                        MenusRepository $menus, 
                        OrdersRepository $orders,
                        VendorsRepository $vendors,
                        UserParameterListRepository $upl,
                        UserParametersRepository $u_par,
                        ProductImagesRepository $p_img,
                        CategoriesRepository $cat,
                        SettingsRepository $set,
                        ArticlesRepository $articles
                    ) {
           //
        $this->p_rep = $products;
        $this->m_rep = $menus;
        $this->o_rep = $orders;
        $this->v_rep = $vendors;
        $this->upl_rep = $upl;
        $this->up_rep = $u_par;
        $this->img_rep = $p_img;
        $this->cat_rep = $cat;
        $this->set_rep = $set;
        $this->a_rep = $articles;


       
    }

    //Подгрузка основных данных
    public function indexSettings() {

        $this->vendor = Config::get('settings.default_vendor_id');

        //Getting User
        $this->default_upl = $this->getDefaultUserParameters();
        $this->user = $this->getUser();
        //dd($this->user);
        //Getting Groups
        if(is_null($this->user)) {
            $this->user_denied_id = Config::get('settings.default_user_id');
        }
        else {
            $this->user_denied_id = $this->user->groups_id;
        }
        
        //$this->default_upl = $this->getDefaultUserParameters();
        //dd($this->default_upl);

        //Getting Title
        $this->addTitle('Мобильный форсаж - ', '');

        $this->settings = $this->getSettingsToArr();
        $this->category_list = $this->getCategoryList();
        //Getting all menus
        $this->menus = $this->m_rep->get('*', [['user_denied_id','>=',$this->user_denied_id], [ 'active','=','1']], FALSE, ['name'=>'sorting', 'order'=>'asc']);
        $this->content_header = $this->getHeaderTitle($this->menus);
        $this->menus = $this->menus->groupBy('type_id');
        //dd($this->menus);
        $this->cart = Session::get('cart');
        $this->current_vendor = $this->getVendor($this->vendor);
        $this->img_types = $this->getImgTypes();
        $this->left_bar = '';
        
    }//indexSettings

    //Подгрузка основных данных шаблона, одинакового для всех страниц
    public function indexLayout() {

        //$this->template = env('THEME').'.retail';

        $topNav = view(env('THEME').'.topNav')->with(['menus'=>$this->menus, 'user'=>$this->user_denied_id, 'cart'=>$this->cart, 'top_cart'=>$this->top_cart])->render();
        $this->vars = Arr::add($this->vars, 'topNav', $topNav);

        //Lef menu
        //$leftBar = view(env('THEME').'.leftBar')->with(['user' => $this->user_rep, 'menus' => $this->left_menu_rep, 'menuItem' => $menu])->render();
        
        $this->vars = Arr::add($this->vars, 'leftBar', $this->left_bar);

        //HeaderLinks
        //$headerLinks = view(env('THEME').'.content.headerLinks')->with('array', $this->content_header)->render();
        
        //ContentHeade
        //$contentHeader = view(env('THEME').'.content.contentHeader')->with(['name' => $this->getLeftMenuName(), 'title' => $headerLinks])->render();
        $contentHeader = '';
        $this->vars = Arr::add($this->vars, 'contentHeader', $contentHeader);

        //Main Content
        $mainContent = view(env('THEME').'.mainContent')->with(['header'=>$this->content_header, 'content'=>$this->content, 'cart'=>($this->cart ? TRUE : FALSE)])->render();
        $this->vars = Arr::add($this->vars, 'mainContent', $mainContent);

        //Footer
        $footer = view(env('THEME').'.footer')->render();
        $this->vars = Arr::add($this->vars, 'footer', $footer);

        //JavaScript DownSite
        $jsDown = view(env('THEME').'.jsDown')->render();
        $this->vars = Arr::add($this->vars, 'jsDown', $jsDown);

    }//indexLayout

    //Output renderable
    public function renderOutput() {
		
		//Header
        $header = view(env('THEME').'.header')->with('title', $this->title)->render();
        $this->vars = Arr::add($this->vars, 'header', $header);

			
		return view($this->template)->with($this->vars);
    }//renderOutput
    
    public function addTitle($pre, $title) {
        $this->title = $this->title.$pre.$title;
    }

    public function getHeaderTitle($menus) {
        foreach ($menus as $item) {
            if($item->route) {
                
                if(URL::current() ==  route($item->route)) {

                    return $item->header_title;
                }
            }
        }
        return '';
    }
    //
    //Get Models
    //

    //User
    protected function getUser() {
        $user = Auth::user();
        //dd($user);
        if($user) {
            $user->load('parameters');
        }
      
       // $user = $this->user_rep->get();
        return $user;
    }//getUser

    public function getVendor($id = FALSE, $property = FALSE) {
        if($id) {
            $vendor = $this->v_rep->get('*', ['id'=>$id])->first();
            $vendor->load('property');
            $vendor = $this->v_rep->addProperty($vendor);
            //dd($vendor);
            return $vendor;
        }
        else {
            return $this->v_rep->get('*');
        }
        
    }//getVendor

    //
    //-----Не Используется Начало
    //
    //LeftMenu
    protected function getLeftMenus() {
        $left_menus = $this->left_menu_rep->get('*', FALSE, ['name'=>'sorting','order'=>'asc']);
        return $left_menus;
    }

    //LeftMenuName
    protected function getLeftMenuName() {
        foreach($this->left_menu_rep as $item) {
            if (Route::currentRouteName() ==  $item->path) {
                return $item->name;
            }
        }
        return FALSE;
    }

    //Getting array with name and links to Root
    protected function getContentHeader1($array) {
        if(0 < count($array) && count($array) < 5) {
            foreach($array as $item) {
                if($item) {
                    $content_header[] = array('title' => $item->name, 'id' => $item->id);
                }
                else {
                    $content_header[] = NULL;
                }
                
            }
            return $content_header;
        }
        else if (count($array) > 4) {
            $content_header[] = NULL;
            $content_header[] = array('title' => $array[count($array)-3]->name, 'id' => $array[count($array)-3]->id);
            $content_header[] = array('title' => $array[count($array)-2]->name, 'id' => $array[count($array)-2]->id);
            $content_header[] = array('title' => $array[count($array)-1]->name, 'id' => $array[count($array)-1]->id);
            return $content_header;
        }
        else return FALSE;
    }

    
    public function getProductsElements($collection) {
        $array = Array();
        //dd($collection);
        foreach($collection as $item) {
            $array = Array();
            if($item->price) {
                foreach($item->price as $price) {
                    if($price->type_id == Config::get('vendors.Vendor-'.$this->vendor.'.type_of_price')) {
                        $item->r_price = $price->value;
                        $item->currency = 'грн';
                      //  $array = Arr::add($array, 'price', $price->value);
                       // $array = Arr::add($array, 'currency', 'грн');
                    }
                   
                }
                //$item->price = $array;
                //dd($item->price);
            }
            //dd($item);
            $array = Array();
            if($item->property) {
                foreach($item->property as $prop) {
                    $item->{$prop->property_alias} = $prop->value;
                }
                //$item->property = $array;
            }
            $item = $this->getAllImg($item);
            
        }
        //dd($collection);
        return $collection;
    }//getProductsElements

  //
  //---Конец Не используется

    public function getProductItem($id, $element = FALSE) {
        $product = $this->p_rep->get('*', ['active'=>'1', 'id'=>$id] , FALSE);
        if($product) {
            $product->load('price', 'property')->load(['image' => function($query){
                $query->orderBy('sorting', 'desc');
            }]);
            //$products->load('product');
            // $books->load(['author' => function ($query) {
            //     $query->orderBy('published_date', 'asc');
            //   }]);
        }
        $result = $product->first();
        if($element) {
            $result = $this->getProductElement($result);
        }
        return $result;
    }
    
    public function getProductElement($item) {
        
        $array = Array();
            if($item->price) {
                foreach($item->price as $price) {
                    if($price->type_id == Config::get('vendors.Vendor-'.$this->vendor.'.type_of_price')) {
                        $item->r_price = $price->value;
                        $item->currency = 'грн';
                    }
                }
            }
            $array = Array();
            if($item->property) {
                foreach($item->property as $prop) {
                    $item->{$prop->property_alias} = $prop->value;
                }
            }
            $item->load(['image' => function($query){
                $query->orderBy('sorting', 'asc');
            }]);
            if($item->image->isEmpty()){
               // dd($item->image);
            }
            $item = $this->getAllImg($item);
            
            
            //dd($item);
            return $item;
    }

    public function getProducts($group_id = FALSE, $vendor_id = FALSE, $where = FALSE, $take = FALSE, $order = ['name'=>'name', 'order'=>'asc']) {
        if(!($where && is_array($where))) {
            $where = array();
        }
        if($vendor_id) {
            $where = Arr::add($where, 'vendor_id', $vendor_id);
        }
        //$where = Arr::add($where, 'vendor_id', $vendor_id);
        if($group_id && is_int($group_id)) {
            $where = Arr::add($where, 'vendor_category_id', $group_id);
        }
        
        
        $products = $this->p_rep->get('*', $where , $take, $order, $this->user->pagination);
        if($products) {
            $products->load('price', 'property');
            //$products->load('product');
        }
        return $products;
    }//getProducts

    public function getImgLink($id, $product) {
        //dd($this->current_vendor);
        if(Storage::disk('public')->exists('cache/img/'.($id % 100).'/'.($id*1).'_sm.jpg')) {
            return Storage::disk('public')->url('cache/img/'.($id % 100).'/'.($id*1).'_sm.jpg');
        }
        //elseif (!is_object($code)) {
        
        elseif ($product->{$this->current_vendor->img_driver}) {
            $code = $product->{$this->current_vendor->img_driver};
            //dd($code);
            $files = Storage::disk('upload')->files('img/'.$this->vendor.'/'.(intdiv($code, 1000)+1).'/'.$code.'_');
            
            if($files) {
                //dd($files);
                foreach($files as $file) {
                    //dd($files);
                    $ext = explode('.', $file);
                    $ext = $ext[count($ext)-1];
                    $temp_name = 'cache/img/temp/'.md5($file).'.'.$ext;
                    $file_content = Storage::disk('upload')->get($file);
                    Storage::disk('public')->put($temp_name, $file_content);
                    
                    //dd('cache/img/temp/'.$temp_name.'.'.$ext);
                    $this->addImg($product, 'storage/'.$temp_name);
                    Storage::disk('public')->delete($temp_name);
                    //$link = Storage::disk('public')->url('cache/img/'.($id % 100).'/'.($id*1).'_or.jpg');

                }
                if(0) {
                    $file = Storage::disk('upload')->get($files[0]);
                    Storage::disk('public')->put('cache/img/'.($id % 100).'/'.($id*1).'_or.jpg', $file);
                    $link = Storage::disk('public')->url('cache/img/'.($id % 100).'/'.($id*1).'_or.jpg');

                    $url = 'storage/cache/img/'.($id % 100).'/'.($id*1).'_sm.jpg';

                    $result = $this->imgResize($link, 150, $url);
                    Storage::disk('public')->delete('cache/img/'.($id % 100).'/'.($id*1).'_or.jpg');

                    return $url;
                }
            }
            else {
                return FALSE;
                
            }
            
        }
        return FALSE;
    }//getImgLink

    public function imgResize($file, $size, $save_link) {
        //dd($file);
        //dd(getimagesize($file));
        list($width_orig, $height_orig) = getimagesize($file);
        if ($array = getimagesize($file)) {
            //dd($array);
            // задание максимальной ширины и высоты
            if(($width_orig > $size) || ($height_orig > $size)) {
                $new_width = $size;
                $new_height = $size;
            }
            else {
                $new_width = $width_orig;
                $new_height = $height_orig;	
            }
            // switch ($size) {
            //     case 'small':
            //         $new_width = 100;
            //         $new_height = 100;
            //         //$surname = '_small_'.$num.'.jpg';
            //         break;
            //     case 'medium':
            //         if ($width_orig > 300) {
            //             $new_width = 300;
            //             $new_height = 300;
            //         }
            //         else {
            //             $new_width = $width_orig;
            //             $new_height = $height_orig;							
            //         }
            //         //$surname = '_medium_'.$num.'.jpg';
            //         break;
            //     case 'big':
            //         if ($width_orig > 600) {
            //             $new_width = 600;
            //             $new_height = 600;
            //         }
            //         else {
            //             $new_width = $width_orig;
            //             $new_height = $height_orig;							
            //         }
            //        // $surname = '_big_'.$num.'.jpg';
            //         break;
                
            //     }


            $ratio_orig = $width_orig/$height_orig;
            if ($new_width/$new_height > $ratio_orig) {
                $new_width = $new_height*$ratio_orig;
            } else {
                $new_height = $new_width/$ratio_orig;
            }

            // ресэмплирование
            $image_p = imagecreatetruecolor($new_width, $new_height);
            $image = imagecreatefromjpeg($file);
            imagecopyresampled($image_p, $image, 0, 0, 0, 0, $new_width, $new_height, $width_orig, $height_orig);

            // Сохраняем изображение 
            imagejpeg($image_p, $save_link);

            // Освобождаем память
            imagedestroy($image_p);
        }

        return $save_link;;
    }//imgResize

    public function trashCart() {
        $cart = Session::get('cart');
        foreach($cart as $key=>$value) {
            Session::put('cart', array());
        }
    }

    //Возвращает классы active для активных пунктов меню
    public function getMenuClasses($menus, $url) {
        $arr = array();
        //dd($menus);
        foreach($menus as $item) {
            if(isset($item->route)) {
                if(route($item->route) == $url) {
                    $arr[] = $item->id;
                    if($item->parent_id) {
                        $arr[] = $item->parent_id;
                    }
                    return $arr;
                }
            }
        }
        return FALSE;
    }//getMenuClasses
    
    public function setUserParameter($parameter, $value) {
        if($parameter && $value) {
            //dd($this->user);
            $update = $this->up_rep->get('*',['alias'=>$parameter, 'user_id'=>$this->user->id]);
            //dd($update);
            if($update) {
                $update->first()->value = $value;
                $update->first()->update();
            }
            else {
                $result = $this->up_rep->add($parameter, $value, $this->user);
            }

            return TRUE;
        }
        return FALSE;
    }//setUserParameter

    public function getDefaultUserParameters() {
        $result = $this->upl_rep->get('*');
        //dd($result);
        return $result;
    }//getDefaultUserParameters

    public function getImgUrl($product, $alias = FALSE) {
        if($alias) {
            $files = $this->img_rep->get('*', ['product_id'=>$product->id, 'type_alias'=>$alias], FALSE, ['name'=>'sorting', 'order'=>'asc']);
        }
        else {
            $files = $this->img_rep->get('*', ['product_id'=>$product->id], FALSE, ['name'=>'sorting', 'order'=>'asc']);
        }

        return $files;
    }//getImgUrl

    public function getImgTypes() {
        $types = new ProductImageType;
        
        $result = $types->select('*')->get();
        //dd($result);
        $arr = array();
        foreach($result as $type) {
            $arr[$type->alias] = $type->size;    
        }
        return $arr;
    }//getImgTypes

    public function getAllImg($product) {
        if(!$product->image->isEmpty()) {
            $images = $product->image->groupBy('type_alias');
            if(Arr::has($images, 'orig')) {
                $product->img_o = $images['orig'][0]->url;
            }
            if(Arr::has($images, 'small')) {
                $product->img_s = $images['small'][0]->url;
            }
            if(Arr::has($images, 'medium')) {
                $product->img_m = $images['medium'][0]->url;
            }
            if(Arr::has($images, 'big')) {
                $product->img_b = $images['big'][0]->url;
            }            

        }
        else {
            $product->img = $this->getImgLink($product->id, $product);
        }
        return $product;
    }//getAllImg

    public function isImage($file) {
        if(exif_imagetype($file) != IMAGETYPE_JPEG) {
            return FALSE;
        }
        return TRUE;
    }//isImage

    //Добавление изображения товара
    public function addImg($product, $file) {
        //Проверяем, является ли файл картинкой и приводим к типу jpg
        if(!$this->isImage($file)) {
            return ['success'=>FALSE, 'error'=>'Файл не является картинкой'];
        }

        //Определить сколько файлов уже есть, если больше максимума - отмена, иначе - какой следующий номер
        $files = $this->getImgUrl($product, Config::get('settings.img_alias_orig'));
        //dd($files);
        if(!$files) {
            $next = 1;
        }
        elseif($files->count() < Config::get('settings.maximum_quantity_images')) {
            $next = $files->count() + 1;
        }
        else {
            return ['success'=>FALSE, 'error'=>'Превышено максимально допустимое количество изображений товара'];
        }

        //Создаем необходимые кэш размеры файла и помещаем в массив все файлы для записи в БД        
        $files_arr = $this->saveImages($product, $file, $next);

        //Сохраняем информацию о созданных файлах в БД
        foreach($files_arr as $image) {
            $img = New ProductImage($image);
            $product->image()->save($img);
        }
        

        return ['success'=>TRUE];
    }//addImg

    //Формируем название части файла в виде кода - для создание оригинального изображения
    public function imgFileName($product) {
        switch ($this->current_vendor->img_driver) {
            case 'id':
                $file_name = $product->id;
                break;
            case 'vend_prod_id':
                $file_name = substr($product->vend_prod_id, strlen($this->current_vendor->id) + 1);
                break;
            case 'product_code':
                if($product->product_code && is_numeric($product->product_code)) {
                    $file_name = intval($product->product_code);
                }
                else {
                    $file_name = FALSE;
                }
                break;
        }
        return $file_name;
    }//imgFileName
   
    //Сохраняем файлы и формируем список для добавления в БД
    public function saveImages($product, $file, $number) {
        
        //Создаем имя файла и путь, по которому будем сохранять
        $file_code = $this->imgFileName($product);
        //$file_name = $file_code.'_'.$number.'_'.Config::get('settings.img_alias_orig').'.jpg';
 
        foreach($this->img_types as $alias=>$size) {
            
            if($alias == 'orig') {
                //$file_name = $file_code.'_'.$number.'_'.$alias.'.jpg';
                $file_name = $file_code.'_'.substr(md5($file_code), -10).rand(999,100000).'_'.$alias.'.jpg';
                //dd($file_name);
                $folder = 'img/'.$this->vendor.'/'.($file_code % 100);
                //Сохраняем файл по сформированному пути
                if(is_string($file)) {
                    Storage::disk('public')->copy(substr($file, 8), $folder.'/'.$file_name);
                    $save = $folder.'/'.$file_name;
                }
                else {
                    $save = $file->storeAs($folder, $file_name, 'public');
                }
                //$save = $file->storeAs($folder, $file_name, 'public');
                $url_orig = 'storage/'.$save;
                $url = $url_orig;
                //Пережимаем файл по установленному максимальному размеру
                $this->imgResize($url, $size, $url);
            }
            else {
                //$file_name = $product->id.'_'.$number.'_'.$alias.'.jpg';
                $file_name = $product->id.'_'.substr(md5($file_code), -10).rand(999,100000).'_'.$alias.'.jpg';
                $folder = 'cache/img/'.($product->id % 100);
                $save = $folder.'/'.$file_name;
                $url = 'storage/'.$folder.'/'.$file_name;
                //dd($url);
                if(!Storage::disk('public')->exists($folder)) {
                    Storage::disk('public')->makeDirectory($folder);
                }
                $this->imgResize($url_orig, $size, $url);
            }

            //Пережимаем файл по установленному максимальному размеру
            //$this->imgResize($url, $size, $url);
            //Формируем массив для добавления в БД
            list($width, $height) = getimagesize($url);
            $files_arr[] = [
                        //'product_id'    => $product->id,
                        'vendor_id'     => $product->vendor_id,
                        'type_alias'    => $alias,
                        'file_name'     => $file_name,
                        'folder'        => 'storage/'.$folder,
                        'url'           => $url,
                        'size'          => $width.'x'.$height,
                        'file_size'     => Storage::disk('public')->size($save),
                        'sorting'       => 100*$number
                        ];
        
        }
        return $files_arr;
    }//saveImages

    //Формируем заголовок для контента
    public function getContentHeader($data) {
        $text = '';
        if(Arr::has($data, 'category')){
            //dd($data);
            $col = $this->cat_rep->parentList($data['category']);
            //dd($col);
            if($col) {
                foreach($col as $item) {
                    if($item->id == $data['category']) {
                        $text = '<li class="breadcrumb-item active">'.$item->name.'</li></ol>';
                    }
                    else {
                        $text = '<li class="breadcrumb-item"><a href="'.route('admin.category', ['id'=>$item->id]).'">'.$item->name.'</a></li>'.$text;
                    }
                }
                $text = '<ol class="breadcrumb float-sm-left"><li class="breadcrumb-item"><a href="'.route('admin.category').'">Категории</a></li>'.$text;
            }
            else {
                $text = 'Категории';
            }
            
        };
        //$text = '<ol class="breadcrumb float-sm-left">'.$text;
        //dd($product);
        
       // $text = $text.'<ol class="breadcrumb float-sm-left">';
        //$text = $text.'<li class="breadcrumb-item"><a href="'.URL::previous().'">Вернуться</a></li>';
        //$text = $text.'<li class="breadcrumb-item active">'.$product->name.'</li></ol>';
        return $text;
    }//getContentHeader

    public function getCategories($vendor_id, $parent_id = FALSE, $sorting = FALSE) {
        if($parent_id || is_null($parent_id)) {
            $categories = $this->cat_rep->get('*', ['vendor_id'=>$vendor_id, 'parent_id'=>$parent_id], FALSE, $sorting);
        }
        else {
            $categories = $this->cat_rep->get('*', ['vendor_id'=>$vendor_id]);
        }
        
        
        return $categories;
    }//getCategories

    public function getSettingsToArr() {
        $array = array();
        $settings = $this->set_rep->get('*');
        
        foreach($settings as $item) {
            //dd($item->value);
            $array = Arr::add($array, $item->alias, $item->value);
        }
        return $array;

    }//getSettingsToArr

    public function getCategoryList($id = FALSE) {
        $where = array();
        if($id) {
            $where = Arr::add($where, 'parent_id', $id);
        }
        if($this->settings['use_other_vendors_categories_tree']) {
            if(Arr::has($this->settings, 'frontend_vendor_group_id')) {
                $where = Arr::add($where, 'vendor_id', $this->settings['frontend_vendor_group_id']);
            }
        }
        $categories = $this->cat_rep->get('*', $where);
        if($categories) {
            return $categories->groupBy('parent_id');
        }
        return FALSE;
    }//getCategoryList

    public function getChildCategories($id = FALSE) {
        if($id) {
            $categories = $this->cat_rep->get('*', ['parent_id'=>$id]);
            if($categories) {
                foreach($categories as $category) {
                    if($category->has_child) {
                        $child = $this->getChildCategories($category->id);
                        if($child) {
                            $categories = $categories->merge($child);
                        }
                    }
                }
            }
            else {
                return FALSE;
            }
            
            return $categories;
        }
        else {
            return FALSE;
        }
    }//getChildCategories


}
