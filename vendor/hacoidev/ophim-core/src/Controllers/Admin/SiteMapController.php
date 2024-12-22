<?php

namespace Ophim\Core\Controllers\Admin;

use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;
use Backpack\Settings\app\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL as LARURL;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use Ophim\Core\Helpers\UserHelper;
use Ophim\Core\Models\Actor;
use Ophim\Core\Models\Catalog;
use Ophim\Core\Models\Category;
use Ophim\Core\Models\Director;
use Ophim\Core\Models\Manga;
use Ophim\Core\Models\Movie;
use Ophim\Core\Models\Region;
use Ophim\Core\Models\Studio;
use Ophim\Core\Models\Tag;
use Ophim\Core\Models\Taxonomy;
use Prologue\Alerts\Facades\Alert;
use Spatie\Sitemap\Sitemap;
use Spatie\Sitemap\SitemapIndex;
use Spatie\Sitemap\Tags\Url;

class SiteMapController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     *
     * @return void
     */
    public function setup()
    {
        CRUD::setRoute(config('backpack.base.route_prefix') . '/sitemap');
        CRUD::setEntityNameStrings('site map', 'site map');

        UserHelper::checkAdminPermissions();
    }

    /**
     * Define what happens when the Update operation is loaded.
     *
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::addField(['name' => 'sitemap', 'type' => 'custom_html', 'value' => 'Sitemap sẽ được lưu tại đường dẫn: <i>' . url('/sitemap.xml') . '</i>']);
        $this->crud->addSaveAction([
            'name' => 'save_and_new',
            'redirect' => function ($crud, $request, $itemId) {
                return $crud->route;
            },
            'button_text' => 'Tạo sitemap',
        ]);

        $this->crud->setOperationSetting('showSaveActionChange', false);
    }

    public function render_styles()
    {
        $xml = view('ophim::sitemap/styles', [
            'title' => Setting::get('site_homepage_title'),
            'domain' => LARURL::to('/')
            // 'domain' => config('custom.frontend_url')
        ])->render();

        file_put_contents(public_path('main-sitemap.xsl'), $xml);
        return;
    }

    public function add_styles($file_name)
    {
        $path = public_path($file_name);
        if(file_exists($path)) {
            $content = file_get_contents($path);
            // $content = str_replace('?'.'>', '?'.'>'.'<'.'?'.'xml-stylesheet type="text/xsl" href="'. LARURL::to('/') .'/main-sitemap.xsl"?'.'>', $content);
            $content = str_replace('?'.'>', '?'.'>'.'<'.'?'.'xml-stylesheet type="text/xsl" href="/main-sitemap.xsl"?'.'>', $content);
            file_put_contents($path, $content);
        }
    }

    public function store(Request $request)
    {
        try {
            //code...
        if (!File::isDirectory('sitemap')) File::makeDirectory('sitemap', 0777, true, true);

        // Generate Category Sitemap
        $categorySitemap = $this->generateCategorySitemap();
        $this->writeToFile(public_path('sitemap/categories-sitemap.xml'), $categorySitemap);

        // Generate Manga Sitemaps
        $mangaSitemapChunks = $this->generateMangaSitemaps();

        // Generate Main Sitemap
        $mainSitemap = $this->generateMainSitemap($mangaSitemapChunks);
        $this->writeToFile(public_path('sitemap.xml'), $mainSitemap);

        // $sourceDirectory = 'F:/xampp/htdocs/10truyenAPIs/public';
        // $destinationDirectory = 'C:/Users/ADMIN/Desktop/STUDY/HTML-CSS/movie_api_frontend/public';
        // File::copy($sourceDirectory . '/sitemap.xml', $destinationDirectory . '/sitemap.xml');
        // File::copy($sourceDirectory . '/main-sitemap.xsl', $destinationDirectory . '/main-sitemap.xsl');
        // File::copyDirectory($sourceDirectory . '/sitemap', $destinationDirectory . '/sitemap');

        $this->render_styles();

        Alert::success("Đã tạo thành công sitemap tại thư mục public")->flash();

        return back();
        } catch (\Throwable $th) {
            return response()->json(['error'=> $th->getMessage()]);
        }
    }

    private function generateCategorySitemap()
    {
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlString .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        Taxonomy::chunk(100, function ($categories) use (&$xmlString) {
            foreach ($categories as $category) {
                $xmlString .= $this->generateUrlEntry(
                    $category->getUrl(),
                    Carbon::now()->toDateString(),
                    'weekly',
                    0.8
                );
            }
        });

        $xmlString .= '</urlset>';

        return $xmlString;
    }

    private function generateMangaSitemaps()
    {
        $chunk = 0;
        $mangaSitemapChunks = [];

        Manga::chunkById(200, function ($mangas) use (&$chunk, &$mangaSitemapChunks) {
            $chunk++;
            $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
            $xmlString .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

            foreach ($mangas as $manga) {
                $xmlString .= $this->generateUrlEntry(
                    $manga->getUrl(),
                    $manga->updated_at,
                    'weekly',
                    0.8
                );
            }

            $xmlString .= '</urlset>';
            $filePath = public_path("sitemap/mangas-sitemap{$chunk}.xml");
            file_put_contents($filePath, $xmlString);
            $mangaSitemapChunks[] = "sitemap/mangas-sitemap{$chunk}.xml";
        });

        return $mangaSitemapChunks;
    }

    private function generateMainSitemap($mangaSitemapChunks)
    {
        $xmlString = '<?xml version="1.0" encoding="UTF-8"?>';
        $xmlString .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Add Category Sitemap
        $xmlString .= $this->generateSitemapEntry(
            LARURL::to('/') . '/sitemap/categories-sitemap.xml',
            Carbon::now()->toDateString()
        );

        // Add Manga Sitemaps
        foreach ($mangaSitemapChunks as $sitemap) {
            $xmlString .= $this->generateSitemapEntry(
                LARURL::to('/') . "/" . $sitemap,
                Carbon::now()->toDateString()
            );
        }

        $xmlString .= '</urlset>';

        return $xmlString;
    }

    private function generateUrlEntry($loc, $lastmod, $changefreq, $priority)
    {
        return '<url>'
            . '<loc>' . $loc . '</loc>'
            . '<lastmod>' . $lastmod . '</lastmod>'
            . '<priority>' . $priority . '</priority>'
            . '</url>';
    }

    private function generateSitemapEntry($loc, $lastmod)
    {
        return '<url>'
            . '<loc>' . $loc . '</loc>'
            . '<lastmod>' . $lastmod . '</lastmod>'
            . '<priority>0.8</priority>'
            . '</url>';
    }

    private function writeToFile($filePath, $content)
    {
        file_put_contents($filePath, $content);
    }
}
