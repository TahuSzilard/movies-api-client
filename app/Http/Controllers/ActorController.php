<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Barryvdh\DomPDF\Facade\Pdf;
class ActorController extends Controller
{
    protected $token;

    public function __construct()
    {
        $this->token = session('api_token');
    }

    // Színészek listázása
    public function index(Request $request)
    {
        $needle = $request->get('needle');
        $errorMessage = null;
        $actors = [];

        try {
            $response = Http::api()->get('actors');

            if ($response->failed()) {
                $errorMessage = $response->json('message') ?? 'Hiba történt a színészek lekérdezésekor.';
            } else {
                $actors = $response->json()['actors'] ?? [];
            }

        } catch (\Exception $e) {
            $errorMessage = "Nem sikerült betölteni a színészeket: " . $e->getMessage();
        }

        // Keresés (csak ha vannak adatok)
        if ($needle && !empty($actors)) {
            $needleLower = mb_strtolower($needle);
            $actors = array_filter($actors, function ($actor) use ($needleLower) {
                return strpos(mb_strtolower($actor['name'] ?? ''), $needleLower) !== false;
            });
            $actors = array_values($actors);
        }

        return view('actors.index', [
            'entities'       => $actors,
            'isAuthenticated'=> $this->token !== null,
            'errorMessage'   => $errorMessage,
        ]);
    }

    

    // Egy színész adatainak megtekintése
    public function show($id)
    {
        try {
            $response = Http::api()->get("/actors/$id");

            if ($response->failed()) {
                $message = $response->json('message') ?? 'A színész nem található.';
                return redirect()->route('actors.index')->with('error', $message);
            }

            $actor = $response->json()['actor'] ?? null;

            if (!$actor) {
                return redirect()->route('actors.index')
                    ->with('error', "A színész adatai nem érhetők el.");
            }

            return view('actors.show', ['entity' => $actor]);

        } catch (\Exception $e) {
            return redirect()->route('actors.index')
                ->with('error', "Nem sikerült betölteni a színész adatait: " . $e->getMessage());
        }
    }

    // Új színész létrehozása (form)
    public function create()
    {
        return view('actors.create');
    }


    // Új színész mentése
// Új színész mentése
public function store(Request $request)
{
    // Validáció: name kötelező, image opcionális
    $request->validate([
        'name' => 'required|string|max:255',
    ]);

    try {
        // POST request az API felé
        $response = Http::api()->withToken($this->token)->post('/actors', [
            'name' => $request->name,
        ]);

        if ($response->failed()) {
            $message = $response->json('message') ?? 'Nem sikerült létrehozni a színészt.';
            return redirect()->route('actors.index')->with('error', $message);
        }

        $actorName = $response->json('actor.name') ?? $request->name;

        return redirect()->route('actors.index')
            ->with('success', "$actorName színész sikeresen létrehozva!");

    } catch (\Exception $e) {
        return redirect()->route('actors.index')
            ->with('error', "Nem sikerült kommunikálni az API-val: " . $e->getMessage());
    }
}




    // Színész szerkesztése (form)
    public function edit($id)
    {
        try {
            $response = Http::api()->get("/actors");
            if ($response->failed()) {
                return redirect()->route('actors.index')
                    ->with('error', 'Nem sikerült lekérni a színészeket.');
            }

            $actors = $response->json()['actors'] ?? [];
            $actor = collect($actors)->firstWhere('id', (int)$id);

            if (!$actor) {
                return redirect()->route('actors.index')
                    ->with('error', 'A színész nem található.');
            }

            return view('actors.edit', ['entity' => $actor]);

        } catch (\Exception $e) {
            return redirect()->route('actors.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }

    // Színész frissítése
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
        ]);

        try {
            $response = Http::api()->withToken($this->token)->patch("/actors/$id", [
                'name' => $request->name,
            ]);

            if ($response->successful()) {
                return redirect()->route('actors.index')
                    ->with('success', "{$request->name} színész sikeresen frissítve!");
            }

            $message = $response->json('message') ?? 'Ismeretlen hiba.';
            return redirect()->route('actors.index')->with('error', $message);

        } catch (\Exception $e) {
            return redirect()->route('actors.index')
                ->with('error', 'API hiba: ' . $e->getMessage());
        }
    }

    // Színész törlése
    public function destroy($id)
    {
        try {
            $response = Http::api()->withToken($this->token)->delete("/actors/$id");

            if ($response->failed()) {
                $message = $response->json('message') ?? 'Nem sikerült törölni a színészt.';
                return redirect()->route('actors.index')->with('error', $message);
            }

            $name = $response->json()['name'] ?? 'Ismeretlen';

            return redirect()->route('actors.index')
                ->with('success', "$name színész sikeresen törölve!");

        } catch (\Exception $e) {
            return redirect()->route('actors.index')
                ->with('error', "Nem sikerült kommunikálni az API-val: " . $e->getMessage());
        }
    }
    public function exportCsv()
    {
        $response = Http::api()->get('actors');
        $actors = $response->json()['actors'] ?? [];

        $filename = "actors_" . date('Y-m-d') . ".csv";

        $headers = [
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
        ];

        $columns = ['ID', 'Név', 'Kép', 'Létrehozva', 'Frissítve'];

        $callback = function() use ($actors, $columns) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $columns);

            foreach ($actors as $actor) {
                fputcsv($file, [
                    $actor['id'],
                    $actor['name'],
                    $actor['image'],
                    $actor['created_at'],
                    $actor['updated_at'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
    public function exportPdf()
    {
        $response = Http::api()->get('actors');
        $data = $response->json()['actors'] ?? [];

        $pdf = Pdf::loadView('exports.actors_pdf', [
            'title' => 'Színészek listája',
            'columns' => ['ID', 'Név', 'Kép', 'Létrehozva', 'Frissítve'],
            'fields' => ['id','name','image','created_at','updated_at'],
            'items' => $data,
            'logo' => 'https://img.freepik.com/premium-vector/laravel-programming-framework-logo-vector-available-ai-8-regular-version_1076780-22054.jpg',
        ]);

        return $pdf->download('actors.pdf');
    }

}
