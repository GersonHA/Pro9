<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class LandingController extends Controller
{
    public function index()
    {
        // Si el admin ya inició sesión, lo llevamos directo al panel.
        if (Auth::guard('admin')->check()) {
            return redirect()->route('system.dashboard');
        }

        return view('system.landing');
    }

    public function nosotros()
    {
        return view('system.landing.nosotros');
    }

    public function funcionalidades()
    {
        return view('system.landing.funcionalidades');
    }

    public function precios()
    {
        return view('system.landing.precios');
    }

    public function contacto()
    {
        return view('system.landing.contacto');
    }

    /**
     * Sitemap dinámico: el mapa de URLs que se le entrega a Google. Se genera
     * con url() para que apunte al dominio real al desplegar, en vez de quedar
     * clavado en localhost como quedaría un archivo estático.
     *
     */
    public function sitemap()
    {
        $urls = [
            ['loc' => url('/'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('landing.funcionalidades'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('landing.precios'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('landing.nosotros'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('landing.contacto'), 'changefreq' => 'monthly', 'priority' => '0.6'],
        ];

        $xml  = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        foreach ($urls as $u) {
            $xml .= "  <url>\n";
            $xml .= "    <loc>{$u['loc']}</loc>\n";
            $xml .= "    <changefreq>{$u['changefreq']}</changefreq>\n";
            $xml .= "    <priority>{$u['priority']}</priority>\n";
            $xml .= "  </url>\n";
        }
        $xml .= '</urlset>';

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    /**
     * robots.txt dinámico: permite todo y apunta al sitemap con url() para que
     * la línea Sitemap use el dominio real. Reemplaza al archivo estático, que
     * no podía generar una URL absoluta correcta entre localhost y producción.
     *
     */
    public function robots()
    {
        $content = "User-agent: *\nDisallow:\n\nSitemap: " . url('sitemap.xml') . "\n";

        return response($content, 200)->header('Content-Type', 'text/plain');
    }
}
