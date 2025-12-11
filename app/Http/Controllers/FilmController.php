<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;

class FilmController extends Controller
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
            // Lekérjük az összes filmet az API-tól
            $response = Http::api()->get('/films');
            if ($response->failed()) {
                return view('films.index', [
                    'entities' => [],
                    'isAuthenticated' => $this->isAuthenticated()
                ])->with('error', 'Nem sikerült lekérni a filmeket.');
            }
    
            $films = $response->json()['films'] ?? [];
    
            // Lekérjük az összes rendezőt
            $directorsResponse = Http::api()->get('/directors');
            $directors = $directorsResponse->successful() ? $directorsResponse->json()['directors'] ?? [] : [];
            $directorsById = collect($directors)->keyBy('id');
    
            // Rendező nevének hozzárendelése
            foreach ($films as &$film) {
                $film['director'] = $directorsById->get($film['director_id'])['name'] ?? 'N/A';
            }
    
            // Ha van kereső kifejezés, szűrjük a filmeket a cím alapján (case-insensitive)
            if ($needle) {
                $films = collect($films)->filter(function ($film) use ($needle) {
                    return stripos($film['title'], $needle) !== false;
                })->values()->all();
            }
    
            return view('films.index', [
                'entities' => $films,
                'isAuthenticated' => $this->isAuthenticated()
            ]);
    
        } catch (\Exception $e) {
            return view('films.index', [
                'entities' => [],
                'isAuthenticated' => $this->isAuthenticated()
            ])->with('error', 'API kommunikációs hiba: ' . $e->getMessage());
        }
    }
    
    

    public function show($id)
    {
        try {
            $response = Http::api()->get("/films/$id");

            if ($response->failed()) {
                return redirect()->route('films.index')
                    ->with('error', 'A film nem található.');
            }

            $film = $response->json()['film'] ?? null;

            if (!$film) {
                return redirect()->route('films.index')
                    ->with('error', 'A film adatai nem érhetők el.');
            }

            return view('films.show', ['entity' => $film]);

        } catch (\Exception $e) {
            return redirect()->route('films.index')
                ->with('error', 'Nem sikerült betölteni a filmet: ' . $e->getMessage());
        }
    }

    public function create()
    {
        return view('films.create');
    }

    public function store(Request $request)
    {
        if (!$this->isAuthenticated()) {
            return redirect()->route('films.index')
                ->with('error', 'Be kell jelentkezni a film létrehozásához.');
        }
    
        $request->validate([
            'title' => 'required|string|max:255',
            'director_id' => 'required|integer',
            'release_date' => 'required|date',
            'description' => 'required|string',
            'image' => 'required|string|min:2',
            'type_id' => 'required|integer',
            'length' => 'required|integer', // itt javítottam az elírást
        ]);
    
        try {
            $response = Http::api()->withToken($this->token)->post('/films', [
                'title' => $request->title,
                'director_id' => $request->director_id,
                'release_date' => $request->release_date,
                'description' => $request->description,
                'image' => $request->image,
                'type_id' => $request->type_id,
                'length' => $request->length, // itt is javítva
            ]);
    
            if ($response->failed()) {
                $status = $response->status();
                $errors = $response->json('errors') ?? [];
                $errorsString = json_encode($errors);
                return redirect()->route('films.index')
                    ->with('error', "Nem sikerült létrehozni a filmet. (HTTP $status) $errorsString");
            }
    
            $film = $response->json('film');
    
            return redirect()->route('films.index')
                ->with('success', "{$film['title']} sikeresen létrehozva!");
    
        } catch (\Exception $e) {
            return redirect()->route('films.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }
    

    public function edit($id)
    {
        try {
            // Lekérjük az összes filmet
            $response = Http::api()->get('/films');
            $films = $response->successful() ? $response->json()['films'] ?? [] : [];
    
            // Kiválasztjuk a szerkesztendő filmet
            $film = collect($films)->firstWhere('id', (int)$id);
    
            if (!$film) {
                return redirect()->route('films.index')
                    ->with('error', 'A film nem található.');
            }
    
            // Lekérjük az összes rendezőt
            $directorsResponse = Http::api()->get('/directors');
            $directors = $directorsResponse->successful() ? $directorsResponse->json()['directors'] ?? [] : [];
    
            return view('films.edit', [
                'film' => $film,
                'directors' => $directors
            ]);
    
        } catch (\Exception $e) {
            return redirect()->route('films.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }
    
    public function update(Request $request, $id)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'director_id' => 'required|integer',
            'release_date' => 'required|date',
            'length' => 'required|integer',
            'description' => 'nullable|string',
        ]);

        try {
            $response = Http::api()->withToken($this->token)->patch("/films/$id", [
                'title' => $request->title,
                'director_id' => $request->director_id,
                'release_date' => $request->release_date,
                'length' => $request->length,
                'description' => $request->description,
            ]);

            if ($response->successful()) {
                return redirect()->route('films.index')
                    ->with('success', "{$request->title} frissítve!");
            }

            return redirect()->route('films.edit', $id)
                ->with('error', $response->json('message') ?? 'Ismeretlen hiba.');

        } catch (\Exception $e) {
            return redirect()->route('films.edit', $id)
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            // Lekérjük az összes filmet, és kiválasztjuk a törlendőt
            $filmsResponse = Http::api()->get('/films');
            $films = $filmsResponse->successful() ? $filmsResponse->json()['films'] ?? [] : [];

            $film = collect($films)->firstWhere('id', (int)$id);
            if (!$film) {
                return redirect()->route('films.index')
                    ->with('error', 'A film nem található.');
            }

            // Ha vannak kapcsolódó entitások (pl. epizódok), töröljük őket
            // Feltételezzük, hogy az API-nak van ilyen végpont, pl. DELETE /films/{id}/episodes
            $episodesResponse = Http::api()->withToken($this->token)->delete("/films/{$id}/episodes");
            if ($episodesResponse->failed() && $episodesResponse->status() != 404) {
                // 404: nincs epizód, nem hiba
                return redirect()->route('films.index')
                    ->with('error', 'Nem sikerült törölni a film epizódjait.');
            }

            // Végül magát a filmet töröljük
            $deleteResponse = Http::api()->withToken($this->token)->delete("/films/{$id}");
            if ($deleteResponse->failed()) {
                return redirect()->route('films.index')
                    ->with('error', 'Nem sikerült törölni a filmet.');
            }

            $title = $film['title'] ?? 'Ismeretlen';

            return redirect()->route('films.index')
                ->with('success', "$title törölve!");

        } catch (\Exception $e) {
            return redirect()->route('films.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }


    }
    public function isAuthenticated()
    {
        return !empty($this->token);
    }


    public function exportCsv()
    {
        $response = Http::api()->get('films');
        $films = $response->json()['films'] ?? [];

        $filename = "films_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $columns = ['ID', 'Cím', 'Rendező', 'Megjelenés', 'Hossz'];

        $callback = function() use ($films, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($films as $film) {
                fputcsv($file, [
                    $film['id'],
                    $film['title'],
                    $film['release_date'],
                    $film['length'],
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function exportPdf()
    {
        $response = Http::api()->get('films');
        $data = $response->json()['films'] ?? [];

        $pdf = Pdf::loadView('exports.films_pdf', [
            'title' => 'Filmek listája',
            'columns' => ['ID','Cím','Megjelenés','Hossz'],
            'fields' => ['id','title','release_date','length'],
            'items' => $data,
            'logo' => 'https://img.freepik.com/premium-vector/laravel-programming-framework-logo-vector-available-ai-8-regular-version_1076780-22054.jpg',
        ]);

        return $pdf->download('films.pdf');
    }


}
