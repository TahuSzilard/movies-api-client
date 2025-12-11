<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class DirectorController extends Controller
{
    protected $token;

    public function __construct()
    {
        $this->token = session('api_token');
    }

    public function index(Request $request)
    {
        $needle = $request->get('needle'); // Kereső kifejezés
    
        try {
            // Lekérjük az összes rendezőt az API-tól
            $response = Http::api()->get('/directors');
            if ($response->failed()) {
                return view('directors.index', [
                    'entities' => [],
                    'isAuthenticated' => $this->isAuthenticated()
                ])->with('error', 'Nem sikerült lekérni a rendezőket.');
            }
    
            $directors = $response->json()['directors'] ?? [];
    
            // Ha van kereső kifejezés, szűrjük a rendezőket a név alapján (case-insensitive)
            if ($needle) {
                $directors = collect($directors)->filter(function ($director) use ($needle) {
                    return stripos($director['name'], $needle) !== false;
                })->values()->all();
            }
    
            return view('directors.index', [
                'entities' => $directors,
                'isAuthenticated' => $this->isAuthenticated()
            ]);
    
        } catch (\Exception $e) {
            return view('directors.index', [
                'entities' => [],
                'isAuthenticated' => $this->isAuthenticated()
            ])->with('error', 'API kommunikációs hiba: ' . $e->getMessage());
        }
    }
    

    public function show($id)
    {
        try {
            $response = Http::api()->get("/directors");

            if ($response->failed()) {
                return redirect()->route('directors.index')
                    ->with('error', 'Nem sikerült lekérni a rendezőket.');
            }

            $directors = $response->json()['directors'] ?? [];
            $director = collect($directors)->firstWhere('id', (int)$id);

            if (!$director) {
                return redirect()->route('directors.index')
                    ->with('error', 'A rendező nem található.');
            }

            return view('directors.show', ['entity' => $director]);

        } catch (\Exception $e) {
            return redirect()->route('directors.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('directors.create');
    }

    public function store(Request $request)
    {
        try {
            $response = Http::api()->withToken($this->token)->post('/directors', [
                'name' => $request->name,
                'birth_date' => $request->birth_date,
                'nationality' => $request->nationality,
            ]);

            if ($response->failed()) {
                return redirect()->route('directors.index')
                    ->with('error', 'Nem sikerült létrehozni a rendezőt.');
            }

            return redirect()->route('directors.index')
                ->with('success', "{$request->name} sikeresen létrehozva!");

        } catch (\Exception $e) {
            return redirect()->route('directors.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        try {
            $response = Http::api()->get("/directors");

            if ($response->failed()) {
                return redirect()->route('directors.index')
                    ->with('error', 'Nem sikerült lekérni a rendezőket.');
            }

            $directors = $response->json()['directors'] ?? [];
            $director = collect($directors)->firstWhere('id', (int)$id);

            if (!$director) {
                return redirect()->route('directors.index')
                    ->with('error', 'A rendező nem található.');
            }

            return view('directors.edit', ['director' => $director]);

        } catch (\Exception $e) {
            return redirect()->route('directors.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        try {
            // 🔥 PUT ➜ PATCH javítva
            $response = Http::api()->withToken($this->token)->patch("/directors/$id", [
                'name' => $request->name,
            ]);

            if ($response->successful()) {
                return redirect()->route('directors.index')
                    ->with('success', "{$request->name} frissítve!");
            }

            return redirect()->route('directors.index')
                ->with('error', $response->json('message') ?? 'Ismeretlen hiba.');

        } catch (\Exception $e) {
            return redirect()->route('directors.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Lekérjük a hozzá tartozó filmeket
            $filmsResponse = Http::api()->get('/films');
            $films = $filmsResponse->successful() ? $filmsResponse->json()['films'] ?? [] : [];
    
            $relatedFilms = collect($films)->filter(fn($film) => $film['director_id'] == $id);
    
            // Töröljük a kapcsolódó filmeket
            foreach ($relatedFilms as $film) {
                Http::api()->withToken($this->token)->delete("/films/{$film['id']}");
            }
    
            // Ha van sorozat endpoint, akkor hasonlóan
            $seriesResponse = Http::api()->get('/series');
            $series = $seriesResponse->successful() ? $seriesResponse->json()['series'] ?? [] : [];
    
            $relatedSeries = collect($series)->filter(fn($s) => $s['director_id'] == $id);
    
            foreach ($relatedSeries as $s) {
                Http::api()->withToken($this->token)->delete("/series/{$s['id']}");
            }
    
            // Végül töröljük a rendezőt
            $response = Http::api()->withToken($this->token)->delete("/directors/$id");
    
            if ($response->failed()) {
                return redirect()->route('directors.index')
                    ->with('error', 'Nem sikerült törölni a rendezőt.');
            }
    
            $name = $response->json()['name'] ?? 'Ismeretlen';
    
            return redirect()->route('directors.index')
                ->with('success', "$name és az összes kapcsolódó film/sorozat törölve!");
    
        } catch (\Exception $e) {
            return redirect()->route('directors.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }
    

    public function isAuthenticated()
    {
        return !empty($this->token);
    }
    public function exportCsv()
    {
        $response = Http::api()->get('directors');
        $directors = $response->json()['directors'] ?? [];

        $filename = "directors_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $columns = ['ID', 'Név', 'Létrehozva', 'Frissítve'];

        $callback = function() use ($directors, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($directors as $director) {
                fputcsv($file, [
                    $director['id'],
                    $director['name'],
                    $director['created_at'],
                    $director['updated_at'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function exportPdf()
    {
        $response = Http::api()->get('directors');
        $data = $response->json()['directors'] ?? [];

        $pdf = Pdf::loadView('exports.directors_pdf', [
            'title' => 'Rendezők listája',
            'columns' => ['ID','Név','Létrehozva','Frissítve'],
            'fields' => ['id','name','created_at','updated_at'],
            'items' => $data,
            'logo' => 'https://img.freepik.com/premium-vector/laravel-programming-framework-logo-vector-available-ai-8-regular-version_1076780-22054.jpg',
        ]);

        return $pdf->download('directors.pdf');
    }


}
