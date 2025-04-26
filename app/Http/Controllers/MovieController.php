<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;

class MovieController extends Controller
{
    public function index()
    {
        $query = Movie::latest();

        if (request('search')) {
            $query->where('judul', 'like', '%' . request('search') . '%')
                ->orWhere('sinopsis', 'like', '%' . request('search') . '%');
        }

        $movies = $query->paginate(6)->withQueryString();
        return view('homepage', compact('movies'));
    }

    public function detail($id)
    {
        $movie = Movie::findOrFail($id);
        return view('detail', compact('movie'));
    }

    public function create()
    {
        $categories = Category::all();
        return view('input', compact('categories'));
    }

    public function store(Request $request)
    {
        $this->validateMovie($request, true);

        $fileName = $this->handleUploadedFile($request);

        Movie::create(array_merge($request->only([
            'id', 'judul', 'category_id', 'sinopsis', 'tahun', 'pemain'
        ]), ['foto_sampul' => $fileName]));

        return redirect()->route('home')->with('success', 'Data berhasil disimpan');
    }

    public function data()
    {
        $movies = Movie::latest()->paginate(10);
        return view('data-movies', compact('movies'));
    }

    public function form_edit($id)
    {
        $movie = Movie::findOrFail($id);
        $categories = Category::all();
        return view('form-edit', compact('movie', 'categories'));
    }

    public function update(Request $request, $id)
    {
        $this->validateMovie($request, false);
        $movie = Movie::findOrFail($id);

        $data = $request->only(['judul', 'sinopsis', 'category_id', 'tahun', 'pemain']);

        if ($request->hasFile('foto_sampul')) {
            if (File::exists(public_path('images/' . $movie->foto_sampul))) {
                File::delete(public_path('images/' . $movie->foto_sampul));
            }

            $data['foto_sampul'] = $this->handleUploadedFile($request);
        }

        $movie->update($data);

        return redirect()->route('movies.data')->with('success', 'Data berhasil diperbarui');
    }

    public function delete($id)
    {
        $movie = Movie::findOrFail($id);

        if (File::exists(public_path('images/' . $movie->foto_sampul))) {
            File::delete(public_path('images/' . $movie->foto_sampul));
        }

        $movie->delete();

        return redirect()->route('movies.data')->with('success', 'Data berhasil dihapus');
    }

    // =====================
    // PRIVATE METHODS
    // =====================

    private function validateMovie(Request $request, $isCreate = true)
    {
        $rules = [
            'judul' => 'required|string|max:255',
            'category_id' => 'required|integer',
            'sinopsis' => 'required|string',
            'tahun' => 'required|integer',
            'pemain' => 'required|string',
            'foto_sampul' => ($isCreate ? 'required|' : '') . 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ];

        if ($isCreate) {
            $rules['id'] = ['required', 'string', 'max:255', Rule::unique('movies', 'id')];
        }

        $request->validate($rules);
    }

    private function handleUploadedFile(Request $request)
    {
        $randomName = Str::uuid()->toString();
        $fileExtension = $request->file('foto_sampul')->getClientOriginalExtension();
        $fileName = $randomName . '.' . $fileExtension;

        $request->file('foto_sampul')->move(public_path('images'), $fileName);

        return $fileName;
    }
}
